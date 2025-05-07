<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 0);

$uploadDir = realpath(__DIR__ . "/../uploads/") . "/"; // Path absolut

if (!is_dir($uploadDir) || !is_readable($uploadDir)) {
    error_log("⚠️ Folder tidak ditemukan atau tidak dapat diakses: " . $uploadDir);
    echo json_encode(["status" => "error", "message" => "Folder tidak ditemukan atau tidak dapat diakses"]);
    exit;
}

// Fungsi untuk mendapatkan daftar file (rekursif)
function getFilesRecursive($dir, $baseDir)
{
    $files = [];
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..')
            continue;
        $fullPath = "$dir/$file";
        if (is_dir($fullPath)) {
            $files[$file] = getFilesRecursive($fullPath, $baseDir);
        } else {
            $relativePath = str_replace($baseDir, '', $fullPath);
            $files[] = $relativePath;
        }
    }
    return $files;
}

$files = getFilesRecursive($uploadDir, $uploadDir);

error_log("📁 File yang ditemukan: " . json_encode($files));
error_log("📁 Response JSON: " . json_encode(["status" => "success", "files" => $files]));

echo json_encode(["status" => "success", "files" => $files], JSON_UNESCAPED_SLASHES);
?>