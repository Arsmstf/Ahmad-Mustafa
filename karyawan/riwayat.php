<?php
require '../config.php';
need('karyawan');

$id = (int) ($_GET['id'] ?? 0);
if ($id) {
	$p = q("SELECT * FROM peminjaman WHERE id_peminjaman=? AND id_user=?", [$id, $_SESSION['u']['id']])->fetch();
	if (!$p) {
		go('karyawan/riwayat.php');
	}
	$d = q(
		"SELECT b.kode_barang,b.nama_barang,d.jumlah FROM detail_peminjaman d JOIN barang b USING(id_barang) WHERE d.id_peminjaman=?",
		[$id]
	)->fetchAll();
	$k = q("SELECT * FROM pengembalian WHERE id_peminjaman=?", [$id])->fetch();
	head('Detail peminjaman #' . $id);
	?>
	<div class="card">
		<p>Status: <?=badge($p['status'])?></p>
		<p>Diajukan: <?=e($p['tanggal_pengajuan'])?></p>
		<p>Pinjam: <?=e($p['tanggal_pinjam'])?> sampai <?=e($p['tanggal_rencana_kembali'])?></p>
		<p>Catatan: <?=e($p['catatan']?:'-')?></p>
		<?php if ($k) echo "<p>Dikembalikan: " . e($k['tanggal_pengembalian']) . " (kondisi: " . e($k['kondisi_saat_kembali']) . ")</p>"; ?>
	</div>
	<table>
		<tr><th>Kode</th><th>Barang</th><th>Jumlah</th></tr>
		<?php foreach ($d as $x) echo "<tr><td>" . e($x['kode_barang']) . "</td><td>" . e($x['nama_barang']) . "</td><td>{$x['jumlah']}</td></tr>"; ?>
	</table>
	<p><a href="riwayat.php">Kembali ke riwayat</a></p>
	<?php foot(); exit;
}

$rows = q(
	"SELECT p.*,(SELECT SUM(jumlah) FROM detail_peminjaman WHERE id_peminjaman=p.id_peminjaman) total FROM peminjaman p WHERE id_user=? ORDER BY id_peminjaman DESC",
	[$_SESSION['u']['id']]
)->fetchAll();
head('Riwayat peminjaman');
?>
<div class="scroll">
	<table>
		<tr><th>No.</th><th>Diajukan</th><th>Pinjam</th><th>Kembali</th><th>Item</th><th>Status</th><th></th></tr>
		<?php
		foreach ($rows as $r) {
			echo "<tr><td>#{$r['id_peminjaman']}</td><td>" . e($r['tanggal_pengajuan']) . "</td><td>" . e($r['tanggal_pinjam']) . "</td><td>" . e($r['tanggal_rencana_kembali']) . "</td><td>{$r['total']}</td><td>" . badge($r['status']) . "</td><td><a href='?id={$r['id_peminjaman']}'>Detail</a></td></tr>";
		}
		if (!$rows) {
			echo "<tr><td colspan=7>Belum ada peminjaman. Buka <a href='barang.php'>daftar barang</a> untuk mengajukan.</td></tr>";
		}
		?>
	</table>
</div>
<?php foot();
