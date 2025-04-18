<?php
// Koneksi ke database
require 'backend/db.php';

// Ambil nilai bulan dan tahun yang dipilih dari form
$selected_month = isset($_GET['bulan']) ? strtolower($_GET['bulan']) : '';
$selected_year = isset($_GET['tahun']) ? $_GET['tahun'] : '';

$database_name = "pln_db" . $selected_year;
if (!in_array($selected_year, ['2024', '2025'])) {
    $database_name = 'pln_db2025';
}

if (!empty($selected_month) && !empty($selected_year)) {
    $table_name = "lpb_" . $selected_month . $selected_year;
} else {
    $table_name = "lpb_desember2025";
}

mysqli_select_db($conn, $database_name);

$result_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM $table_name");
$total_pelanggan = mysqli_fetch_assoc($result_total)['total'];

$result_nyala = mysqli_query($conn, "SELECT COUNT(*) AS nyala FROM $table_name WHERE KDSWITCHING LIKE '%CA01'");
$nyala = mysqli_fetch_assoc($result_nyala)['nyala'];

$padam = $total_pelanggan - $nyala;

$filter_id = isset($_GET['id_pelanggan']) ? $_GET['id_pelanggan'] : '';
$filter_nama = isset($_GET['nama_pelanggan']) ? $_GET['nama_pelanggan'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT IDPEL, NAMA, TARIP, DAYA, KDSWITCHING FROM $table_name WHERE 1=1";

if (!empty($filter_id)) {
    $query .= " AND IDPEL LIKE '%$filter_id%'";
}
if (!empty($filter_nama)) {
    $query .= " AND NAMA LIKE '%$filter_nama%'";
}
if ($filter_status == 'Nyala') {
    $query .= " AND KDSWITCHING LIKE '%CA01'";
} elseif ($filter_status == 'Padam') {
    $query .= " AND KDSWITCHING NOT LIKE '%CA01'";
}

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jam Nyala</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

/* Sidebar Styling */
.sidebar {
    width: 250px;
    height: 100vh;
    background-color: #f8f9fa;
    padding-top: 20px;
    padding-left: 10px;
    padding-right: 10px;
    position: fixed;
}

/* Styling untuk daftar menu */
.sidebar-menu {
    list-style: none;
    padding: 0;
}

/* Styling untuk setiap item */
.sidebar-item {
    margin-bottom: 10px;
}

/* Styling untuk link */
.sidebar-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    padding: 12px;
    border-radius: 8px;
    font-weight: bold;
    color: #333;
    background-color: transparent;
    transition: 0.3s;
}

.sidebar-link i {
    -size: 20px;
    margin-right: 10px;
}

/* Efek hover */
.sidebar-link:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

/* Item aktif (terpilih) */
.sidebar-item.active .sidebar-link {
    background-color: white;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
}


        /* Navbar */
        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #ddd;
        }

        .navbar-brand {
            color: #0056b3;
            font-weight: bold;
        }

        .navbar-nav .nav-link {
            color: #333;
            font-weight: bold;
        }

        .navbar-nav .nav-link[href*="logout"] {
            color: red !important; /* Khusus Logout tetap merah */
        }

        .navbar-nav .nav-link:hover {
            color: #0056b3;
        }

        /* Content */
        .content {
            margin-left: 260px;
            padding: 20px;
        }

        .table thead {
            background-color: yellow;
        }

        .table tbody td {
            background-color: #d8e9f3;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand ms-3" href="dashboard.php">PLN Management</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto me-3">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="backend/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'jam_nyala.php' ? 'active' : ''; ?>">
                <a href="jam_nyala.php" class="sidebar-link">
                    <i class="bi bi-house-door-fill" style="color: #007bff;"></i>
                    <span>Monitoring</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'lpb.php' ? 'active' : ''; ?>">
                <a href="lpb.php" class="sidebar-link">
                    <i class="bi bi-person-circle" style="color: #6c63ff;"></i>
                    <span>LPB</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h2 class="mb-4">Jam Nyala</h2>

        <!-- Filter Form -->
        <div class="row mb-3 g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">ID Pelanggan</label>
                <input type="text" class="form-control" placeholder="Masukkan ID">
            </div>
            <div class="col-md-3">
                <label class="form-label">Nama Pelanggan</label>
                <input type="text" class="form-control" placeholder="Masukkan Nama">
            </div>
            <div class="col-md-3">
                <label class="form-label">Pilih Tanggal</label>
                <input type="date" class="form-control">
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary">Cari</button>
            </div>
        </div>

        <!-- Statistik -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="alert alert-info">Total Pelanggan: <strong><?php echo $total_pelanggan; ?></strong></div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-success">Daya Normal: <strong><?php echo $nyala; ?></strong></div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-danger">Daya Tidak Normal: <strong><?php echo $padam; ?></strong></div>
            </div>
        </div>

        <!-- Data Table -->
        <table id="jamNyalaTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Pelanggan</th>
                    <th>Nama</th>
                    <th>Tarif</th>
                    <th>Daya</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <!-- Data dari database -->
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#jamNyalaTable').DataTable();
        });
    </script>
</body>
</html>
