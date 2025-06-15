-- Veritabanı adı: family_budget_db (isterseniz farklı bir isim kullanabilirsiniz)

-- Önceki sürümden kalan varsa veritabanını sil (SADECE GELİŞTİRME ORTAMINDA GÜVENLİDİR!)
DROP DATABASE IF EXISTS family_budget_db;

-- Veritabanını UTF-8 karakter seti ve Türkçe destekli harmanlama ile oluştur
CREATE DATABASE family_budget_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Oluşturduğumuz veritabanını kullan
USE family_budget_db;

-- Kullanıcılar tablosu: Uygulamaya giriş yapacak kullanıcı bilgilerini tutar.
-- password alanı, şifrenin hashlenmiş halini saklayacaktır.
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Şifrelerin hashlenmiş hali için yeterli uzunluk
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; -- Tablo için karakter seti ve harmanlama

-- Aileler/Gruplar tablosu: Ortak bütçeyi paylaşacak aileleri veya grupları temsil eder.
CREATE TABLE families (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,   -- Şifre sütunu eklendi (hashlenmiş şifre için)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aile Üyeleri tablosu: Hangi kullanıcının hangi aileye ait olduğunu belirten bir ara tablo (pivot table).
-- Bir kullanıcı birden fazla aileye ait olabilir veya bir aile birden fazla üyeye sahip olabilir.
CREATE TABLE family_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) DEFAULT 'member', -- Üye, yönetici gibi roller eklenebilir
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (family_id, user_id) -- Bir kullanıcının bir ailede sadece bir kez bulunmasını sağlar
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Harcama ve Gelir Kategorileri tablosu: İşlemleri sınıflandırmak için kullanılır.
-- Örnek kategoriler: Gıda, Ulaşım, Kira, Maaş, Ek Gelir vb.
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('income', 'expense') NOT NULL -- Gelir mi gider mi kategorisi olduğunu belirtir
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Finansal İşlemler tablosu: Gelir ve giderleri kaydeder.
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL, -- İşlemin hangi aileye ait olduğunu belirtir
    user_id INT NOT NULL, -- İşlemi yapan kullanıcıyı belirtir
    category_id INT NOT NULL, -- İşlemin kategorisini belirtir
    amount DECIMAL(10, 2) NOT NULL, -- İşlem miktarı
    type ENUM('income', 'expense') NOT NULL, -- İşlemin gelir mi gider mi olduğunu belirtir
    description TEXT, -- İşlem açıklaması
    transaction_date DATE NOT NULL, -- İşlemin gerçekleştiği tarih
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan kategorileri ekleme (İsteğe bağlı, uygulamanızdan da eklenebilir)
-- Bu satırlar, karakter seti düzeltildikten sonra doğru şekilde eklenecektir.
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

