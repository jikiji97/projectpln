<?php
// Koneksi ke database
require 'backend/db.php';

// Ambil total pelanggan
$result_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM lpb_januari");
$total_pelanggan = mysqli_fetch_assoc($result_total)['total'];

// Ambil jumlah pelanggan yang nyala (asumsi status ada di kolom KDSWITCHING)
$result_nyala = mysqli_query($conn, "SELECT COUNT(*) AS nyala FROM lpb_januari WHERE KDSWITCHING LIKE '%CA01'");
$nyala = mysqli_fetch_assoc($result_nyala)['nyala'];



// Hitung pelanggan padam
$padam = $total_pelanggan - $nyala;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jam Nyala</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <!-- ✅ Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">PLN Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="backend/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            background: linear-gradient(to bottom, #2d5dcc, #1e3a8a);
            color: white;
            padding: 20px;
            
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
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

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="jam_nyala.php">⏳ Monitoring</a>
        <a href="lpb.php">📜 Laporan LPB</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h2 class="mb-4">Jam Nyala</h2>

        <!-- Filter Form -->
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">ID Pelanggan</label>
                <input type="text" class="form-control" placeholder="Masukkan ID">
            </div>
            <div class="col-md-3">
                <label class="form-label">Nama Pelanggan</label>
                <input type="text" class="form-control" placeholder="Masukkan Nama">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bulan</label>
                <select class="form-select">
                    <option>Semua</option>
                    <option>Januari</option>
                    <option>Februari</option>
                    <option>Maret</option>
                    <option>April</option>
                    <option>Mei</option>
                    <option>Juni</option>
                    <option>Juli</option>
                    <option>Agustus</option>
                    <option>September</option>
                    <option>Oktober</option>
                    <option>September</option>
                    <option>November</option>
                    <option>Desember</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tahun</label>
                <select class="form-select">
                    <option>Semua</option>
                    <option>2024</option>
                    <option>2025</option>
                </select>

            </div>
            <div class="col-md-3 d-flex justify-content-center mt-4">
                <button class="btn btn-primary w-100">Cari</button>
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
                <?php
                $query = "SELECT IDPEL, NAMA, TARIP, DAYA, KDSWITCHING FROM lpb_januari";
                $result = mysqli_query($conn, $query);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $status = (strpos($row['KDSWITCHING'], 'CA01') !== false) ? 
                              "<span class='badge bg-success'>Nyala</span>" : 
                              "<span class='badge bg-danger'>Padam</span>";
                    echo "<tr>
                            <td>{$row['IDPEL']}</td>
                            <td>{$row['NAMA']}</td>
                            <td>{$row['TARIP']}</td>
                            <td>{$row['DAYA']}</td>
                            <td>{$status}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- Copyright -->
        <p class="text-center mt-4">&copy; 2025 PLN Data Management | All Rights Reserved.</p>
    </div>

    <!-- jQuery & DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#jamNyalaTable').DataTable();
        });
    </script>

</body>
</html>
