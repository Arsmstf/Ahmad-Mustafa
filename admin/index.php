<?php
require '../config.php';
need('admin');

$c = fn($s) => q($s)->fetchColumn() ?: 0;
$s = [
	'Total barang' => $c("SELECT SUM(jumlah_total) FROM barang"),
	'Barang tersedia' => $c("SELECT SUM(jumlah_tersedia) FROM barang"),
	'Menunggu diproses' => $c("SELECT COUNT(*) FROM peminjaman WHERE status='Menunggu'"),
	'Sedang dipinjam' => $c("SELECT COUNT(*) FROM peminjaman WHERE status='Dipinjam'"),
	'Selesai' => $c("SELECT COUNT(*) FROM peminjaman WHERE status='Selesai'"),
	'Ditolak' => $c("SELECT COUNT(*) FROM peminjaman WHERE status='Ditolak'"),
];

head('Dashboard admin');
echo "<div class='grid'>";
foreach ($s as $k => $v) {
	echo "<div class='card stat'><span>" . e($k) . "</span><strong>$v</strong></div>";
}
echo "</div>";
$r = q(
	"SELECT p.id_peminjaman,u.nama,p.tanggal_pengajuan,p.status FROM peminjaman p JOIN users u ON u.id_user=p.id_user ORDER BY p.id_peminjaman DESC LIMIT 5"
)->fetchAll();
?>
<h2>Pengajuan terbaru</h2>
<table>
	<tr><th>No.</th><th>Karyawan</th><th>Diajukan</th><th>Status</th></tr>
	<?php
	foreach ($r as $x) {
		echo "<tr><td>#{$x['id_peminjaman']}</td><td>" . e($x['nama']) . "</td><td>" . e($x['tanggal_pengajuan']) . "</td><td>" . badge($x['status']) . "</td></tr>";
	}
	if (!$r) {
		echo "<tr><td colspan=4>Belum ada pengajuan.</td></tr>";
	}
	?>
</table>
<p><a href="pengajuan.php">Kelola semua pengajuan</a></p>
<?php foot();
