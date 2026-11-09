<?php
session_start();
$pdo = new PDO(
	'mysql:host=localhost;dbname=peminjaman_kantor;charset=utf8mb4',
	'root',
	'',
	[
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	]
);

define(
	'BASE',
	rtrim(
		str_replace('\\', '/', substr(__DIR__, strlen(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']))))),
		'/'
	)
);

function e($s)
{
	return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function q($sql, $p = [])
{
	global $pdo;
	$s = $pdo->prepare($sql);
	$s->execute($p);
	return $s;
}

function go($u)
{
	header('Location: ' . BASE . '/' . $u);
	exit;
}

function need($role)
{
	if (empty($_SESSION['u']) || $_SESSION['u']['role'] !== $role) {
		go('login.php');
	}
}

function csrf()
{
	if (empty($_SESSION['t'])) {
		$_SESSION['t'] = bin2hex(random_bytes(16));
	}
	return '<input type="hidden" name="t" value="' . $_SESSION['t'] . '">';
}

function cek()
{
	if (
		$_SERVER['REQUEST_METHOD'] === 'POST'
		&& (!isset($_POST['t']) || !hash_equals($_SESSION['t'] ?? '', $_POST['t']))
	) {
		die('Token tidak valid. Muat ulang halaman.');
	}
}

function flash($m = null)
{
	if ($m !== null) {
		$_SESSION['f'] = $m;
		return;
	}
	$f = $_SESSION['f'] ?? '';
	unset($_SESSION['f']);
	return $f;
}

function head($judul)
{
	$u = $_SESSION['u'] ?? null;
	$B = BASE;
	echo "<!doctype html><html lang='id'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>" . e($judul) . " - Peminjaman Kantor</title><link rel='stylesheet' href='$B/assets/style.css'></head><body><header><a class='logo' href='$B/'>Pinjam<b>Kantor</b></a><nav>";
	if ($u && $u['role'] === 'admin') {
		echo "<a href='$B/admin/index.php'>Dashboard</a><a href='$B/admin/barang.php'>Barang</a><a href='$B/admin/pengajuan.php'>Pengajuan</a><a href='$B/admin/laporan.php'>Laporan</a>";
	}
	if ($u && $u['role'] === 'karyawan') {
		echo "<a href='$B/karyawan/barang.php'>Daftar barang</a><a href='$B/karyawan/riwayat.php'>Riwayat</a>";
	}
	if ($u) {
		echo "<a href='$B/logout.php'>Keluar (" . e($u['nama']) . ")</a>";
	}
	echo "</nav></header><main><h1>" . e($judul) . "</h1>";
	if ($f = flash()) {
		echo "<div class='note'>" . e($f) . "</div>";
	}
}

function foot()
{
	echo "</main></body></html>";
}

function badge($s)
{
	return "<span class='b " . strtolower($s) . "'>" . e($s) . "</span>";
}
