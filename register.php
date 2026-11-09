<?php
require 'config.php';
cek();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$n = trim($_POST['nama']);
	$m = trim($_POST['email']);
	$p = $_POST['password'];
	if ($n === '' || !filter_var($m, FILTER_VALIDATE_EMAIL) || strlen($p) < 6) {
		$err = 'Isi nama, email valid, dan password minimal 6 karakter.';
	} elseif (q("SELECT 1 FROM users WHERE email=?", [$m])->fetch()) {
		$err = 'Email sudah digunakan.';
	} else {
		q("INSERT INTO users(nama,email,password_hash,role) VALUES(?,?,?,'karyawan')", [$n, $m, password_hash($p, PASSWORD_DEFAULT)]);
		flash('Registrasi berhasil. Silakan masuk.');
		go('login.php');
	}
}

head('Registrasi');
if ($err) {
	echo "<div class='note err'>" . e($err) . "</div>";
}
?>
<form method="post" class="card narrow"><?=csrf()?>
	<label>Nama<input name="nama" required></label>
	<label>Email<input type="email" name="email" required></label>
	<label>Password<input type="password" name="password" minlength="6" required></label>
	<button>Daftar</button><p>Sudah punya akun? <a href="login.php">Masuk</a></p>
</form>
<?php foot();
