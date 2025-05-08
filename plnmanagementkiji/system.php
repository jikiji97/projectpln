<?php
require 'backend/db.php';
require 'vendor/autoload.php'; // Untuk PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_excel') {
    // Ambil parameter filter
    $filter_id = isset($_POST['id_pelanggan']) ? $_POST['id_pelanggan'] : '';
    $filter_nama = isset($_POST['nama_pelanggan']) ? $_POST['nama_pelanggan'] : '';
    $filter_status = isset($_POST['status']) ? $_POST['status'] : '';
    $filter_tarif = isset($_POST['tarif']) ? (array) $_POST['tarif'] : [];
    $filter_daya = isset($_POST['daya']) ? (array) $_POST['daya'] : [];

    // Tentukan database dan tabel yang akan digunakan
    $filter_tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
    $date_parts = explode('-', $filter_tanggal);
    $selected_year = $date_parts[0];
    $month_number = $date_parts[1];

    // Convert month number to month name
    $month_names = [
        '01' => 'januari',
        '02' => 'februari',
        '03' => 'maret',
        '04' => 'april',
        '05' => 'mei',
        '06' => 'juni',
        '07' => 'juli',
        '08' => 'agustus',
        '09' => 'september',
        '10' => 'oktober',
        '11' => 'november',
        '12' => 'desember'
    ];

    $selected_month = $month_names[$month_number];

    $database_name = "pln_db" . $selected_year;
    if (!in_array($selected_year, ['2024', '2025'])) {
        $database_name = 'pln_db2025';
    }

    $table_name = "lpb_" . $selected_month . $selected_year;

    mysqli_select_db($conn, $database_name);

    // Buat query dengan filter
    $query = "SELECT IDPEL, NAMA, TARIP, DAYA, KDSWITCHING, TGLBAYAR FROM $table_name WHERE 1=1";

    // Add date filter
    $query .= " AND TGLBAYAR = '" . mysqli_real_escape_string($conn, $filter_tanggal) . "'";

    if (!empty($filter_id)) {
        $query .= " AND IDPEL LIKE '%" . mysqli_real_escape_string($conn, $filter_id) . "%'";
    }
    if (!empty($filter_nama)) {
        $query .= " AND NAMA LIKE '%" . mysqli_real_escape_string($conn, $filter_nama) . "%'";
    }
    if ($filter_status == 'Nyala') {
        $query .= " AND KDSWITCHING LIKE '%CA01'";
    } elseif ($filter_status == 'Mati') {
        $query .= " AND KDSWITCHING NOT LIKE '%CA01'";
    }
    if (!empty($filter_tarif)) {
        $tarif_in = "'" . implode("','", array_map(function ($item) use ($conn) {
            return mysqli_real_escape_string($conn, $item);
        }, $filter_tarif)) . "'";
        $query .= " AND TARIP IN ($tarif_in)";
    }
    if (!empty($filter_daya)) {
        $daya_in = "'" . implode("','", array_map(function ($item) use ($conn) {
            return mysqli_real_escape_string($conn, $item);
        }, $filter_daya)) . "'";
        $query .= " AND DAYA IN ($daya_in)";
    }

    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Error in query: " . mysqli_error($conn));
    }

    // Buat spreadsheet baru
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set header kolom
    $sheet->setCellValue('A1', 'ID Pelanggan');
    $sheet->setCellValue('B1', 'Nama');
    $sheet->setCellValue('C1', 'Tarif');
    $sheet->setCellValue('D1', 'Daya');
    $sheet->setCellValue('E1', 'Status');
    $sheet->setCellValue('F1', 'Tanggal');
    $sheet->setCellValue('G1', 'Jam Nyala');
    $sheet->setCellValue('H1', 'Bulan');
    $sheet->setCellValue('I1', 'Tahun');

    // Style untuk header
    $headerStyle = [
        'font' => [
            'bold' => true,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'FFFF00',
            ],
        ],
    ];
    $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

    // Isi data
    $row = 2;
    while ($data = mysqli_fetch_assoc($result)) {
        $status = (strpos($data['KDSWITCHING'], 'CA01') !== false) ? 'Nyala' : 'Mati';
        $jam_nyala = rand(1, 12) . " jam"; // Sesuai dengan yang ditampilkan di tabel

        // Parse tanggal
        $tanggal_lengkap = $data['TGLBAYAR'];
        $tanggal = date('d', strtotime($tanggal_lengkap));
        $bulan = date('m', strtotime($tanggal_lengkap));
        $tahun = date('Y', strtotime($tanggal_lengkap));

        $sheet->setCellValue('A' . $row, $data['IDPEL']);
        $sheet->setCellValue('B' . $row, $data['NAMA']);
        $sheet->setCellValue('C' . $row, $data['TARIP']);
        $sheet->setCellValue('D' . $row, $data['DAYA']);
        $sheet->setCellValue('E' . $row, $status);
        $sheet->setCellValue('F' . $row, $data['TGLBAYAR']);
        $sheet->setCellValue('G' . $row, $jam_nyala);
        $sheet->setCellValue('H' . $row, $bulan);
        $sheet->setCellValue('I' . $row, $tahun);

        $row++;
    }

    // Auto-size kolom
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set nama file
    $filename = 'Data_Pelanggan_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Set header untuk download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Tulis file Excel
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>