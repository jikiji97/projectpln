<?php
include 'db.php';
require __DIR__ . '/../vendor/autoload.php'; // Load PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ["status" => "error", "message" => "Gagal mengunggah file"];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(["status" => "success", "message" => "API aktif, gunakan POST untuk unggah file."]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $upload_type = $_POST['upload_type'] ?? 'file'; // default 'file' kalau ga dikirim
        if (!empty($_FILES['files'])) {
            // echo json_encode(["status" => "success", "message" => "File diterima", "debug" => $_FILES]);
        } else {
            echo json_encode(["status" => "error", "message" => "File tidak ditemukan atau tidak dikirim."]);
            exit; // Tambahkan exit agar tidak lanjut ke proses lain
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Metode request salah."]);
        exit;
    }

    // Cek apakah ada file yang diunggah
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        throw new Exception("Tidak ada file yang dipilih.");
    }

    $target_dir = dirname(__DIR__) . "/uploads/";
    if (!is_dir($target_dir) && !mkdir($target_dir, 0777, true)) {
        throw new Exception("Gagal membuat folder upload.");
    }

    if (!is_writable($target_dir)) {
        throw new Exception("Folder tidak dapat ditulisi! Periksa izin.");
    }

    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'png', 'txt']; // Sesuaikan dengan kebutuhan
    $uploaded_files = [];

    foreach ($_FILES['files']['name'] as $i => $filename) {
        $file_tmp = $_FILES['files']['tmp_name'][$i];
        $file_error = $_FILES['files']['error'][$i];

        // Cek error upload file
        if ($file_error !== UPLOAD_ERR_OK) {
            throw new Exception("Upload gagal untuk file: $filename (Error code: $file_error).");
        }

        // Cek format file dengan mime type dan ekstensi
        $mime_type = mime_content_type($file_tmp);
        $allowed_mimes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'application/pdf' => ['pdf'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'application/vnd.ms-excel' => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
            'text/plain' => ['txt']
        ];

        $is_allowed = false;
        $original_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        foreach ($allowed_mimes as $mime => $extensions) {
            if ($mime_type === $mime) {
                $is_allowed = true;
                // Gunakan ekstensi asli jika valid, jika tidak gunakan default
                if (in_array($original_extension, $extensions)) {
                    $filetype = $original_extension;
                } else {
                    $filetype = $extensions[0];
                }
                break;
            }
        }

        if (!$is_allowed) {
            throw new Exception("Format file tidak diizinkan: $filename (Mime type: $mime_type)");
        }

        // 🔥 Perbedaan logika untuk folder dan file biasa
        if ($upload_type === 'folder') {
            // Path relatif dari folder yang diupload
            $relative_path = $_POST['paths'][$i] ?? $filename;
            $target_file = $target_dir . $relative_path;

            // Pastikan direktori target ada
            $target_directory = dirname($target_file);
            if (!is_dir($target_directory)) {
                if (!mkdir($target_directory, 0777, true)) {
                    throw new Exception("Gagal membuat struktur folder: " . $target_directory);
                }
            }
        } else {
            // Tangani subfolder target dari POST untuk upload file biasa
            $subDir = '';
            if (isset($_POST['target_dir']) && !empty($_POST['target_dir'])) {
                $safeDir = basename($_POST['target_dir']); // mencegah traversal directory
                $subDir = $safeDir . "/";
            }

            $target_directory = $target_dir . $subDir;
            if (!is_dir($target_directory)) {
                mkdir($target_directory, 0777, true);
            }

            // Generate safe filename tapi pertahankan spasi dan titik
            $safeFileName = pathinfo($filename, PATHINFO_FILENAME);
            // Hanya bersihkan karakter yang benar-benar berbahaya
            $safeFileName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $safeFileName);

            // Tambahkan timestamp hanya jika file dengan nama yang sama sudah ada
            $baseFileName = $safeFileName . "." . $filetype;
            $target_file = $target_directory . $baseFileName;
            if (file_exists($target_file)) {
                $target_file = $target_directory . $safeFileName . "_" . time() . "." . $filetype;
            }
        }

        // Pindahkan file ke lokasi yang benar
        if (!move_uploaded_file($file_tmp, $target_file)) {
            throw new Exception("Gagal memindahkan file ke: " . $target_file);
        }

        $uploaded_files[] = $relative_path ?? $filename;

        // Proses file Excel jika diperlukan
        if (in_array($filetype, ['xlsx', 'xls', 'csv'])) {
            processExcelFile($target_file, $filename, $conn);
        }
    }

    $response = ["status" => "success", "message" => "Semua file berhasil diunggah dan diproses!", "files" => $uploaded_files];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    file_put_contents("error_log.txt", date("Y-m-d H:i:s") . " - " . $e->getMessage() . "\n", FILE_APPEND);

    http_response_code(500);
    $response = ["status" => "error", "message" => $e->getMessage()];
}

