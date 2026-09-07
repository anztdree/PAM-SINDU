<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

/* ============================================================
   PAM SINDU — PETUGAS (ONLINE) — API DATABASE
   Backend untuk folder "Petugas ( Online )" di hosting
   freehosting.com dengan panel DirectAdmin (PHP + SQLite).

   Semua operasi aplikasi Petugas lewat sini:
     action=cek            : status server + pastikan tabel siap
     action=table          : baca baris (filter periode/nama)
     action=insert         : sisip banyak baris (1 transaksi)
     action=update         : ubah baris sesuai filter
     action=delete         : hapus baris sesuai filter / semua
     action=upsert_periode : upsert satu periode (Tutup Buku)
     action=import_rows    : masukkan hasil parse .sql (JSON)
     action=import_db      : pulihkan dari upload file .db
     action=export_db      : unduh file database (.db)
     action=export_sql     : unduh dump .sql untuk Website
     action=periode_list   : daftar periode + jumlah baris berisi data
     action=setting_get    : baca semua pengaturan (mis. penanda bulan kerja)
     action=setting_set    : simpan satu pengaturan (Tutup Buku)
   ============================================================ */

// ---- KONFIGURASI ----
// File database SQLite disimpan SEJAJAR dengan api.php.
// Dibuat otomatis saat pertama kali dipakai; sudah diproteksi .htaccess.
$DB_FILE = __DIR__ . "/pam.db";
$MAKS_BARIS = 5000;
$TABEL_OK = array("transaksi_pelanggan_rt08", "transaksi_pelanggan_rt09");
$KOLOM_OK = array("nama", "meter_awal", "meter_akhir", "cash", "biaya_beban", "saldo_lalu", "status");
$BULAN_ID = array("januari","februari","maret","april","mei","juni",
                  "juli","agustus","september","oktober","november","desember");

function balas($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function gagal($pesan, $http = 400) {
    http_response_code($http);
    balas(array("ok" => false, "pesan" => $pesan));
}

if (!extension_loaded("pdo_sqlite")) {
    gagal("Hosting ini belum mengaktifkan PDO SQLite (pdo_sqlite). Hubungi penyedia hosting.", 500);
}

try {
    $pdo = new PDO("sqlite:" . $DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA busy_timeout = 5000");
} catch (Exception $e) {
    gagal("Tidak bisa membuka file database SQLite: " . $e->getMessage(), 500);
}

// Pastikan file database + kedua tabel RT siap (dibuat otomatis bila belum ada).
function siapkanTabel($pdo) {
    $schema = "(
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        periode TEXT NOT NULL,
        nama TEXT NOT NULL,
        meter_awal INTEGER DEFAULT 0,
        meter_akhir INTEGER DEFAULT 0,
        cash INTEGER DEFAULT 0,
        biaya_beban INTEGER DEFAULT 2000,
        saldo_lalu INTEGER DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'Belum Bayar' CHECK (status IN ('Belum Bayar','Sudah Bayar')),
        updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    )";
    $pdo->exec("CREATE TABLE IF NOT EXISTS transaksi_pelanggan_rt08 " . $schema);
    $pdo->exec("CREATE TABLE IF NOT EXISTS transaksi_pelanggan_rt09 " . $schema);
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS unique_pelanggan_periode_rt08 ON transaksi_pelanggan_rt08 (periode, nama)");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS unique_pelanggan_periode_rt09 ON transaksi_pelanggan_rt09 (periode, nama)");
    $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_transaksi_rt08_updated_at AFTER UPDATE ON transaksi_pelanggan_rt08
        BEGIN UPDATE transaksi_pelanggan_rt08 SET updated_at = datetime('now','localtime') WHERE id = NEW.id; END");
    $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_transaksi_rt09_updated_at AFTER UPDATE ON transaksi_pelanggan_rt09
        BEGIN UPDATE transaksi_pelanggan_rt09 SET updated_at = datetime('now','localtime') WHERE id = NEW.id; END");
    // Pengaturan aplikasi (kunci-nilai) — menyimpan penanda "bulan kerja"
    // yang hanya maju lewat Tutup Buku. Berlaku untuk SEMUA perangkat.
    $pdo->exec("CREATE TABLE IF NOT EXISTS pengaturan (
        kunci TEXT PRIMARY KEY,
        nilai TEXT NOT NULL
    )");
}
siapkanTabel($pdo);

