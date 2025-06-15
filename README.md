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

## 🗄️ Veritabanı Yapısı

Aşağıda, uygulamanın kullandığı MySQL veritabanı tabloları yer almaktadır. Bu yapılar, **phpMyAdmin** veya benzeri bir SQL aracı kullanılarak doğrudan çalıştırılabilir.

> **Not:** `CREATE DATABASE` komutu kaldırılmıştır. Script'i çalıştırmadan önce uygun bir veritabanı seçilmiş olmalıdır.

```sql
-- Kullanıcılar tablosu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aile/grup bilgileri
CREATE TABLE families (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aile üyelikleri (N:N ilişki)
CREATE TABLE family_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (family_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gelir ve gider kategorileri
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('income', 'expense') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- İşlem kayıtları
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan kategoriler
INSERT INTO categories (name, type) VALUES
('Maaş', 'income'),
('Ek Gelir', 'income'),
('Gıda', 'expense'),
('Ulaşım', 'expense'),
('Kira', 'expense'),
('Faturalar', 'expense'),
('Eğlence', 'expense'),
('Eğitim', 'expense'),
('Sağlık', 'expense'),
('Giyim', 'expense'),
('Diğer', 'expense');

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

