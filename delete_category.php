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

$category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$message = "";
$error_type = 'danger'; // Varsayılan hata tipi

if (!$category_id) {
    $message = "Geçersiz veya eksik kategori ID'si belirtildi.";
} else {
    // Kategoriye ait işlem olup olmadığını kontrol et
    $stmt_check_transactions = $conn->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
    $stmt_check_transactions->bind_param("i", $category_id);
    $stmt_check_transactions->execute();
    $result_check_transactions = $stmt_check_transactions->get_result();
    $transaction_count = $result_check_transactions->fetch_row()[0];
    $stmt_check_transactions->close();

    if ($transaction_count > 0) {
        $message = "Bu kategoriye bağlı " . $transaction_count . " adet işlem bulunmaktadır. Kategori silinemez.";
    } else {
        // Kategoriye ait işlem yoksa silme işlemine devam et
        $stmt_delete_category = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt_delete_category->bind_param("i", $category_id);

        if ($stmt_delete_category->execute()) {
            $message = "Kategori başarıyla silindi.";
            $error_type = 'success'; // Başarı mesajı tipi
        } else {
            $message = "Kategori silinirken bir hata oluştu: " . $conn->error;
        }
        $stmt_delete_category->close();
    }
}

$conn->close();

// list_categories.php'ye mesajı taşı ve yönlendir
$_SESSION['category_message'] = ['type' => $error_type, 'text' => $message];
header("Location: list_categories.php");
exit();
?>
