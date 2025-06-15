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

// Header dosyasını dahil et.
// Header.php içerisinde <head> ve <body> etiketlerinin açıldığını varsayıyoruz.
require_once 'includes/header.php';


$user_id = $_SESSION['user_id'];
$family_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); // URL'den aile ID'sini al
$family_details = null;
$family_members = [];
$errors = [];

// Aile ID'si geçerli mi kontrol et
if (!$family_id) {
    $errors[] = "Geçersiz veya eksik aile ID'si belirtildi.";
} else {
    // Aile detaylarını veritabanından çek
    $stmt_family = $conn->prepare("SELECT id, name FROM families WHERE id = ?");
    $stmt_family->bind_param("i", $family_id);
    $stmt_family->execute();
    $result_family = $stmt_family->get_result();

    if ($result_family->num_rows === 1) {
        $family_details = $result_family->fetch_assoc();

        // Kullanıcının bu aileye üye olup olmadığını kontrol et (yetkilendirme)
        $stmt_check_member = $conn->prepare("SELECT role FROM family_members WHERE family_id = ? AND user_id = ?");
        $stmt_check_member->bind_param("ii", $family_id, $user_id);
        $stmt_check_member->execute();
        $result_check_member = $stmt_check_member->get_result();

        if ($result_check_member->num_rows === 0) {
            // Kullanıcı bu aileye üye değilse erişim yetkisi yok
            $errors[] = "Bu aileye erişim yetkiniz yok.";
            $family_details = null; // Yetkisiz erişimde detayları sıfırla
        } else {
            // Kullanıcı aileye üye ise aile üyelerini çek
            $stmt_members = $conn->prepare("SELECT fm.role, u.username FROM family_members fm JOIN users u ON fm.user_id = u.id WHERE fm.family_id = ? ORDER BY fm.role DESC, u.username ASC");
            $stmt_members->bind_param("i", $family_id);
            $stmt_members->execute();
            $result_members = $stmt_members->get_result();
            while ($row = $result_members->fetch_assoc()) {
                $family_members[] = $row;
            }
            $stmt_members->close();

            // İsteğe bağlı: Ailenin finansal özetini de burada çekebilirsiniz
            // Örneğin: Toplam gelir, toplam gider vb.
        }
        $stmt_check_member->close();
    } else {
        $errors[] = "Aile bulunamadı.";
    }
    $stmt_family->close();
}

$conn->close();
?>

<div class="container container-main">
    <div class="family-details-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>my_families.php" class="btn btn-secondary">Ailelerime Geri Dön</a>
                </div>
            </div>
        <?php elseif ($family_details): ?>
            <h2 class="text-center mb-4 text-primary fw-bold"><?php echo htmlspecialchars($family_details['name']); ?> Ailesi Detayları</h2>

            <div class="card p-4 mb-4 shadow-sm rounded-3">
                <h4 class="mb-3">Aile Üyeleri</h4>
                <?php if (!empty($family_members)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($family_members as $member): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($member['username']); ?>
                                <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars(ucfirst($member['role'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">Bu ailede henüz üye bulunmamaktadır.</p>
                <?php endif; ?>
            </div>

            <div class="text-center mt-4">
                <a href="<?php echo BASE_URL; ?>list_transactions.php?family_id=<?php echo $family_details['id']; ?>" class="btn btn-info me-2">İşlemleri Görüntüle</a>
                <a href="<?php echo BASE_URL; ?>financial_summary.php?family_id=<?php echo $family_details['id']; ?>" class="btn btn-success me-2">Finansal Özeti Görüntüle</a>
                <a href="<?php echo BASE_URL; ?>my_families.php" class="btn btn-secondary">Ailelerime Geri Dön</a>
            </div>

        <?php else: ?>
            <div class="alert alert-info text-center" role="alert">
                Görüntülenecek aile bilgisi bulunamadı. Lütfen geçerli bir aile seçtiğinizden emin olun.
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>my_families.php" class="btn btn-primary">Ailelerime Geri Dön</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Footer dosyasını dahil et
require_once 'includes/footer.php';
?>
