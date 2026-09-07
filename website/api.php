<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

/* ============================================================
   PAM SINDU — API DATABASE (MODE LIHAT)
   Dipakai oleh index.html (viewer):
   - action=get_data    : baca seluruh baris (opsional ?periode=)
   - action=import_rows : simpan hasil parse file .sql ke database
                          (per periode DIGANTI, tidak dobel;
                           periode lain di database tetap aman)
   - action=kosongkan_data : hapus SEMUA baris RT 08 + RT 09
                          (gerbang password sama dengan import)
   Tidak ada lagi save_item / tutup_buku — viewer hanya baca;
   satu-satunya tulisan adalah import hasil export .sql dan
   kosongkan data (keduanya lewat gerbang password).
   ============================================================ */

// KONFIGURASI DATABASE (hosting InfinityFree)
// Saran: ganti password DB ini dari panel InfinityFree bila sempat.
$host = "192.168.0.100";
$user = "asuueuor_pam";
$pass = "bTTE5dkKJnvkY6gPSAVE";
$db   = "asuueuor_pam";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi DB gagal: " . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");

// Pastikan tabel tersedia (aman bila tabel versi lama sudah ada — tidak ditimpa)
// REV: tabel utama RT 08 kini bernama transaksi_pelanggan_rt08 (sebelumnya
// transaksi_pelanggan tanpa suffix) supaya konsisten dengan tabel RT 09.
$conn->query("CREATE TABLE IF NOT EXISTS transaksi_pelanggan_rt08 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periode VARCHAR(30) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    meter_awal INT NOT NULL DEFAULT 0,
    meter_akhir INT NOT NULL DEFAULT 0,
    cash INT NOT NULL DEFAULT 0,
    biaya_beban INT NOT NULL DEFAULT 0,
    saldo_lalu INT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'Belum Bayar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS transaksi_pelanggan_rt09 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periode VARCHAR(30) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    meter_awal INT NOT NULL DEFAULT 0,
    meter_akhir INT NOT NULL DEFAULT 0,
    cash INT NOT NULL DEFAULT 0,
    biaya_beban INT NOT NULL DEFAULT 0,
    saldo_lalu INT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'Belum Bayar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Pilih tabel berdasarkan parameter ?rt=08|09 (default 08)
$rt  = isset($_GET['rt']) ? trim($_GET['rt']) : '08';
$tabelRT = ($rt === '09') ? 'transaksi_pelanggan_rt09' : 'transaksi_pelanggan_rt08';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ---------- 1. BACA DATA ----------
if ($action === 'get_data') {
    // Cek dulu apakah tabel RT ini sudah dibuat di MySQL. Bila belum
    // (mis. RT 09 belum di-migrate), balas data kosong — supaya viewer
    // tetap menampilkan "Terhubung" dengan pesan "belum ada data RT 09",
    // bukan error 500 yg bikin status pill jadi "Terputus".
    $cekTabel = $conn->query("SHOW TABLES LIKE '" . $tabelRT . "'");
    if (!$cekTabel || $cekTabel->num_rows === 0) {
        echo json_encode([
            "status" => "success",
            "data" => [],
            "info" => "Tabel " . $tabelRT . " belum dibuat di database — import file .sql dari Petugas untuk menyiapkan data RT " . $rt . "."
        ]);
        $conn->close();
        exit;
    }

    if (!empty($_GET['periode'])) {
        $stmt = $conn->prepare("SELECT periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status
                                FROM " . $tabelRT . " WHERE periode = ? ORDER BY id");
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Gagal prepare query: " . $conn->error]);
            $conn->close();
            exit;
        }
        $stmt->bind_param("s", $_GET['periode']);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status
                                FROM " . $tabelRT . " ORDER BY id");
        if (!$result) {
            echo json_encode(["status" => "error", "message" => "Gagal query: " . $conn->error]);
            $conn->close();
            exit;
        }
    }
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
}

// ---------- 2. IMPORT HASIL EXPORT .SQL (baris sudah diparse di browser) ----------
if ($action === 'import_rows') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Gerbang password import — harus sama dengan PASS_IMPOR di index.html
    if (!isset($input['password']) || $input['password'] !== 'root') {
        echo json_encode(["status" => "error", "message" => "Password import salah"]);
        $conn->close();
        exit;
    }

    $rows  = (isset($input['rows']) && is_array($input['rows'])) ? $input['rows'] : [];

    // Kelompokkan baris per (RT, periode). Setiap baris dari Petugas membawa
    // field "rt" (08 atau 09) dari nama tabel di INSERT statement — supaya
    // baris RT 08 masuk ke transaksi_pelanggan_rt08 dan baris RT 09 masuk
    // ke transaksi_pelanggan_rt09. Bila baris tidak bawa tag RT (file .sql
    // lama), default rt=08 supaya backward-compatible dengan backup lama.
    $perTabel = []; // "08" atau "09" => [periode => [baris...]]
    foreach ($rows as $r) {
        if (!is_array($r)) continue;
        $p = trim(strval(isset($r['periode']) ? $r['periode'] : ''));
        $n = trim(strval(isset($r['nama']) ? $r['nama'] : ''));
        if ($p === '' || $n === '') continue;
        $rt = trim(strval(isset($r['rt']) ? $r['rt'] : '08'));
        if ($rt !== '09') $rt = '08'; // hanya 08 atau 09
        if (!isset($perTabel[$rt])) $perTabel[$rt] = [];
        if (!isset($perTabel[$rt][$p])) $perTabel[$rt][$p] = [];
        $perTabel[$rt][$p][] = [
            intval(isset($r['meter_awal'])  ? $r['meter_awal']  : 0),  // 0
            intval(isset($r['meter_akhir']) ? $r['meter_akhir'] : 0),  // 1
            intval(isset($r['cash'])        ? $r['cash']        : 0),  // 2
            intval(isset($r['biaya_beban']) ? $r['biaya_beban'] : 0),  // 3
            intval(isset($r['saldo_lalu'])  ? $r['saldo_lalu']  : 0),  // 4
            ((isset($r['status']) ? $r['status'] : '') === 'Sudah Bayar') ? 'Sudah Bayar' : 'Belum Bayar', // 5
            $n                                                          // 6
        ];
    }

    if (!count($perTabel)) {
        echo json_encode(["status" => "error", "message" => "Tidak ada baris valid untuk disimpan"]);
        $conn->close();
        exit;
    }

    // Siapkan prepared statements untuk kedua tabel sekaligus, supaya bisa
    // distribusi baris RT 08 & RT 09 dalam satu transaksi (atomik).
    $conn->begin_transaction();
    $stmts = []; // rt => ['del' => ..., 'ins' => ..., 'tabel' => ...]
    foreach (['08', '09'] as $rt) {
        $tabel = ($rt === '09') ? 'transaksi_pelanggan_rt09' : 'transaksi_pelanggan_rt08';
        $del = $conn->prepare("DELETE FROM " . $tabel . " WHERE periode = ?");
        $ins = $conn->prepare("INSERT INTO " . $tabel . " (periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$del || !$ins) {
            echo json_encode(["status" => "error", "message" => "Gagal menyiapkan query untuk " . $tabel . ": " . $conn->error]);
            $conn->close();
            exit;
        }
        $stmts[$rt] = ['del' => $del, 'ins' => $ins];
    }

    $tersimpan = 0;
    $periodeTersimpan = [];
    $rtTersimpan = [];
    foreach ($perTabel as $rt => $perPeriode) {
        $del = $stmts[$rt]['del'];
        $ins = $stmts[$rt]['ins'];
        $adaUntukRTIni = false;
        foreach ($perPeriode as $periode => $daftar) {
            $del->bind_param("s", $periode);
            $del->execute();
            foreach ($daftar as $v) {
                //                periode nama   awal   akhir  cash   beban  saldo  status
                $ins->bind_param("ssiiiiis", $periode, $v[6], $v[0], $v[1], $v[2], $v[3], $v[4], $v[5]);
                if ($ins->execute()) $tersimpan++;
            }
            $periodeTersimpan[] = $periode;
            $adaUntukRTIni = true;
        }
        if ($adaUntukRTIni) $rtTersimpan[] = "RT " . $rt;
    }

    // Tutup semua prepared statements
    foreach ($stmts as $s) { $s['del']->close(); $s['ins']->close(); }

    $conn->commit();

    echo json_encode([
        "status"    => "success",
        "tersimpan" => $tersimpan,
        "periode"   => array_values(array_unique($periodeTersimpan)),
        "rt"        => array_values(array_unique($rtTersimpan))
    ]);
}

// ---------- 3. KOSONGKAN DATA (hapus semua baris RT 08 + RT 09) ----------
if ($action === 'kosongkan_data') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Gerbang password — harus sama dengan PASS_IMPOR di index.html
    if (!isset($input['password']) || $input['password'] !== 'root') {
        echo json_encode(["status" => "error", "message" => "Password salah"]);
        $conn->close();
        exit;
    }

    $conn->begin_transaction();
    $terhapus = 0;
    $ok = true;
    foreach (['transaksi_pelanggan_rt08', 'transaksi_pelanggan_rt09'] as $tabel) {
        $res = $conn->query("DELETE FROM " . $tabel);
        if (!$res) {
            $ok = false;
            break;
        }
        $terhapus += $conn->affected_rows;
    }
    if (!$ok) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Gagal menghapus data: " . $conn->error]);
        $conn->close();
        exit;
    }
    $conn->commit();
    echo json_encode(["status" => "success", "terhapus" => $terhapus]);
}

if ($action === '' ) {
    echo json_encode(["status" => "error", "message" => "Paramter action wajib: get_data / import_rows / kosongkan_data"]);
}

$conn->close();
?>
