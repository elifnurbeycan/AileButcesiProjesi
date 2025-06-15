<?php
// Bu dosya, tüm sayfaların başında dahil edilecek ortak başlık (header) kısmıdır.
// HTML <head> ve <body> etiketlerini açar, CSS ve navigasyon menüsünü içerir.

// Uygulamanızın kök dizini için bir sabit tanımlayın.
// XAMPP'de projeniz htdocs altında "aile_butcesi_projesi" ise bu "/aile_butcesi_projesi/" olmalıdır.
// Canlı sunucuda ise genellikle "/" olabilir veya projeniz bir alt dizinde ise o dizin yolu olur.
define('BASE_URL', '/aile_butcesi_projesi/');

// Oturum başlatılmamışsa başlat (güvenlik için her sayfanın başında gerekli olabilir)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aile Bütçesi - <?php echo basename($_SERVER['PHP_SELF'], '.php'); ?></title>
    <!-- Bootstrap CSS v5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <!-- Kendi özel CSS dosyanız -->
    <link href="<?php echo BASE_URL; ?>css/style.css" rel="stylesheet">
    <!-- Font Awesome İkon Kütüphanesi (list_transactions.php için gerekli) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous">

    <style>
        /* Genel body stilleri */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Navbar stilleri */
        .navbar {
            background-color: #007bff;
            padding: 15px 0;
            border-bottom-left-radius: 15px; /* Alt köşeleri yuvarlat */
            border-bottom-right-radius: 15px; /* Alt köşeleri yuvarlat */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Hafif gölge */
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
            transition: color 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.2);
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(255, 255, 255, 0.8)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
        }

        /* Ana içerik kapsayıcısı stilleri */
        .container-main {
            flex-grow: 1; /* İçeriğin dikeyde yer kaplamasını sağlar */
            padding: 40px 0;
        }

        /* Form ve kart stillerinde yuvarlak köşeler ve gölge */
        .rounded-3 {
            border-radius: 0.5rem !important; /* Tailwind'in rounded-lg ile aynı veya benzer */
        }
        .shadow-sm {
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
        }
        .card, .alert, .form-control, .form-select, .btn {
            border-radius: 0.5rem; /* Geniş yuvarlatılmış köşeler */
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s ease, border-color 0.3s ease;
            font-weight: bold;
            padding: 12px 20px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            transition: background-color 0.3s ease, border-color 0.3s ease;
            font-weight: bold;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
        .text-primary {
            color: #007bff !important;
        }

        /* Tablo stilleri */
        .table th, .table td {
            vertical-align: middle;
        }
        .table thead th {
            background-color: #e9ecef;
            color: #495057;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.03);
        }
        .table-responsive {
            border-radius: 0.5rem;
            overflow: hidden; /* Köşeleri yuvarlatılmış tablo için */
        }

        /* Alert mesajları */
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="<?php echo BASE_URL; ?>dashboard.php">Aile Bütçesi</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>dashboard.php">Anasayfa</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'add_transaction.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>add_transaction.php">İşlem Ekle</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'list_transactions.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>list_transactions.php">İşlemleri Listele</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'financial_summary.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>financial_summary.php">Finansal Özet</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['my_families.php', 'create_family.php', 'join_family.php'])) ? 'active' : ''; ?>" href="#" id="navbarDropdownFamilies" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Aileler
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownFamilies">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>my_families.php">Ailelerim</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>create_family.php">Aile Oluştur</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>join_family.php">Aileye Katıl</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'list_categories.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>list_categories.php">Kategoriler</a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">Çıkış Yap</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>login.php">Giriş Yap</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'register.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>register.php">Kayıt Ol</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main>
