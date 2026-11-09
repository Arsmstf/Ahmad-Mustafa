<?php
require '../config.php';
need('admin');

$a = $_GET['dari'] ?? date('Y-m-01');
$b = $_GET['sampai'] ?? date('Y-m-d');
$st = q(
	"SELECT status,COUNT(*) n FROM peminjaman WHERE DATE(tanggal_pengajuan) BETWEEN ? AND ? GROUP BY status",
	[$a, $b]
)->fetchAll(PDO::FETCH_KEY_PAIR);
$top = q(
	"SELECT b.nama_barang,SUM(d.jumlah) n FROM detail_peminjaman d JOIN barang b USING(id_barang) JOIN peminjaman p USING(id_peminjaman) WHERE DATE(p.tanggal_pengajuan) BETWEEN ? AND ? AND p.status IN('Dipinjam','Selesai') GROUP BY b.id_barang ORDER BY n DESC LIMIT 5",
	[$a, $b]
)->fetchAll();
$rows = q(
	"SELECT p.*,u.nama FROM peminjaman p JOIN users u ON u.id_user=p.id_user WHERE DATE(p.tanggal_pengajuan) BETWEEN ? AND ? ORDER BY p.id_peminjaman DESC",
	[$a, $b]
)->fetchAll();
$max = $top ? max(array_column($top, 'n')) : 1;
head('Laporan peminjaman');
?>
<form class="row">
	<label>Dari<input type="date" name="dari" value="<?=e($a)?>"></label>
	<label>Sampai<input type="date" name="sampai" value="<?=e($b)?>"></label>
	<button>Tampilkan</button>
</form>
<div class="grid">
	<?php foreach (['Menunggu', 'Disetujui', 'Ditolak', 'Dipinjam', 'Selesai'] as $s) echo "<div class='card stat'><span>$s</span><strong>" . ($st[$s] ?? 0) . "</strong></div>"; ?>
</div>
<h2>Barang paling sering dipinjam</h2>
<?php
foreach ($top as $t) {
	echo "<div class='bar'><span>" . e($t['nama_barang']) . "</span><i style='width:" . round($t['n'] / $max * 100) . "%'></i><b>{$t['n']}</b></div>";
}
if (!$top) {
	echo "<p>Belum ada data pada periode ini.</p>";
}
?>
<h2>Riwayat transaksi</h2>
<div class="scroll">
	<table>
		<tr><th>No.</th><th>Karyawan</th><th>Diajukan</th><th>Pinjam</th><th>Kembali</th><th>Status</th></tr>
		<?php
		foreach ($rows as $r) {
			echo "<tr><td>#{$r['id_peminjaman']}</td><td>" . e($r['nama']) . "</td><td>" . e($r['tanggal_pengajuan']) . "</td><td>" . e($r['tanggal_pinjam']) . "</td><td>" . e($r['tanggal_rencana_kembali']) . "</td><td>" . badge($r['status']) . "</td></tr>";
		}
		?>
	</table>
</div>
<?php foot();
