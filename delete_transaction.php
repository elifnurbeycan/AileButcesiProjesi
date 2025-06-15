<?php
// Oturum başlamamışsa başlatılır.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmamışsa login sayfasına yönlendirilir.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısı dahil edilir.
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
// İşlem ID'si GET ile alınır ve doğrulanır.
$transaction_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
// Silme onayı kontrol edilir ('confirm=yes' parametresi var mı).
$confirm_delete = filter_input(INPUT_GET, 'confirm', FILTER_SANITIZE_STRING) === 'yes';

$message = "";
$error = false;

// İşlem ID'si geçerli mi kontrolü
if (!$transaction_id) {
    $message = "Geçersiz işlem ID'si.";
    $error = true;
} else {
    // İşlem veritabanından çekilir (sahibi ve aile bilgisi ile).
    $stmt = $conn->prepare("SELECT t.user_id, t.family_id FROM transactions t WHERE t.id = ?");
    $stmt->bind_param("i", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction_data = $result->fetch_assoc();
    $stmt->close();

    if (!$transaction_data) {
        $message = "İşlem bulunamadı.";
        $error = true;
    } else {
        $transaction_owner_id = $transaction_data['user_id'];
        $transaction_family_id = $transaction_data['family_id'];

        // Kullanıcının, işlemin ait olduğu ailede üye olup olmadığı kontrol edilir.
        $stmt_member = $conn->prepare("SELECT COUNT(*) FROM family_members WHERE user_id = ? AND family_id = ?");
        $stmt_member->bind_param("ii", $user_id, $transaction_family_id);
        $stmt_member->execute();
        $result_member = $stmt_member->get_result();
        $is_member = $result_member->fetch_row()[0] > 0;
        $stmt_member->close();

        // Eğer kullanıcı aile üyesi değilse işlemi silemez.
        if (!$is_member) {
             $message = "Bu işlemi silmek için yetkiniz yok.";
             $error = true;
        } else {
            // Silme işlemi için onay alınmamışsa onay sayfası gösterilir.
            if (!$confirm_delete) {
                // Sayfa başlığı ve üst kısım dahil edilir.
                require_once 'includes/header.php';
                ?>
                <div class="container my-5">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="card p-4 shadow-sm rounded-3">
                                <h2 class="card-title text-center mb-4">İşlemi Silme Onayı</h2>
                                <p class="text-center">Bu işlemi kalıcı olarak silmek istediğinizden emin misiniz?</p>
                                <div class="d-flex justify-content-center gap-3 mt-4">
                                    <!-- Evet sil, veya iptal bağlantıları -->
                                    <a href="delete_transaction.php?id=<?php echo $transaction_id; ?>&confirm=yes" class="btn btn-danger">Evet, Sil</a>
                                    <a href="list_transactions.php?family_id=<?php echo $transaction_family_id; ?>" class="btn btn-secondary">İptal</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                // Sayfa alt kısmı dahil edilir.
                require_once 'includes/footer.php';
                exit(); // Onay sayfası gösterilip script sonlandırılır.
            } else {
                // Onay geldiyse silme işlemi gerçekleştirilir.
                $stmt_delete = $conn->prepare("DELETE FROM transactions WHERE id = ?");
                $stmt_delete->bind_param("i", $transaction_id);

                if ($stmt_delete->execute()) {
                    $message = "İşlem başarıyla silindi.";
                    // Silme mesajı session ile list sayfasına taşınır.
                    $_SESSION['delete_message'] = $message;
                    header("Location: list_transactions.php?family_id=" . $transaction_family_id);
                    exit();
                } else {
                    $message = "İşlem silinirken bir hata oluştu: " . $conn->error;
                    $error = true;
                }
                $stmt_delete->close();
            }
        }
    }
}

// Hata veya mesaj durumunda uygun sayfaya yönlendirme yapılır.
// Silme işlemi tamamlanmadıysa veya hata varsa mesaj session'a aktarılır.
$_SESSION['delete_message'] = $message;
// Eğer aile ID'si varsa o aileye yönlendir, yoksa genel liste sayfasına.
$redirect_family_id = isset($transaction_family_id) ? $transaction_family_id : '';
if (!empty($redirect_family_id)) {
    header("Location: list_transactions.php?family_id=" . $redirect_family_id);
} else {
    header("Location: list_transactions.php");
}
exit();

$conn->close();
?>
