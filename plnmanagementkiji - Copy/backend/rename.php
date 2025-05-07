<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    if (!isset($_POST['oldname']) || !isset($_POST['newname'])) {
        throw new Exception("Missing required parameters");
    }

    $oldname = trim($_POST['oldname']);
    $newname = trim($_POST['newname']);

    if (empty($oldname) || empty($newname)) {
        throw new Exception("Names cannot be empty");
    }

    $uploadDir = dirname(__DIR__) . "/uploads/";
    $oldPath = $uploadDir . $oldname;
    $newPath = $uploadDir . $newname;

    // Validasi path
    if (!file_exists($oldPath)) {
        throw new Exception("Source folder/file does not exist");
    }

    if (file_exists($newPath)) {
        throw new Exception("A folder/file with this name already exists");
    }

    // Lakukan rename
    if (!rename($oldPath, $newPath)) {
        throw new Exception("Failed to rename folder/file");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Berhasil mengubah nama"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>