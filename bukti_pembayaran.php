<?php
require 'config.php';
$kode = trim($_GET['kode'] ?? '');
$admin = !empty($_SESSION['admin']);
if (!$admin && !pelanggan()) { $_SESSION['kembali'] = 'pesanan.php'; flash('Masuk dulu untuk melihat bukti pembayaran.'); pindah('masuk.php'); }

$sql = 'SELECT id_pelanggan,bukti_pembayaran FROM tbl_pesanan WHERE kode_pesanan=?';
$st = $db->prepare($sql); $st->execute([$kode]); $pesanan = $st->fetch();
if (!$pesanan || (!$admin && (int)$pesanan['id_pelanggan'] !== (int)pelanggan()['id'])) {
  http_response_code(404); exit('Bukti pembayaran tidak ditemukan.');
}
$nama_file = $pesanan['bukti_pembayaran'];
if (!$nama_file || !preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $nama_file)) {
  http_response_code(404); exit('Bukti pembayaran tidak ditemukan.');
}
$path = __DIR__.'/uploads/bukti-pembayaran/'.$nama_file;
if (!is_file($path) || !is_readable($path)) { http_response_code(404); exit('Bukti pembayaran tidak ditemukan.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
  http_response_code(415); exit('Format bukti pembayaran tidak didukung.');
}
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));
header('Content-Disposition: inline; filename="bukti-pembayaran"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
