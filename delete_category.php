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

// URL'den kategori ID'si alınır ve doğrulanır.
$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$message = "";
$error_type = 'danger'; // Varsayılan mesaj türü (hata)

// Geçerli bir kategori ID'si olup olmadığı kontrol edilir.
if (!$category_id) {
    $message = "Geçersiz veya eksik kategori ID'si belirtildi.";
} else {
    // Kategoriye bağlı işlem var mı kontrol edilir.
    $stmt_check_transactions = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
    $stmt_check_transactions->bind_param("i", $category_id);
    $stmt_check_transactions->execute();
    $result_check_transactions = $stmt_check_transactions->get_result();
    $transaction_count = $result_check_transactions->fetch_row()[0];
    $stmt_check_transactions->close();

    if ($transaction_count > 0) {
        // Kategoriye bağlı işlem varsa silme işlemi yapılmaz, kullanıcı bilgilendirilir.
        $message = "Bu kategoriye bağlı " . $transaction_count . " adet işlem bulunmaktadır. Kategori silinemez.";
    } else {
        // Kategoriye bağlı işlem yoksa, silme işlemi gerçekleştirilir.
        $stmt_delete_category = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt_delete_category->bind_param("i", $category_id);

        if ($stmt_delete_category->execute()) {
            // Silme başarılıysa kullanıcıya başarı mesajı gösterilir.
            $message = "Kategori başarıyla silindi.";
            $error_type = 'success'; // Başarı mesajı türü
        } else {
            // Silme sırasında hata oluşursa hata mesajı gösterilir.
            $message = "Kategori silinirken bir hata oluştu: " . $conn->error;
        }
        $stmt_delete_category->close();
    }
}

// Veritabanı bağlantısı kapatılır.
$conn->close();

// Mesaj oturuma kaydedilir ve kategori listeleme sayfasına yönlendirilir.
$_SESSION['category_message'] = ['type' => $error_type, 'text' => $message];
header("Location: list_categories.php");
exit();
?>
