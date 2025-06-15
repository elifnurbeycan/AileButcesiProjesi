<?php
// Oturum başlatılır. Bu, kullanıcının başarılı girişinden sonra oturum bilgilerini saklamak için kullanılır.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Eğer kullanıcı zaten giriş yapmışsa, dashboard sayfasına yönlendir.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); // Henüz oluşturmadık ama sonraki adımda olacak
    exit();
}

// Veritabanı bağlantı dosyasını dahil et.
require_once 'includes/db.php';

// Hata mesajlarını saklamak için boş bir dizi
$errors = [];

// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al ve güvenlik için trim() fonksiyonu ile boşlukları temizle
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // ----- Veri Doğrulama (Validation) -----

    // E-posta boş mu?
    if (empty($email)) {
        $errors[] = "E-posta boş bırakılamaz.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Geçerli bir e-posta formatı mı?
        $errors[] = "Geçerli bir e-posta adresi giriniz.";
    }

    // Şifre boş mu?
    if (empty($password)) {
        $errors[] = "Şifre boş bırakılamaz.";
    }

    // ----- Giriş İşlemi -----

    // Eğer şu ana kadar hata yoksa, veritabanında kullanıcıyı ara
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Girilen şifreyi, veritabanındaki hashlenmiş şifre ile doğrula
            if (password_verify($password, $user['password'])) {
                // Şifre doğruysa, kullanıcı oturumunu başlat
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Kullanıcıyı dashboard sayfasına yönlendir
                header("Location: dashboard.php"); // Henüz oluşturmadık ama sonraki adımda olacak
                exit();
            } else {
                // Şifre yanlış
                $errors[] = "E-posta veya şifre hatalı.";
            }
        } else {
            // Kullanıcı bulunamadı (e-posta hatalı)
            $errors[] = "E-posta veya şifre hatalı.";
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
    <title>Giriş Yap - Aile Bütçesi Uygulaması</title>
    <!-- Bootstrap CSS v5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Kendi özel CSS dosyanız (stil.css) -->
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 450px;
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
        .text-center a {
            color: #007bff;
            text-decoration: none;
        }
        .text-center a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4 text-primary fw-bold">Giriş Yap</h2>

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
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" class="form-control rounded-3" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Şifre</label>
                <input type="password" class="form-control rounded-3" id="password" name="password" required>
            </div>
            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-primary">Giriş Yap</button>
            </div>
            <p class="text-center mt-3">Hesabın yok mu? <a href="register.php">Kayıt Ol</a></p>
        </form>
    </div>

    <!-- Bootstrap JS (Popper.js ile birlikte) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
