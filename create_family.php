<?php
// Oturumu başlatıyoruz, daha önce başlamadıysa.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmadıysa, login sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısını dahil ediyoruz.
require_once 'includes/db.php';

// Oturumdaki kullanıcı ID'sinin veritabanında geçerli olup olmadığını kontrol et.
$user_id_from_session = $_SESSION['user_id'];
$stmt_check_user = $conn->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt_check_user->bind_param("i", $user_id_from_session);
$stmt_check_user->execute();
$result_check_user = $stmt_check_user->get_result();

if ($result_check_user->num_rows === 0) {
    // Kullanıcı bulunamazsa oturumu temizleyip giriş sayfasına yönlendir.
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Kullanıcı veritabanında varsa, bilgilerini oturuma güncelle.
$current_user = $result_check_user->fetch_assoc();
$_SESSION['user_id'] = $current_user['id'];
$_SESSION['username'] = $current_user['username'];
$stmt_check_user->close();

// Header dosyasını ekliyoruz (burada <head> ve <body> açılır).
require_once 'includes/header.php';

// Hata ve başarı mesajlarını tutmak için boş diziler değişkenler oluşturuyoruz.
$errors = [];
$success_message = '';

// Form gönderilmiş mi kontrolü.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini alıyoruz, gereksiz boşlukları temizliyoruz.
    $family_name = trim($_POST['family_name']);
    $family_password = $_POST['family_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ----- Veri doğrulama -----

    // Aile adı boş mu?
    if (empty($family_name)) {
        $errors[] = "Aile adı boş bırakılamaz.";
    } elseif (strlen($family_name) < 3 || strlen($family_name) > 100) {
        $errors[] = "Aile adı 3 ile 100 karakter arasında olmalı.";
    } elseif (!preg_match("/^[a-zA-Z0-9\sçÇğĞıİöÖşŞüÜ]+$/u", $family_name)) {
        // Sadece harf, rakam ve boşluk olabilir (Türkçe karakter desteğiyle).
        $errors[] = "Aile adı sadece harf, rakam ve boşluk içerebilir.";
    }

    // Şifre boş mu, yeterince uzun mu?
    if (empty($family_password)) {
        $errors[] = "Aile şifresi boş bırakılamaz.";
    } elseif (strlen($family_password) < 6) {
        $errors[] = "Aile şifresi en az 6 karakter olmalı.";
    }

    // Şifreler eşleşiyor mu?
    if ($family_password !== $confirm_password) {
        $errors[] = "Şifreler eşleşmiyor.";
    }

    // ----- Veritabanı kontrolü -----

    // Eğer şimdilik hata yoksa, aynı isimde aile var mı kontrolü yap.
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM families WHERE name = ?");
        $stmt->bind_param("s", $family_name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Bu aile adı zaten kullanılıyor. Başka bir isim seçin.";
        }
        $stmt->close();
    }

    // ----- Aile oluşturma -----

    // Hatalar yoksa aileyi veritabanına ekle.
    if (empty($errors)) {
        $hashed_password = password_hash($family_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO families (name, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $family_name, $hashed_password);

        if ($stmt->execute()) {
            $new_family_id = $stmt->insert_id;

            // Aileyi oluşturan kullanıcıyı bu ailenin yöneticisi yap.
            $user_id = $_SESSION['user_id'];
            $stmt_member = $conn->prepare("INSERT INTO family_members (family_id, user_id, role) VALUES (?, ?, 'admin')");
            $stmt_member->bind_param("ii", $new_family_id, $user_id);

            if ($stmt_member->execute()) {
                $success_message = "Aile başarıyla oluşturuldu ve yönetici olarak eklendiniz!";
                // İstersen buradan aile listesine yönlendirebilirsin.
                // header("Location: my_families.php");
                // exit();
            } else {
                $errors[] = "Aile oluşturuldu ancak üyelik eklenirken hata oldu: " . $conn->error;
                // İstersen aileyi geri silebilirsin (rollback).
                // $conn->query("DELETE FROM families WHERE id = $new_family_id");
            }
            $stmt_member->close();
        } else {
            $errors[] = "Aile oluşturulurken hata oluştu: " . $conn->error;
        }
        $stmt->close();
    }
}

// Bağlantıyı kapatıyoruz.
$conn->close();
?>

<!-- HTML kısmı -->
<div class="container container-main">
    <div class="create-family-container">
        <h2 class="text-center mb-4 text-primary fw-bold">Yeni Aile Oluştur</h2>

        <?php
        // Hataları göster.
        if (!empty($errors)) {
            echo '<div class="alert alert-danger" role="alert"><ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
        }

        // Başarı mesajını göster.
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
            <div class="mb-3">
                <label for="family_password" class="form-label">Aile Şifresi</label>
                <input type="password" class="form-control rounded-3" id="family_password" name="family_password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Aile Şifresi (Tekrar)</label>
                <input type="password" class="form-control rounded-3" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Aile Oluştur</button>
            </div>
        </form>
        <p class="text-center mt-3">Zaten bir aileniz varsa <a href="join_family.php">Aileye Katıl</a> sayfasına gidin.</p>
    </div>
</div>

<?php
// Footer dosyasını dahil ediyoruz.
require_once 'includes/footer.php';
?>
