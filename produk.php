<?php
require 'config.php';
$st = $db->prepare('SELECT p.*, k.nama_kategori FROM tbl_produk p JOIN tbl_kategori k USING(id_kategori) WHERE id_produk = ?');
$st->execute([(int)($_GET['id'] ?? 0)]); $r = $st->fetch();
if (!$r) { http_response_code(404); $judul = 'Produk tidak ditemukan'; include 'partials/header.php'; echo '<p class="kosong">Produk tidak ditemukan. <a href="index.php">Kembali ke toko</a></p>'; include 'partials/footer.php'; exit; }
$judul = $r['nama_produk']; include 'partials/header.php';
?>
<div class="detail">
  <div class="foto besar"><?= $r['gambar'] ? '<img src="'.e($r['gambar']).'" alt="'.e($r['nama_produk']).'">' : '<span class="inisial">'.e(mb_strtoupper(mb_substr($r['nama_produk'], 0, 1))).'</span>' ?></div>
  <div>
    <a href="index.php?kat=<?= $r['id_kategori'] ?>"><?= e($r['nama_kategori']) ?></a>
    <h1><?= e($r['nama_produk']) ?></h1>
    <p class="harga besar"><?= rupiah($r['harga']) ?></p>
    <p><?= $r['stok'] > 0 ? 'Stok tersedia: '.(int)$r['stok'] : 'Stok habis' ?></p>
    <p><?= nl2br(e($r['deskripsi'] ?: 'Belum ada deskripsi untuk produk ini.')) ?></p>
    <form method="post" action="keranjang.php" class="baris"><?= csrf_field() ?><input type="hidden" name="aksi" value="tambah"><input type="hidden" name="id" value="<?= $r['id_produk'] ?>">
      <input type="number" name="jml" value="1" min="1" max="<?= max(1, (int)$r['stok']) ?>" aria-label="Jumlah">
      <button class="btn besar" <?= $r['stok'] <= 0 ? 'disabled' : '' ?>>Tambah ke keranjang</button></form>
  </div>
</div>
<?php include 'partials/footer.php'; ?>
