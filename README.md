# 🏠 Aile Bütçesi ve Ortak Harcama Yönetimi Uygulaması

Bu proje, **PHP ve MySQL** kullanılarak geliştirilmiş, birden fazla kullanıcının ortak aile bütçelerini etkili bir şekilde yönetmelerine olanak tanıyan **web tabanlı bir uygulamadır**. 

Kullanıcılar:
- Kişisel hesaplarını oluşturabilir
- Aile gruplarına katılabilir veya yeni aileler oluşturabilir
- Gelir ve gider işlemlerini kaydedebilir, kategorize edebilir
- Finansal özetlerini görüntüleyebilir
- Genel bütçe durumlarını takip edebilir

---

## 📌 Proje Hakkında

Uygulama, **finansal şeffaflığı artırmak** ve **aile içi bütçe yönetimini kolaylaştırmak** amacıyla tasarlanmıştır. Temel **CRUD (Create, Read, Update, Delete)** işlemlerinin yanı sıra çoklu kullanıcı desteği ve aile yönetimi gibi gelişmiş özellikler sunar.

🔗 **Canlı Uygulama:** [Aile Bütçesi Uygulaması](http://95.130.171.20/~st24360859210/dashboard.php)  
📺 **Tanıtım Videosu:** [Proje Tanıtım Videosunu İzle](https://youtu.be/A976tvMBdAg)

---

## 🚀 Özellikler

### 👤 Kullanıcı Yönetimi
- Güvenli kullanıcı kaydı
- Hashlenmiş şifre ile giriş (`password_hash`)
- Oturum yönetimi (PHP `session`)
- Güvenli çıkış işlemi

### 👪 Aile / Grup Yönetimi
- Yeni aile oluşturma (şifre korumalı)
- Mevcut aileye katılma (şifre ile)
- Aile üyelerini listeleme
- Aileden ayrılma
- Aileyi silme (yalnızca yöneticiler için, ilişkili verileri de siler)

### 💰 İşlem Yönetimi
- Gelir ve gider işlemleri ekleme
- Kategori ataması yapma
- Açıklama ve tarih ekleme
- Aileye göre işlemleri listeleme
- İşlem düzenleme ve silme (onaylı)

### 📂 Kategori Yönetimi
- Yeni gelir ve gider kategorileri ekleme
- Mevcut kategorileri listeleme
- Kullanılan kategorileri silememe (veri bütünlüğü için)

### 📊 Finansal Özet
- Tarih aralığına göre toplam gelir, gider ve net bakiye
- Ana sayfada tüm ailelerin özet bakiyesi

### 💻 Kullanıcı Arayüzü
- Responsive tasarım (mobil ve masaüstü uyumlu)
- Bootstrap 5.3 ve özel CSS ile modern arayüz

---

## 🧰 Kullanılan Teknolojiler

| Katman     | Teknoloji               |
|------------|--------------------------|
| Backend    | PHP (yalın)              |
| Veritabanı | MySQL                    |
| Frontend   | HTML5, CSS3, Bootstrap 5.3, JavaScript |
| Güvenlik   | `password_hash`, `session`, `password_verify` |
| DB Bağlantısı | MySQLi (PHP yerleşik)  |

---

## 🖼️ Ekran Görüntüleri

| Sayfa | Görsel |
|-------|--------|
| Giriş | ![Giriş Sayfası](images/web1.png) |
| Ailelerim | ![Kayıt Sayfası](images/web2.png) |
| Aile Detayları | ![Anasayfa](images/web3.png) |
| İşlem Ekle | ![İşlem Ekleme Formu](images/web4.png) |
| İşlem Listesi | ![İşlemleri Listeleme](images/web5.png) |
| Finansal Özet | ![Ailelerim Sayfası](images/web6.png) |
| Kategori Ekle | ![Aile Oluşturma Formu](images/web7.png) |
| Aileye Katıl | ![Aileye Katılma Formu](images/web8.png) |
| Aile Oluştur | ![Kategorileri Yönetme](images/web9.png) |

---

## 🤝 Katkıda Bulunma

Projeye katkı sağlamak isterseniz bir **issue** oluşturabilir veya **pull request** gönderebilirsiniz.

---

## 📬 İletişim

Görüş ve önerileriniz için benimle iletişime geçebilirsiniz.

