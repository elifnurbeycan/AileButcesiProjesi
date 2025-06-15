<?php
// Oturum kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Giriş yapmış kullanıcıyı doğrudan dashboard'a yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Veritabanı bağlantısı
require_once 'includes/db.php';

$errors = [];

// Form gönderildiyse işle
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Alan kontrolü
    if (empty($email)) {
        $errors[] = "E-posta boş bırakılamaz.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Geçerli bir e-posta adresi giriniz.";
    }

    if (empty($password)) {
        $errors[] = "Şifre boş bırakılamaz.";
    }

    // Kullanıcı bilgisi kontrolü
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Şifre doğrulama
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "E-posta veya şifre hatalı.";
            }
        } else {
            $errors[] = "E-posta veya şifre hatalı.";
        }

        $stmt->close();
    }

    // Bağlantıyı kapat
    $conn->close();
}

// Sayfa başlığı
$page_title = "Giriş Yap";

// Üst bilgi bileşeni
require_once 'includes/header.php';
?>

<div class="login-container">
    <h2 class="text-center mb-4 text-primary fw-bold">Giriş Yap</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="mb-3">
            <label for="email" class="form-label">E-posta</label>
            <input type="email" class="form-control rounded-3" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
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

<?php
// Alt bilgi bileşeni
require_once 'includes/footer.php';
?>
