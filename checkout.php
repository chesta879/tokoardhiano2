<?php
require 'config.php'; wajib_login();
if (empty($_SESSION['cart'])) { flash('Keranjang masih kosong.'); pindah('keranjang.php'); }
$p = pelanggan(); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  cek_csrf(); $alamat = trim($_POST['alamat'] ?? ''); $metode = $_POST['metode'] ?? '';
  if ($alamat === '' || !in_array($metode, ['Transfer bank', 'COD', 'E-wallet'])) $err = 'Isi alamat pengiriman dan pilih metode pembayaran.';
  else {
    try {
      $db->beginTransaction();
      $kode = 'PSN'.date('ymd').strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
      $status_pembayaran = $metode === 'COD' ? 'Tidak perlu konfirmasi' : 'Belum dibayar';
      $db->prepare('INSERT INTO tbl_pesanan(kode_pesanan,id_pelanggan,alamat_pengiriman,metode_pembayaran,status_pembayaran) VALUES(?,?,?,?,?)')->execute([$kode, $p['id'], $alamat, $metode, $status_pembayaran]);
      foreach ($_SESSION['cart'] as $id => $jml) {
        $st = $db->prepare('SELECT nama_produk,harga,stok FROM tbl_produk WHERE id_produk=? FOR UPDATE'); $st->execute([$id]); $r = $st->fetch();
        if (!$r || $r['stok'] < $jml) throw new Exception('Stok '.($r['nama_produk'] ?? 'produk').' tidak mencukupi. Kurangi jumlah di keranjang.');
        $db->prepare('INSERT INTO tbl_transaksi(tanggal,id_pelanggan,total_belanja,kode_pesanan) VALUES(CURDATE(),?,?,?)')->execute([$p['id'], $r['harga'] * $jml, $kode]);
        $idt = $db->lastInsertId();
        $db->prepare('INSERT INTO tbl_detail_transaksi(id_transaksi,id_produk,jumlah,harga) VALUES(?,?,?,?)')->execute([$idt, $id, $jml, $r['harga']]);
        $db->prepare('UPDATE tbl_produk SET stok=stok-? WHERE id_produk=?')->execute([$jml, $id]);
      }
      $db->commit(); $_SESSION['cart'] = [];
      flash("Pesanan $kode berhasil dibuat."); pindah('pesanan.php');
    } catch (Exception $ex) { if ($db->inTransaction()) $db->rollBack(); $err = $ex->getMessage(); }
  }
}
$ids = array_keys($_SESSION['cart']); $in = implode(',', array_fill(0, count($ids), '?'));
$st = $db->prepare("SELECT * FROM tbl_produk WHERE id_produk IN ($in)"); $st->execute($ids);
$items = $st->fetchAll(); $total = 0; foreach ($items as $r) $total += $r['harga'] * $_SESSION['cart'][$r['id_produk']];
$judul = 'Pembayaran'; include 'partials/header.php';
?>
<h2 class="judul">Pembayaran</h2>
<?php if ($err): ?><div class="info salah"><?= e($err) ?></div><?php endif; ?>
<div class="dua">
<form method="post" class="form"><?= csrf_field() ?>
  <label>Alamat pengiriman<textarea name="alamat" rows="4" required><?= e($_POST['alamat'] ?? '') ?></textarea></label>
  <label>Metode pembayaran<select name="metode"><option>Transfer bank</option><option>COD</option><option>E-wallet</option></select></label>
  <button class="btn besar">Buat pesanan</button>
</form>
<aside class="ringkas"><h3>Ringkasan</h3>
<?php foreach ($items as $r): ?><p><?= e($r['nama_produk']) ?> × <?= $_SESSION['cart'][$r['id_produk']] ?><span><?= rupiah($r['harga'] * $_SESSION['cart'][$r['id_produk']]) ?></span></p><?php endforeach; ?>
<p class="tot">Total<span><?= rupiah($total) ?></span></p></aside></div>
<?php include 'partials/footer.php'; ?>
