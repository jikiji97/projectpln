<?php
header('Content-Type: application/json');

$response = ["status" => "error", "message" => "Gagal membuat folder"];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['foldername'])) {
        throw new Exception("Invalid request.");
    }

    $foldername = trim($_POST['foldername']);
    if (empty($foldername)) {
        throw new Exception("Nama folder tidak boleh kosong.");
    }

    $target_dir = "uploads/" . $foldername;

    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true) && !is_dir($target_dir)) {
            throw new Exception("Gagal membuat direktori.");
        }
        $response = ["status" => "success", "message" => "Folder '$foldername' berhasil dibuat!"];
    } else {
        $response = ["status" => "error", "message" => "Folder '$foldername' sudah ada."];
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = ["status" => "error", "message" => $e->getMessage()];
}

echo json_encode([
    "status" => "success",
    "message" => "Upload folder berhasil."
]);
?>