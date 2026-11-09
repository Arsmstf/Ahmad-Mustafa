<?php
require 'config.php';
cek();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$u = q("SELECT * FROM users WHERE email=?", [trim($_POST['email'])])->fetch();
	if (!$u || !password_verify($_POST['password'], $u['password_hash'])) {
		$err = 'Email atau password salah.';
	} elseif ($u['status'] !== 'aktif') {
		$err = 'Akun tidak aktif. Hubungi admin.';
	} else {
		session_regenerate_id(true);
		$_SESSION['u'] = ['id' => $u['id_user'], 'nama' => $u['nama'], 'role' => $u['role']];
		go('');
	}
}

head('Masuk');
if ($err) {
	echo "<div class='note err'>" . e($err) . "</div>";
}
?>
<form method="post" class="card narrow"><?=csrf()?>
	<label>Email<input type="email" name="email" required></label>
	<label>Password<input type="password" name="password" required></label>
	<button>Masuk</button><p>Belum punya akun? <a href="register.php">Daftar sebagai karyawan</a></p>
</form>
<?php foot();
