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
   Tidak ada lagi save_item / tutup_buku — viewer hanya baca,
   satu-satunya tulisan adalah import hasil export .sql.
   ============================================================ */

// KONFIGURASI DATABASE (hosting InfinityFree)
// Saran: ganti password DB ini dari panel InfinityFree bila sempat.
$host = "sql208.infinityfree.com";
$user = "if0_42771179";
$pass = "LTEMWvwgTLzp";
$db   = "if0_42771179_pam";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi DB gagal: " . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");

// Pastikan tabel tersedia (aman bila tabel versi lama sudah ada — tidak ditimpa)
$conn->query("CREATE TABLE IF NOT EXISTS transaksi_pelanggan (
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

$action = isset($_GET['action']) ? $_GET['action'] : '';

// ---------- 1. BACA DATA ----------
if ($action === 'get_data') {
    if (!empty($_GET['periode'])) {
        $stmt = $conn->prepare("SELECT periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status
                                FROM transaksi_pelanggan WHERE periode = ? ORDER BY id");
        $stmt->bind_param("s", $_GET['periode']);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status
                                FROM transaksi_pelanggan ORDER BY id");
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

    // kelompokkan per periode + bersihkan nilai
    $perPeriode = [];
    foreach ($rows as $r) {
        if (!is_array($r)) continue;
        $p = trim(strval(isset($r['periode']) ? $r['periode'] : ''));
        $n = trim(strval(isset($r['nama']) ? $r['nama'] : ''));
        if ($p === '' || $n === '') continue;
        $perPeriode[$p][] = [
            intval(isset($r['meter_awal'])  ? $r['meter_awal']  : 0),  // 0
            intval(isset($r['meter_akhir']) ? $r['meter_akhir'] : 0),  // 1
            intval(isset($r['cash'])        ? $r['cash']        : 0),  // 2
            intval(isset($r['biaya_beban']) ? $r['biaya_beban'] : 0),  // 3
            intval(isset($r['saldo_lalu'])  ? $r['saldo_lalu']  : 0),  // 4
            ((isset($r['status']) ? $r['status'] : '') === 'Sudah Bayar') ? 'Sudah Bayar' : 'Belum Bayar', // 5
            $n                                                          // 6
        ];
    }

    if (!count($perPeriode)) {
        echo json_encode(["status" => "error", "message" => "Tidak ada baris valid untuk disimpan"]);
        $conn->close();
        exit;
    }

    // Satu transaksi: untuk tiap periode — hapus lama, sisipkan baru (tidak dobel)
    $conn->begin_transaction();
    $del = $conn->prepare("DELETE FROM transaksi_pelanggan WHERE periode = ?");
    $ins = $conn->prepare("INSERT INTO transaksi_pelanggan (periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$del || !$ins) {
        echo json_encode(["status" => "error", "message" => "Gagal menyiapkan query: " . $conn->error]);
        $conn->close();
        exit;
    }

    $tersimpan = 0;
    foreach ($perPeriode as $periode => $daftar) {
        $del->bind_param("s", $periode);
        $del->execute();
        foreach ($daftar as $v) {
            //                periode nama   awal   akhir  cash   beban  saldo  status
            $ins->bind_param("ssiiiiis", $periode, $v[6], $v[0], $v[1], $v[2], $v[3], $v[4], $v[5]);
            if ($ins->execute()) $tersimpan++;
        }
    }
    $conn->commit();

    echo json_encode([
        "status"    => "success",
        "tersimpan" => $tersimpan,
        "periode"   => array_keys($perPeriode)
    ]);
}

if ($action === '' ) {
    echo json_encode(["status" => "error", "message" => "Paramter action wajib: get_data / import_rows"]);
}

$conn->close();
?>
