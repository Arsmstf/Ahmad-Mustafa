<?php
require 'config.php';

if (empty($_SESSION['u'])) {
	go('login.php');
}

go($_SESSION['u']['role'] === 'admin' ? 'admin/index.php' : 'karyawan/barang.php');
