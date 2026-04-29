-- Database: mini_kasir
CREATE DATABASE IF NOT EXISTS mini_kasir;
USE mini_kasir;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_barang VARCHAR(100) NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    stok INT NOT NULL DEFAULT 0,
    diskon_otomatis DECIMAL(5,2) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATETIME NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    bayar DECIMAL(10,2) NOT NULL,
    kembalian DECIMAL(10,2) NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Default users — password: "password"
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('kasir', '$2y$10$TKh8H1.PFbuSpgvguEHNsus8pGaXa24GRCTb.N0C59P3n5VGNaJXa', 'kasir');

-- Contoh data barang
INSERT INTO barang (nama_barang, harga, stok, diskon_otomatis) VALUES
('Kopi Arabika', 25000, 50, 0),
('Teh Hijau', 15000, 100, 10),
('Jus Jeruk', 20000, 30, 5);