<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$host = "localhost";
$user = "root";     // Default user KSWEB
$pass = "root";         // Default password KSWEB (biasanya kosong atau 'admin')
$db   = "pam";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi DB Gagal: " . $conn->connect_error]));
}

$action = $_GET['action'] ?? '';

// 1. GET DATA PERIODE
if ($action === 'get_data') {
    $periode = $_GET['periode'] ?? '';
    $stmt = $conn->prepare("SELECT nama AS NAMA, meter_awal AS METER_AWAL, meter_akhir AS METER_AKHIR, cash AS CASH, biaya_beban AS BIAYA_BEBAN, saldo_lalu AS SALDO_LALU, status AS STATUS FROM transaksi_pelanggan WHERE periode = ?");
    $stmt->bind_param("s", $periode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
}

// 2. SIMPAN / UPDATE TRANSAKSI INDIVIDUAL
if ($action === 'save_item') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("INSERT INTO transaksi_pelanggan (periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE 
                            meter_awal = VALUES(meter_awal),
                            meter_akhir = VALUES(meter_akhir),
                            cash = VALUES(cash),
                            status = VALUES(status)");
                            
    $stmt->bind_param("ssiiiiss", 
        $input['periode'], 
        $input['nama'], 
        $input['meter_awal'], 
        $input['meter_akhir'], 
        $input['cash'], 
        $input['biaya_beban'], 
        $input['saldo_lalu'], 
        $input['status']
    );
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}

// 3. TUTUP BUKU & BULAN BARU
if ($action === 'tutup_buku') {
    $input = json_decode(file_get_contents('php://input'), true);
    $periodeBaru = $input['periode_baru'];
    $items = $input['items'];

    $stmt = $conn->prepare("INSERT INTO transaksi_pelanggan (periode, nama, meter_awal, meter_akhir, cash, biaya_beban, saldo_lalu, status) 
                            VALUES (?, ?, ?, 0, 0, ?, ?, 'Belum Bayar')
                            ON DUPLICATE KEY UPDATE 
                            meter_awal = VALUES(meter_awal),
                            saldo_lalu = VALUES(saldo_lalu)");

    foreach ($items as $item) {
        $stmt->bind_param("ssiii", 
            $periodeBaru, 
            $item['NAMA'], 
            $item['METER_AWAL'], 
            $item['BIAYA_BEBAN'], 
            $item['SALDO_LALU']
        );
        $stmt->execute();
    }
    echo json_encode(["status" => "success"]);
}

$conn->close();
?>
