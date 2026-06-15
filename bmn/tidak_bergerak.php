<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| TAMBAH ASET
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {

    $kategoriId = (int)($_POST['kategori_id'] ?? 0);
    $kode       = trim($_POST['kode_aset'] ?? '');
    $nama       = trim($_POST['nama_aset'] ?? '');
    $kondisi    = $_POST['kondisi'] ?? 'Baik';
    $satuan     = trim($_POST['satuan'] ?? 'Unit');
    $jumlah     = (int)($_POST['jumlah'] ?? 1);

    $nilai = (float) str_replace(
        ['.', ','],
        ['', '.'],
        $_POST['nilai_perolehan'] ?? '0'
    );

    $tanggal = $_POST['tanggal_perolehan'] ?? null;

    if ($kategoriId && $kode && $nama) {

        Database::execute(
            'INSERT INTO bmn_aset
            (
                kategori_id,
                kode_aset,
                nama_aset,
                kondisi,
                satuan,
                jumlah,
                nilai_perolehan,
                tanggal_perolehan
            )
            VALUES (?,?,?,?,?,?,?,?)',
            [
                $kategoriId,
                $kode,
                $nama,
                $kondisi,
                $satuan,
                $jumlah,
                $nilai,
                $tanggal ?: null
            ]
        );

        flashSet('success', 'Aset berhasil ditambahkan.');
    }

    redirect('/bmn/tidak_bergerak.php');
}

/*
|--------------------------------------------------------------------------
| EDIT ASET
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    $id       = (int)($_POST['id'] ?? 0);
    $nama     = trim($_POST['nama_aset'] ?? '');
    $kondisi  = $_POST['kondisi'] ?? 'Baik';
    $jumlah   = (int)($_POST['jumlah'] ?? 1);
    $tanggal  = $_POST['tanggal_perolehan'] ?? null;

    if ($id && $nama) {

        Database::execute(
            'UPDATE bmn_aset
             SET
                nama_aset=?,
                kondisi=?,
                jumlah=?,
                tanggal_perolehan=?
             WHERE id=?',
            [
                $nama,
                $kondisi,
                $jumlah,
                $tanggal,
                $id
            ]
        );

        flashSet('success', 'Data berhasil diperbarui.');
    }

    redirect('/bmn/tidak_bergerak.php');
}

/*
|--------------------------------------------------------------------------
| HAPUS
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    Database::execute(
        'DELETE FROM bmn_aset WHERE id=?',
        [(int)$_GET['hapus']]
    );

    flashSet('success', 'Aset berhasil dihapus.');

    redirect('/bmn/tidak_bergerak.php');
}

/*
|--------------------------------------------------------------------------
| CETAK LAPORAN
|--------------------------------------------------------------------------
*/

