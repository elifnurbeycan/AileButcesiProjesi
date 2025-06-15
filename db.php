<?php
// includes/db.php
// Veritabanı bağlantısı için dosya

require_once 'config.php'; // config.php'de DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME tanımlı olmalı

// MySQLi bağlantısı oluştur
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Bağlantı kontrolü
if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Karakter seti UTF-8 olarak ayarla (Türkçe karakterler için önemli)
$conn->set_charset("utf8mb4");
?>
