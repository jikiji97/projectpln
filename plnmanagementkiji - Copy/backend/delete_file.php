<?php
header("Content-Type: application/json");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Metode tidak didukung.");
    }

    if (!isset($_POST['filename'])) {
        throw new Exception("Nama file tidak diberikan.");
    }

    $filename = $_POST['filename'];
    $folder = "uploads/"; // Sesuaikan dengan folder tempat file disimpan
    $filepath = __DIR__ . "/../" . $folder . $filename;

    // Periksa apakah path ada
    if (!file_exists($filepath)) {
        throw new Exception("File/folder tidak ditemukan di server: $filename");
    }

    // Fungsi rekursif untuk menghapus folder dan isinya
    function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

    // Hapus file atau folder
    if (is_dir($filepath)) {
        if (!deleteDirectory($filepath)) {
            throw new Exception("Gagal menghapus folder: $filename");
        }
        $message = "Folder berhasil dihapus!";
    } else {
        if (!unlink($filepath)) {
            throw new Exception("Gagal menghapus file: $filename");
        }
        $message = "File berhasil dihapus!";
    }

    error_log("🗑 Berhasil dihapus: " . $filepath);
    echo json_encode(["status" => "success", "message" => $message]);

} catch (Exception $e) {
    error_log("⛔ ERROR: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>