<?php
require 'config.php'; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_csrf(); $nama = trim($_POST['nama'] ?? ''); $kota = trim($_POST['kota'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pw = $_POST['password'] ?? '';
  if ($nama === '' || $kota === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 6) $err = 'Lengkapi semua kolom. Email harus valid dan kata sandi minimal 6 karakter.';
  else {
    $st = $db->prepare('SELECT 1 FROM tbl_akun_pelanggan WHERE email=?'); $st->execute([$email]);
    if ($st->fetch()) $err = 'Email sudah terdaftar. Silakan masuk.';
    else {
      try {
        $db->beginTransaction();
        $db->prepare('INSERT INTO tbl_pelanggan(nama_pelanggan,kota) VALUES(?,?)')->execute([$nama, $kota]); $id = $db->lastInsertId();
        $db->prepare('INSERT INTO tbl_akun_pelanggan(id_pelanggan,email,password_hash) VALUES(?,?,?)')->execute([$id, $email, password_hash($pw, PASSWORD_DEFAULT)]);
        $db->commit(); session_regenerate_id(true);
        $_SESSION['pelanggan'] = ['id' => (int)$id, 'nama' => $nama]; flash('Akun berhasil dibuat.'); pindah('index.php');
      } catch (Exception $ex) { $db->rollBack(); $err = 'Pendaftaran gagal. Coba lagi.'; }
    }
  }
}
$judul = 'Daftar'; $auth_page = true; include 'partials/header.php';
?>
<section class="auth-content"><h2 class="judul">Buat akun</h2>
<?php if ($err): ?><div class="info salah"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="form sempit"><?= csrf_field() ?>
<label>Nama lengkap<input name="nama" value="<?= e($_POST['nama'] ?? '') ?>" required></label>
<label>Kota<input name="kota" value="<?= e($_POST['kota'] ?? '') ?>" required></label>
<label>Email<input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required></label>
<label>Kata sandi<input type="password" name="password" minlength="6" required></label>
<button class="btn besar">Daftar</button><p>Sudah punya akun? <a href="masuk.php">Masuk</a></p></form></section>
<?php include 'partials/footer.php'; ?>
