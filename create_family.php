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

// Hata ve başarı mesajlarını saklamak için boş diziler/değişkenler
$errors = [];
$success_message = '';

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verisini al ve güvenlik için trim() fonksiyonu ile boşlukları temizle
    $family_name = trim($_POST['family_name']);

    // ----- Veri Doğrulama (Validation) -----

    // Aile adı boş mu?
    if (empty($family_name)) {
        $errors[] = "Aile adı boş bırakılamaz.";
    } elseif (strlen($family_name) < 3 || strlen($family_name) > 100) {
        $errors[] = "Aile adı 3 ile 100 karakter arasında olmalıdır.";
    } elseif (!preg_match("/^[a-zA-Z0-9\sçÇğĞıİöÖşŞüÜ]+$/u", $family_name)) {
        // Aile adı sadece harf, rakam ve boşluk içerebilir (Türkçe karakter desteği ile)
        $errors[] = "Aile adı sadece harf, rakam ve boşluk içerebilir.";
    }

    // ----- Veritabanı Kontrolleri (Validation) -----

    // Eğer şu ana kadar hata yoksa, veritabanında aynı isimde bir aile zaten var mı kontrol et
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM families WHERE name = ?");
        $stmt->bind_param("s", $family_name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Bu aile adı zaten kullanılıyor. Lütfen başka bir ad seçin.";
        }
        $stmt->close();
    }

    // ----- Aile Oluşturma İşlemi -----

    // Eğer tüm kontrollerden geçtiyse, aileyi kaydet
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO families (name) VALUES (?)");
        $stmt->bind_param("s", $family_name);

        if ($stmt->execute()) {
            $new_family_id = $stmt->insert_id; // Yeni oluşturulan ailenin ID'sini al

            // Aileyi oluşturan kullanıcıyı otomatik olarak bu aileye üye olarak ekle
            $user_id = $_SESSION['user_id'];
            $stmt_member = $conn->prepare("INSERT INTO family_members (family_id, user_id, role) VALUES (?, ?, 'admin')"); // Aileyi oluşturan varsayılan olarak 'admin' olsun
            $stmt_member->bind_param("ii", $new_family_id, $user_id);

            if ($stmt_member->execute()) {
                $success_message = "Aile başarıyla oluşturuldu ve siz aileye yönetici olarak eklendiniz!";
                // İsteğe bağlı: Aile oluşturulduktan sonra kullanıcının ailelerini listeleyen sayfaya yönlendir.
                // header("Location: my_families.php");
                // exit();
            } else {
                // family_members eklemede hata oluşursa, aileyi de geri al (opsiyonel)
                $errors[] = "Aile oluşturuldu ancak aileye üye eklenirken bir hata oluştu: " . $conn->error;
                // Aileyi geri al (rollback) örneği
                // $conn->query("DELETE FROM families WHERE id = $new_family_id");
            }
            $stmt_member->close();

        } else {
            $errors[] = "Aile oluşturulurken bir hata oluştu: " . $conn->error;
        }
        $stmt->close();
    }
}

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aile Oluştur - Aile Bütçesi Uygulaması</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .create-family-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s ease, border-color 0.3s ease;
            border-radius: 8px;
            font-weight: bold;
            padding: 12px 20px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .alert {
            border-radius: 8px;
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
                        <a class="nav-link" href="#">Ailelerim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="create_family.php">Aile Oluştur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <div class="create-family-container">
            <h2 class="text-center mb-4 text-primary fw-bold">Yeni Aile Oluştur</h2>

            <?php
            // Hata mesajlarını göster
            if (!empty($errors)) {
                echo '<div class="alert alert-danger" role="alert">';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }

            // Başarı mesajını göster
            if (!empty($success_message)) {
                echo '<div class="alert alert-success" role="alert">';
                echo htmlspecialchars($success_message);
                echo '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="family_name" class="form-label">Aile / Grup Adı</label>
                    <input type="text" class="form-control rounded-3" id="family_name" name="family_name" value="<?php echo htmlspecialchars($_POST['family_name'] ?? ''); ?>" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Aile Oluştur</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS (Popper.js ile birlikte) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
