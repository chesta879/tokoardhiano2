-- =====================================================================
-- Database Toko Kita (db_toko) - skema lengkap, siap diimpor ke MySQL/MariaDB
-- Kompatibel dengan kode PHP toko-v2 (nama tabel/kolom sama dengan config.php)
-- =====================================================================
CREATE DATABASE IF NOT EXISTS db_toko CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_toko;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- MASTER ----------
CREATE TABLE tbl_kategori (
  id_kategori   INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE tbl_cabang (
  id_cabang   INT AUTO_INCREMENT PRIMARY KEY,
  nama_cabang VARCHAR(100) NOT NULL,
  kota        VARCHAR(50)  NOT NULL
) ENGINE=InnoDB;

CREATE TABLE tbl_produk (
  id_produk   INT AUTO_INCREMENT PRIMARY KEY,
  nama_produk VARCHAR(100) NOT NULL,
  id_kategori INT NOT NULL,
  harga       DECIMAL(12,2) NOT NULL CHECK (harga >= 0),
  stok        INT NOT NULL DEFAULT 0 CHECK (stok >= 0),
  deskripsi   TEXT NULL,
  gambar      VARCHAR(500) NULL,
  KEY idx_produk_kategori (id_kategori),
  CONSTRAINT fk_produk_kategori FOREIGN KEY (id_kategori) REFERENCES tbl_kategori(id_kategori)
) ENGINE=InnoDB;

-- ---------- PELANGGAN & AKUN ----------
CREATE TABLE tbl_pelanggan (
  id_pelanggan   INT AUTO_INCREMENT PRIMARY KEY,
  nama_pelanggan VARCHAR(100) NOT NULL,
  kota           VARCHAR(50)  NOT NULL,
  KEY idx_pelanggan_kota (kota)
) ENGINE=InnoDB;

CREATE TABLE tbl_akun_pelanggan (          -- login pelanggan (storefront)
  id_akun       INT AUTO_INCREMENT PRIMARY KEY,
  id_pelanggan  INT NOT NULL UNIQUE,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  dibuat_pada   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_akun_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES tbl_pelanggan(id_pelanggan) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_pengguna (                -- staf: admin & kasir
  id_pengguna   INT AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
  aktif         TINYINT(1) NOT NULL DEFAULT 1,
  dibuat_pada   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- PESANAN & TRANSAKSI ----------
CREATE TABLE tbl_pesanan (                 -- 1 checkout = 1 pesanan (banyak produk)
  kode_pesanan      VARCHAR(32) PRIMARY KEY,
  id_pelanggan      INT NOT NULL,
  alamat_pengiriman TEXT NOT NULL,
  metode_pembayaran VARCHAR(40) NOT NULL,  -- Transfer bank | COD | E-wallet
  status_pembayaran VARCHAR(30) NOT NULL DEFAULT 'Belum dibayar',
  bukti_pembayaran  VARCHAR(40) NULL,
  status            VARCHAR(30) NOT NULL DEFAULT 'Diproses', -- Diproses|Dikirim|Selesai|Dibatalkan
  kanal             VARCHAR(10) NOT NULL DEFAULT 'online',   -- online | kasir
  id_kasir          INT NULL,
  bayar             DECIMAL(12,2) NULL,
  kembalian         DECIMAL(12,2) NULL,
  dibuat_pada       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pesanan_pelanggan (id_pelanggan), KEY idx_pesanan_status (status),
  CONSTRAINT fk_pesanan_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES tbl_pelanggan(id_pelanggan),
  CONSTRAINT fk_pesanan_kasir FOREIGN KEY (id_kasir) REFERENCES tbl_pengguna(id_pengguna) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE tbl_transaksi (               -- 1 baris per produk dalam pesanan
  id_transaksi  INT AUTO_INCREMENT PRIMARY KEY,
  tanggal       DATE NOT NULL,
  id_pelanggan  INT NOT NULL,
  total_belanja DECIMAL(12,2) NOT NULL DEFAULT 0,
  kode_pesanan  VARCHAR(32) NULL,          -- NULL = transaksi kasir manual (admin)
  KEY idx_transaksi_tanggal (tanggal), KEY idx_transaksi_pelanggan (id_pelanggan), KEY idx_transaksi_pesanan (kode_pesanan),
  CONSTRAINT fk_transaksi_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES tbl_pelanggan(id_pelanggan),
  CONSTRAINT fk_transaksi_pesanan   FOREIGN KEY (kode_pesanan) REFERENCES tbl_pesanan(kode_pesanan)
) ENGINE=InnoDB;

CREATE TABLE tbl_detail_transaksi (
  id_detail    INT AUTO_INCREMENT PRIMARY KEY,
  id_transaksi INT NOT NULL,
  id_produk    INT NOT NULL,
  jumlah       INT NOT NULL CHECK (jumlah > 0),
  harga        DECIMAL(12,2) NOT NULL,     -- harga saat transaksi (snapshot)
  KEY idx_detail_transaksi (id_transaksi), KEY idx_detail_produk (id_produk),
  CONSTRAINT fk_detail_transaksi FOREIGN KEY (id_transaksi) REFERENCES tbl_transaksi(id_transaksi),
  CONSTRAINT fk_detail_produk    FOREIGN KEY (id_produk)    REFERENCES tbl_produk(id_produk)
) ENGINE=InnoDB;

-- ---------- LOG ----------
CREATE TABLE tbl_log (
  id_log INT AUTO_INCREMENT PRIMARY KEY, nama_tabel VARCHAR(50), aktivitas VARCHAR(50),
  id_data INT, keterangan VARCHAR(255), waktu TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE log_pelanggan (
  id_log INT AUTO_INCREMENT PRIMARY KEY, aktivitas VARCHAR(50), id_pelanggan INT,
  waktu TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE log_perubahan_pelanggan (
  id_log INT AUTO_INCREMENT PRIMARY KEY, id_pelanggan INT, nama_lama VARCHAR(100), nama_baru VARCHAR(100),
  waktu TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------- DATA AWAL ----------
INSERT INTO tbl_kategori (nama_kategori) VALUES ('Sembako'),('Minuman'),('Kebersihan');
INSERT INTO tbl_pelanggan (nama_pelanggan,kota) VALUES ('Pelanggan Umum','-');   -- untuk penjualan kasir tanpa akun
INSERT INTO tbl_cabang (nama_cabang, kota) VALUES ('Cabang Utara','Ponorogo'),('Cabang Timur','Madiun'),('Cabang Barat','Magetan');
INSERT INTO tbl_produk (nama_produk,id_kategori,harga,stok) VALUES
 ('Beras 5 kg',1,65000,100),('Minyak Goreng 2 L',1,38000,100),('Gula 1 kg',1,17000,100),
 ('Kopi Sachet (box)',2,25000,100),('Teh Celup (box)',2,15000,100),('Sabun Mandi',3,12000,100),
 ('Deterjen 1 kg',3,28000,100),('Mie Instan (dus)',1,110000,100);

-- ---------- VIEW ----------
CREATE VIEW view_total_belanja AS
 SELECT p.id_pelanggan, p.nama_pelanggan, COUNT(t.id_transaksi) AS total_transaksi, COALESCE(SUM(t.total_belanja),0) AS total_belanja
 FROM tbl_pelanggan p LEFT JOIN tbl_transaksi t ON p.id_pelanggan=t.id_pelanggan GROUP BY p.id_pelanggan, p.nama_pelanggan;
CREATE VIEW view_transaksi_pelanggan AS
 SELECT t.id_transaksi, t.tanggal, p.nama_pelanggan, p.kota, t.total_belanja
 FROM tbl_transaksi t JOIN tbl_pelanggan p ON t.id_pelanggan=p.id_pelanggan;

-- ---------- FUNGSI, PROSEDUR, TRIGGER ----------
DELIMITER $$
CREATE FUNCTION HitungTotal(harga DECIMAL(12,2), jumlah INT) RETURNS DECIMAL(12,2) DETERMINISTIC NO SQL
BEGIN RETURN harga * jumlah; END$$
CREATE FUNCTION HitungPajak(harga DECIMAL(12,2)) RETURNS DECIMAL(12,2) DETERMINISTIC NO SQL
BEGIN RETURN harga * 0.11; END$$
CREATE FUNCTION KategoriBelanja(total DECIMAL(12,2)) RETURNS VARCHAR(30) DETERMINISTIC NO SQL
BEGIN RETURN CASE WHEN total >= 1000000 THEN 'Pelanggan Gold' WHEN total >= 500000 THEN 'Pelanggan Silver' ELSE 'Pelanggan Reguler' END; END$$

CREATE PROCEDURE TambahPelanggan(IN p_nama VARCHAR(100), IN p_kota VARCHAR(50))
BEGIN INSERT INTO tbl_pelanggan(nama_pelanggan,kota) VALUES(p_nama,p_kota); END$$
CREATE PROCEDURE CariPelanggan(IN p_id INT)
BEGIN SELECT * FROM tbl_pelanggan WHERE id_pelanggan=p_id; END$$
CREATE PROCEDURE CekTransaksi(IN p_id INT)
BEGIN
  IF (SELECT COUNT(*) FROM tbl_transaksi WHERE id_pelanggan=p_id) > 0
  THEN SELECT 'Pelanggan Memiliki Transaksi' AS keterangan;
  ELSE SELECT 'Belum Memiliki Transaksi' AS keterangan; END IF;
END$$
CREATE PROCEDURE LaporanTransaksi(IN p_id_pelanggan INT)
BEGIN
  SELECT p.nama_pelanggan, t.id_transaksi, t.tanggal, pr.nama_produk, d.jumlah, d.harga, d.jumlah*d.harga AS subtotal
  FROM tbl_pelanggan p JOIN tbl_transaksi t ON p.id_pelanggan=t.id_pelanggan
  JOIN tbl_detail_transaksi d ON t.id_transaksi=d.id_transaksi JOIN tbl_produk pr ON d.id_produk=pr.id_produk
  WHERE p.id_pelanggan=p_id_pelanggan ORDER BY t.tanggal, t.id_transaksi;
END$$
-- Transaksi kasir manual dari admin (1 produk). Checkout toko online memakai transaksi PHP sendiri.
CREATE PROCEDURE TambahTransaksi(IN p_id_pelanggan INT, IN p_id_produk INT, IN p_jumlah INT)
BEGIN
  DECLARE v_harga DECIMAL(12,2); DECLARE v_stok INT; DECLARE v_id INT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  START TRANSACTION;
  SELECT harga, stok INTO v_harga, v_stok FROM tbl_produk WHERE id_produk=p_id_produk FOR UPDATE;
  IF v_stok IS NULL OR v_stok < p_jumlah THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Stok produk tidak mencukupi'; END IF;
  INSERT INTO tbl_transaksi(tanggal,id_pelanggan,total_belanja) VALUES(CURDATE(),p_id_pelanggan,HitungTotal(v_harga,p_jumlah));
  SET v_id = LAST_INSERT_ID();
  INSERT INTO tbl_detail_transaksi(id_transaksi,id_produk,jumlah,harga) VALUES(v_id,p_id_produk,p_jumlah,v_harga);
  UPDATE tbl_produk SET stok=stok-p_jumlah WHERE id_produk=p_id_produk;
  COMMIT;
  SELECT v_id AS id_transaksi_baru;
END$$

CREATE TRIGGER before_insert_transaksi BEFORE INSERT ON tbl_transaksi FOR EACH ROW
BEGIN IF NEW.total_belanja < 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Total belanja tidak boleh negatif'; END IF; END$$
CREATE TRIGGER after_insert_transaksi AFTER INSERT ON tbl_transaksi FOR EACH ROW
BEGIN INSERT INTO tbl_log(nama_tabel,aktivitas,id_data,keterangan) VALUES('tbl_transaksi','INSERT',NEW.id_transaksi,CONCAT('Transaksi pelanggan ',NEW.id_pelanggan)); END$$
CREATE TRIGGER after_insert_pelanggan AFTER INSERT ON tbl_pelanggan FOR EACH ROW
BEGIN
  INSERT INTO log_pelanggan(aktivitas,id_pelanggan) VALUES('INSERT PELANGGAN',NEW.id_pelanggan);
  INSERT INTO tbl_log(nama_tabel,aktivitas,id_data,keterangan) VALUES('tbl_pelanggan','INSERT',NEW.id_pelanggan,CONCAT('Pelanggan baru: ',NEW.nama_pelanggan));
END$$
CREATE TRIGGER after_update_pelanggan AFTER UPDATE ON tbl_pelanggan FOR EACH ROW
BEGIN
  INSERT INTO log_perubahan_pelanggan(id_pelanggan,nama_lama,nama_baru) VALUES(OLD.id_pelanggan,OLD.nama_pelanggan,NEW.nama_pelanggan);
  INSERT INTO tbl_log(nama_tabel,aktivitas,id_data,keterangan) VALUES('tbl_pelanggan','UPDATE',OLD.id_pelanggan,CONCAT(OLD.nama_pelanggan,' -> ',NEW.nama_pelanggan));
END$$
CREATE TRIGGER after_delete_pelanggan AFTER DELETE ON tbl_pelanggan FOR EACH ROW
BEGIN
  INSERT INTO log_pelanggan(aktivitas,id_pelanggan) VALUES('DELETE PELANGGAN',OLD.id_pelanggan);
  INSERT INTO tbl_log(nama_tabel,aktivitas,id_data,keterangan) VALUES('tbl_pelanggan','DELETE',OLD.id_pelanggan,CONCAT('Pelanggan dihapus: ',OLD.nama_pelanggan));
END$$
DELIMITER ;

-- Akun staf: buka /admin/login.php. Jika tbl_pengguna kosong, halaman itu membuat admin pertama.
-- Admin menambah kasir di menu Pengguna.
