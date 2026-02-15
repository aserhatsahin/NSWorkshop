-- Mevcut veritabanı silinir ve yeniden oluşturulur
DROP DATABASE IF EXISTS resim_atolyesi;
CREATE DATABASE resim_atolyesi;
USE resim_atolyesi;

-- Grup tablosu (her gün 3 grup)
CREATE TABLE course_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(50) NOT NULL, -- Grup 1, Grup 2, vs.
    course_day ENUM('Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar') NOT NULL
);

-- course_groups tablosu için grup verileri (her gün 3 grup)
INSERT INTO course_groups (group_name, course_day) VALUES
('Grup 1', 'Pazartesi'), ('Grup 2', 'Pazartesi'), ('Grup 3', 'Pazartesi'),
('Grup 1', 'Salı'),      ('Grup 2', 'Salı'),      ('Grup 3', 'Salı'),
('Grup 1', 'Çarşamba'),  ('Grup 2', 'Çarşamba'),  ('Grup 3', 'Çarşamba'),
('Grup 1', 'Perşembe'),  ('Grup 2', 'Perşembe'),  ('Grup 3', 'Perşembe'),
('Grup 1', 'Cuma'),      ('Grup 2', 'Cuma'),      ('Grup 3', 'Cuma'),
('Grup 1', 'Cumartesi'), ('Grup 2', 'Cumartesi'), ('Grup 3', 'Cumartesi'),
('Grup 1', 'Pazar'),     ('Grup 2', 'Pazar'),     ('Grup 3', 'Pazar');

-- Öğrenci tablosu (grup_id ile bağlanır)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    guardian_name VARCHAR(100),
    contact_info VARCHAR(100),
    course_start DATE,
    monthly_fee DECIMAL(10,2),
    total_debt DECIMAL(10,2) DEFAULT 0,
    group_id INT,
    is_active TINYINT(1) DEFAULT 1,
    active_since DATE DEFAULT NULL,
    FOREIGN KEY (group_id) REFERENCES course_groups(id)
);

-- Ürün tablosu
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50),
    price DECIMAL(10,2) NOT NULL
);

-- Öğrenci fotoğrafları tablosu
CREATE TABLE student_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Kurs Ücretleri Tablosu
CREATE TABLE course_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    date_start DATE NOT NULL,
    date_end DATE NOT NULL,
    status TINYINT(1) DEFAULT 0, -- 0: Ödenmedi, 1: Ödendi
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Satın Alınan Ürünler Tablosu
CREATE TABLE purchased_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    purchase_date DATE NOT NULL,
    status TINYINT(1) DEFAULT 0, -- 0: Borçlu, 1: Ödendi
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
