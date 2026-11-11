<?php
require 'config.php'; wajib_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Metode tidak diizinkan.'); }
cek_csrf();

$kode = trim($_POST['kode'] ?? '');
$st = $db->prepare('SELECT status,status_pembayaran,metode_pembayaran,bukti_pembayaran FROM tbl_pesanan WHERE kode_pesanan=? AND id_pelanggan=?');
$st->execute([$kode, pelanggan()['id']]); $pesanan = $st->fetch();
if (!$pesanan) { http_response_code(404); exit('Pesanan tidak ditemukan.'); }
if ($pesanan['metode_pembayaran'] === 'COD') { flash('Pesanan COD tidak memerlukan bukti pembayaran.'); pindah('pesanan.php'); }
if (!in_array($pesanan['status_pembayaran'], ['Belum dibayar', 'Ditolak'], true)
  || in_array($pesanan['status'], ['Dibatalkan', 'Selesai'], true)) {
  flash('Bukti pembayaran tidak dapat diunggah untuk status pesanan ini.'); pindah('pesanan.php');
}

$file = $_FILES['bukti'] ?? null;
if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
  $pesan = !$file ? 'Pilih file bukti pembayaran.' : match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas.',
    UPLOAD_ERR_NO_FILE => 'Pilih file bukti pembayaran.',
    default => 'Unggahan gagal. Silakan coba lagi.'
  };
  flash($pesan); pindah('pesanan.php');
}
if ($file['size'] > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
  flash('File tidak valid atau ukurannya melebihi 5 MB.'); pindah('pesanan.php');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$image = getimagesize($file['tmp_name']);
$format = [
  'image/jpeg' => ['extension' => '.jpg', 'type' => IMAGETYPE_JPEG],
  'image/png' => ['extension' => '.png', 'type' => IMAGETYPE_PNG],
  'image/webp' => ['extension' => '.webp', 'type' => IMAGETYPE_WEBP]
];
if (!$image || !isset($format[$mime]) || $image[2] !== $format[$mime]['type']) {
  flash('Format bukti harus berupa gambar JPG, PNG, atau WebP yang valid.'); pindah('pesanan.php');
}

$nama_file = bin2hex(random_bytes(16)).$format[$mime]['extension'];
$direktori = __DIR__.'/uploads/bukti-pembayaran';
if (!is_dir($direktori) && !mkdir($direktori, 0750, true) && !is_dir($direktori)) {
  flash('Folder penyimpanan bukti pembayaran tidak dapat dibuat.'); pindah('pesanan.php');
}
$tujuan = $direktori.'/'.$nama_file;
if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
  flash('Bukti pembayaran gagal disimpan. Periksa izin folder uploads.'); pindah('pesanan.php');
}

try {
  $st = $db->prepare("UPDATE tbl_pesanan SET bukti_pembayaran=?,status_pembayaran='Menunggu verifikasi'
    WHERE kode_pesanan=? AND id_pelanggan=? AND status_pembayaran=? AND status NOT IN ('Dibatalkan','Selesai')");
  $st->execute([$nama_file, $kode, pelanggan()['id'], $pesanan['status_pembayaran']]);
  if ($st->rowCount() !== 1) {
    unlink($tujuan); flash('Status pesanan berubah. Muat ulang halaman lalu coba lagi.'); pindah('pesanan.php');
  }
} catch (PDOException $ex) {
  unlink($tujuan); flash('Bukti gagal disimpan ke pesanan. Silakan coba lagi.'); pindah('pesanan.php');
}

$lama = $pesanan['bukti_pembayaran'];
if ($lama && preg_match('/\A[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $lama)) {
  $file_lama = $direktori.'/'.$lama;
  if (is_file($file_lama)) unlink($file_lama);
}
flash('Bukti pembayaran berhasil dikirim dan menunggu verifikasi staf.'); pindah('pesanan.php');
