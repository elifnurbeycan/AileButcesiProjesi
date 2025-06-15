<?php
// Oturum başlatılmamışsa başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Giriş yapılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısını al
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$family_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$message = "";

// Aile ID'si geçersizse kullanıcıyı bilgilendirip geri gönder
if (!$family_id) {
    $message = "Geçersiz veya eksik aile ID'si belirtildi.";
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
    header("Location: my_families.php");
    exit();
}

// Kullanıcının bu ailenin yöneticisi olup olmadığını kontrol et
$is_admin = false;
$stmt_check_admin = $conn->prepare("SELECT role FROM family_members WHERE family_id = ? AND user_id = ? AND role = 'admin'");
$stmt_check_admin->bind_param("ii", $family_id, $user_id);
$stmt_check_admin->execute();
$result_check_admin = $stmt_check_admin->get_result();

if ($result_check_admin->num_rows > 0) {
    $is_admin = true;
}
$stmt_check_admin->close();

// Yetkisi yoksa geri gönder
if (!$is_admin) {
    $message = "Bu aileyi silmek için yönetici yetkiniz yok.";
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
    header("Location: my_families.php");
    exit();
}

// Aileyi ve bağlı verileri silme işlemi
// NOT: Eğer veritabanında ON DELETE CASCADE varsa, sadece aileyi silmek yeterlidir
// Aksi durumda işlemler, üyeler ve aileyi ayrı ayrı silmek gerekir

$conn->begin_transaction(); // İşlem başlatılır
try {
    // Aileye bağlı işlemleri sil
    $stmt_delete_transactions = $conn->prepare("DELETE FROM transactions WHERE family_id = ?");
    $stmt_delete_transactions->bind_param("i", $family_id);
    $stmt_delete_transactions->execute();
    $stmt_delete_transactions->close();

    // Aile üyelerini sil
    $stmt_delete_members = $conn->prepare("DELETE FROM family_members WHERE family_id = ?");
    $stmt_delete_members->bind_param("i", $family_id);
    $stmt_delete_members->execute();
    $stmt_delete_members->close();

    // Ailenin kendisini sil
    $stmt_delete_family = $conn->prepare("DELETE FROM families WHERE id = ?");
    $stmt_delete_family->bind_param("i", $family_id);
    $stmt_delete_family->execute();
    $stmt_delete_family->close();

    // Tüm işlemleri onayla
    $conn->commit();
    $message = "Aile ve tüm ilişkili veriler başarıyla silindi.";
    $_SESSION['family_message'] = ['type' => 'success', 'text' => $message];

} catch (mysqli_sql_exception $e) {
    // Hata olursa işlemleri geri al
    $conn->rollback();
    $message = "Aileyi silerken bir hata oluştu: " . $e->getMessage();
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
}

$conn->close();

// Aileler sayfasına geri yönlendir
header("Location: my_families.php");
exit();
?>
