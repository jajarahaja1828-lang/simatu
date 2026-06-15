<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| TAMBAH DATA
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {

    $kategoriInput = $_POST['kategori_id'] ?? '';
    $jenisAset     = $_POST['jenis_aset'] ?? 'bergerak'; 

    $kategoriId = 0;

    if($kategoriInput === 'baru'){
        $kategoriBaru = trim($_POST['kategori_baru'] ?? '');

        if($kategoriBaru){
            Database::execute(
                "INSERT INTO bmn_kategori
                (
                    nama_kategori,
                    jenis
                )
                VALUES
                (
                    ?,
                    ?
                )",
                [$kategoriBaru, $jenisAset]
            );

            $kategori = Database::fetch(
                "SELECT id
                 FROM bmn_kategori
                 ORDER BY id DESC
                 LIMIT 1"
            );

            $kategoriId = $kategori['id'];
        }
    }else{
        $kategoriId = (int)$kategoriInput;
    }

    $kode       = trim($_POST['kode_aset'] ?? '');
    $nama       = trim($_POST['nama_aset'] ?? '');
    $kondisi    = $_POST['kondisi'] ?? 'Baik';
    $satuan     = trim($_POST['satuan'] ?? 'Unit');
    $jumlah     = (int) ($_POST['jumlah'] ?? 1);
    $nilai      = (float) ($_POST['nilai_perolehan'] ?? 0);
    $tanggal    = $_POST['tanggal_perolehan'] ?? date('Y-m-d');
    $ket        = trim($_POST['keterangan'] ?? '');

    if ($kategoriId && $kode && $nama) {
        try {
            Database::execute(
                "INSERT INTO bmn_aset
                (
                    kategori_id,
                    kode_aset,
                    nama_aset,
                    kondisi,
                    satuan,
                    jumlah,
                    nilai_perolehan,
                    tanggal_perolehan,
                    keterangan
                )
                VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    $kategoriId,
                    $kode,
                    $nama,
                    $kondisi,
                    $satuan,
                    $jumlah,
                    $nilai,
                    $tanggal,
                    $ket
                ]
            );

            flashSet('success', 'Aset berhasil ditambahkan');
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    redirect('/bmn/index.php');
}

/*
|--------------------------------------------------------------------------
| EDIT DATA
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    $id         = (int) ($_POST['id'] ?? 0);
    $kategoriId = (int) ($_POST['kategori_id'] ?? 0); 
    $nama       = trim($_POST['nama_aset'] ?? '');
    $kondisi    = $_POST['kondisi'] ?? 'Baik';
    $jumlah     = (int) ($_POST['jumlah'] ?? 1);
    $satuan     = trim($_POST['satuan'] ?? '');
    $nilai      = (float) ($_POST['nilai_perolehan'] ?? 0); 
    $tanggal    = $_POST['tanggal_perolehan'] ?? date('Y-m-d');
    $ket        = trim($_POST['keterangan'] ?? '');

    if ($id && $nama && $kategoriId) {
        Database::execute(
            "UPDATE bmn_aset
            SET
                kategori_id=?,
                nama_aset=?,
                kondisi=?,
                jumlah=?,
                satuan=?,
                nilai_perolehan=?, 
                tanggal_perolehan=?,
                keterangan=?
            WHERE id=?",
            [
                $kategoriId,
                $nama,
                $kondisi,
                $jumlah,
                $satuan,
                $nilai,
                $tanggal,
                $ket,
                $id
            ]
        );

        flashSet('success', 'Data berhasil diperbarui');
    }

    redirect('/bmn/index.php');
}

/*
|--------------------------------------------------------------------------
| HAPUS DATA
|--------------------------------------------------------------------------
*/  

if (isset($_GET['hapus'])) {

    Database::execute(
        "DELETE FROM bmn_aset WHERE id=?",
        [(int) $_GET['hapus']]
    );

    flashSet('success', 'Data berhasil dihapus');

    redirect('/bmn/index.php');
}

/*
|--------------------------------------------------------------------------
| CETAK LAPORAN (SUDAH DIPISAH BERGERAK/TIDAK BERGERAK & ADA TTD)
|--------------------------------------------------------------------------
*/

if(isset($_GET['cetak'])){

    $jenis   = $_GET['jenis'] ?? 'semua';
    $tanggal = $_GET['tanggal'] ?? date('Y-m-d');

    $where  = "";
    $params = [];

    if($jenis == 'harian'){
        $where = "AND DATE(a.tanggal_perolehan)=?";
        $params[] = $tanggal;
    }
    elseif($jenis == 'bulanan'){
        $where = "AND MONTH(a.tanggal_perolehan)=MONTH(?) AND YEAR(a.tanggal_perolehan)=YEAR(?)";
        $params[] = $tanggal;
        $params[] = $tanggal;
    }
    elseif($jenis == 'tahunan'){
        $where = "AND YEAR(a.tanggal_perolehan)=YEAR(?)";
        $params[] = $tanggal;
    }

    // Ambil data barang bergerak
    $dataBergerak = Database::fetchAll(
        "SELECT a.*, k.nama_kategori 
         FROM bmn_aset a
         JOIN bmn_kategori k ON k.id = a.kategori_id
         WHERE k.jenis = 'bergerak' $where
         ORDER BY a.id DESC", $params
    );

    // Ambil data barang tidak bergerak
    $dataTidakBergerak = Database::fetchAll(
        "SELECT a.*, k.nama_kategori 
         FROM bmn_aset a
         JOIN bmn_kategori k ON k.id = a.kategori_id
         WHERE k.jenis = 'tidak_bergerak' $where
         ORDER BY a.id DESC", $params
    );

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Laporan BMN</title>
<style>
body{ font-family:Arial,sans-serif; padding:30px; color:#111827; line-height: 1.4; }
.header{ text-align:center; margin-bottom:25px; border-bottom: 3px double #000; padding-bottom: 10px; }
.header h2{ margin:0; font-size:22px; text-transform: uppercase; }
.header h3{ margin:5px 0 0 0; font-size:16px; font-weight: normal; }
.header p{ margin:5px 0 0 0; color:#4b5563; font-size: 12px; }
.top-action{ margin-bottom:20px; display:flex; gap:10px; }
.btn{ border:none; border-radius:8px; padding:10px 18px; font-weight:600; cursor:pointer; }
.btn-back{ background:#e5e7eb; }
.btn-print{ background:#2563eb; color:#fff; }
.section-title{ font-size: 14px; font-weight: bold; margin-top: 25px; margin-bottom: 8px; text-transform: uppercase; background: #f3f4f6; padding: 6px 10px; border-left: 4px solid #2563eb; }
table{ width:100%; border-collapse:collapse; margin-bottom: 20px; }
th{ background:#f3f4f6; color:#111827; border:1px solid #9ca3af; padding:10px 8px; font-size:12px; font-weight: bold; text-align: center; }
td{ border:1px solid #9ca3af; padding:8px; font-size:12px; }
.text-center{ text-align:center; }
.text-right{ text-align:right; }
.empty-row{ text-align: center; color: #6b7280; font-style: italic; padding: 15px; }

/* Tanda Tangan Kedinasan Model Pas */
.ttd-container { width: 100%; margin-top: 40px; display: flex; justify-content: flex-end; page-break-inside: avoid; }
.ttd-box { width: 300px; text-align: center; font-size: 13px; }
.ttd-space { height: 75px; }

@media print{ .no-print{ display:none; } body{ padding: 0; } }
</style>
</head>
<body>

<div class="top-action no-print">
    <button onclick="window.location='<?= BASE_PATH ?>/bmn/index.php'" class="btn btn-back">
        Kembali
    </button>
    <button onclick="window.print()" class="btn btn-print">
        Cetak Sekarang
    </button>
</div>

<div class="header">
    <h2>Laporan Barang Milik Negara (BMN)</h2>
    <h3>Balai Pemasyarakatan Kelas I Jakarta Selatan</h3>
    <p>Tanggal Cetak : <?= date('d F Y') ?> | Periode Laporan: <?= ucfirst($jenis) ?></p>
</div>

<div class="section-title">A. Kelompok Barang Bergerak</div>
<table>
<thead>
<tr>
    <th width="4%">No</th>
    <th width="12%">Kode Aset</th>
    <th>Nama Barang / Aset</th>
    <th width="18%">Kategori</th>
    <th width="7%">Jumlah</th>
    <th width="8%">Satuan</th>
    <th width="15%">Harga Perolehan</th>
    <th width="12%">Kondisi</th>
</tr>
</thead>
<tbody>
<?php if(empty($dataBergerak)): ?>
<tr>
    <td colspan="8" class="empty-row">Tidak ada data barang bergerak pada periode ini.</td>
</tr>
<?php else: ?>
    <?php foreach($dataBergerak as $i => $d): ?>
    <tr>
        <td class="text-center"><?= $i + 1 ?></td>
        <td class="text-center"><?= htmlspecialchars($d['kode_aset']) ?></td>
        <td><strong><?= htmlspecialchars($d['nama_aset']) ?></strong></td>
        <td><?= htmlspecialchars($d['nama_kategori']) ?></td>
        <td class="text-center"><?= $d['jumlah'] ?></td>
        <td class="text-center"><?= htmlspecialchars($d['satuan']) ?></td>
        <td class="text-right">Rp <?= number_format($d['nilai_perolehan'], 0, ',', '.') ?></td>
        <td class="text-center"><?= htmlspecialchars($d['kondisi']) ?></td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>

<div class="section-title">B. Kelompok Barang Tidak Bergerak</div>
<table>
<thead>
<tr>
    <th width="4%">No</th>
    <th width="12%">Kode Aset</th>
    <th>Nama Barang / Aset</th>
    <th width="18%">Kategori</th>
    <th width="7%">Jumlah</th>
    <th width="8%">Satuan</th>
    <th width="15%">Harga Perolehan</th>
    <th width="12%">Kondisi</th>
</tr>
</thead>
<tbody>
<?php if(empty($dataTidakBergerak)): ?>
<tr>
    <td colspan="8" class="empty-row">Tidak ada data barang tidak bergerak pada periode ini.</td>
</tr>
<?php else: ?>
    <?php foreach($dataDomestic = $dataTidakBergerak as $i => $d): ?>
    <tr>
        <td class="text-center"><?= $i + 1 ?></td>
        <td class="text-center"><?= htmlspecialchars($d['kode_aset']) ?></td>
        <td><strong><?= htmlspecialchars($d['nama_aset']) ?></strong></td>
        <td><?= htmlspecialchars($d['nama_kategori']) ?></td>
        <td class="text-center"><?= $d['jumlah'] ?></td>
        <td class="text-center"><?= htmlspecialchars($d['satuan']) ?></td>
        <td class="text-right">Rp <?= number_format($d['nilai_perolehan'], 0, ',', '.') ?></td>
        <td class="text-center"><?= htmlspecialchars($d['kondisi']) ?></td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>

<div style="margin-top:80px;width:100%;">

    <table style="width:100%;border:none;">
        <tr>

            <td style="width:33%;text-align:center;border:none;">
                Operator
                <br><br><br><br><br>
                <strong><u>Velia Irna Wionie</u></strong><br>
                NIP. 197710302009011004
            </td>

            <td style="width:33%;text-align:center;border:none;">
                Kasubag TU
                <br><br><br><br><br>
                <strong><u>DEVI SARITKA, A.Md.P., S.H., M.H</u></strong><br>
                NIP. 1991122922010122001
            </td>

            <td style="width:33%;text-align:center;border:none;">
                Jakarta, <?= date('d F Y') ?><br>
                Pengelola Barang Milik Negara
                <br><br><br><br><br>
                <strong><u>Ahmad Ariansyah</u></strong><br>
                NIP. 19950824 202403 1 002
            </td>

        </tr>
    </table>

</div>

</body>
</html>

<?php
exit;
}

/*
|--------------------------------------------------------------------------
| LOAD DATA UTAMA DASHBOARD
|--------------------------------------------------------------------------
*/

$semuaKategori = Database::fetchAll("SELECT * FROM bmn_kategori ORDER BY nama_kategori");

$kategoriBergerak = Database::fetchAll("SELECT * FROM bmn_kategori WHERE jenis='bergerak' ORDER BY nama_kategori");
$kategoriTidakBergerak = Database::fetchAll("SELECT * FROM bmn_kategori WHERE jenis='tidak_bergerak' ORDER BY nama_kategori");

$selectedKat = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

$where = '';
$params = [];

if ($selectedKat) {
    $where = "AND k.id=?";
    $params[] = $selectedKat;
}

$asets = Database::fetchAll(
    "SELECT
        a.*,
        k.nama_kategori,
        k.jenis as jenis_kategori
    FROM bmn_aset a
    JOIN bmn_kategori k
        ON k.id = a.kategori_id
    WHERE 1=1
    $where
    ORDER BY a.id DESC",
    $params
);

/*
|--------------------------------------------------------------------------
| STATS DASHBOARD
|--------------------------------------------------------------------------
*/

$totalAset = count($asets);
$totalBaik = 0;
$totalRR   = 0;
$totalRB   = 0;

foreach($asets as $a){
    if($a['kondisi'] == 'Baik') $totalBaik++;
    if($a['kondisi'] == 'Rusak Ringan') $totalRR++;
    if($a['kondisi'] == 'Rusak Berat') $totalRB++;
}

renderHeader('BMN Barang Bergerak', 'bmn-bergerak');

?>

<style>
.dashboard-grid{ display:grid; grid-template-columns:260px 1fr; gap:20px; }
.sidebar-kategori{ background:#fff; border-radius:20px; padding:20px; border:1px solid #e5e7eb; height:fit-content; }
.sidebar-kategori h3{ font-size:20px; font-weight:700; margin-bottom:20px; }
.sidebar-kategori .sub-heading-title { font-size:12px; font-weight:700; color:#9ca3af; text-transform: uppercase; margin-top:15px; margin-bottom:5px; padding-left:5px; letter-spacing: 0.5px; }
.kategori-item{ display:block; padding:12px 14px; border-radius:12px; color:#374151; text-decoration:none; margin-bottom:4px; transition:0.2s; font-weight:500; }
.kategori-item:hover{ background:#eff6ff; color:#2563eb; }
.kategori-item.active{ background:#2563eb; color:#fff; }
.content-box{ background:#fff; border-radius:20px; padding:24px; border:1px solid #e5e7eb; }
.stats{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:25px; }
.stat-box{ border-radius:16px; padding:18px; font-weight:600; color:#111827; }
.stat-box h2{ margin:10px 0 0; font-size:28px; font-weight:700; }
.bg-blue{ background:#dbeafe; } .bg-green{ background:#dcfce7; } .bg-yellow{ background:#fef9c3; } .bg-red{ background:#fee2e2; }
.table-modern{ width:100%; border-collapse:collapse; }
.table-modern th{ background:#f9fafb; padding:14px; font-size:13px; text-align:left; border-bottom:1px solid #e5e7eb; }
.table-modern td{ padding:14px; border-bottom:1px solid #f1f5f9; }
.badge-status{ padding:6px 10px; border-radius:999px; font-size:12px; font-weight:600; }
.badge-baik{ background:#dcfce7; color:#166534; } .badge-ringan{ background:#fef9c3; color:#854d0e; } .badge-berat{ background:#fee2e2; color:#991b1b; }
.action-btn{ border:none; border-radius:10px; }
.modal-content{ border-radius:20px; border:none; }
.form-control, .form-select{ border-radius:12px; padding:12px; }
.btn-primary{ border-radius:12px; padding:10px 18px; font-weight:600; }
@media(max-width:992px){ .dashboard-grid{ grid-template-columns:1fr; } .stats{ grid-template-columns:1fr 1fr; } }
</style>

<div class="dashboard-grid">

<div class="sidebar-kategori">
    <h3>KATEGORI</h3>
    <a href="<?= BASE_PATH ?>/bmn/index.php" class="kategori-item <?= !$selectedKat ? 'active' : '' ?>">
        Semua Barang
    </a>

    <div class="sub-heading-title">Barang Bergerak</div>
    <?php foreach($kategoriBergerak as $k): ?>
        <a href="<?= BASE_PATH ?>/bmn/index.php?kategori=<?= $k['id'] ?>" class="kategori-item <?= $selectedKat == $k['id'] ? 'active' : '' ?>">
            <?= sanitize($k['nama_kategori']) ?>
        </a>
    <?php endforeach; ?>

    <div class="sub-heading-title">Barang Tidak Bergerak</div>
    <?php foreach($kategoriTidakBergerak as $k): ?>
        <a href="<?= BASE_PATH ?>/bmn/index.php?kategori=<?= $k['id'] ?>" class="kategori-item <?= $selectedKat == $k['id'] ? 'active' : '' ?>">
            <?= sanitize($k['nama_kategori']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="content-box">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
<div>
<h2 style="margin:0;font-weight:700;">Barang Aset BMN</h2>
<div style="color:#6b7280;">Sistem Informasi BMN</div>
</div>

<div style="display:flex;gap:10px;">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCetak">
        <i class="bi bi-printer"></i> Cetak Laporan
    </button>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg"></i> Tambah Aset
    </button>
</div>
</div>

<div class="stats">
<div class="stat-box bg-blue"> Total Aset <h2><?= $totalAset ?></h2> </div>
<div class="stat-box bg-green"> Baik <h2><?= $totalBaik ?></h2> </div>
<div class="stat-box bg-yellow"> Rusak Ringan <h2><?= $totalRR ?></h2> </div>
<div class="stat-box bg-red"> Rusak Berat <h2><?= $totalRB ?></h2> </div>
</div>

<div style="margin-bottom:20px;">
<input type="text" id="searchInput" class="form-control" placeholder="Cari aset...">
</div>

<div class="table-responsive">
<table class="table-modern" id="mainTable">
<thead>
<tr>
<th>Kode</th>
<th>Nama Barang</th>
<th>Kategori</th>
<th>Jumlah</th>
<th>Harga Perolehan</th> 
<th>Tanggal</th>
<th>Kondisi</th>
<th width="140">Aksi</th>
</tr>
</thead>
<tbody>

<?php foreach($asets as $a): ?>
<?php
$badge = 'badge-baik';
if($a['kondisi']=='Rusak Ringan'){ $badge='badge-ringan'; }
if($a['kondisi']=='Rusak Berat'){ $badge='badge-berat'; }
?>

<tr>
<td><?= sanitize($a['kode_aset']) ?></td>
<td><strong><?= sanitize($a['nama_aset']) ?></strong></td>
<td>
    <?= sanitize($a['nama_kategori']) ?>
    <small class="text-muted d-block" style="font-size: 11px;">(<?= $a['jenis_kategori'] == 'bergerak' ? 'Bergerak' : 'Tidak Bergerak' ?>)</small>
</td>
<td><?= $a['jumlah'] ?></td>
<td><strong>Rp <?= number_format($a['nilai_perolehan'], 0, ',', '.') ?></strong></td> 
<td>
<?= !empty($a['tanggal_perolehan']) ? date('d/m/Y', strtotime($a['tanggal_perolehan'])) : '-' ?>
</td>
<td>
<span class="badge-status <?= $badge ?>"><?= $a['kondisi'] ?></span>
</td>
<td>

<div style="display:flex;gap:8px;">
<button type="button" class="btn btn-warning btn-sm action-btn"
onclick="openEditModal(
<?= $a['id'] ?>,
<?= $a['kategori_id'] ?>,
'<?= addslashes($a['nama_aset']) ?>',
'<?= $a['kondisi'] ?>',
<?= $a['jumlah'] ?>,
'<?= addslashes($a['satuan']) ?>',
<?= $a['nilai_perolehan'] ?>, 
'<?= $a['tanggal_perolehan'] ?>',
'<?= addslashes($a['keterangan']) ?>'
)">
<i class="bi bi-pencil"></i>
</button>
<a href="<?= BASE_PATH ?>/bmn/index.php?hapus=<?= $a['id'] ?>"
class="btn btn-danger btn-sm action-btn"
onclick="return confirm('Yakin ingin menghapus data ini?')">
<i class="bi bi-trash"></i>
</a>
</div>

</td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>
</div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<form method="POST">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="id" id="editId">

<div class="modal-header bg-warning">
<h5 class="modal-title">Edit Aset</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<div class="mb-3">
    <label>Kategori</label>
    <select name="kategori_id" id="editKategori" class="form-select" required>
        <?php foreach($semuaKategori as $k): ?>
            <option value="<?= $k['id'] ?>">
                <?= sanitize($k['nama_kategori']) ?> (<?= $k['jenis'] == 'bergerak' ? 'Bergerak' : 'Tidak Bergerak' ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-3">
<label>Nama Aset</label>
<input type="text" name="nama_aset" id="editNama" class="form-control" required>
</div>

<div class="row">
<div class="col-md-4 mb-3">
<label>Kondisi</label>
<select name="kondisi" id="editKondisi" class="form-select">
<option>Baik</option>
<option>Rusak Ringan</option>
<option>Rusak Berat</option>
</select>
</div>

<div class="col-md-4 mb-3">
<label>Jumlah</label>
<input type="number" name="jumlah" id="editJumlah" class="form-control">
</div>

<div class="col-md-4 mb-3">
<label>Satuan</label>
<input type="text" name="satuan" id="editSatuan" class="form-control">
</div>
</div>

<div class="mb-3">
<label>Harga Perolehan (Rp)</label>
<input type="number" name="nilai_perolehan" id="editNilai" class="form-control" step="any">
</div>

<div class="mb-3">
<label>Tanggal Perolehan</label>
<input type="date" name="tanggal_perolehan" id="editTanggal" class="form-control">
</div>

<div class="mb-3">
<label>Keterangan</label>
<textarea name="keterangan" id="editKet" class="form-control"></textarea>
</div>
</div>

<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
<button type="submit" class="btn btn-warning">Simpan Perubahan</button>
</div>
</form>
</div>
</div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content">
<form method="POST">
<input type="hidden" name="action" value="tambah">

<div class="modal-header bg-primary text-white">
<h5 class="modal-title">Tambah Aset</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<div class="row">
<div class="col-md-6 mb-3">
<label>Kategori</label>
<select name="kategori_id" id="kategoriSelect" class="form-select" required>
<option value="">-- Pilih --</option>
<?php foreach($semuaKategori as $k): ?>
<option value="<?= $k['id'] ?>">
    <?= sanitize($k['nama_kategori']) ?> (<?= $k['jenis'] == 'bergerak' ? 'Bergerak' : 'Tidak Bergerak' ?>)
</option>
<?php endforeach; ?>
<option value="baru">+ Tambah Kategori Baru</option>
</select>

<div id="kategoriBaruBox" style="display:none;margin-top:10px;">
    <div class="mb-2">
        <label>Kategori Baru</label>
        <input type="text" name="kategori_baru" class="form-control" placeholder="Masukkan kategori baru">
    </div>
    <div>
        <label>Jenis Kelompok Aset</label>
        <select name="jenis_aset" class="form-select">
            <option value="bergerak">Barang Bergerak</option>
            <option value="tidak_bergerak">Barang Tidak Bergerak</option>
        </select>
    </div>
</div>
</div>

<div class="col-md-6 mb-3">
<label>Kode Aset</label>
<input type="text" name="kode_aset" class="form-control" required>
</div>
</div>

<div class="mb-3">
<label>Nama Aset</label>
<input type="text" name="nama_aset" class="form-control" required>
</div>

<div class="row">
<div class="col-md-4 mb-3">
<label>Kondisi</label>
<select name="kondisi" class="form-select">
<option>Baik</option>
<option>Rusak Ringan</option>
<option>Rusak Berat</option>
</select>
</div>

<div class="col-md-4 mb-3">
<label>Jumlah</label>
<input type="number" name="jumlah" class="form-control" value="1">
</div>

<div class="col-md-4 mb-3">
<label>Satuan</label>
<input type="text" name="satuan" class="form-control" value="Unit">
</div>
</div>

<div class="mb-3">
<label>Harga Perolehan</label>
<input type="number" name="nilai_perolehan" class="form-control" value="0">
</div>

<div class="mb-3">
<label>Tanggal Perolehan</label>
<input type="date" name="tanggal_perolehan" class="form-control" value="<?= date('Y-m-d') ?>">
</div>

<div class="mb-3">
<label>Keterangan</label>
<textarea name="keterangan" class="form-control"></textarea>
</div>
</div>

<div class="modal-footer">
<button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
<button type="submit" class="btn btn-primary">Simpan</button>
</div>
</form>
</div>
</div>
</div>

<div class="modal fade" id="modalCetak" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="<?= BASE_PATH ?>/bmn/index.php" target="_blank">
                <input type="hidden" name="cetak" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Cetak Laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis Laporan</label>
                        <select name="jenis" class="form-select">
                            <option value="harian">Harian</option>
                            <option value="bulanan">Bulanan</option>
                            <option value="tahunan">Tahunan</option>
                            <option value="semua">Semua Data</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-printer"></i> Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function(){
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll('#mainTable tbody tr');
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});

function openEditModal(id, kategoriId, nama, kondisi, jumlah, satuan, nilai, tanggal, ket){
    document.getElementById('editId').value = id;
    document.getElementById('editKategori').value = kategoriId; 
    document.getElementById('editNama').value = nama;
    document.getElementById('editKondisi').value = kondisi;
    document.getElementById('editJumlah').value = jumlah;
    document.getElementById('editSatuan').value = satuan;
    document.getElementById('editNilai').value = nilai; 
    document.getElementById('editTanggal').value = tanggal;
    document.getElementById('editKet').value = ket;

    let modal = new bootstrap.Modal(document.getElementById('modalEdit'));
    modal.show();
}

document.getElementById('kategoriSelect').addEventListener('change', function(){
    let box = document.getElementById('kategoriBaruBox');
    if(this.value === 'baru'){
        box.style.display = 'block';
    }else{
        box.style.display = 'none';
    }
});
</script>

<?php renderFooter(); ?>