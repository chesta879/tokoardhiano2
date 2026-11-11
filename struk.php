<?php
require 'config.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$admin = !empty($_SESSION['admin']);
$p = pelanggan();
if (!$admin && !$p) {
  $_SESSION['kembali'] = 'pesanan.php';
  flash('Masuk dulu untuk melihat atau mencetak struk.');
  pindah('masuk.php');
}

$kode = trim($_GET['kode'] ?? '');
$sql = 'SELECT o.*, p.nama_pelanggan FROM tbl_pesanan o
  JOIN tbl_pelanggan p USING(id_pelanggan) WHERE o.kode_pesanan=?';
$st = $db->prepare($sql);
$st->execute([$kode]);
$pesanan = $st->fetch();
if (!$pesanan || (!$admin && (int)$pesanan['id_pelanggan'] !== (int)$p['id'])) {
  http_response_code(404);
  exit('Struk pesanan tidak ditemukan.');
}

$st = $db->prepare('SELECT pr.nama_produk,d.jumlah,d.harga FROM tbl_transaksi t
  JOIN tbl_detail_transaksi d USING(id_transaksi)
  JOIN tbl_produk pr USING(id_produk) WHERE t.kode_pesanan=? ORDER BY t.id_transaksi,d.id_detail');
$st->execute([$kode]);
$items = $st->fetchAll();
$total = 0;
foreach ($items as $item) $total += (float)$item['harga'] * (int)$item['jumlah'];
$kembali = $admin ? 'index.php' : 'pesanan.php';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Struk <?= e($pesanan['kode_pesanan']) ?> · <?= e(NAMA_TOKO) ?></title>
<link rel="stylesheet" href="assets/style.css">
<style>
body.struk-halaman{background:#f4f5f7;padding:24px 12px}
.struk-aksi{max-width:420px;margin:0 auto 14px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.struk-aksi .baris{flex-wrap:nowrap}
.struk-kertas{max-width:420px;margin:0 auto;padding:26px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 24px rgba(20,33,61,.08);font:13px/1.5 Arial,sans-serif;color:#111}
.struk-kertas h1{margin:0;text-align:center;font-size:20px;letter-spacing:.4px}
.struk-kertas .subjudul{text-align:center;margin:2px 0 16px;color:#444}
.struk-meta{border-top:1px dashed #888;border-bottom:1px dashed #888;padding:9px 0;margin:12px 0}
.struk-meta p{display:flex;justify-content:space-between;gap:12px;margin:3px 0}
.struk-meta p span:last-child{text-align:right}
.struk-alamat{margin:10px 0}
.struk-tabel{width:100%;border-collapse:collapse;font:inherit}
.struk-tabel th,.struk-tabel td{padding:7px 0;text-align:left;vertical-align:top;border-bottom:1px dashed #ccc}
.struk-tabel th:last-child,.struk-tabel td:last-child{text-align:right;white-space:nowrap}
.struk-rincian{color:#555;font-size:11px}
.struk-total{display:flex;justify-content:space-between;gap:12px;padding-top:10px;font-weight:700;font-size:16px}
.struk-catatan{text-align:center;margin:18px 0 0;padding-top:10px;border-top:1px dashed #888;color:#444}
@media(max-width:480px){body.struk-halaman{padding:12px 8px}.struk-kertas{padding:20px 16px}.struk-aksi{padding:0 4px}}
@media print{
  @page{size:80mm auto;margin:5mm}
  body.struk-halaman{background:#fff;padding:0}
  .struk-aksi{display:none!important}
  .struk-kertas{max-width:none;margin:0;padding:0;border:0;border-radius:0;box-shadow:none}
  *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
</head>
<body class="struk-halaman">
<div class="struk-aksi">
  <a class="btn abu" href="<?= e($kembali) ?>">Kembali</a>
  <button class="btn besar" type="button" onclick="window.print()">Cetak struk</button>
</div>
<main class="struk-kertas">
  <h1><?= e(NAMA_TOKO) ?></h1>
  <p class="subjudul">Struk pesanan</p>
  <div class="struk-meta">
    <p><span>Kode pesanan</span><strong><?= e($pesanan['kode_pesanan']) ?></strong></p>
    <p><span>Tanggal</span><span><?= e($pesanan['dibuat_pada']) ?></span></p>
    <p><span>Pelanggan</span><span><?= e($pesanan['nama_pelanggan']) ?></span></p>
    <p><span>Status pesanan</span><span><?= e($pesanan['status']) ?></span></p>
    <p><span>Metode pembayaran</span><span><?= e($pesanan['metode_pembayaran']) ?></span></p>
    <p><span>Status pembayaran</span><span><?= e($pesanan['status_pembayaran']) ?></span></p>
  </div>
  <p class="struk-alamat"><strong>Alamat pengiriman:</strong><br><?= nl2br(e($pesanan['alamat_pengiriman'])) ?></p>
  <table class="struk-tabel">
    <thead><tr><th>Produk</th><th>Jumlah / subtotal</th></tr></thead>
    <tbody>
    <?php foreach ($items as $item): $subtotal = (float)$item['harga'] * (int)$item['jumlah']; ?>
      <tr>
        <td><?= e($item['nama_produk']) ?><br><span class="struk-rincian"><?= rupiah($item['harga']) ?> × <?= (int)$item['jumlah'] ?></span></td>
        <td><?= rupiah($subtotal) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <p class="struk-total"><span>Total pesanan</span><span><?= rupiah($total) ?></span></p>
  <p class="struk-catatan">Terima kasih telah berbelanja di <?= e(NAMA_TOKO) ?>.</p>
</main>
</body>
</html>
