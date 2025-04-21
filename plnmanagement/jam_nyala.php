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

$filter_id = isset($_GET['id_pelanggan']) ? $_GET['id_pelanggan'] : '';
$filter_nama = isset($_GET['nama_pelanggan']) ? $_GET['nama_pelanggan'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_tarif = isset($_GET['tarif']) ? (array) $_GET['tarif'] : [];
$filter_daya = isset($_GET['daya']) ? (array) $_GET['daya'] : [];

$query = "SELECT IDPEL, NAMA, TARIP, DAYA, KDSWITCHING, TGLBAYAR FROM $table_name WHERE 1=1";

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

// Ambil total pelanggan (after filtering)
$result_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ($query) AS filtered");
$total_pelanggan = mysqli_fetch_assoc($result_total)['total'];

// Ambil jumlah pelanggan yang nyala (after filtering)
$result_nyala = mysqli_query($conn, "$query AND KDSWITCHING LIKE '%CA01'");
$nyala = mysqli_num_rows($result_nyala);

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
        <form method="GET" action="">
            <div class="row mb-3 g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">ID Pelanggan</label>
                    <input type="text" class="form-control" name="id_pelanggan" placeholder="Masukkan ID"
                        value="<?= htmlspecialchars($filter_id) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nama Pelanggan</label>
                    <input type="text" class="form-control" name="nama_pelanggan" placeholder="Masukkan Nama"
                        value="<?= htmlspecialchars($filter_nama) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pilih Tanggal</label>
                    <input type="date" class="form-control" name="tanggal">
                </div>
                <div class="col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
            </div>

            <div class="row mb-3 g-2 align-items-end">
                <!-- Filter Tarif -->
                <div class="col-md-3">
                    <label class="form-label">Pilih Tarif</label>
                    <div class="custom-multiselect" id="tarifSelect">
                        <div class="selected" onclick="toggleDropdown('tarifSelect')">
                            <?= !empty($filter_tarif) ? implode(', ', $filter_tarif) : '-- Pilih --' ?>
                        </div>
                        <div class="dropdown">
                            <?php 
                            $tarif_options = ['R1', 'R1M', 'B2', 'R2', 'B1', 'R3', 'S2', 'P3', 'P1', 'L', 'I1'];
                            foreach ($tarif_options as $option): ?>
                                <label>
                                    <input type="checkbox" name="tarif[]" value="<?= $option ?>" 
                                        <?= in_array($option, $filter_tarif) ? 'checked' : '' ?>
                                        onclick="updateSelectedText('tarifSelect')">
                                    <?= $option ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Filter Daya -->
                <div class="col-md-3">
                    <label class="form-label">Pilih Daya</label>
                    <div class="custom-multiselect" id="dayaSelect">
                        <div class="selected" onclick="toggleDropdown('dayaSelect')">
                            <?= !empty($filter_daya) ? implode(', ', $filter_daya) : '-- Pilih --' ?>
                        </div>
                        <div class="dropdown">
                            <?php 
                            $daya_options = ['450', '900', '1300', '2200', '3500', '4400', '5500', '6600', '7700', '10600', '11000', '13200', '16500', '23000', '33000'];
                            foreach ($daya_options as $option): ?>
                                <label>
                                    <input type="checkbox" name="daya[]" value="<?= $option ?>" 
                                        <?= in_array($option, $filter_daya) ? 'checked' : '' ?>
                                        onclick="updateSelectedText('dayaSelect')">
                                    <?= $option ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Filter Status -->
                <div class="col-md-3">
                    <label class="form-label">Pilih Status</label>
                    <select class="form-select" name="status">
                        <option value="">-- Pilih --</option>
                        <option value="Nyala" <?= $filter_status == 'Nyala' ? 'selected' : '' ?>>Nyala</option>
                        <option value="Mati" <?= $filter_status == 'Mati' ? 'selected' : '' ?>>Mati</option>
                    </select>
                </div>

                <!-- Tombol Terapkan -->
                <div class="col-md-3 d-grid align-self-end">
                    <button type="submit" class="btn btn-primary">Cari</button>
                </div>
            </div>
        </form>

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
                    <th>Jam Nyala</th>
                    <th>Tanggal</th>
                    <th>Bulan</th>
                    <th>Tahun</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) {
                    $status = (strpos($row['KDSWITCHING'], 'CA01') !== false) ?
                        "<span class='badge bg-success'>Nyala</span>" :
                        "<span class='badge bg-danger'>Padam</span>";
                    $jam_nyala = rand(1, 12) . " jam";

                    // Ambil tanggal dari data
                    $tanggal_lengkap = $row['TGLBAYAR']; // contoh: 2025-04-20
                    $tanggal = date('d', strtotime($tanggal_lengkap));
                    $bulan = date('m', strtotime($tanggal_lengkap));
                    $tahun = date('Y', strtotime($tanggal_lengkap));

                    echo "<tr>
                            <td>{$row['IDPEL']}</td>
                            <td>{$row['NAMA']}</td>
                            <td>{$row['TARIP']}</td>
                            <td>{$row['DAYA']}</td>
                            <td>{$status}</td>
                            <td>{$jam_nyala}</td>
                            <td>{$tanggal}</td>
                            <td>{$bulan}</td>
                            <td>{$tahun}</td>
                          </tr>";
                } ?>
            </tbody>
        </table>
    </div>

    <style>
        .custom-multiselect {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .custom-multiselect .selected {
            border: 1px solid #ccc;
            padding: 6px;
            border-radius: 4px;
            cursor: pointer;
            background-color: #fff;
        }

        .custom-multiselect .dropdown {
            display: none;
            position: absolute;
            background-color: #fff;
            width: 100%;
            border: 1px solid #ccc;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            padding: 5px;
        }

        .custom-multiselect.open .dropdown {
            display: block;
        }

        .custom-multiselect .dropdown label {
            display: block;
            margin-bottom: 5px;
        }
    </style>

    <script>
        function toggleDropdown(id) {
            const el = document.getElementById(id);
            el.classList.toggle("open");
        }

        document.addEventListener("click", function (e) {
            ['tarifSelect', 'dayaSelect'].forEach(id => {
                const container = document.getElementById(id);
                if (!container.contains(e.target)) {
                    container.classList.remove("open");
                    updateSelectedText(container);
                }
            });
        });

        function updateSelectedText(container) {
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');
            const selectedDiv = container.querySelector('.selected');
            const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);

            if (selected.length === 0) {
                selectedDiv.innerText = "-- Pilih --";
            } else if (selected.length === 1) {
                selectedDiv.innerText = selected[0];
            } else {
                selectedDiv.innerText = `${selected.length} selected`;
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#jamNyalaTable').DataTable();
        });
    </script>

    <!-- Copyright -->
    <footer class="bg-light py-3 mt-5">
    <div class="container">
        <p class="text-center mb-0">&copy; 2025 PLN Data Management | All rights reserved.</p>
    </div>
    </footer>
</body>
</html>
