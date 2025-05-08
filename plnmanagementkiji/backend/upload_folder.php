<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['files']) || !isset($_POST['paths'])) {
        echo "Tidak ada file atau path.";
        exit;
    }

    $baseDir = __DIR__ . '/uploads/';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0777, true);
    }

    $files = $_FILES['files'];
    $paths = $_POST['paths'];

    for ($i = 0; $i < count($paths); $i++) {
        $relativePath = $paths[$i]; // misal: folderku/file1.xlsx
        $destinationPath = $baseDir . $relativePath;

        $folderPath = dirname($destinationPath);
        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0777, true); // auto create folder
        }

        if (move_uploaded_file($files['tmp_name'][$i], $destinationPath)) {
            // File berhasil dipindahkan
            // Kamu bisa proses PhpSpreadsheet disini (opsional)
            // contoh:
            // if (pathinfo($destinationPath, PATHINFO_EXTENSION) === 'xlsx') {
            //     include 'vendor/autoload.php';
            //     // proses spreadsheet
            // }
        } else {
            echo "Gagal upload file: " . $relativePath . "<br>";
        }
    }

    echo json_encode([
        "status" => "success",
        "message" => "Upload folder berhasil."
    ]);
}
?>