<?php
require 'config.php';
$_SESSION['cart'] = $_SESSION['cart'] ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_csrf(); $aksi = $_POST['aksi'] ?? '';
  if ($aksi === 'tambah') {
    $id = (int)$_POST['id']; $jml = max(1, (int)($_POST['jml'] ?? 1));
    $st = $db->prepare('SELECT stok FROM tbl_produk WHERE id_produk=?'); $st->execute([$id]); $stok = $st->fetchColumn();
    if ($stok === false) flash('Produk tidak ditemukan.');
    else { $baru = ($_SESSION['cart'][$id] ?? 0) + $jml;
      if ($baru > $stok) { $baru = (int)$stok; flash('Jumlah disesuaikan dengan stok yang tersedia.'); } else flash('Produk ditambahkan ke keranjang.');
      if ($baru > 0) $_SESSION['cart'][$id] = $baru; }
    pindah($_SERVER['HTTP_REFERER'] ?? 'index.php');
  }
  if ($aksi === 'ubah') { foreach ($_POST['jml'] ?? [] as $id => $j) { $j = (int)$j; if ($j > 0) $_SESSION['cart'][(int)$id] = $j; else unset($_SESSION['cart'][(int)$id]); } flash('Keranjang diperbarui.'); }
  if ($aksi === 'hapus') { unset($_SESSION['cart'][(int)$_POST['id']]); flash('Produk dihapus dari keranjang.'); }
  pindah('keranjang.php');
}
$items = []; $total = 0;
if ($_SESSION['cart']) {
  $ids = array_keys($_SESSION['cart']); $in = implode(',', array_fill(0, count($ids), '?'));
  $st = $db->prepare("SELECT * FROM tbl_produk WHERE id_produk IN ($in)"); $st->execute($ids);
  foreach ($st as $r) { $r['jml'] = min($_SESSION['cart'][$r['id_produk']], max(0, $r['stok'])); $r['sub'] = $r['jml'] * $r['harga']; $total += $r['sub']; $items[] = $r; }
}
$judul = 'Keranjang belanja'; include 'partials/header.php';
?>
<h2 class="judul">Keranjang belanja</h2>
<?php if (!$items): ?><p class="kosong">Keranjang masih kosong. <a href="index.php">Pilih produk dulu</a>.</p>
<?php else: ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="ubah">
<div class="scroll"><table class="tabel"><thead><tr><th>Produk</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr></thead><tbody>
<?php foreach ($items as $r): ?><tr><td><a href="produk.php?id=<?= $r['id_produk'] ?>"><?= e($r['nama_produk']) ?></a></td><td><?= rupiah($r['harga']) ?></td>
<td><input type="number" name="jml[<?= $r['id_produk'] ?>]" value="<?= $r['jml'] ?>" min="0" max="<?= (int)$r['stok'] ?>"></td><td><?= rupiah($r['sub']) ?></td>
<td><button class="link" form="hapus<?= $r['id_produk'] ?>">Hapus</button></td></tr><?php endforeach; ?>
</tbody><tfoot><tr><td colspan="3">Total</td><td colspan="2"><strong><?= rupiah($total) ?></strong></td></tr></tfoot></table></div>
<div class="baris"><button class="btn abu">Perbarui jumlah</button><a class="btn besar" href="checkout.php">Lanjut ke pembayaran</a></div></form>
<?php foreach ($items as $r): ?><form id="hapus<?= $r['id_produk'] ?>" method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id_produk'] ?>"></form><?php endforeach; ?>
<?php endif; include 'partials/footer.php'; ?>
