<?php
// Bu dosya, ana dizine erişildiğinde kullanıcıyı dashboard.php'ye yönlendirir.

// Oturum başlatılmamışsa başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// BASE_URL sabitini almak için sadece config.php'yi dahil et.
// config.php, herhangi bir HTML çıktısı olmadan sadece PHP sabitlerini tanımlar.
// config.php'nin 'includes' klasörünün içinde olduğunu varsayıyoruz.
// Eğer config.php ana dizinde ise (public_html'in altında), bu satırı sadece 'require_once 'config.php';' olarak değiştirin.
require_once 'includes/config.php';

// Kullanıcıyı dashboard.php sayfasına yönlendir
// BASE_URL sabiti artık config.php'den geliyor ve doğru yolu içeriyor.
header("Location: " . BASE_URL . "dashboard.php");
exit(); // Yönlendirme yapıldıktan sonra scriptin çalışmasını durdur
?>
