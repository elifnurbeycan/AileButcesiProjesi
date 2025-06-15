<?php
// Oturum başlatılır. Bu, kullanıcının oturum bilgilerine erişmek için gereklidir.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantı dosyasını dahil et.
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$families = []; // Kullanıcının üyesi olduğu aileleri saklamak için boş dizi

// Kullanıcının üyesi olduğu aileleri ve rolünü veritabanından çek
// JOIN kullanarak family_members tablosunu families tablosu ile birleştiriyoruz
$stmt = $conn->prepare("SELECT f.id, f.name, fm.role FROM families f JOIN family_members fm ON f.id = fm.family_id WHERE fm.user_id = ? ORDER BY f.name ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $families[] = $row; // Her aileyi diziye ekle
    }
}
$stmt->close();

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ailelerim - Aile Bütçesi Uygulaması</title>
    <!-- Bootstrap CSS v5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Kendi özel CSS dosyanız -->
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
        .family-list-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .family-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            background-color: #fefefe;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .family-card h5 {
            color: #007bff;
            font-weight: bold;
            margin-bottom: 0;
        }
        .family-card .badge {
            font-size: 0.85em;
            padding: 0.5em 0.75em;
            border-radius: 5px;
            background-color: #6c757d; /* Gri renk */
            color: #fff;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 8px;
        }
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Aile Bütçesi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Anasayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Harcamalar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="my_families.php">Ailelerim</a>
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

    <div class="container container-main">
        <div class="family-list-container">
            <h2 class="text-center mb-4 text-primary fw-bold">Ailelerim</h2>

            <?php if (empty($families)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Henüz hiçbir aileye üye değilsiniz. Yeni bir aile oluşturabilir veya mevcut bir aileye katılabilirsiniz.
                </div>
                <div class="text-center">
                    <a href="create_family.php" class="btn btn-primary me-2">Yeni Aile Oluştur</a>
                    <a href="join_family.php" class="btn btn-success">Mevcut Aileye Katıl</a>
                </div>
            <?php else: ?>
                <div class="alert alert-success text-center mb-4" role="alert">
                    Aşağıda üyesi olduğunuz aileler listelenmektedir.
                </div>
                <?php foreach ($families as $family): ?>
                    <div class="family-card">
                        <div>
                            <h5><?php echo htmlspecialchars($family['name']); ?></h5>
                            <span class="badge"><?php echo htmlspecialchars(ucfirst($family['role'])); ?></span>
                        </div>
                        <div>
                            <!-- Aile detaylarına gitmek için link (ileride geliştirilecek) -->
                            <a href="family_details.php?id=<?php echo $family['id']; ?>" class="btn btn-sm btn-outline-primary me-2">Detaylar</a>
                            <!-- Aileden ayrılma/silme özelliği (ileride geliştirilecek) -->
                            <!-- <a href="leave_family.php?id=<?php echo $family['id']; ?>" class="btn btn-sm btn-danger">Ayrıl</a> -->
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="text-center mt-4">
                    <a href="create_family.php" class="btn btn-primary me-2">Yeni Aile Oluştur</a>
                    <a href="join_family.php" class="btn btn-success">Mevcut Aileye Katıl</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS (Popper.js ile birlikte) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
