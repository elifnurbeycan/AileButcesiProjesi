<?php
// Oturum başlatılır.
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
$errors = [];
$success_message = '';

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $family_name_to_join = trim($_POST['family_name_to_join']);

    // ----- Veri Doğrulama (Validation) -----
    if (empty($family_name_to_join)) {
        $errors[] = "Katılmak istediğiniz aile adını boş bırakmayınız.";
    }

    // ----- Aileye Katılma İşlemi -----
    if (empty($errors)) {
        // 1. Aileyi veritabanında ara
        $stmt_find_family = $conn->prepare("SELECT id FROM families WHERE name = ?");
        $stmt_find_family->bind_param("s", $family_name_to_join);
        $stmt_find_family->execute();
        $result_find_family = $stmt_find_family->get_result();

        if ($result_find_family->num_rows == 1) {
            $family = $result_find_family->fetch_assoc();
            $family_id_to_join = $family['id'];

            // 2. Kullanıcının zaten bu aileye üye olup olmadığını kontrol et
            $stmt_check_member = $conn->prepare("SELECT id FROM family_members WHERE family_id = ? AND user_id = ?");
            $stmt_check_member->bind_param("ii", $family_id_to_join, $user_id);
            $stmt_check_member->execute();
            $stmt_check_member->store_result();

            if ($stmt_check_member->num_rows > 0) {
                $errors[] = "Zaten bu aileye üyesiniz.";
            } else {
                // 3. Kullanıcıyı aileye üye olarak ekle
                $stmt_add_member = $conn->prepare("INSERT INTO family_members (family_id, user_id, role) VALUES (?, ?, 'member')");
                $stmt_add_member->bind_param("ii", $family_id_to_join, $user_id);

                if ($stmt_add_member->execute()) {
                    $success_message = "Başarıyla '" . htmlspecialchars($family_name_to_join) . "' ailesine katıldınız!";
                    // İsteğe bağlı: Başarılı katılım sonrası aile listeleme sayfasına yönlendir.
                    // header("Location: my_families.php");
                    // exit();
                } else {
                    $errors[] = "Aileye katılırken bir hata oluştu: " . $conn->error;
                }
                $stmt_add_member->close();
            }
            $stmt_check_member->close();
        } else {
            $errors[] = "Belirtilen aile adı bulunamadı. Lütfen doğru bir aile adı giriniz.";
        }
        $stmt_find_family->close();
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
    <title>Aileye Katıl - Aile Bütçesi Uygulaması</title>
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
        .join-family-container {
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
                        <a class="nav-link" href="my_families.php">Ailelerim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_family.php">Aile Oluştur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="join_family.php">Aileye Katıl</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <div class="join-family-container">
            <h2 class="text-center mb-4 text-primary fw-bold">Mevcut Aileye Katıl</h2>

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
                    <label for="family_name_to_join" class="form-label">Katılmak İstediğiniz Aile Adı</label>
                    <input type="text" class="form-control rounded-3" id="family_name_to_join" name="family_name_to_join" value="<?php echo htmlspecialchars($_POST['family_name_to_join'] ?? ''); ?>" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Aileye Katıl</button>
                </div>
            </form>
            <p class="text-center mt-3">Yoksa yeni bir aile mi oluşturmak istersiniz? <a href="create_family.php">Aile Oluştur</a></p>
        </div>
    </div>

    <!-- Bootstrap JS (Popper.js ile birlikte) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
