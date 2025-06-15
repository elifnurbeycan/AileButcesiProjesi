<?php
// Oturum yoksa başlatılır
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Giriş yapılmamışsa login sayfasına gönder
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db.php';

// Header dahil edilir (sayfa başlığı ve body açılışı burada)
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$family_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$family_details = null;
$family_members = [];
$errors = [];
$user_role_in_family = null;

if (!$family_id) {
    $errors[] = "Geçersiz veya eksik aile ID'si.";
} else {
    // Aile bilgilerini al
    $stmt_family = $conn->prepare("SELECT id, name FROM families WHERE id = ?");
    $stmt_family->bind_param("i", $family_id);
    $stmt_family->execute();
    $result_family = $stmt_family->get_result();

    if ($result_family->num_rows === 1) {
        $family_details = $result_family->fetch_assoc();

        // Kullanıcının ailedeki rolünü kontrol et
        $stmt_check_member = $conn->prepare("SELECT role FROM family_members WHERE family_id = ? AND user_id = ?");
        $stmt_check_member->bind_param("ii", $family_id, $user_id);
        $stmt_check_member->execute();
        $result_check_member = $stmt_check_member->get_result();

        if ($result_check_member->num_rows === 0) {
            $errors[] = "Bu aileye erişim yetkiniz yok.";
            $family_details = null;
        } else {
            $user_role_in_family = $result_check_member->fetch_assoc()['role'];
            $stmt_check_member->close();

            // Aile üyelerini getir
            $stmt_members = $conn->prepare("SELECT fm.role, u.username FROM family_members fm JOIN users u ON fm.user_id = u.id WHERE fm.family_id = ? ORDER BY fm.role DESC, u.username ASC");
            $stmt_members->bind_param("i", $family_id);
            $stmt_members->execute();
            $result_members = $stmt_members->get_result();

            while ($row = $result_members->fetch_assoc()) {
                $family_members[] = $row;
            }
            $stmt_members->close();
        }
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
                    <p class="text-muted">Bu ailede henüz üye yok.</p>
                <?php endif; ?>
            </div>

            <div class="text-center mt-4 action-buttons">
                <a href="<?php echo BASE_URL; ?>list_transactions.php?family_id=<?php echo $family_details['id']; ?>" class="btn btn-info me-2 mb-2">İşlemleri Görüntüle</a>
                <a href="<?php echo BASE_URL; ?>financial_summary.php?family_id=<?php echo $family_details['id']; ?>" class="btn btn-success me-2 mb-2">Finansal Özeti Görüntüle</a>

                <?php if ($user_role_in_family === 'admin'): ?>
                    <button type="button" class="btn btn-danger me-2 mb-2" data-bs-toggle="modal" data-bs-target="#deleteFamilyModal">
                        Aileyi Sil
                    </button>
                <?php endif; ?>

                <?php if ($user_role_in_family === 'member' || $user_role_in_family === 'admin'): ?>
                    <button type="button" class="btn btn-warning me-2 mb-2" data-bs-toggle="modal" data-bs-target="#leaveFamilyModal">
                        Aileden Ayrıl
                    </button>
                <?php endif; ?>

                <a href="<?php echo BASE_URL; ?>my_families.php" class="btn btn-secondary mb-2">Ailelerime Geri Dön</a>
            </div>

            <!-- Silme onay modalı -->
            <div class="modal fade" id="deleteFamilyModal" tabindex="-1" aria-labelledby="deleteFamilyModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteFamilyModalLabel">Aileyi Silme Onayı</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                        </div>
                        <div class="modal-body">
                            Bu aileyi ve tüm ilişkili verileri kalıcı olarak silmek istediğinize emin misiniz? Bu işlem geri alınamaz.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <a href="<?php echo BASE_URL; ?>delete_family.php?id=<?php echo $family_details['id']; ?>" class="btn btn-danger">Evet, Sil</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ayrılma onay modalı -->
            <div class="modal fade" id="leaveFamilyModal" tabindex="-1" aria-labelledby="leaveFamilyModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="leaveFamilyModalLabel">Aileden Ayrılma Onayı</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                        </div>
                        <div class="modal-body">
                            Bu aileden ayrılmak istediğinize emin misiniz? Aileye tekrar katılmak için adını ve şifresini bilmeniz gerekecek.
                            <?php if ($user_role_in_family === 'admin'): ?>
                                <p class="text-danger mt-2">**Dikkat:** Yöneticisi olduğunuz bir aileden ayrılıyorsunuz. Başka yönetici yoksa aile yönetimsiz kalabilir.</p>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <a href="<?php echo BASE_URL; ?>leave_family.php?id=<?php echo $family_details['id']; ?>" class="btn btn-warning">Evet, Ayrıl</a>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-info text-center" role="alert">
                Görüntülenecek aile bilgisi bulunamadı. Lütfen geçerli bir aile seçin.
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>my_families.php" class="btn btn-primary">Ailelerime Geri Dön</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
