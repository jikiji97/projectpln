<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard PLN</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* 🔹 Style Global */
        body {
            background-color: #f5f5f5; /* Abu-abu minimalis */
            color: #333;
            font-family: 'Montserrat', sans-serif;
        }

        /* ✅ Navbar Baru */
        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #ddd;
        }

        .navbar-brand {
            color: #0056b3;
            font-weight: bold;
        }

        .navbar-nav .nav-link {
            color: #333 !important;
            font-weight: bold;
        }

        .navbar-nav .nav-link:hover {
            color: #0056b3 !important;
        }

        .navbar-nav .nav-link[href*="logout"] {
            color: red !important;
        }

        /* 🔹 Home (Dengan Background Gambar) */
        #home {
            background: #00000;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: black;
            font-size: 2rem;
            font-weight: bold;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        /* 🔹 Section Styling */
        section {
            padding: 100px 0;
            text-align: center;
        }

        /* 🔹 Services & Contact */
        #services {
            background-color: #ffc107;
            color: black;
        }

        #contact {
        color: white; /* Mengubah warna teks menjadi putih */
        }

        #contact a {
            color: white; /* Warna default link menjadi putih */
            text-decoration: none; /* Menghilangkan garis bawah link */
        }

        #contact a:hover {
            color: yellow; /* Warna link berubah menjadi kuning saat hover */
            text-decoration: underline; /* Menambahkan garis bawah saat hover */
        }

        /* 🔹 Footer */
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .navbar-nav .nav-link {
            color: black !important; /* Semua teks navbar jadi hitam */
        }

        .navbar-nav .nav-link[href*="logout"] {
            color: red !important; /* Khusus Logout tetap merah */
        }

    </style>
</head>
<body>

<!-- ✅ Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand ms-3" href="dashboard.php">PLN Management</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="backend/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- ✅ Home (Dengan Background Gambar) -->
<section id="home" style="background: #F8F9FA; height: 70vh; display: flex; align-items: center; text-align: center;">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-6 text-lg-start text-center">

        <!-- 🌤️ Kartu Hari Baru -->
        <div class="card-time-cloud mb-5">
          <div class="card-time-cloud-front"></div>
          <div class="card-time-cloud-back">
            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
              <path
                fill="#FFFFFF"
                d="M32.4,-41C45.2,-42.2,61,-38.6,63.9,-29.9C66.8,-21.2,56.8,-7.2,47.5,1.7C38.2,10.6,29.6,14.4,26.3,28.4C22.9,42.3,24.7,66.4,18.4,73C12,79.7,-2.5,68.8,-19.2,64.4C-35.9,60,-54.8,61.9,-56.2,52.9C-57.7,43.8,-41.7,23.7,-37.5,9.4C-33.3,-5,-41,-13.6,-44.4,-26.2C-47.8,-38.7,-47,-55.2,-38.9,-56.2C-30.7,-57.2,-15.4,-42.7,-2.8,-38.3C9.8,-34,19.6,-39.8,32.4,-41Z"
                transform="translate(100 100)">
              </path>
            </svg>
          </div>
          <div class="card-time-cloud-rain-group">
            <div class="card-time-cloud-rain"></div>
            <div class="card-time-cloud-rain"></div>
            <div class="card-time-cloud-rain"></div>
            <div class="card-time-cloud-rain"></div>
            <div class="card-time-cloud-rain"></div>
            <div class="card-time-cloud-rain"></div>
            <div class="card-time-cloud-rain"></div>
            <div class="card-time-cloud-rain"></div>
            <div class="card-time-cloud-rain"></div>
            <div class="card-time-cloud-rain"></div>
          </div>
          <p class="card-time-cloud-day" id="cloud-day">Loading...</p>
          <p class="card-time-cloud-day-number" id="cloud-date">Loading...</p>
          <p class="card-time-cloud-hour" id="cloud-time">Loading...</p>
        </div>

        <p class="lead">Halo, Selamat Datang!</p>
        <h1 class="fw-bold display-4" style="color: #15677b;">PLN Data Management</h1>
        <p class="lead" style="color:rgb(0, 0, 0);">Kelola & Analisis Data Jam Nyala PLN</p>
      </div>

      <div class="col-lg-6 d-none d-lg-block">
        <img src="https://scontent.fsub9-1.fna.fbcdn.net/v/t51.75761-15/482903834_18362640115193190_1798138328590238832_n.jpg?_nc_cat=103&ccb=1-7&_nc_sid=127cfc&_nc_eui2=AeG_QZk9zd9GVesYRku1xLW-gzB8IpztxxiDMHwinO3HGDQi_kRiOUG02SXql5mvA5XlDMzENec9GfldKAssUdaZ&_nc_ohc=NSsibO9UDvQQ7kNvwGKJhep&_nc_oc=AdmjQQ36FGzK0Dv8zgO8_x-p1w9TrSY9gOVJlV33YmM2zAAMYETMq_YpB_V3tLiACTI&_nc_zt=23&_nc_ht=scontent.fsub9-1.fna&_nc_gid=djixYUlsgxV-JgOIM3vKJA&oh=00_AfFvuDntFgpwMthpIU80HGjB48tt39a_KPtZcCbx6oAEGw&oe=67FA4037" alt="PLN Dashboard" class="img-fluid">
      </div>
    </div>
  </div>
</section>

