<?php
require 'backend/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Parameters from DataTables
$draw = isset($_POST['draw']) ? $_POST['draw'] : 1;
$start = isset($_POST['start']) ? $_POST['start'] : 0;
$length = isset($_POST['length']) ? $_POST['length'] : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$order_column = isset($_POST['order'][0]['column']) ? $_POST['order'][0]['column'] : 0;
$order_dir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';

// Filter parameters
$filter_id = isset($_POST['filter_id']) ? $_POST['filter_id'] : '';
$filter_nama = isset($_POST['filter_nama']) ? $_POST['filter_nama'] : '';
$filter_status = isset($_POST['filter_status']) ? $_POST['filter_status'] : '';
$filter_tarif = isset($_POST['filter_tarif']) ? $_POST['filter_tarif'] : [];
$filter_daya = isset($_POST['filter_daya']) ? $_POST['filter_daya'] : [];
$filter_tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
$jam_nyala_min = isset($_POST['jam_nyala_min']) && $_POST['jam_nyala_min'] !== '' ? floatval($_POST['jam_nyala_min']) : 20;
$jam_nyala_max = isset($_POST['jam_nyala_max']) && $_POST['jam_nyala_max'] !== '' ? floatval($_POST['jam_nyala_max']) : 160;

// Debug received parameters
error_log("Received parameters: " . print_r($_POST, true));
error_log("Filter tanggal: " . $filter_tanggal);

// If no date is provided, use current date
if (empty($filter_tanggal)) {
    $filter_tanggal = date('Y-m-d');
}

// Extract year and month from the filter date
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

error_log("Selected year: " . $selected_year);
error_log("Selected month: " . $selected_month);

// Database selection
$database_name = "pln_db" . $selected_year;
if (!in_array($selected_year, ['2024', '2025'])) {
    $database_name = 'pln_db2025';
}

// Table selection based on month and year
$table_name = "lpb_" . $selected_month . $selected_year;

error_log("Database name: " . $database_name);
error_log("Table name: " . $table_name);

