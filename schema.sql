-- Bu SQL dosyası, bir MySQL veritabanında Aile Bütçesi Uygulaması için gerekli tabloları oluşturur
-- ve varsayılan bazı kategori verilerini ekler.
-- Canlı sunucu ortamında kullanılmak üzere DROP DATABASE, CREATE DATABASE ve USE komutları kaldırılmıştır.
-- Bu script'i çalıştırmadan önce ilgili veritabanının phpMyAdmin üzerinden seçili olduğundan emin olun.

-- Uygulama kullanıcı bilgileri
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Hash'lenmiş şifre saklanır
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ortak bütçe kullanan aile/grup bilgileri
CREATE TABLE families (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Hash'lenmiş giriş şifresi
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aile ile kullanıcı ilişkisi (bir kullanıcı birden fazla ailede olabilir)
CREATE TABLE family_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) DEFAULT 'member', -- 'admin' veya 'member'
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (family_id, user_id) -- Bir kullanıcı bir aileye sadece bir kez üye olabilir
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Harcama ve gelir kategorileri
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('income', 'expense') NOT NULL -- 'income' (gelir) veya 'expense' (gider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gelir ve gider işlemleri
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    family_id INT NOT NULL,
    user_id INT NOT NULL, -- İşlemi yapan kullanıcının ID'si
    category_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Güncelleme tarihi otomatik değişsin
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan kategoriler (gerekirse uygulama üzerinden de eklenebilir)
-- Bu verilerin eklenmesi için tablonun boş olması veya UNIQUE kısıtlamalarına uyması gerekir.
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
