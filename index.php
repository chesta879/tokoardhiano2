<?php
require 'config.php';
$q = trim($_GET['q'] ?? ''); $kat = (int)($_GET['kat'] ?? 0); $hal = max(1, (int)($_GET['hal'] ?? 1)); $per = 12;
$w = ['1=1']; $p = [];
if ($q !== '') { $w[] = 'p.nama_produk LIKE ?'; $p[] = "%$q%"; }
if ($kat) { $w[] = 'p.id_kategori = ?'; $p[] = $kat; }
$w = implode(' AND ', $w);
$st = $db->prepare("SELECT COUNT(*) FROM tbl_produk p WHERE $w"); $st->execute($p);
$total = (int)$st->fetchColumn(); $halaman = max(1, (int)ceil($total / $per)); $hal = min($hal, $halaman);
$st = $db->prepare("SELECT p.*, k.nama_kategori FROM tbl_produk p JOIN tbl_kategori k USING(id_kategori) WHERE $w ORDER BY p.id_produk DESC LIMIT $per OFFSET ".(($hal - 1) * $per));
$st->execute($p); $produk = $st->fetchAll();
$judul = $q !== '' ? "Hasil untuk \"$q\"" : 'Belanja kebutuhan harian';
include 'partials/header.php';
?>
<?php if ($q === '' && !$kat && $hal == 1): ?>
<section class="hero"><div><h1>Kebutuhan dapur dan rumah, satu keranjang.</h1>
<p>Beras, minyak, minuman, hingga perlengkapan kebersihan. Pesan dari rumah, kami antar.</p>
<a class="btn besar" href="#produk">Mulai belanja</a></div>
<div class="hero-kat"><?php foreach ($kategori as $k): ?><a href="index.php?kat=<?= $k['id_kategori'] ?>"><?= e($k['nama_kategori']) ?></a><?php endforeach; ?></div></section>
<?php endif; ?>
<h2 id="produk" class="judul"><?= e($judul) ?> <small><?= $total ?> produk</small></h2>
<?php if (!$produk): ?><p class="kosong">Produk tidak ditemukan. Coba kata kunci lain atau <a href="index.php">lihat semua produk</a>.</p><?php endif; ?>
<div class="grid"><?php foreach ($produk as $r) kartu_produk($r); ?></div>
<?php if ($halaman > 1): ?><nav class="hal"><?php for ($i = 1; $i <= $halaman; $i++): ?>
<a class="<?= $i == $hal ? 'on' : '' ?>" href="?<?= e(http_build_query(['q' => $q, 'kat' => $kat, 'hal' => $i])) ?>"><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
<?php include 'partials/footer.php'; ?>