// Try to select database
if (!mysqli_select_db($conn, $database_name)) {
    $response = array(
        "draw" => intval($draw),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => "Database tidak ditemukan: " . $database_name
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
if (mysqli_num_rows($table_check) == 0) {
    $response = array(
        "draw" => intval($draw),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => "Tabel tidak ditemukan: " . $table_name
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Column names for ordering
$columns = array(
    0 => 'IDPEL',
    1 => 'NAMA',
    2 => 'TARIP',
    3 => 'DAYA',
    4 => 'KDSWITCHING',
    5 => 'TGLBAYAR'
);

// Base query
$where = "WHERE 1=1";

// Apply date filter
$where .= " AND TGLBAYAR = '" . mysqli_real_escape_string($conn, $filter_tanggal) . "'";

error_log("Date filter condition: " . $where);

// Apply other filters
if (!empty($filter_id)) {
    $where .= " AND IDPEL LIKE '%" . mysqli_real_escape_string($conn, $filter_id) . "%'";
}
if (!empty($filter_nama)) {
    $where .= " AND NAMA LIKE '%" . mysqli_real_escape_string($conn, $filter_nama) . "%'";
}
if ($filter_status == 'Nyala') {
    $where .= " AND KDSWITCHING LIKE '%CA01'";
} elseif ($filter_status == 'Mati') {
    $where .= " AND KDSWITCHING NOT LIKE '%CA01'";
}
if (!empty($filter_tarif)) {
    $tarif_in = "'" . implode("','", array_map(function ($item) use ($conn) {
        return mysqli_real_escape_string($conn, $item);
    }, $filter_tarif)) . "'";
    $where .= " AND TARIP IN ($tarif_in)";
}
if (!empty($filter_daya)) {
    $daya_in = "'" . implode("','", array_map(function ($item) use ($conn) {
        return mysqli_real_escape_string($conn, $item);
    }, $filter_daya)) . "'";
    $where .= " AND DAYA IN ($daya_in)";
}

// Filter jam nyala range
$where .= " AND (CASE WHEN DAYA > 0 THEN PEMKWH/(DAYA/1000) ELSE 0 END) >= $jam_nyala_min AND (CASE WHEN DAYA > 0 THEN PEMKWH/(DAYA/1000) ELSE 0 END) <= $jam_nyala_max";

// Apply search
if (!empty($search)) {
    $where .= " AND (IDPEL LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR 
                     NAMA LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR 
                     TARIP LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR 
                     DAYA LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

// Count total records
$sql_count = "SELECT COUNT(*) as count FROM $table_name $where";
error_log("Count query: " . $sql_count);

$count_result = mysqli_query($conn, $sql_count);
if (!$count_result) {
    $error = "Error in count query: " . mysqli_error($conn) . "\nQuery: " . $sql_count;
    error_log($error);
    $response = array(
        "draw" => intval($draw),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => $error
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$total_records = mysqli_fetch_assoc($count_result)['count'];
error_log("Total records found: " . $total_records);

// Get statistics
$sql_stats = "SELECT 
    COUNT(*) as total_pelanggan,
    SUM(CASE WHEN KDSWITCHING LIKE '%CA01' THEN 1 ELSE 0 END) as daya_normal,
    SUM(CASE WHEN KDSWITCHING NOT LIKE '%CA01' THEN 1 ELSE 0 END) as daya_tidak_normal
FROM $table_name $where";

$stats_result = mysqli_query($conn, $sql_stats);
if (!$stats_result) {
    $error = "Error in stats query: " . mysqli_error($conn) . "\nQuery: " . $sql_stats;
    error_log($error);
    $response = array(
        "draw" => intval($draw),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => $error
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$stats = mysqli_fetch_assoc($stats_result);

// Get filtered data
$sql = "SELECT IDPEL, NAMA, TARIP, DAYA, KDSWITCHING, TGLBAYAR, PEMKWH 
        FROM $table_name 
        $where 
        ORDER BY " . $columns[$order_column] . " $order_dir 
        LIMIT $start, $length";

error_log("Data query: " . $sql);

$result = mysqli_query($conn, $sql);
if (!$result) {
    $error = "Error in data query: " . mysqli_error($conn) . "\nQuery: " . $sql;
    error_log($error);
    $response = array(
        "draw" => intval($draw),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => array(),
        "error" => $error
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Prepare data array
$data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $status = (strpos($row['KDSWITCHING'], 'CA01') !== false) ?
        "<span class='badge bg-success'>Nyala</span>" :
        "<span class='badge bg-danger'>Padam</span>";

    // Calculate jam nyala using the formula: pemkwh / (daya/1000)
    $daya_kw = floatval($row['DAYA']) / 1000; // Convert daya to kW
    $pemkwh = floatval($row['PEMKWH']);
    $jam_nyala = $daya_kw > 0 ? round($pemkwh / $daya_kw, 2) : 0;
    $jam_nyala = $jam_nyala . " jam";

    // Parse tanggal
    $tanggal_lengkap = $row['TGLBAYAR'];
    $tanggal = date('d', strtotime($tanggal_lengkap));
    $bulan = date('m', strtotime($tanggal_lengkap));
    $tahun = date('Y', strtotime($tanggal_lengkap));

    $data[] = array(
        $row['IDPEL'],
        $row['NAMA'],
        $row['TARIP'],
        $row['DAYA'],
        $status,
        $jam_nyala,
        $tanggal,
        $bulan,
        $tahun
    );
}

// Prepare response
$response = array(
    "draw" => intval($draw),
    "recordsTotal" => intval($total_records),
    "recordsFiltered" => intval($total_records),
    "data" => $data,
    "stats" => array(
        "total_pelanggan" => intval($stats['total_pelanggan']),
        "daya_normal" => intval($stats['daya_normal']),
        "daya_tidak_normal" => intval($stats['daya_tidak_normal'])
    )
);

// Debug final response
error_log("Response: " . json_encode($response));

// Set proper JSON header
header('Content-Type: application/json');

// Output JSON response
echo json_encode($response);
?>