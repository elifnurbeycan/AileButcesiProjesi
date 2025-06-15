<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Zaten giriş yapılmışsa dashboard'a yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'includes/db.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Girdi verilerini al ve temizle
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirm_password = $_POST["confirm_password"] ?? '';

    // Doğrulama
    if ($username === '') {
        $errors[] = "Kullanıcı adı boş bırakılamaz.";
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi giriniz.";
    }

    if ($password === '') {
        $errors[] = "Şifre boş bırakılamaz.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Şifre en az 6 karakter olmalıdır.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Şifreler eşleşmiyor.";
    }

    // Eğer doğrulama başarılıysa veritabanı işlemi
    if (empty($errors)) {
        // Aynı e-posta kullanılmış mı kontrol et
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Bu e-posta adresi zaten kullanılıyor.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            if ($stmt->execute()) {
                // Başarılı kayıt sonrası login sayfasına yönlendir
                header("Location: login.php?registered=1");
                exit();
            } else {
                $errors[] = "Kayıt sırasında bir hata oluştu. Lütfen tekrar deneyin.";
            }
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Kayıt Ol - Aile Bütçesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .register-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }
    </style>
</head>
<body>
<div class="register-container">
    <h2 class="text-center mb-4 text-primary fw-bold">Kayıt Ol</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="mb-3">
            <label for="username" class="form-label">Kullanıcı Adı</label>
            <input type="text" class="form-control" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-posta</label>
            <input type="email" class="form-control" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Şifre</label>
            <input type="password" class="form-control" id="password" name="password" required />
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Şifre (Tekrar)</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required />
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Kayıt Ol</button>
        </div>
        <p class="text-center mt-3">
            Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
        </p>
    </form>
</div>
</body>
</html>