function tabelValid($t) {
    global $TABEL_OK;
    return in_array($t, $TABEL_OK, true) ? $t : null;
}
function bacakanJson() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if (!is_array($data)) gagal("Permintaan tidak valid (JSON tidak terbaca)");
    return $data;
}
function teksBersih($v) {
    return trim(strval($v));
}
function statusNormal($s) {
    return (isset($s) && $s === "Sudah Bayar") ? "Sudah Bayar" : "Belum Bayar";
}
function barisValid($r) {
    $periode = isset($r["periode"]) ? teksBersih($r["periode"]) : "";
    $nama    = isset($r["nama"]) ? teksBersih($r["nama"]) : "";
    if ($periode === "" || $nama === "") return null;
    return array(
        "periode"     => $periode,
        "nama"        => $nama,
        "meter_awal"  => intval(isset($r["meter_awal"]) ? $r["meter_awal"] : 0),
        "meter_akhir" => intval(isset($r["meter_akhir"]) ? $r["meter_akhir"] : 0),
        "cash"        => intval(isset($r["cash"]) ? $r["cash"] : 0),
        "biaya_beban" => intval(isset($r["biaya_beban"]) ? $r["biaya_beban"] : 2000),
        "saldo_lalu"  => intval(isset($r["saldo_lalu"]) ? $r["saldo_lalu"] : 0),
        "status"      => statusNormal(isset($r["status"]) ? $r["status"] : "")
    );
}

// UPSERT satu baris: sudah ada (periode, nama) -> UPDATE, belum -> INSERT.
// Manual (tanpa ON CONFLICT) supaya kompatibel SQLite versi lama sekalipun.
function upsertBaris($pdo, $tabel, $r, $kolomTerbatas) {
    $ada = $pdo->prepare("SELECT id FROM " . $tabel . " WHERE periode = ? AND nama = ? LIMIT 1");
    $ada->execute(array($r["periode"], $r["nama"]));
    if ($ada->fetch()) {
        $kolom = array("meter_awal", "meter_akhir", "cash", "biaya_beban", "saldo_lalu", "status");
        if (is_array($kolomTerbatas) && count($kolomTerbatas)) {
            $kolom = array();
            $boleh = array("meter_awal", "meter_akhir", "cash", "biaya_beban", "saldo_lalu", "status");
            foreach ($kolomTerbatas as $k) {
                if (in_array($k, $boleh, true)) $kolom[] = $k;
            }
            if (!count($kolom)) $kolom = array("meter_awal", "meter_akhir", "cash", "biaya_beban", "saldo_lalu", "status");
        }
        $set = array();
        $args = array();
        foreach ($kolom as $k) { $set[] = $k . " = ?"; $args[] = $r[$k]; }
        $args[] = $r["periode"]; $args[] = $r["nama"];
        $st = $pdo->prepare("UPDATE " . $tabel . " SET " . implode(", ", $set) . " WHERE periode = ? AND nama = ?");
        $st->execute($args);
        return "ubah";
    }
    $st = $pdo->prepare("INSERT INTO " . $tabel . " (periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $st->execute(array($r["periode"], $r["nama"], $r["meter_awal"], $r["meter_akhir"],
                       $r["cash"], $r["biaya_beban"], $r["saldo_lalu"], $r["status"]));
    return "sisip";
}

