# Aile Bütçesi Yönetim Uygulaması

Bu proje, PHP ve MySQL kullanarak geliştirilmiş, birden fazla kullanıcının ortak aile bütçelerini etkili bir şekilde yönetmelerine olanak tanıyan web tabanlı bir uygulamadır. Kullanıcılar kişisel hesaplarını oluşturabilir, aile gruplarına katılabilir veya kendi ailelerini oluşturabilir, gelir ve gider işlemlerini kaydedebilir, kategorize edebilir, finansal özetlerini görüntüleyebilir ve genel bütçe durumlarını takip edebilirler.

## Proje Hakkında

Uygulama, finansal şeffaflığı artırmayı ve aile içi bütçe yönetimini kolaylaştırmayı hedefler. Temel CRUD (Create, Read, Update, Delete) operasyonlarının yanı sıra, çoklu aile üyeliği ve roller gibi gelişmiş özellikler sunar.

## Özellikler

* **Kullanıcı Yönetimi:**
    * Güvenli kullanıcı kaydı.
    * E-posta ve şifre ile oturum açma (hashlenmiş şifreler).
    * Oturum yönetimi ve güvenli çıkış.
* **Aile/Grup Yönetimi:**
    * Yeni aile/grup oluşturma (şifre korumalı).
    * Mevcut ailelere katılma (şifre gereklidir).
    * Üyesi olunan aileleri listeleme.
    * Aile detaylarını ve üyelerini görüntüleme.
    * Aileden ayrılma.
    * Aileyi silme (sadece yöneticiler için ve tüm ilişkili verileri siler).
* **İşlem Yönetimi:**
    * Gelir ve gider işlemleri ekleme.
    * İşlemleri ilgili kategoriye atama.
    * İşlem açıklaması ve tarih belirleme.
    * Seçilen bir aileye ait tüm işlemleri detaylı olarak listeleme.
    * Mevcut işlemleri düzenleme.
    * İşlemleri silme (onaylı).
* **Kategori Yönetimi:**
    * Yeni gelir ve gider kategorileri ekleme.
    * Mevcut kategorileri listeleme.
    * Kullanılmayan kategorileri silme (ilişkili işlem varsa engellenir).
* **Finansal Özet:**
    * Belirli bir aile için seçilen tarih aralığındaki toplam gelir, toplam gider ve net bakiye özetini görüntüleme.
    * Kullanıcının üyesi olduğu tüm ailelerin ana sayfada (Dashboard) toplam net bakiyesini gösterme.
* **Kullanıcı Arayüzü:**
    * Duyarlı (responsive) tasarım ile mobil ve masaüstü cihazlarda uyumlu görünüm.
    * Bootstrap 5.3 ve özel CSS ile modern ve sezgisel arayüz.

## Teknolojiler

