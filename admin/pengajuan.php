<?php
require '../config.php';
need('admin');
cek();
$adm = $_SESSION['u']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int) $_POST['id'];
  $a = $_POST['aksi'];
  $p = q("SELECT * FROM peminjaman WHERE id_peminjaman=?", [$id])->fetch();
  if ($p) {
    if ($a === 'setujui' && $p['status'] === 'Menunggu') {
      q("UPDATE peminjaman SET status='Disetujui',id_admin_proses=? WHERE id_peminjaman=?", [$adm, $id]);
      flash("Pengajuan #$id disetujui.");
    } elseif ($a === 'tolak' && $p['status'] === 'Menunggu') {
      q("UPDATE peminjaman SET status='Ditolak',id_admin_proses=? WHERE id_peminjaman=?", [$adm, $id]);
      flash("Pengajuan #$id ditolak.");
    } elseif ($a === 'serahkan' && $p['status'] === 'Disetujui') {
      $pdo->beginTransaction();
      $ok = true;
      foreach (q("SELECT id_barang,jumlah FROM detail_peminjaman WHERE id_peminjaman=?", [$id])->fetchAll() as $d) {
        $n = q(
          "UPDATE barang SET jumlah_tersedia=jumlah_tersedia-? WHERE id_barang=? AND jumlah_tersedia>=?",
          [$d['jumlah'], $d['id_barang'], $d['jumlah']]
        )->rowCount();
        if (!$n) {
          $ok = false;
          break;
        }
      }
      if ($ok) {
        q("UPDATE peminjaman SET status='Dipinjam' WHERE id_peminjaman=?", [$id]);
        $pdo->commit();
        flash("Barang #$id diserahkan. Stok berkurang.");
      } else {
        $pdo->rollBack();
        flash('Penyerahan gagal: stok tidak mencukupi.');
      }
    } elseif ($a === 'kembali' && $p['status'] === 'Dipinjam') {
      $kond = $_POST['kondisi'];
      $cat = trim($_POST['catatan']);
      if (isset($_POST['terima'])) {
        $pdo->beginTransaction();
        q(
          "INSERT INTO pengembalian(id_peminjaman,id_admin,kondisi_saat_kembali,catatan) VALUES(?,?,?,?)",
          [$id, $adm, $kond, $cat]
        );
        foreach (q("SELECT id_barang,jumlah FROM detail_peminjaman WHERE id_peminjaman=?", [$id])->fetchAll() as $d) {
          q("UPDATE barang SET jumlah_tersedia=jumlah_tersedia+? WHERE id_barang=?", [$d['jumlah'], $d['id_barang']]);
        }
        q("UPDATE peminjaman SET status='Selesai' WHERE id_peminjaman=?", [$id]);
        $pdo->commit();
        flash("Pengembalian #$id dikonfirmasi. Stok bertambah.");
      } else {
        q(
          "UPDATE peminjaman SET catatan=CONCAT(IFNULL(catatan,''),?) WHERE id_peminjaman=?",
          ["\n[Pengembalian belum diterima: $kond. $cat]", $id]
        );
        flash("Masalah pengembalian #$id dicatat. Status tetap Dipinjam.");
      }
    }
  }
  go('admin/pengajuan.php');
}

$s = $_GET['s'] ?? '';
$w = in_array($s, ['Menunggu', 'Disetujui', 'Ditolak', 'Dipinjam', 'Selesai']) ? "WHERE p.status='$s'" : '';
$rows = q("SELECT p.*,u.nama FROM peminjaman p JOIN users u ON u.id_user=p.id_user $w ORDER BY p.id_peminjaman DESC")->fetchAll();
head('Pengajuan peminjaman');
echo "<p class='row'>Filter: <a href='?'>Semua</a>";
foreach (['Menunggu', 'Disetujui', 'Dipinjam', 'Selesai', 'Ditolak'] as $x) {
  echo "<a href='?s=$x'>$x</a>";
}
echo "</p>";

foreach ($rows as $r):
  $d = q(
    "SELECT b.nama_barang,d.jumlah FROM detail_peminjaman d JOIN barang b USING(id_barang) WHERE id_peminjaman=?",
    [$r['id_peminjaman']]
  )->fetchAll();
  $f = fn($a, $t, $cls = '') => "<form method='post' style='display:inline'>" . csrf() . "<input type='hidden' name='id' value='{$r['id_peminjaman']}'><input type='hidden' name='aksi' value='$a'><button class='$cls'>$t</button></form>";
  ?>
  <div class="card">
    <div class="head"><strong>#<?=$r['id_peminjaman']?> - <?=e($r['nama'])?></strong><?=badge($r['status'])?></div>
    <p>Pinjam <?=e($r['tanggal_pinjam'])?> sampai <?=e($r['tanggal_rencana_kembali'])?>. Diajukan <?=e($r['tanggal_pengajuan'])?></p>
    <ul>
      <?php foreach ($d as $x) echo "<li>" . e($x['nama_barang']) . " x {$x['jumlah']}</li>"; ?>
    </ul>
    <?php
    if ($r['catatan']) {
      echo "<p class='muted'>" . nl2br(e($r['catatan'])) . "</p>";
    }
    if ($r['status'] === 'Menunggu') {
      echo $f('setujui', 'Setujui') . ' ' . $f('tolak', 'Tolak', 'sec');
    }
    if ($r['status'] === 'Disetujui') {
      echo $f('serahkan', 'Catat penyerahan barang');
    }
    if ($r['status'] === 'Dipinjam'):
      ?>
      <form method="post" class="row">
        <?=csrf()?>
        <input type="hidden" name="id" value="<?=$r['id_peminjaman']?>">
        <input type="hidden" name="aksi" value="kembali">
        <select name="kondisi"><option>Baik</option><option>Rusak ringan</option><option>Rusak berat</option><option>Tidak lengkap</option></select>
        <input name="catatan" placeholder="Catatan pengembalian">
        <button name="terima" value="1">Konfirmasi pengembalian</button>
        <button class="sec" name="tunda" value="1">Belum dapat diterima</button>
      </form>
    <?php endif; ?>
  </div>
<?php endforeach;
if (!$rows) {
  echo "<p>Tidak ada pengajuan.</p>";
}
foot();