// Upsert banyak baris dalam 1 transaksi; hasil hitungan sisip/ubah.
function upsertBanyak($pdo, $tabel, $baris, $kolomTerbatas) {
    $sisip = 0; $ubah = 0;
    $pdo->beginTransaction();
    try {
        foreach ($baris as $r) {
            if (upsertBaris($pdo, $tabel, $r, $kolomTerbatas) === "sisip") $sisip++;
            else $ubah++;
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    return array($sisip, $ubah);
}

$action = isset($_GET["action"]) ? $_GET["action"] : "";

// ---------- 1. CEK ----------
if ($action === "cek") {
    $rt08 = intval($pdo->query("SELECT COUNT(*) FROM transaksi_pelanggan_rt08")->fetchColumn());
    $rt09 = intval($pdo->query("SELECT COUNT(*) FROM transaksi_pelanggan_rt09")->fetchColumn());
    balas(array("ok" => true, "db" => true, "tabel" => true,
                "rt08" => array("baris" => $rt08), "rt09" => array("baris" => $rt09)));
}

// ---------- 2. BACA BARIS ----------
if ($action === "table") {
    $tabel = tabelValid(isset($_GET["table"]) ? $_GET["table"] : "");
    if (!$tabel) gagal("Tabel tidak dikenal");
    $where = array();
    $args = array();
    if (!empty($_GET["periode"])) { $where[] = "periode = ?"; $args[] = teksBersih($_GET["periode"]); }
    if (!empty($_GET["nama"]))    { $where[] = "nama = ?";    $args[] = teksBersih($_GET["nama"]); }
    $limit = intval(isset($_GET["limit"]) ? $_GET["limit"] : 500);
    if ($limit <= 0 || $limit > $MAKS_BARIS) $limit = $MAKS_BARIS;
    $urut = "id";
    if (isset($_GET["urut"]) && $_GET["urut"] === "nama") $urut = "nama";
    $sql = "SELECT id, periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status, updated_at
            FROM " . $tabel;
    if (count($where)) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY " . $urut . " LIMIT " . $limit;
    $st = $pdo->prepare($sql);
    $st->execute($args);
    balas(array("ok" => true, "data" => $st->fetchAll()));
}

// ---------- 3. SISIP BANYAK ----------
if ($action === "insert") {
    $input = bacakanJson();
    $tabel = tabelValid(isset($input["table"]) ? $input["table"] : "");
    if (!$tabel) gagal("Tabel tidak dikenal");
    $rows = isset($input["rows"]) && is_array($input["rows"]) ? $input["rows"] : array($input);
    $baris = array();
    foreach ($rows as $r) { $b = barisValid($r); if ($b) $baris[] = $b; }
    if (!count($baris)) gagal("Tidak ada baris valid (periode & nama wajib)");
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("INSERT INTO " . $tabel . " (periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($baris as $r) {
            $st->execute(array($r["periode"], $r["nama"], $r["meter_awal"], $r["meter_akhir"],
                               $r["cash"], $r["biaya_beban"], $r["saldo_lalu"], $r["status"]));
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        gagal("Gagal menyimpan: " . $e->getMessage(), 500);
    }
    balas(array("ok" => true, "sisip" => count($baris)));
}

// ---------- 4. UBAH ----------
if ($action === "update") {
    $input = bacakanJson();
    $tabel = tabelValid(isset($input["table"]) ? $input["table"] : "");
    if (!$tabel) gagal("Tabel tidak dikenal");
    $filter = isset($input["filter"]) && is_array($input["filter"]) ? $input["filter"] : array();
    $where = array();
    $args = array();
    if (isset($filter["periode"])) { $where[] = "periode = ?"; $args[] = teksBersih($filter["periode"]); }
    if (isset($filter["nama"]))    { $where[] = "nama = ?";    $args[] = teksBersih($filter["nama"]); }
    if (!count($where)) gagal("Filter periode / nama wajib ada saat mengubah data");
    $data = isset($input["data"]) && is_array($input["data"]) ? $input["data"] : array();
    $set = array();
    $setArgs = array();
    foreach ($data as $k => $v) {
        if (!in_array($k, $KOLOM_OK, true)) continue;
        $set[] = $k . " = ?";
        $setArgs[] = ($k === "status") ? statusNormal($v) : (($k === "nama") ? teksBersih($v) : intval($v));
    }
    if (!count($set)) gagal("Tidak ada kolom yang bisa diubah");
    $st = $pdo->prepare("UPDATE " . $tabel . " SET " . implode(", ", $set) . " WHERE " . implode(" AND ", $where));
    $st->execute(array_merge($setArgs, $args));
    balas(array("ok" => true, "ubah" => $st->rowCount()));
}

// ---------- 5. HAPUS ----------
if ($action === "delete") {
    $input = bacakanJson();
    $tabel = tabelValid(isset($input["table"]) ? $input["table"] : "");
    if (!$tabel) gagal("Tabel tidak dikenal");
    $where = array();
    $args = array();
    if (!empty($input["semua"])) {
        // tanpa filter = hapus seluruh tabel (Kosongkan Data)
    } else {
        if (isset($input["periode"])) { $where[] = "periode = ?"; $args[] = teksBersih($input["periode"]); }
        if (isset($input["nama"]))    { $where[] = "nama = ?";    $args[] = teksBersih($input["nama"]); }
        if (!count($where)) gagal("Filter nama / periode wajib ada saat menghapus data");
    }
    $st = $pdo->prepare("DELETE FROM " . $tabel . (count($where) ? " WHERE " . implode(" AND ", $where) : ""));
    $st->execute($args);
    balas(array("ok" => true, "hapus" => $st->rowCount()));
}

// ---------- 6. UPSERT SATU PERIODE (Tutup Buku) ----------
if ($action === "upsert_periode") {
    $input = bacakanJson();
    $tabel = tabelValid(isset($input["table"]) ? $input["table"] : "");
    if (!$tabel) gagal("Tabel tidak dikenal");
    $rows = isset($input["rows"]) && is_array($input["rows"]) ? $input["rows"] : array();
    $baris = array();
    foreach ($rows as $r) { $b = barisValid($r); if ($b) $baris[] = $b; }
    if (!count($baris)) balas(array("ok" => true, "sisip" => 0, "ubah" => 0));
    try {
        list($sisip, $ubah) = upsertBanyak($pdo, $tabel, $baris, isset($input["kolom"]) ? $input["kolom"] : null);
    } catch (Exception $e) {
        gagal("Gagal menyimpan periode: " . $e->getMessage(), 500);
    }
    balas(array("ok" => true, "sisip" => $sisip, "ubah" => $ubah));
}

// ---------- 7. IMPORT HASIL PARSE .sql (baris JSON dari browser) ----------
if ($action === "import_rows") {
    $input = bacakanJson();
    $rows = isset($input["rows"]) && is_array($input["rows"]) ? $input["rows"] : array();
    $perTabel = array();
    foreach ($rows as $r) {
        if (!is_array($r)) continue;
        $b = barisValid($r);
        if (!$b) continue;
        $rt = isset($r["rt"]) ? teksBersih($r["rt"]) : "08";
        $tabel = tabelValid($rt === "09" ? "transaksi_pelanggan_rt09" : "transaksi_pelanggan_rt08");
        if (!$tabel) continue;
        $perTabel[$tabel][] = $b;
    }
    $totalFile = 0; $sisip = 0; $ubah = 0;
    try {
        foreach ($perTabel as $tabel => $baris) {
            $totalFile += count($baris);
            list($s, $u) = upsertBanyak($pdo, $tabel, $baris, null);
            $sisip += $s; $ubah += $u;
        }
    } catch (Exception $e) {
        gagal("Import gagal di tengah jalan: " . $e->getMessage(), 500);
    }
    balas(array("ok" => true, "totalSisip" => $sisip, "totalUbah" => $ubah,
                "dilewati" => array(), "totalFile" => $totalFile));
}

// ---------- 8. IMPORT FILE .db (pulihkan backup) ----------
if ($action === "import_db") {
    if (!isset($_FILES["file"])) gagal("File .db tidak terkirim");
    $f = $_FILES["file"];
    if ($f["error"] !== UPLOAD_ERR_OK) gagal("Upload gagal (kode " . intval($f["error"]) . ")");
    if ($f["size"] > 20971520) gagal("File terlalu besar (maks 20 MB)");
    $tm = $f["tmp_name"];
    $kepala = "";
    $fh = fopen($tm, "rb");
    if ($fh) { $kepala = fread($fh, 16); fclose($fh); }
    if (strpos($kepala, "SQLite format 3") !== 0) gagal("File bukan database SQLite yang valid — pilih file .db hasil Export");

    try {
        $src = new PDO("sqlite:" . $tm);
        $src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        gagal("File .db tidak bisa dibaca: " . $e->getMessage());
    }

    $kolomSql = "SELECT periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status FROM ";
    function bacaTabelSumber($src, $sql, $nama) {
        try {
            $st = $src->prepare($sql . $nama . " ORDER BY id");
            $st->execute();
            return $st->fetchAll();
        } catch (Exception $e) { return array(); } // tabel tidak ada di file ini — anggap kosong
    }

    $rt08 = bacaTabelSumber($src, $kolomSql, "transaksi_pelanggan_rt08");
    $rt09 = bacaTabelSumber($src, $kolomSql, "transaksi_pelanggan_rt09");
    $lama = bacaTabelSumber($src, $kolomSql, "transaksi_pelanggan");
    if (!count($rt08) && count($lama)) $rt08 = $lama; // backup sangat lama (satu tabel tanpa suffix)
    $src = null;

    $totalFile = 0; $sisip = 0; $ubah = 0;
    try {
        $grup = array("transaksi_pelanggan_rt08" => $rt08, "transaksi_pelanggan_rt09" => $rt09);
        foreach ($grup as $tabel => $rows) {
            $baris = array();
            foreach ($rows as $r) {
                $b = barisValid($r);
                if ($b) { $baris[] = $b; $totalFile++; }
            }
            if (count($baris)) {
                list($s, $u) = upsertBanyak($pdo, $tabel, $baris, null);
                $sisip += $s; $ubah += $u;
            }
        }
    } catch (Exception $e) {
        gagal("Import gagal di tengah jalan: " . $e->getMessage(), 500);
    }
    balas(array("ok" => true, "totalSisip" => $sisip, "totalUbah" => $ubah,
                "dilewati" => array(), "totalFile" => $totalFile));
}

// ---------- 9. EXPORT .db ----------
if ($action === "export_db") {
    $nama = "pam-" . date("Y") . "-" . $BULAN_ID[intval(date("n")) - 1] . ".db";
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"" . $nama . "\"");
    header("Content-Length: " . filesize($DB_FILE));
    header("Cache-Control: no-store");
    readfile($DB_FILE);
    exit;
}

// ---------- 10. EXPORT .sql (dump untuk Website) ----------
if ($action === "export_sql") {
    $nama = "pam-" . date("Y") . "-" . $BULAN_ID[intval(date("n")) - 1] . ".sql";
    $kop = "-- Backup Database BPSPAMS Tirta Makmur - SINDUHARJO 08/04 & 09/04\n" .
           "-- Berisi data SEMUA RT (RT 08 + RT 09) — 1 file, 2 tabel.\n" .
           "-- Dibuat: " . date("d/m/Y H:i:s") . "\n\n";
    $isi = $kop;
    foreach (array("transaksi_pelanggan_rt08" => "RT 08", "transaksi_pelanggan_rt09" => "RT 09") as $tabel => $label) {
        $st = $pdo->prepare("SELECT periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status
                             FROM " . $tabel . " ORDER BY id");
        $st->execute();
        $rows = $st->fetchAll();
        $isi .= "-- " . $label . " (" . $tabel . "): " . count($rows) . " baris\n";
        if (!count($rows)) { $isi .= "-- (kosong)\n\n"; continue; }
        $nilai = array();
        foreach ($rows as $r) {
            $esc = function ($v) { return str_replace(array("\\", "'"), array("\\\\", "\\'"), strval($v)); };
            $nilai[] = "('" . $esc($r["periode"]) . "', '" . $esc($r["nama"]) . "', " .
                intval($r["meter_awal"]) . ", " . intval($r["meter_akhir"]) . ", " . intval($r["cash"]) . ", " .
                intval($r["biaya_beban"]) . ", " . intval($r["saldo_lalu"]) . ", '" . $esc($r["status"]) . "')";
        }
        $isi .= "INSERT INTO `" . $tabel . "` (`periode`, `nama`, `meter_awal`, `meter_akhir`, `cash`, `biaya_beban`, `saldo_lalu`, `status`) VALUES\n" .
                implode(",\n", $nilai) . ";\n\n";
    }
    header("Content-Type: application/sql; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"" . $nama . "\"");
    header("Cache-Control: no-store");
    echo $isi;
    exit;
}

// ---------- 11. DAFTAR PERIODE (batas navigasi bulan) ----------
// Daftar periode unik gabungan kedua tabel RT. Dipakai client untuk
// mengunci navigasi ‹ › pada rentang bulan yang benar-benar berisi data.
// FIX PENTING: dulu balasannya diambil dengan mengambil NILAI array
// (hasilnya [true,true,...] saja) sehingga client tidak bisa membaca
// satu pun nama periode — aplikasi mengira database kosong & menutup
// navigasi ke bulan lama. Yang benar mengambil KUNCI array = teks
// periodenya.
// Sekalian kirim "berisi": jumlah baris BERISI DATA NYATA per periode
// (meter akhir > 0, ada uang, atau sudah bayar). Baris kerangka hasil
// cetakan Tutup Buku yang belum diisi (semua nol, belum bayar) TIDAK
// dihitung — supaya bulan itu tidak dianggap "ada datanya" hanya karena
// kerangkanya sudah ada. Plus "pengaturan" (penanda bulan kerja).
if ($action === "periode_list") {
    $semua = array();
    $berisi = array();
    foreach (array("transaksi_pelanggan_rt08", "transaksi_pelanggan_rt09") as $tbPeriode) {
        try {
            $st = $pdo->query("SELECT periode, " .
                "SUM(CASE WHEN meter_akhir > 0 OR cash > 0 OR status = 'Sudah Bayar' THEN 1 ELSE 0 END) AS nyata " .
                "FROM " . $tbPeriode . " GROUP BY periode");
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $p = trim(strval($row["periode"]));
                if ($p === "") continue;
                $semua[$p] = true;
                $berisi[$p] = (isset($berisi[$p]) ? $berisi[$p] : 0) + intval($row["nyata"]);
            }
        } catch (Exception $e) { /* tabel RT belum siap — lewati */ }
    }
    $pengaturan = bacaPengaturan($pdo);
    balas(array("ok" => true, "periode" => array_keys($semua),
                "berisi" => $berisi, "pengaturan" => $pengaturan));
}

// ---------- 12. PENGATURAN (penanda bulan kerja) ----------
// Simpan kecil kunci-nilai. Penanda "bulan_kerja" = satu-satunya bulan
// yang boleh diedit; maju HANYA saat Tutup Buku dijalankan. Tersimpan di
// server supaya semua perangkat (HP petugas lain, dll.) sama.
function bacaPengaturan($pdo) {
    $peng = array();
    try {
        $st = $pdo->query("SELECT kunci, nilai FROM pengaturan");
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $peng[trim(strval($row["kunci"]))] = strval($row["nilai"]);
        }
    } catch (Exception $e) { /* tabel belum siap — balik kosong */ }
    return $peng;
}

