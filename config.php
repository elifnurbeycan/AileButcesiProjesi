<?php
/**
 * config.php
 * Veritabanı bağlantı bilgilerini ve diğer genel uygulama ayarlarını içerir.
 * Bu dosyadaki hassas bilgileri GitHub'a yüklerken sansürlemeyi unutmayın!
 */

// Veritabanı bağlantı sabitleri
define('DB_SERVER', 'localhost'); // Veritabanı sunucusu adresi (genellikle localhost)
define('DB_USERNAME', 'root');     // Veritabanı kullanıcı adı
define('DB_PASSWORD', '');         // Veritabanı parolası (XAMPP/WAMP'ta genellikle boş)
define('DB_NAME', 'family_budget_db'); // Veritabanı adı (daha önce oluşturduğumuz)

// Diğer uygulama ayarları (ileride eklenebilir)
// define('BASE_URL', 'http://localhost/aile_butcesi_projesi/');
?>
