<?php
// Oturum başlatılmamışsa başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmamışsa login sayfasına gönder
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısını dahil et
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $family_name_to_join = trim($_POST['family_name_to_join']);
    $family_password = trim($_POST['family_password']); // Kullanıcının yazdığı şifre

    if (empty($family_name_to_join)) {
        $errors[] = "Katılmak istediğiniz aile adını boş bırakmayınız.";
    }

    if (empty($family_password)) {
        $errors[] = "Aile şifresini giriniz.";
    }

    if (empty($errors)) {
        // Girilen aile adı veritabanında var mı kontrol et
        $stmt_find_family = $conn->prepare("SELECT id, password FROM families WHERE name = ?");
        $stmt_find_family->bind_param("s", $family_name_to_join);
        $stmt_find_family->execute();
        $result_find_family = $stmt_find_family->get_result();

        if ($result_find_family->num_rows == 1) {
            $family = $result_find_family->fetch_assoc();
            $family_id_to_join = $family['id'];
            $stored_hashed_password = $family['password']; // Kayıtlı şifre (hash)

            // Şifre doğru mu kontrol et
            if (!password_verify($family_password, $stored_hashed_password)) {
                $errors[] = "Aile şifresi hatalı.";
            } else {
                // Kullanıcı zaten o ailede mi kontrol et
                $stmt_check_member = $conn->prepare("SELECT id FROM family_members WHERE family_id = ? AND user_id = ?");
                $stmt_check_member->bind_param("ii", $family_id_to_join, $user_id);
                $stmt_check_member->execute();
                $stmt_check_member->store_result();

                if ($stmt_check_member->num_rows > 0) {
                    $errors[] = "Zaten bu aileye üyesiniz.";
                } else {
                    // Üyeliği ekle
                    $stmt_add_member = $conn->prepare("INSERT INTO family_members (family_id, user_id, role) VALUES (?, ?, 'member')");
                    $stmt_add_member->bind_param("ii", $family_id_to_join, $user_id);
                    if ($stmt_add_member->execute()) {
                        $success_message = "Başarıyla '" . htmlspecialchars($family_name_to_join) . "' ailesine katıldınız!";
                    } else {
                        $errors[] = "Aileye katılırken bir hata oluştu: " . $conn->error;
                    }
                    $stmt_add_member->close();
                }
                $stmt_check_member->close();
            }
        } else {
            $errors[] = "Belirtilen aile adı bulunamadı.";
        }
        $stmt_find_family->close();
    }
}

$conn->close();

// Sayfanın üst kısmını ekle
require_once 'includes/header.php';
?>

<div class="container container-main">
    <div class="join-family-container">
        <h2 class="text-center mb-4 text-primary fw-bold">Mevcut Aileye Katıl</h2>

        <?php
        if (!empty($errors)) {
            echo '<div class="alert alert-danger"><ul>';
            foreach ($errors as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></div>';
        }

        if (!empty($success_message)) {
            echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="family_name_to_join" class="form-label">Katılmak İstediğiniz Aile Adı</label>
                <input type="text" class="form-control rounded-3" id="family_name_to_join" name="family_name_to_join" value="<?php echo htmlspecialchars($_POST['family_name_to_join'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="family_password" class="form-label">Aile Şifresi</label>
                <input type="password" class="form-control rounded-3" id="family_password" name="family_password" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Aileye Katıl</button>
            </div>
        </form>
        <p class="text-center mt-3">Yoksa yeni bir aile mi oluşturmak istersiniz? <a href="create_family.php">Aile Oluştur</a></p>
    </div>
</div>

<?php
// Sayfanın alt kısmını ekle
require_once 'includes/footer.php';
?>
