<?php
// Oturum başlatıyoruz, eğer daha önce başlatılmamışsa.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmamışsa login sayfasına yönlendiriyoruz.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısını dahil ediyoruz.
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$family_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$message = "";

// Aile ID'si geçerli değilse hata mesajı gösterip aileler sayfasına dönüyoruz.
if (!$family_id) {
    $message = "Geçersiz veya eksik aile ID'si belirtildi.";
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
    header("Location: my_families.php");
    exit();
}

// Kullanıcının bu aileye üye olup olmadığını ve rolünü kontrol ediyoruz.
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

// Eğer kullanıcı aile üyesi değilse, hata verip geri gönderiyoruz.
if (!$user_is_member) {
    $message = "Bu aileden ayrılmak için yetkiniz yok veya bu aileye üye değilsiniz.";
    $_SESSION['family_message'] = ['type' => 'danger', 'text' => $message];
    header("Location: my_families.php");
    exit();
}

// Eğer kullanıcı adminse ve ailede başka admin yoksa ekstra kontrol yapılabilir.
// Şimdilik sadece aileden çıkış işlemi yapıyoruz.

// Aileden ayrılma işlemini yapıyoruz: family_members tablosundan ilgili kaydı siliyoruz.
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

// İşlem sonrası aileler sayfasına yönlendiriyoruz.
header("Location: my_families.php");
exit();
?>
