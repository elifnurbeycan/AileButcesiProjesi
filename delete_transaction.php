<?php
// Oturum başlatılır.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmamışsa, giriş sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantı dosyasını dahil et.
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$transaction_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$confirm_delete = filter_input(INPUT_GET, 'confirm', FILTER_SANITIZE_STRING) === 'yes';

$message = "";
$error = false;

// İşlem ID'si geçerli mi kontrol et
if (!$transaction_id) {
    $message = "Geçersiz işlem ID'si.";
    $error = true;
} else {
    // İşlemin detaylarını ve ait olduğu aileyi kontrol et
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

        // Kullanıcının, işlemi silecek aileye üye olup olmadığını kontrol et
        $stmt_member = $conn->prepare("SELECT COUNT(*) FROM family_members WHERE user_id = ? AND family_id = ?");
        $stmt_member->bind_param("ii", $user_id, $transaction_family_id);
        $stmt_member->execute();
        $result_member = $stmt_member->get_result();
        $is_member = $result_member->fetch_row()[0] > 0;
        $stmt_member->close();

        // Eğer kullanıcı işlemin sahibi değilse VE aile üyesi değilse (veya başka bir yetkilendirme kuralı)
        // Burada basitçe kullanıcının işlemin sahibi olmasını VEYA ailesinin üyesi olmasını kontrol ediyoruz.
        // Daha katı bir kural istenirse, sadece işlemin sahibi silebilir gibi ayarlanabilir.
        if (!$is_member) { // Aile üyesi değilse, işlemi silemez
             $message = "Bu işlemi silmek için yetkiniz yok.";
             $error = true;
        } else {
            // Onay bekleniyor mu?
            if (!$confirm_delete) {
                // Silme onayı için bir HTML sayfası göster.
                ?>
                <!DOCTYPE html>
                <html lang="tr">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>İşlemi Silme Onayı</title>
                    <!-- Bootstrap CSS v5.3 -->
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
                    <!-- Kendi özel CSS dosyanız -->
                    <link href="css/style.css" rel="stylesheet">
                </head>
                <body class="d-flex flex-column min-vh-100">
                    <?php include 'includes/header.php'; // Navbar ?>
                    <div class="container my-5">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card p-4 shadow-sm rounded-3">
                                    <h2 class="card-title text-center mb-4">İşlemi Silme Onayı</h2>
                                    <p class="text-center">Bu işlemi kalıcı olarak silmek istediğinizden emin misiniz?</p>
                                    <div class="d-flex justify-content-center gap-3 mt-4">
                                        <a href="delete_transaction.php?id=<?php echo $transaction_id; ?>&confirm=yes" class="btn btn-danger">Evet, Sil</a>
                                        <a href="list_transactions.php?family_id=<?php echo $transaction_family_id; ?>" class="btn btn-secondary">İptal</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php include 'includes/footer.php'; // Footer ?>
                    <!-- Bootstrap JS v5.3 -->
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
                </body>
                </html>
                <?php
                exit(); // Onay sayfasını gösterdikten sonra scripti durdur.
            } else {
                // Silme işlemi
                $stmt_delete = $conn->prepare("DELETE FROM transactions WHERE id = ?");
                $stmt_delete->bind_param("i", $transaction_id);

                if ($stmt_delete->execute()) {
                    $message = "İşlem başarıyla silindi.";
                    $_SESSION['delete_message'] = $message; // list_transactions.php'ye mesajı taşı
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

// Hata durumunda veya mesaj göstermek için geri yönlendirme yap.
// Eğer buraya kadar geldiyse ve yönlendirme yapılmadıysa, bir hata oluşmuştur.
$_SESSION['delete_message'] = $message; // list_transactions.php'ye mesajı taşı
// Hata durumunda aynı aile ID'si ile listeleme sayfasına yönlendir
$redirect_family_id = isset($transaction_family_id) ? $transaction_family_id : ($selected_family_id ?? '');
header("Location: list_transactions.php" . ($redirect_family_id ? "?family_id=" . $redirect_family_id : ""));
exit();

$conn->close();
?>