if ($action === "setting_get") {
    balas(array("ok" => true, "pengaturan" => bacaPengaturan($pdo)));
}

if ($action === "setting_set") {
    $input = bacakanJson();
    $kunci = isset($input["kunci"]) ? teksBersih($input["kunci"]) : "";
    $nilai = isset($input["nilai"]) ? teksBersih($input["nilai"]) : "";
    if ($kunci === "" || $nilai === "") gagal("kunci dan nilai wajib diisi");
    if (strlen($kunci) > 100 || strlen($nilai) > 100) gagal("kunci / nilai terlalu panjang (maks 100 karakter)");
    try {
        // Manual (tanpa ON CONFLICT) supaya kompatibel SQLite versi lama.
        $ada = $pdo->prepare("SELECT kunci FROM pengaturan WHERE kunci = ? LIMIT 1");
        $ada->execute(array($kunci));
        if ($ada->fetch()) {
            $st = $pdo->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = ?");
            $st->execute(array($nilai, $kunci));
        } else {
            $st = $pdo->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?)");
            $st->execute(array($kunci, $nilai));
        }
    } catch (Exception $e) {
        gagal("Gagal menyimpan pengaturan: " . $e->getMessage(), 500);
    }
    balas(array("ok" => true, "kunci" => $kunci, "nilai" => $nilai));
}

gagal("Parameter action wajib: cek / table / insert / update / delete / upsert_periode / import_rows / import_db / export_db / export_sql / periode_list / setting_get / setting_set");
?>