<style>
.card-time-cloud {
  position: relative;
  width: 200px;
  height: 100px;
  background: linear-gradient(135deg, #00aaff, #0080ff);
  border-radius: 20px;
  padding: 15px;
  color: white;
  overflow: hidden;
}

.card-time-cloud-front,
.card-time-cloud-back {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  z-index: 0;
}

.card-time-cloud-back svg {
  width: 100%; height: 100%;
  opacity: 0.15;
}

.card-time-cloud-rain-group {
  position: absolute;
  top: 80px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 2px;
}

.card-time-cloud-rain {
  width: 2px;
  height: 10px;
  background: white;
  opacity: 0.5;
  animation: rain 1s infinite linear;
}

@keyframes rain {
  0% { transform: translateY(0); }
  100% { transform: translateY(20px); opacity: 0; }
}

.card-time-cloud-day,
.card-time-cloud-day-number,
.card-time-cloud-hour {
  position: relative;
  z-index: 2;
  margin: 0;
  font-weight: bold;
  font-size: 14px;
}

.card-time-cloud-hour {
  font-size: 18px;
  margin-top: 4px;
}

.card-time-cloud-icon {
  position: absolute;
  bottom: 10px;
  right: 10px;
  z-index: 2;
}

.card-content:hover {
    transform: translateY(-5px);
}

.service-box {
  display: flex;
  align-items: center;
  background-color: #ffffff;
  border-radius: 14px;
  padding: 15px 20px;
  box-shadow: 0 4px 12px rgba(104, 162, 229, 0.73);
  text-decoration: none;
  color: #000;
  transition: box-shadow 0.2s ease;
  width: 280px;
}

.service-box:hover {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.service-icon {
  width: 50px;
  height: 50px;
  margin-right: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.service-icon img {
  width: 100%;
  height: auto;
}

.service-text small {
  color: #666;
}

</style>

<script>
  function updateTimeCloud() {
    const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

    const now = new Date();
    const day = days[now.getDay()];
    const date = now.getDate();
    const month = months[now.getMonth()];
    const year = now.getFullYear();
    const hour = String(now.getHours()).padStart(2, '0');
    const minute = String(now.getMinutes()).padStart(2, '0');

    document.getElementById("cloud-day").textContent = day;
    document.getElementById("cloud-date").textContent = `${date} ${month} ${year}`;
    document.getElementById("cloud-time").textContent = `${hour}:${minute}`;
  }

  updateTimeCloud();
  setInterval(updateTimeCloud, 60000); // perbarui setiap menit
</script>

<!-- ✅ Services -->
<section id="services" style="background: #F8F9FA; padding: 50px 0;">
  <div class="container">
    <h5 class="fw-bold text-center">Aplikasi dan Layanan</h5>
    <div class="d-flex justify-content-center gap-4 flex-wrap mt-4">
      <a href="jam_nyala.php" class="service-box">
        <div class="service-icon">
        </div>
        <div class="service-text">
          <h6 class="fw-bold mb-1">Monitoring</h6>
          <small>Data jam nyala PLN</small>
        </div>
      </a>
      <a href="lpb.php" class="service-box">
        <div class="service-icon">
        </div>
        <div class="service-text">
          <h6 class="fw-bold mb-1">LPB</h6>
          <small>Listrik Prabayar</small>
        </div>
      </a>
    </div>
  </div>
</section>

<!-- ✅ Contact -->
<section id="contact"style="background: #15677b">
    <div class="container">
        <div class="row align-items-start">
            <!-- Informasi Kontak -->
            <div class="col-md-4 text-start">
                <h5 class="fw-bold">Get in touch</h5>
                <p>📧 <a href="mailto:pln123@pln.co.id">pln123@pln.co.id</a></p>

                <h5 class="fw-bold">Phone Call</h5>
                <p>📞 <a href="(031) 5042572">(031) 5042572</a></p>

                <h5 class="fw-bold">Location</h5>
                <p>📍 <a href="https://maps.app.goo.gl/oedL56cSVpqYDEvD7" target="_blank">
                    Jl. Ngagel Tim. No.14, Pucang Sewu, Kec. Gubeng, Surabaya, Jawa Timur 60283
                </a></p>
                <hr style="width: 50px; border: 2px solid black; margin-left: 0;">
            </div>

            <!-- Peta di Tengah -->
            <div class="col-md-4 text-center">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3949.3508038402213!2d112.75671691478377!3d-7.277891594743021!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd7fbf738af5041%3A0xf9dbcb8a8c5fcb6!2sJl.%20Ngagel%20Tim.%20No.14%2C%20Pucang%20Sewu%2C%20Kec.%20Gubeng%2C%20Surabaya%2C%20Jawa%20Timur%2060283!5e0!3m2!1sen!2x!4v1645000000000" 
                    width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy">
                </iframe>
            </div>

            <!-- Hours di Sebelah Kanan -->
            <div class="col-md-3 text-center ms-auto">
                <h5 class="fw-bold">Hours</h5>  
                <div class="hours-container">
                    <div class="hour-item"><span>Monday</span> <span>8:00am – 12:00am</span></div>
                    <div class="hour-item"><span>Tuesday</span> <span>8:00am – 12:00am</span></div>
                    <div class="hour-item"><span>Wednesday</span> <span>8:00am – 12:00am</span></div>
                    <div class="hour-item"><span>Thursday</span> <span>8:00am – 12:00am</span></div>
                    <div class="hour-item"><span>Friday</span> <span>8:00am – 12:00am</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Styling untuk jam agar sejajar */
    .hours-container {
        display: flex;
        flex-direction: column;
        width: 100%;
        text-align: right;
    }
    .hour-item {
        display: flex;
        justify-content: space-between;
        width: 250px; /* Sesuaikan agar rapi */
        font-family: Arial, sans-serif;
    }
</style>

<!-- ✅ Footer -->
<footer>
    <p>© 2025 PLN Data Management | All Rights Reserved.</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>