if (isset($_GET['cetak'])) {

    $jenis   = $_GET['jenis'] ?? 'semua';
    $tanggal = $_GET['tanggal'] ?? date('Y-m-d');

    $where  = "";
    $params = [];

    /*
    |--------------------------------------------------------------------------
    | FILTER HARIAN
    |--------------------------------------------------------------------------
    */

    if ($jenis == 'harian') {

        $where = "AND DATE(a.tanggal_perolehan)=?";
        $params[] = $tanggal;
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER BULANAN
    |--------------------------------------------------------------------------
    */

    elseif ($jenis == 'bulanan') {

        $where = "AND MONTH(a.tanggal_perolehan)=MONTH(?)
                  AND YEAR(a.tanggal_perolehan)=YEAR(?)";

        $params[] = $tanggal;
        $params[] = $tanggal;
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER TAHUNAN
    |--------------------------------------------------------------------------
    */

    elseif ($jenis == 'tahunan') {

        $where = "AND YEAR(a.tanggal_perolehan)=YEAR(?)";

        $params[] = $tanggal;
    }

    /*
    |--------------------------------------------------------------------------
    | AMBIL DATA
    |--------------------------------------------------------------------------
    */

    $dataCetak = Database::fetchAll(
        "SELECT
            a.*,
            k.nama_kategori
        FROM bmn_aset a
        JOIN bmn_kategori k
            ON k.id = a.kategori_id
        WHERE k.jenis='tidak_bergerak'
        $where
        ORDER BY a.id DESC",
        $params
    );

?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">

<title>
    Cetak Laporan BMN Tidak Bergerak
</title>

<style>

body{
    font-family:Arial, sans-serif;
    margin:30px;
    color:#222;
}

.header{
    text-align:center;
    margin-bottom:25px;
}

.header h2{
    margin:0;
    font-size:28px;
    color:#1e3a8a;
}

.header p{
    margin-top:6px;
    color:#666;
    font-size:14px;
}

.top-action{
    margin-bottom:20px;
}

.btn{
    display:inline-block;
    padding:10px 18px;
    border-radius:8px;
    text-decoration:none;
    font-size:14px;
    font-weight:600;
    margin-right:8px;
}

.btn-back{
    background:#e5e7eb;
    color:#111827;
}

.btn-print{
    background:#2563eb;
    color:white;
}

.table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

.table th{
    background:#2563eb;
    color:white;
    padding:12px;
    border:1px solid #d1d5db;
    font-size:13px;
}

.table td{
    border:1px solid #d1d5db;
    padding:10px;
    font-size:13px;
}

.table tr:nth-child(even){
    background:#f9fafb;
}

.badge{
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}

.baik{
    background:#dcfce7;
    color:#166534;
}

.ringan{
    background:#fef9c3;
    color:#854d0e;
}

.berat{
    background:#fee2e2;
    color:#991b1b;
}

.footer{
    margin-top:80px;
    text-align:right;
}

@media print{

    .top-action{
        display:none;
    }

    body{
        margin:0;
        padding:20px;
    }
}

</style>

</head>

<body>

<div class="top-action">

    <a href="/simatu/public/bmn/tidak_bergerak.php"
       class="btn btn-back">
        Kembali
    </a>

    <button onclick="window.print()"
            class="btn btn-print">
        Cetak Sekarang
    </button>

</div>

<div class="header">

    <h2>
        LAPORAN BMN TIDAK BERGERAK
    </h2>

    <p>

        <?php

        if($jenis == 'harian'){
            echo "Laporan Harian";
        }
        elseif($jenis == 'bulanan'){
            echo "Laporan Bulanan";
        }
        elseif($jenis == 'tahunan'){
            echo "Laporan Tahunan";
        }
        else{
            echo "Semua Data";
        }

        ?>

    </p>

</div>

<table class="table">

<thead>

<tr>

    <th>No</th>
    <th>Kode</th>
    <th>Nama Aset</th>
    <th>Kategori</th>
    <th>Jumlah</th>
    <th>Satuan</th>
    <th>Kondisi</th>
    <th>Nilai Perolehan</th>
    <th>Tanggal</th>

</tr>

</thead>

<tbody>

<?php if(empty($dataCetak)): ?>

<tr>

    <td colspan="9" style="text-align:center;">
        Tidak ada data
    </td>

</tr>

<?php endif; ?>

<?php foreach($dataCetak as $i => $d): ?>

<tr>

    <td style="text-align:center;">
        <?= $i + 1 ?>
    </td>

    <td>
        <?= sanitize($d['kode_aset']) ?>
    </td>

    <td>
        <?= sanitize($d['nama_aset']) ?>
    </td>

    <td>
        <?= sanitize($d['nama_kategori']) ?>
    </td>

    <td style="text-align:center;">
        <?= number_format($d['jumlah']) ?>
    </td>

    <td>
        <?= sanitize($d['satuan']) ?>
    </td>

    <td>

        <?php

        $badge = 'baik';

        if($d['kondisi'] == 'Rusak Ringan'){
            $badge = 'ringan';
        }

        if($d['kondisi'] == 'Rusak Berat'){
            $badge = 'berat';
        }

        ?>

        <span class="badge <?= $badge ?>">

            <?= sanitize($d['kondisi']) ?>

        </span>

    </td>

    <td>

        <?= $d['nilai_perolehan'] > 0
            ? formatRupiah($d['nilai_perolehan'])
            : '-' ?>

    </td>

    <td>

        <?= !empty($d['tanggal_perolehan'])
            ? date('d/m/Y', strtotime($d['tanggal_perolehan']))
            : '-' ?>

    </td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<div style="
    margin-top:80px;
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:40px;
">

    <!-- TANDA TANGAN 1 -->
    <div style="
        width:30%;
        text-align:center;
    ">

        <div style="margin-bottom:80px;">
            Mengetahui,
            <br>
            Kepala Sub Bagian
        </div>

        <strong>
            ____________________
        </strong>

    </div>

    <!-- TANDA TANGAN 2 -->
    <div style="
        width:30%;
        text-align:center;
    ">

        <div style="margin-bottom:80px;">
            Pemeriksa Barang
        </div>

        <strong>
            ____________________
        </strong>

    </div>

    <!-- TANDA TANGAN 3 -->
    <div style="
        width:30%;
        text-align:center;
    ">

        <div style="margin-bottom:80px;">
            Tangerang,
            <?= date('d F Y') ?>
            <br>
            Administrator
        </div>

        <strong>
            ____________________
        </strong>

    </div>

</div>

</body>
</html>

<?php
exit;
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$kategoriList = Database::fetchAll(
    "SELECT *
     FROM bmn_kategori
     WHERE jenis='tidak_bergerak'
     ORDER BY kode"
);

$asets = Database::fetchAll(
    "SELECT
        a.*,
        k.nama_kategori
     FROM bmn_aset a
     JOIN bmn_kategori k
        ON k.id=a.kategori_id
     WHERE k.jenis='tidak_bergerak'
     ORDER BY k.kode, a.kode_aset"
);

$totalUnit  = array_sum(array_column($asets, 'jumlah'));
$totalNilai = array_sum(array_column($asets, 'nilai_perolehan'));

renderHeader(
    'BMN — Barang Tidak Bergerak',
    'bmn-tidak-bergerak'
);
?>

<div class="page-header"
     style="display:flex;align-items:flex-start;justify-content:space-between;">

    <div>

        <div class="page-title">
            BMN — Barang Tidak Bergerak
        </div>

        <div class="page-subtitle">
            Tanah, Bangunan, dan Infrastruktur Negara
        </div>

    </div>

    <div style="display:flex;gap:10px;">

        <button class="btn-primary-custom"
                onclick="openModal('modalCetak')">

            <i class="bi bi-printer"></i>
            Cetak Laporan

        </button>

        <button class="btn-primary-custom"
                onclick="openModal('modalTambah')">

            <i class="bi bi-plus-lg"></i>
            Tambah Aset

        </button>

    </div>

</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">

    <div class="stat-card">

        <div class="stat-icon blue">
            <i class="bi bi-building-fill"></i>
        </div>

        <div class="stat-value">
            <?= count($asets) ?>
        </div>

        <div class="stat-label">
            Jenis Aset
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon green">
            <i class="bi bi-geo-fill"></i>
        </div>

        <div class="stat-value">
            <?= number_format($totalUnit) ?>
        </div>

        <div class="stat-label">
            Total Unit / M2
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon purple">
            <i class="bi bi-cash-stack"></i>
        </div>

        <div class="stat-value" style="font-size:16px;">
            <?= formatRupiah($totalNilai) ?>
        </div>

        <div class="stat-label">
            Total Nilai Perolehan
        </div>

    </div>

</div>

<div class="card">

    <div class="card-header">

        <div class="card-title">
            Daftar Aset Tidak Bergerak
        </div>

        <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text"
                   id="searchInput"
                   placeholder="Cari aset...">
        </div>

    </div>

    <div class="table-wrapper">

        <table class="table" id="mainTable">

            <thead>

            <tr>

                <th>Kode</th>
                <th>Nama Aset</th>
                <th>Kategori</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Kondisi</th>
                <th>Nilai Perolehan</th>
                <th>Tanggal</th>
                <th>Aksi</th>

            </tr>

            </thead>

            <tbody>

            <?php foreach ($asets as $a): ?>

            <tr>

                <td>
                    <?= sanitize($a['kode_aset']) ?>
                </td>

                <td>
                    <strong>
                        <?= sanitize($a['nama_aset']) ?>
                    </strong>
                </td>

                <td>
                    <?= sanitize($a['nama_kategori']) ?>
                </td>

                <td>
                    <?= number_format($a['jumlah']) ?>
                </td>

                <td>
                    <?= sanitize($a['satuan']) ?>
                </td>

                <td>

                    <?php
                    $cls = match($a['kondisi']) {
                        'Baik' => 'badge-baik',
                        'Rusak Ringan' => 'badge-rusak-ringan',
                        default => 'badge-rusak-berat'
                    };
                    ?>

                    <span class="badge-custom <?= $cls ?>">
                        <?= $a['kondisi'] ?>
                    </span>

                </td>

                <td>

                    <?= $a['nilai_perolehan'] > 0
                        ? formatRupiah((float)$a['nilai_perolehan'])
                        : '-' ?>

                </td>

                <td>

                    <?= !empty($a['tanggal_perolehan'])
                        ? date('d/m/Y', strtotime($a['tanggal_perolehan']))
                        : '-' ?>

                </td>

                <td>

                    <div style="display:flex;gap:6px;">

                        <button class="btn-icon"
                            onclick="openEditModal(
                                <?= $a['id'] ?>,
                                '<?= addslashes(sanitize($a['nama_aset'])) ?>',
                                '<?= $a['kondisi'] ?>',
                                <?= $a['jumlah'] ?>,
                                '<?= $a['tanggal_perolehan'] ?>'
                            )">

                            <i class="bi bi-pencil"></i>

                        </button>

                        <button class="btn-icon danger"
                            onclick="confirmDelete(
                                '/bmn/tidak_bergerak.php?hapus=<?= $a['id'] ?>',
                                '<?= addslashes(sanitize($a['nama_aset'])) ?>'
                            )">

                            <i class="bi bi-trash"></i>

                        </button>

                    </div>

                </td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<!-- MODAL CETAK -->

<div class="modal-custom" id="modalCetak">

    <div class="modal-overlay"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                <i class="bi bi-printer me-2"></i>
                Cetak Laporan

            </div>

            <button class="modal-close"
                    onclick="closeModal('modalCetak')">
                &times;
            </button>

        </div>

        <form method="GET"
              action="/simatu/public/bmn/tidak_bergerak.php"
              target="_blank">

            <input type="hidden"
                   name="cetak"
                   value="1">

            <div class="modal-body">

                <div class="form-group">

                    <label class="form-label">
                        Jenis Laporan
                    </label>

                    <select name="jenis"
                            class="form-select">

                        <option value="harian">
                            Harian
                        </option>

                        <option value="bulanan">
                            Bulanan
                        </option>

                        <option value="tahunan">
                            Tahunan
                        </option>

                        <option value="semua">
                            Semua Data
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Tanggal
                    </label>

                    <input type="date"
                           name="tanggal"
                           class="form-control"
                           value="<?= date('Y-m-d') ?>">

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn-cancel"
                        onclick="closeModal('modalCetak')">

                    Batal

                </button>

                <button type="submit"
                        class="btn-submit">

                    <i class="bi bi-printer"></i>
                    Cetak

                </button>

            </div>

        </form>

    </div>

</div>

<!-- MODAL TAMBAH -->

<div class="modal-custom" id="modalTambah">

    <div class="modal-overlay"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                <i class="bi bi-plus-circle me-2"></i>
                Tambah Aset Tidak Bergerak

            </div>

            <button class="modal-close"
                    onclick="closeModal('modalTambah')">
                &times;
            </button>

        </div>

        <form method="POST">

            <input type="hidden"
                   name="action"
                   value="tambah">

            <div class="modal-body">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

                    <div class="form-group">

                        <label class="form-label">
                            Kategori
                        </label>

                        <select name="kategori_id"
                                class="form-select"
                                required>

                            <option value="">
                                -- Pilih Kategori --
                            </option>

                            <?php foreach ($kategoriList as $k): ?>

                            <option value="<?= $k['id'] ?>">

                                <?= sanitize($k['nama_kategori']) ?>

                            </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Kode Aset
                        </label>

                        <input type="text"
                               name="kode_aset"
                               class="form-control"
                               required>

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Nama Aset
                    </label>

                    <input type="text"
                           name="nama_aset"
                           class="form-control"
                           required>

                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">

                    <div class="form-group">

                        <label class="form-label">
                            Kondisi
                        </label>

                        <select name="kondisi"
                                class="form-select">

                            <option>Baik</option>
                            <option>Rusak Ringan</option>
                            <option>Rusak Berat</option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Satuan
                        </label>

                        <input type="text"
                               name="satuan"
                               class="form-control"
                               value="Unit">

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Jumlah
                        </label>

                        <input type="number"
                               name="jumlah"
                               class="form-control"
                               value="1">

                    </div>

                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

                    <div class="form-group">

                        <label class="form-label">
                            Nilai Perolehan
                        </label>

                        <input type="text"
                               name="nilai_perolehan"
                               class="form-control">

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Tanggal Perolehan
                        </label>

                        <input type="date"
                               name="tanggal_perolehan"
                               class="form-control">

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn-cancel"
                        onclick="closeModal('modalTambah')">

                    Batal

                </button>

                <button type="submit"
                        class="btn-submit">

                    <i class="bi bi-check-lg"></i>
                    Simpan

                </button>

            </div>

        </form>

    </div>

</div>

<!-- MODAL EDIT -->

<div class="modal-custom" id="modalEdit">

    <div class="modal-overlay"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                <i class="bi bi-pencil me-2"></i>
                Edit Aset

            </div>

            <button class="modal-close"
                    onclick="closeModal('modalEdit')">
                &times;
            </button>

        </div>

        <form method="POST">

            <input type="hidden"
                   name="action"
                   value="edit">

            <input type="hidden"
                   name="id"
                   id="editId">

            <div class="modal-body">

                <div class="form-group">

                    <label class="form-label">
                        Nama Aset
                    </label>

                    <input type="text"
                           name="nama_aset"
                           id="editNama"
                           class="form-control"
                           required>

                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">

                    <div class="form-group">

                        <label class="form-label">
                            Kondisi
                        </label>

                        <select name="kondisi"
                                id="editKondisi"
                                class="form-select">

                            <option>Baik</option>
                            <option>Rusak Ringan</option>
                            <option>Rusak Berat</option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Jumlah
                        </label>

                        <input type="number"
                               name="jumlah"
                               id="editJumlah"
                               class="form-control">

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Tanggal
                        </label>

                        <input type="date"
                               name="tanggal_perolehan"
                               id="editTanggal"
                               class="form-control">

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn-cancel"
                        onclick="closeModal('modalEdit')">

                    Batal

                </button>

                <button type="submit"
                        class="btn-submit">

                    <i class="bi bi-check-lg"></i>
                    Simpan

                </button>

            </div>

        </form>

    </div>

</div>

<script>

/*
|--------------------------------------------------------------------------
| MODAL
|--------------------------------------------------------------------------
*/

function openModal(id){

    const modal = document.getElementById(id);

    if(modal){
        modal.classList.add('show');
        modal.style.display = 'flex';
    }
}

function closeModal(id){

    const modal = document.getElementById(id);

    if(modal){
        modal.classList.remove('show');
        modal.style.display = 'none';
    }
}

/*
|--------------------------------------------------------------------------
| CLOSE SAAT KLIK OVERLAY
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.modal-overlay').forEach(function(el){

    el.addEventListener('click', function(){

        const modal = this.closest('.modal-custom');

        if(modal){
            modal.style.display = 'none';
        }
    });

});

/*
|--------------------------------------------------------------------------
| DELETE CONFIRM
|--------------------------------------------------------------------------
*/

function confirmDelete(url,nama){

    if(confirm('Yakin ingin menghapus ' + nama + ' ?')){

        window.location.href = url;
    }
}

/*
|--------------------------------------------------------------------------
| OPEN EDIT
|--------------------------------------------------------------------------
*/

function openEditModal(id,nama,kondisi,jumlah,tanggal){

    document.getElementById('editId').value       = id;
    document.getElementById('editNama').value     = nama;
    document.getElementById('editKondisi').value  = kondisi;
    document.getElementById('editJumlah').value   = jumlah;
    document.getElementById('editTanggal').value  = tanggal;

    openModal('modalEdit');
}

/*
|--------------------------------------------------------------------------
| SEARCH TABLE
|--------------------------------------------------------------------------
*/

const searchInput = document.getElementById('searchInput');

if(searchInput){

    searchInput.addEventListener('keyup', function(){

        let keyword = this.value.toLowerCase();

        let rows = document.querySelectorAll('#mainTable tbody tr');

        rows.forEach(function(row){

            let text = row.innerText.toLowerCase();

            row.style.display =
                text.includes(keyword)
                ? ''
                : 'none';
        });
    });
}


</script>

<?php renderFooter(); ?>