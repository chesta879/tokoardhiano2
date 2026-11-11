<?php
require 'config.php'; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_csrf();
  $st = $db->prepare('SELECT a.password_hash, p.id_pelanggan, p.nama_pelanggan FROM tbl_akun_pelanggan a JOIN tbl_pelanggan p USING(id_pelanggan) WHERE a.email=?');
  $st->execute([strtolower(trim($_POST['email'] ?? ''))]); $u = $st->fetch();
  if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
    session_regenerate_id(true); $_SESSION['pelanggan'] = ['id' => (int)$u['id_pelanggan'], 'nama' => $u['nama_pelanggan']];
    $k = $_SESSION['kembali'] ?? 'index.php'; unset($_SESSION['kembali']); pindah($k);
  } else $err = 'Email atau kata sandi salah.';
}
$judul = 'Masuk'; $auth_page = true; include 'partials/header.php';
?>
<section class="auth-content"><h2 class="judul">Masuk</h2>
<?php if ($err): ?><div class="info salah"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="form sempit"><?= csrf_field() ?>
<label>Email<input type="email" name="email" required></label>
<label>Kata sandi<input type="password" name="password" required></label>
<button class="btn besar">Masuk</button><p>Belum punya akun? <a href="daftar.php">Daftar</a></p></form></section>
<?php include 'partials/footer.php'; ?>