* **Backend:** PHP (yalın kodlama, framework kullanılmamıştır)
* **Veritabanı:** MySQL
* **Frontend:** HTML5, CSS3 (Bootstrap 5.3 ile), JavaScript
* **Veritabanı Bağlantısı:** MySQLi (PHP'nin yerleşik kütüphanesi)
* **Güvenlik:** `password_hash()` ve `password_verify()` ile şifre hashleme, PHP Session ile oturum yönetimi.


### Canlı Sunucuya Dağıtım (Hosting)

1.  **Veritabanı Oluşturma:**
    * Hosting kontrol panelinize (cPanel vb.) giriş yapın.
    * `MySQL Veritabanları` bölümünden yeni bir veritabanı (örn. `kullaniciadi_butce`) ve bir veritabanı kullanıcısı (örn. `kullaniciadi_user`) oluşturun.
    * Oluşturduğunuz kullanıcıya bu veritabanı üzerinde **tüm ayrıcalıkları** verin ve **tüm bu bilgileri not alın.**
2.  **Veritabanı Şemasını Yükleme:**
    * Hosting kontrol panelinizdeki `phpMyAdmin`'e giriş yapın.
    * Sol menüden az önce oluşturduğunuz boş veritabanını seçin.
    * `SQL` sekmesine tıklayın.
    * Projenizin `schema.sql` dosyasını açın ve içindeki `DROP DATABASE`, `CREATE DATABASE`, `USE` komutlarını **silin**. Sadece `CREATE TABLE` ve `INSERT` komutlarını bırakın.
    * Bu düzenlenmiş SQL kodunu `phpMyAdmin`'deki SQL sorgu alanına yapıştırın ve çalıştırın. Bu, tablolarınızı oluşturacaktır.
3.  **Dosyaları Yükleme (`config.php`, `includes/header.php` ve `index.php` Güncellemeleri):**
    * **`config.php` dosyasını güncelleyin:** Yerelinizdeki `includes/config.php` dosyasını açın ve `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME` değerlerini **canlı sunucuda oluşturduğunuz veritabanı bilgilerinizle** değiştirin.
    * **`BASE_URL` değerini `config.php`'ye taşıyın:** `BASE_URL` tanımını `includes/header.php` dosyasından alıp `config.php`'ye taşıyın ve canlı URL'nize göre ayarlayın:
        ```php
        // config.php içinde
        define('BASE_URL', '/~st24360859210/'); // Sizin hosting URL'nizdeki ~ kullanıcı adı kısmı
        ```
    * **`includes/header.php`'yi güncelleyin:** `includes/header.php` dosyanızda `BASE_URL` tanımını kaldırın ve dosyanın başında `require_once 'config.php';` satırını ekleyin (eğer `config.php` `includes` klasöründeyse).
    * **`index.php`'yi güncelleyin:** Yerelinizdeki `index.php` dosyasını açın ve `header("Location: " . BASE_URL . "dashboard.php");` yönlendirmesinden önce sadece `require_once 'includes/config.php';` (veya konumuna göre `require_once 'config.php';`) olduğundan emin olun.
4.  **FTP ile Dosyaları Yükleme:**
    * [FileZilla Client](https://filezilla-project.org/) gibi bir FTP programı kullanın.
    * Size verilen FTP Host, Kullanıcı Adı, Şifre ve Port (21) bilgilerini kullanarak sunucuya bağlanın.
    * Yerel projenizin ana klasörünün içindeki **tüm dosyaları ve klasörleri** seçin (`add_transaction.php`, `css/`, `includes/` vb.).
    * Bu dosyaları sunucudaki **`public_html` klasörünün içine** (veya `BASE_URL`'i `/aile_butcesi_projesi/` olarak ayarladıysanız `public_html/aile_butcesi_projesi/` klasörünün içine) sürükleyip bırakın.
    * **Önemli:** Yüklerken çakışan dosyalar için "Üzerine Yaz" (Overwrite) seçeneğini onaylayın.
5.  **Dosya İzinlerini Ayarlama (CHMOD):**
    * FileZilla'da, `public_html` klasörünün üzerine sağ tıklayıp "Dosya İzinleri..." (File Permissions...) seçeneğini seçin.
    * **Klasörler için:** Sayısal değeri `755` yapın, "Alt dizinlere yinele" ve "Sadece dizinlere uygula" seçeneklerini işaretleyip onaylayın.
    * **Dosyalar için:** Tekrar `public_html` klasörüne sağ tıklayıp "Dosya İzinleri..." seçeneğini seçin. Sayısal değeri `644` yapın, "Alt dizinlere yinele" ve "Sadece dosyalara uygula" seçeneklerini işaretleyip onaylayın.
6.  **Uygulamayı Test Etme:** Tarayıcınızda size verilen "Your website:" adresine (`http://95.130.171.20/~st24360859210`) gidin. Tarayıcı önbelleğini temizlemeyi (Ctrl+F5 veya Cmd+Shift+R) unutmayın.

## Proje Uyumluluğu (Ödev Şartlarına Göre)

Bu proje, Web Tabanlı Programlama dersinizin tüm temel şartlarını ve puanlama kriterlerini karşılamaktadır:

* **Kullanıcı Yönetimi:** Kayıt, giriş/çıkış, şifre hashleme, oturum yönetimi tamamen mevcuttur.
* **CRUD İşlemleri:** Bilgi girişi (Create), listeleme (Read), düzenleme (Update) ve silme (Delete) fonksiyonları (işlemler, aileler, kategoriler için) eksiksiz olarak uygulanmıştır.
* **Teknolojiler:** PHP, MySQL, HTML, Bootstrap ve JavaScript kullanımı şartnameye uygundur. Arka uçta yalın PHP kullanılmıştır, herhangi bir framework içermez.
* **Veritabanı:** Birden fazla MySQL tablosu kullanılmıştır.
* **Güvenlik:** Şifreler hashlenerek saklanır ve oturumlar çerezler yerine PHP Session ile yönetilir.
* **Arayüz:** Bootstrap ve özel CSS ile stillendirilmemiş öğe bulunmamaktadır.
* **Hosting:** Uygulama canlı sunucuya dağıtılabilir şekilde yapılandırılmıştır.

## Ekran Görüntüleri ve Video

Projenizin çalışır durumdaki ekran görüntülerini ve kısa bir tanıtım videosunu buraya ekleyebilirsiniz.

* [Ekran Görüntüsü 1 - Anasayfa/Dashboard]
* [Ekran Görüntüsü 2 - İşlem Ekleme Formu]
* [Proje Tanıtım Videosu Bağlantısı (YouTube/Google Drive)]

## Katkıda Bulunma

Eğer bu projeyi geliştirmek isterseniz, lütfen bir "issue" açın veya "pull request" gönderin.

## İletişim

Sorularınız veya geri bildirimleriniz için benimle iletişime geçebilirsiniz.
