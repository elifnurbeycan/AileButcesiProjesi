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

// Kullanıcının bu aileye üye olup olmadığını ve rolünü kontrol et
$user_is_member = false;
$user_role = null;
$stmt_check_member = $conn->prepare("SELECT role FROM family_members WHERE family_id = ? AND user_id = ?");
$stmt_check_member->bind_param("ii", $family_id, $user_id);
$stmt_check_member->execute();
$result_check_member = $stmt_check_member->get_result();

if ($result_check_member->num_rows > 0) {
    $user_is_member = true;
    $user_role = $result_check_member->fetch_assoc()['role'];
}
$stmt_check_member->close();

if (!$user_is_member) {
    $message = "Bu aileden ayrılmak için yetkiniz yok veya bu aileye üye değilsiniz.";
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
    header("Location: my_families.php");
    exit();
}

// Eğer ayrılan kişi bir admin ise ve ailede başka admin yoksa özel kontrol (isteğe bağlı)
// Bu kısım daha ileri seviye iş mantığı için eklenebilir. Şimdilik sadece ayrılmasına izin veriyoruz.
// Örn: Eğer son admin ayrılıyorsa, ailenin yönetimsiz kalmaması için bir uyarı veya aile silme zorunluluğu gibi.
// Basitlik adına, mevcut durumda sadece aile üyesinin family_members tablosundan silinmesini sağlıyoruz.

// Aileden ayrılma işlemi: family_members tablosundan kaydı sil
$stmt_leave = $conn->prepare("DELETE FROM family_members WHERE family_id = ? AND user_id = ?");
$stmt_leave->bind_param("ii", $family_id, $user_id);

if ($stmt_leave->execute()) {
    $message = "Aileden başarıyla ayrıldınız.";
    $_SESSION['family_message'] = ['type' => 'success', 'text' => $message];
} else {
    $message = "Aileden ayrılırken bir hata oluştu: " . $conn->error;
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
}
$stmt_leave->close();

$conn->close();

// Kullanıcının aileler sayfasına geri yönlendir
header("Location: my_families.php");
exit();
?>
