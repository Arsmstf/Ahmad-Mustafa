<?php
require '../config.php';
need('admin');
cek();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['aksi'];
    if ($a === 'kategori' && trim($_POST['nama_kategori']) !== '') {
        q("INSERT INTO kategori(nama_kategori,deskripsi) VALUES(?,?)", [trim($_POST['nama_kategori']), trim($_POST['deskripsi'])]);
        flash('Kategori ditambahkan.');
    }
    if ($a === 'simpan') {
        $t = max(0, (int) $_POST['jumlah_total']);
        $id = (int) $_POST['id'];
        $f = [
            (int) $_POST['id_kategori'],
            trim($_POST['kode_barang']),
            trim($_POST['nama_barang']),
            trim($_POST['kondisi']),
            trim($_POST['lokasi']),
        ];
        try {
            if ($id) {
                $old = q("SELECT jumlah_total,jumlah_tersedia FROM barang WHERE id_barang=?", [$id])->fetch();
                $tersedia = max(0, min($t, $old['jumlah_tersedia'] + $t - $old['jumlah_total']));
                q(
                    "UPDATE barang SET id_kategori=?,kode_barang=?,nama_barang=?,kondisi=?,lokasi=?,jumlah_total=?,jumlah_tersedia=? WHERE id_barang=?",
                    [...$f, $t, $tersedia, $id]
                );
                flash('Barang diperbarui.');
            } else {
                q(
                    "INSERT INTO barang(id_kategori,kode_barang,nama_barang,kondisi,lokasi,jumlah_total,jumlah_tersedia) VALUES(?,?,?,?,?,?,?)",
                    [...$f, $t, $t]
                );
                flash('Barang ditambahkan.');
            }
        } catch (PDOException $x) {
            flash('Gagal menyimpan. Kode barang mungkin sudah digunakan.');
        }
    }
    if ($a === 'hapus') {
        try {
            q("DELETE FROM barang WHERE id_barang=?", [(int) $_POST['id']]);
            flash('Barang dihapus.');
        } catch (PDOException $x) {
            flash('Barang tidak bisa dihapus karena sudah ada di riwayat peminjaman.');
        }
    }
    go('admin/barang.php');
}

$ed = q("SELECT * FROM barang WHERE id_barang=?", [(int) ($_GET['edit'] ?? 0)])->fetch() ?: [
    'id_barang' => 0,
    'id_kategori' => 0,
    'kode_barang' => '',
    'nama_barang' => '',
    'jumlah_total' => 1,
    'kondisi' => 'Baik',
    'lokasi' => '',
];
$kat = q("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();
$rows = q("SELECT b.*,k.nama_kategori FROM barang b JOIN kategori k USING(id_kategori) ORDER BY b.id_barang DESC")->fetchAll();
head('Kelola barang');
?>
<form method="post" class="card">
    <?=csrf()?>
    <input type="hidden" name="aksi" value="simpan">
    <input type="hidden" name="id" value="<?=$ed['id_barang']?>">
    <h2><?=$ed['id_barang']?'Ubah barang':'Tambah barang'?></h2>
    <div class="row">
        <label>Kode<input name="kode_barang" value="<?=e($ed['kode_barang'])?>" required></label>
        <label>Nama<input name="nama_barang" value="<?=e($ed['nama_barang'])?>" required></label>
        <label>Kategori<select name="id_kategori"><?php foreach ($kat as $k) echo "<option value='{$k['id_kategori']}'" . ($k['id_kategori'] == $ed['id_kategori'] ? ' selected' : '') . ">" . e($k['nama_kategori']) . "</option>"; ?></select></label>
        <label>Jumlah total<input type="number" name="jumlah_total" min="0" value="<?=$ed['jumlah_total']?>" required></label>
        <label>Kondisi<input name="kondisi" value="<?=e($ed['kondisi'])?>"></label>
        <label>Lokasi<input name="lokasi" value="<?=e($ed['lokasi'])?>"></label>
    </div>
    <button>Simpan barang</button>
</form>
<form method="post" class="card">
    <?=csrf()?>
    <input type="hidden" name="aksi" value="kategori">
    <h2>Tambah kategori</h2>
    <div class="row">
        <label>Nama kategori<input name="nama_kategori" required></label>
        <label>Deskripsi<input name="deskripsi"></label>
    </div>
    <button>Simpan kategori</button>
</form>
<div class="scroll">
    <table>
        <tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Tersedia</th><th>Kondisi</th><th>Lokasi</th><th></th></tr>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?=e($r['kode_barang'])?></td>
                <td><?=e($r['nama_barang'])?></td>
                <td><?=e($r['nama_kategori'])?></td>
                <td><?=$r['jumlah_tersedia']?> / <?=$r['jumlah_total']?></td>
                <td><?=e($r['kondisi'])?></td>
                <td><?=e($r['lokasi'])?></td>
                <td class="act">
                    <a href="?edit=<?=$r['id_barang']?>">Ubah</a>
                    <form method="post" onsubmit="return confirm('Hapus barang ini?')">
                        <?=csrf()?>
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?=$r['id_barang']?>">
                        <button class="link">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php foot();
