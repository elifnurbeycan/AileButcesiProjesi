<?php
// Oturum kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Zaten giriş yapılmışsa yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'includes/db.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirm_password = $_POST["confirm_password"] ?? '';

    // Alan kontrolleri
    if ($username === '') {
        $errors[] = "Kullanıcı adı boş bırakılamaz.";
    }

    if ($email === '') {
        $errors[] = "E-posta boş bırakılamaz.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

    // Veritabanı işlemleri
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Bu e-posta adresi zaten kullanılıyor.";
        } else {
            $stmt->close();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
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

// Başlık
$page_title = "Kayıt Ol";
require_once 'includes/header.php';
?>

<div class="register-container">
    <h2 class="text-center mb-4 text-primary fw-bold">Kayıt Ol</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="mb-3">
            <label for="username" class="form-label">Kullanıcı Adı</label>
            <input type="text" class="form-control rounded-3" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">E-posta</label>
            <input type="email" class="form-control rounded-3" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Şifre</label>
            <input type="password" class="form-control rounded-3" id="password" name="password" required />
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Şifre (Tekrar)</label>
            <input type="password" class="form-control rounded-3" id="confirm_password" name="confirm_password" required />
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Kayıt Ol</button>
        </div>
        <p class="text-center mt-3">
            Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
        </p>
    </form>
</div>

<?php
require_once 'includes/footer.php';
?>
