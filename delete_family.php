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
$family_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$message = "";

if (!$family_id) {
    $message = "Geçersiz veya eksik aile ID'si belirtildi.";
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
    header("Location: my_families.php");
    exit();
}

// Kullanıcının bu ailenin yöneticisi (admin) olup olmadığını kontrol et
$is_admin = false;
$stmt_check_admin = $conn->prepare("SELECT role FROM family_members WHERE family_id = ? AND user_id = ? AND role = 'admin'");
$stmt_check_admin->bind_param("ii", $family_id, $user_id);
$stmt_check_admin->execute();
$result_check_admin = $stmt_check_admin->get_result();

if ($result_check_admin->num_rows > 0) {
    $is_admin = true;
}
$stmt_check_admin->close();

if (!$is_admin) {
    $message = "Bu aileyi silmek için yönetici yetkiniz yok.";
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
    header("Location: my_families.php");
    exit();
}

// Aileyi silme işlemi: Önce ilişkili verileri sil (transactions, family_members), sonra aileyi sil
// ÖNEMLİ NOT: Eğer veritabanı tablolarınızda FOREIGN KEY constraint'leri ON DELETE CASCADE olarak ayarlıysa,
// sadece `families` tablosundan silmek, diğer ilişkili kayıtları otomatik olarak silecektir.
// Güvenli olması için, manuel silme adımlarını burada belirtiyorum. Eğer CASCADE ayarlıysa,
// bu DELETE sorgularına gerek kalmaz, sadece families tablosunu silmek yeterli olur.

$conn->begin_transaction(); // İşlemi başlat (rollback yapabilmek için)
try {
    // 1. İlişkili işlemleri sil
    $stmt_delete_transactions = $conn->prepare("DELETE FROM transactions WHERE family_id = ?");
    $stmt_delete_transactions->bind_param("i", $family_id);
    $stmt_delete_transactions->execute();
    $stmt_delete_transactions->close();

    // 2. İlişkili aile üyelerini sil
    $stmt_delete_members = $conn->prepare("DELETE FROM family_members WHERE family_id = ?");
    $stmt_delete_members->bind_param("i", $family_id);
    $stmt_delete_members->execute();
    $stmt_delete_members->close();

    // 3. Aileyi sil
    $stmt_delete_family = $conn->prepare("DELETE FROM families WHERE id = ?");
    $stmt_delete_family->bind_param("i", $family_id);
    $stmt_delete_family->execute();
    $stmt_delete_family->close();

    $conn->commit(); // İşlemleri onayla
    $message = "Aile ve tüm ilişkili veriler başarıyla silindi.";
    $_SESSION['family_message'] = ['type' => 'success', 'text' => $message];

} catch (mysqli_sql_exception $e) {
    $conn->rollback(); // Hata olursa işlemleri geri al
    $message = "Aileyi silerken bir hata oluştu: " . $e->getMessage();
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
}

$conn->close();

// Kullanıcının aileler sayfasına geri yönlendir
header("Location: my_families.php");
exit();
?>
