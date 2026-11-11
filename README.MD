CARA PAKAI (XAMPP / Laragon / hosting PHP 8+)
1. Salin folder "tokoku" ke htdocs (XAMPP) atau www (Laragon).
2. Impor db_tokoku.sql lewat phpMyAdmin (tab Import).
3. Cek config.php (DB_USER/DB_PASS). Bawaan XAMPP: root, tanpa kata sandi.
4. Buka http://localhost/tokoku/
5. Staf: http://localhost/tokoku/admin/login.php (admin pertama dibuat di halaman itu).

KONFIRMASI PEMBAYARAN
- Database baru: impor db_tokoku.sql seperti biasa.
- Database yang sudah digunakan: jalankan sekali di phpMyAdmin:
  ALTER TABLE tbl_pesanan
    ADD status_pembayaran VARCHAR(30) NOT NULL DEFAULT 'Belum dibayar',
    ADD bukti_pembayaran VARCHAR(40) NULL;
  UPDATE tbl_pesanan SET status_pembayaran='Tidak perlu konfirmasi'
    WHERE metode_pembayaran='COD';
- Pelanggan mengunggah bukti JPG, PNG, atau WebP dari halaman Pesanan saya.
  Staf memeriksa bukti dan mengubah status konfirmasi pada panel Pesanan.

FOTO PRODUK
- Staf dapat mengunggah foto JPG, PNG, atau WebP (maks. 5 MB) saat menambah
  atau mengubah produk di menu Produk. Foto yang diunggah menggantikan URL gambar.
- Folder uploads/produk harus dapat ditulisi oleh PHP. Foto tersimpan di folder
  tersebut dan tampil pada daftar serta halaman detail produk.

CETAK STRUK
- Pelanggan dapat mencetak struk dari halaman Pesanan saya.
- Staf dapat mencetak struk pesanan dari tabel Pesanan di panel staf.
- Halaman struk menyediakan tombol Cetak struk dan tata letak cetak thermal 80 mm.