echo json_encode($response);

/**
 * Proses file Excel dan masukkan ke dalam database
 */
function processExcelFile($filePath, $filename, $conn)
{
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $data = $worksheet->toArray();

    if (empty($data) || !isset($data[0]) || !is_array($data[0])) {
        throw new Exception("File Excel $filename tidak memiliki data atau formatnya salah.");
    }

    list($tahun, $bulan) = extractDateFromFilenameOrData($filename, $data);
    if (!$tahun || !$bulan) {
        throw new Exception("Bulan atau tahun tidak ditemukan dalam file: $filename.");
    }

    $nama_bulan = [
        1 => "januari",
        2 => "februari",
        3 => "maret",
        4 => "april",
        5 => "mei",
        6 => "juni",
        7 => "juli",
        8 => "agustus",
        9 => "september",
        10 => "oktober",
        11 => "november",
        12 => "desember"
    ];

    $dbname = "pln_db" . $tahun;
    $table_name = "lpb_" . $nama_bulan[$bulan] . $tahun;

    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $conn->select_db($dbname);

    createOrUpdateTable($conn, $dbname, $table_name, $data);
    insertDataToTable($conn, $dbname, $table_name, $data);
}


/**
 * Ekstrak tahun dan bulan dari nama file atau data dalam Excel
 */
function extractDateFromFilenameOrData($filename, $data)
{
    $tahun = null;
    $bulan = null;

    if (preg_match('/\b(20\d{2})(0[1-9]|1[0-2])\d{2}\b/', $filename, $matches)) {
        $tahun = $matches[1];
        $bulan = (int) $matches[2];
    }

    if (!$tahun || !$bulan) {
        foreach ($data as $row) {
            foreach ($row as $cell) {
                if (!empty($cell) && preg_match('/\b(20\d{2})-(0[1-9]|1[0-2])/', $cell, $match_tanggal)) {
                    $tahun = $match_tanggal[1];
                    $bulan = (int) $match_tanggal[2];
                    break 2;
                }
            }
        }
    }

    return [$tahun, $bulan];
}


/**
 * Buat atau update tabel database berdasarkan data dari Excel
 */
function createOrUpdateTable($conn, $dbname, $table_name, $data)
{
    $columns = array_map(fn($col) => preg_replace('/[^a-zA-Z0-9_]/', '_', strtoupper($col)), $data[0]);

    // Pastikan tabel ada
    $conn->query("CREATE TABLE IF NOT EXISTS `$table_name` (`id` INT AUTO_INCREMENT PRIMARY KEY)");

    // Ambil daftar kolom yang sudah ada
    $existing_columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `$table_name`");
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }

    // Tambah kolom yang belum ada
    foreach ($columns as $col) {
        if (!in_array($col, $existing_columns)) {
            $conn->query("ALTER TABLE `$table_name` ADD `$col` TEXT");
        }
    }
}


/**
 * Masukkan data ke dalam tabel database
 */
function insertDataToTable($conn, $dbname, $table_name, $data)
{
    $columns = array_map(fn($col) => preg_replace('/[^a-zA-Z0-9_]/', '_', strtoupper($col)), $data[0]);
    $column_names = implode(", ", array_map(fn($col) => "`$col`", $columns));
    $placeholders = implode(", ", array_fill(0, count($columns), "?"));
    $update_stmt = implode(", ", array_map(fn($col) => "`$col` = VALUES(`$col`)", $columns));

    $sql = "INSERT INTO `$table_name` ($column_names) VALUES ($placeholders)
ON DUPLICATE KEY UPDATE $update_stmt";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Kesalahan database: " . $conn->error);
    }

    foreach ($data as $index => $row) {
        if ($index == 0)
            continue;
        $stmt->bind_param(str_repeat("s", count($columns)), ...$row);
        $stmt->execute();
    }
}
?>