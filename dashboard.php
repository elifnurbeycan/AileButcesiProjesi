<?php
// Oturumu başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Giriş yapılmamışsa login'e yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Oturumdan kullanıcı bilgilerini al
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Kullanıcı'; // Eğer set edilmediyse varsayılan değer

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Paneli - Aile Bütçesi Uygulaması</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Özel CSS -->
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: #007bff;
            padding: 15px 0;
        }
        .navbar-brand {
            font-weight: bold;
            color: #fff !important;
            font-size: 1.5rem;
        }
        .navbar-nav .nav-link {
            color: #fff !important;
            margin-left: 20px;
            font-weight: 500;
        }
        .navbar-nav .nav-link:hover {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        .container-main {
            flex-grow: 1;
            padding: 40px 0;
        }
        .welcome-card {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .welcome-card h3 {
            color: #007bff;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .btn-logout {
            background-color: #dc3545;
            border-color: #dc3545;
            transition: background-color 0.3s ease, border-color 0.3s ease;
            border-radius: 8px;
            font-weight: bold;
            padding: 10px 20px;
        }
        .btn-logout:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Aile Bütçesi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Anasayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expenses.php">Harcamalar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_families.php">Ailelerim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_family.php">Aile Oluştur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="join_family.php">Aileye Katıl</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main d-flex justify-content-center align-items-center">
        <div class="welcome-card">
            <h3>Hoş Geldin, <?php echo htmlspecialchars($username); ?>!</h3>
            <p class="lead">Aile Bütçesi Yönetim Uygulamasına hoş geldiniz.</p>
            <p>Yukarıdaki menüyü kullanarak harcamalarınızı yönetebilir, ailelerinizi düzenleyebilir ve daha fazlasını yapabilirsiniz.</p>
            <a href="logout.php" class="btn btn-logout mt-3">Çıkış Yap</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
