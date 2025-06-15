<?php
session_start();
session_unset(); // Tüm session değişkenlerini temizler
session_destroy(); // Oturumu tamamen bitirir
header("Location: login.php"); // Giriş sayfasına yönlendir
exit();
?>
