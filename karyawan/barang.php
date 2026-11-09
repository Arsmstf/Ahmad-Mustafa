<?php
require '../config.php';
need('karyawan');
cek();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pinjam = $_POST['tgl_pinjam'];
    $kembali = $_POST['tgl_kembali'];
    $items = array_filter(array_map('intval', $_POST['jml'] ?? []));
    $ok = true;

    if (!$items || $pinjam < date('Y-m-d') || $kembali < $pinjam) {
        $ok = false;
        flash('Pilih minimal satu barang dan periksa tanggal peminjaman.');
    } else {
        foreach ($items as $id => $j) {
            $b = q("SELECT jumlah_tersedia FROM barang WHERE id_barang=?", [$id])->fetch();
            if (!$b || $j < 1 || $j > $b['jumlah_tersedia']) {
                $ok = false;
                flash('Barang tidak tersedia atau jumlah melebihi stok.');
                break;
            }
        }
    }

    if ($ok) {
        $pdo->beginTransaction();
        q(
            "INSERT INTO peminjaman(id_user,tanggal_pinjam,tanggal_rencana_kembali,status,catatan) VALUES(?,?,?,'Menunggu',?)",
            [$_SESSION['u']['id'], $pinjam, $kembali, trim($_POST['catatan'])]
        );
        $pid = $pdo->lastInsertId();
        foreach ($items as $id => $j) {
            q("INSERT INTO detail_peminjaman(id_peminjaman,id_barang,jumlah) VALUES(?,?,?)", [$pid, $id, $j]);
        }
        $pdo->commit();
        flash('Pengajuan berhasil dikirim dengan status Menunggu.');
        go('karyawan/riwayat.php');
    }
    go('karyawan/barang.php');
}

$cari = '%' . ($_GET['cari'] ?? '') . '%';
$rows = q(
    "SELECT b.*,k.nama_kategori FROM barang b JOIN kategori k USING(id_kategori) WHERE b.nama_barang LIKE ? OR k.nama_kategori LIKE ? ORDER BY k.nama_kategori,b.nama_barang",
    [$cari, $cari]
)->fetchAll();
head('Daftar barang');
?>
<form class="row"><input name="cari" placeholder="Cari barang atau kategori" value="<?=e($_GET['cari']??'')?>"><button>Cari</button></form>
<form method="post"><?=csrf()?>
    <div class="scroll">
        <table>
            <tr><th>Kode</th><th>Barang</th><th>Kategori</th><th>Lokasi</th><th>Tersedia</th><th>Jumlah dipinjam</th></tr>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?=e($r['kode_barang'])?></td>
                    <td><?=e($r['nama_barang'])?></td>
                    <td><?=e($r['nama_kategori'])?></td>
                    <td><?=e($r['lokasi'])?></td>
                    <td><?=$r['jumlah_tersedia']?> / <?=$r['jumlah_total']?></td>
                    <td><?php if ($r['jumlah_tersedia'] > 0): ?><input type="number" class="qty" name="jml[<?=$r['id_barang']?>]" min="0" max="<?=$r['jumlah_tersedia']?>" value="0"><?php else: ?>Habis<?php endif; ?></td>
                </tr>
            <?php endforeach; if (!$rows) echo "<tr><td colspan=6>Tidak ada barang yang cocok.</td></tr>"; ?>
        </table>
    </div>
    <div class="card">
        <h2>Ajukan peminjaman</h2>
        <div class="row">
            <label>Tanggal pinjam<input type="date" name="tgl_pinjam" min="<?=date('Y-m-d')?>" value="<?=date('Y-m-d')?>" required></label>
            <label>Rencana kembali<input type="date" name="tgl_kembali" min="<?=date('Y-m-d')?>" required></label>
        </div>
        <label>Catatan<textarea name="catatan" rows="2" placeholder="Keperluan peminjaman"></textarea></label>
        <button>Ajukan peminjaman</button>
    </div>
</form>
<?php foot();
