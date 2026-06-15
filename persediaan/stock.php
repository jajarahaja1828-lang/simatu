<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {

    $kode    = trim($_POST['kode_barang'] ?? '');
    $nama    = trim($_POST['nama_barang'] ?? '');
    $satuan  = trim($_POST['satuan'] ?? '');
    $stok    = (int)($_POST['stok'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');

    $jenis = $_POST['jenis_transaksi'] ?? 'masuk';

    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($kode && $nama && $satuan) {

        try {

            // INSERT BARANG
            Database::execute(
                'INSERT INTO barang_persediaan
                (
                    kode_barang,
                    nama_barang,
                    satuan,
                    stok,
                    tanggal
                )
                VALUES (?,?,?,?,?)',
                [
                    $kode,
                    $nama,
                    $satuan,
                    $stok,
                    $tanggal
                ]
            );

            // AMBIL ID BARANG
            $barang = Database::fetch(
                'SELECT id FROM barang_persediaan
                 WHERE kode_barang=?',
                [$kode]
            );

            $barangId = $barang['id'];

            // BARANG MASUK
            if ($jenis == 'masuk') {

                Database::execute(
                    'INSERT INTO transaksi_masuk
                    (
                        barang_id,
                        jumlah,
                        tanggal,
                        keterangan
                    )
                    VALUES (?,?,?,?)',
                    [
                        $barangId,
                        $stok,
                        $tanggal,
                        $keterangan ?: 'Barang masuk'
                    ]
                );

            }

            // BARANG KELUAR
            else {

                Database::execute(
                    'INSERT INTO transaksi_keluar
                    (
                        barang_id,
                        jumlah,
                        tanggal,
                        tujuan,
                        keterangan
                    )
                    VALUES (?,?,?,?,?)',
                    [
                        $barangId,
                        $stok,
                        $tanggal,
                        'Pengeluaran Barang',
                        $keterangan ?: 'Barang keluar'
                    ]
                );

            }

            flashSet('success', 'Barang berhasil ditambahkan.');

        } catch (PDOException $e) {

            flashSet('error', $e->getMessage());

        }

    }

    redirect('/persediaan/stock.php');
}/*
|--------------------------------------------------------------------------
| HANDLE EDIT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    $id        = (int)($_POST['id'] ?? 0);
    $nama      = trim($_POST['nama_barang'] ?? '');
    $satuan    = trim($_POST['satuan'] ?? '');
    $stokInput = (int)($_POST['stok'] ?? 0);
    $tanggal   = $_POST['tanggal'] ?? date('Y-m-d');

    $jenis     = $_POST['jenis_transaksi'] ?? 'masuk';

    $keterangan = trim(
        $_POST['keterangan'] ?? ''
    );

    if ($id && $nama) {

        $lama = Database::fetch(
            'SELECT * FROM barang_persediaan WHERE id=?',
            [$id]
        );

        if (!$lama) {

            flashSet('error', 'Data barang tidak ditemukan.');

            redirect('/persediaan/stock.php');

        }

        $stokSekarang = (int)$lama['stok'];

        /*
        |--------------------------------------------------------------------------
        | HITUNG STOCK
        |--------------------------------------------------------------------------
        */

        if ($jenis == 'masuk') {

            $stokBaru = $stokSekarang + $stokInput;

        } else {

            $stokBaru = $stokSekarang - $stokInput;

            if ($stokBaru < 0) {

                flashSet(
                    'error',
                    'Stock tidak mencukupi.'
                );

                redirect('/persediaan/stock.php');

            }

        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE BARANG
        |--------------------------------------------------------------------------
        */

        Database::execute(
            'UPDATE barang_persediaan
            SET
                nama_barang=?,
                satuan=?,
                stok=?,
                tanggal=?
            WHERE id=?',
            [
                $nama,
                $satuan,
                $stokBaru,
                $tanggal,
                $id
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | TRANSAKSI MASUK
        |--------------------------------------------------------------------------
        */

        if ($jenis == 'masuk') {

            Database::execute(
                'INSERT INTO transaksi_masuk
                (
                    barang_id,
                    jumlah,
                    tanggal,
                    keterangan
                )
                VALUES (?,?,?,?)',
                [
                    $id,
                    $stokInput,
                    $tanggal,
                    $keterangan ?: 'Barang masuk'
                ]
            );

        }

        /*
        |--------------------------------------------------------------------------
        | TRANSAKSI KELUAR
        |--------------------------------------------------------------------------
        */

        else {

            Database::execute(
                'INSERT INTO transaksi_keluar
                (
                    barang_id,
                    jumlah,
                    tanggal,
                    tujuan,
                    keterangan
                )
                VALUES (?,?,?,?,?)',
                [
                    $id,
                    $stokInput,
                    $tanggal,
                    'Pengeluaran Barang',
                    $keterangan ?: 'Barang keluar'
                ]
            );

        }

        flashSet(
            'success',
            'Stock berhasil diperbarui.'
        );

    }

    redirect('/persediaan/stock.php');
}
/*
|--------------------------------------------------------------------------
| HANDLE HAPUS
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    Database::execute(
        'DELETE FROM barang_persediaan WHERE id=?',
        [$id]
    );

    flashSet('success', 'Barang berhasil dihapus.');

    redirect('/persediaan/stock.php');
}

/*
|--------------------------------------------------------------------------
| HANDLE CETAK
|--------------------------------------------------------------------------
*/

if(isset($_GET['cetak'])){

    $filter = $_GET['filter'] ?? 'harian';

    $tanggal = $_GET['tanggal'] ?? date('Y-m-d');

    $bulan = $_GET['bulan'] ?? date('Y-m');

    // FILTER DATA
    if($filter == 'harian'){

        $barangsCetak = Database::fetchAll(
            "SELECT * FROM barang_persediaan
             WHERE tanggal = ?
             ORDER BY kode_barang ASC",
            [$tanggal]
        );

        $judul = 'Laporan Stock Barang';
        $subjudul = 'Tanggal : ' . date('d F Y', strtotime($tanggal));

    } else {

        $barangsCetak = Database::fetchAll(
            "SELECT * FROM barang_persediaan
             WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?
             ORDER BY kode_barang ASC",
            [$bulan]
        );

        $judul = 'Laporan Stock Barang';
        $subjudul = 'Bulan : ' . date('F Y', strtotime($bulan . '-01'));

    }
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Cetak Stock Barang</title>

    <style>

@page{
    size:A4 portrait;
    margin:20mm 15mm 20mm 15mm;
}

body{
    font-family:Arial,sans-serif;
    color:#222;
    font-size:13px;
    margin:0;
    padding:0;
}

.wrapper-print{
    width:100%;
}

/* HEADER */

.header{
    text-align:center;
    margin-bottom:20px;
}

.header h2{
    margin:0;
    font-size:22px;
    font-weight:bold;
}

.header p{
    margin-top:6px;
    color:#555;
    font-size:14px;
}

/* BUTTON */

.top-action{
    display:flex;
    gap:10px;
    margin-bottom:20px;
}

.btn{
    text-decoration:none;
    padding:10px 18px;
    border-radius:8px;
    font-size:14px;
    font-weight:600;
    display:inline-block;
}

.btn-back{
    background:#e5e7eb;
    color:#111827;
}

.btn-print{
    background:#2563eb;
    color:#fff;
    border:none;
    cursor:pointer;
}

/* TABLE */

table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
}

thead{
    display:table-header-group;
}

tfoot{
    display:table-footer-group;
}

tr{
    page-break-inside:avoid;
}

th{
    background:#1e3a8a;
    color:#fff;
    border:1px solid #cbd5e1;
    padding:10px;
    font-size:13px;
    text-align:center;
}

td{
    border:1px solid #cbd5e1;
    padding:8px 10px;
    font-size:13px;
}

.text-center{
    text-align:center;
}

/* STATUS */

.status-aman{
    color:#15803d;
    font-weight:600;
}

.status-rendah{
    color:#d97706;
    font-weight:600;
}

.status-kritis{
    color:#dc2626;
    font-weight:600;
}

/* TANDA TANGAN */

.ttd-wrapper{
    margin-top:50px;
    width:100%;
    page-break-inside:avoid;
}

.ttd-row{
    display:flex;
    justify-content:space-between;
    gap:50px;
}

.ttd-box{
    width:45%;
    text-align:center;
}

.ttd-bottom{
    margin-top:50px;
    text-align:center;
}

.ttd-jabatan{
    font-size:14px;
    margin-bottom:80px;
}

.ttd-nama{
    font-weight:bold;
    text-decoration:underline;
    font-size:14px;
}

.ttd-nip{
    font-size:12px;
    margin-top:4px;
}

/* PRINT */

@media print{

    body{
        padding:0;
    }

    .no-print{
        display:none !important;
    }

    .wrapper-print{
        width:100%;
    }

    table{
        page-break-inside:auto;
    }

    tr{
        page-break-inside:avoid;
        page-break-after:auto;
    }

    .ttd-wrapper{
        margin-top:40px;
    }

}

</style>
</head>

<body>
    <div class="wrapper-print">

<div class="header">

    <h2><?= $judul ?></h2>

    <p><?= $subjudul ?></p>

</div>

<div class="top-action no-print">

   <button onclick="window.location='<?= BASE_PATH ?>/persediaan/stock.php'">
    Kembali
</button>

    </a>

    <button onclick="window.print()"
            class="btn btn-print">

        🖨 Cetak Sekarang

    </button>

</div>

<table>

    <thead>

    <tr>

        <th width="5%">No</th>
        <th>Kode Barang</th>
        <th>Nama Barang</th>
        <th>Satuan</th>
        <th width="15%">Stok</th>
        <th width="18%">Status</th>

    </tr>

    </thead>

    <tbody>

    <?php if(empty($barangsCetak)): ?>

        <tr>

            <td colspan="6" class="text-center">

                Tidak ada data

            </td>

        </tr>

    <?php else: ?>

        <?php foreach($barangsCetak as $i => $b): ?>

            <?php

            $status = 'Aman';

            if($b['stok'] < 5){

                $status = 'Kritis';

            } elseif($b['stok'] < 10){

                $status = 'Rendah';

            }

            ?>

            <tr>

                <td class="text-center">
                    <?= $i + 1 ?>
                </td>

                <td>
                    <?= sanitize($b['kode_barang']) ?>
                </td>

                <td>
                    <?= sanitize($b['nama_barang']) ?>
                </td>

                <td>
                    <?= sanitize($b['satuan']) ?>
                </td>

                <td class="text-center">
                    <?= number_format($b['stok']) ?>
                </td>

              <td class="text-center">

    <?php if($status == 'Aman'): ?>

        <span class="status-aman">
            Aman
        </span>

    <?php elseif($status == 'Rendah'): ?>

        <span class="status-rendah">
            Rendah
        </span>

    <?php else: ?>

        <span class="status-kritis">
            Kritis
        </span>

    <?php endif; ?>

</td>

            </tr>

        <?php endforeach; ?>

    <?php endif; ?>

    </tbody>

</table>

<!-- TANDA TANGAN -->

<div class="ttd-wrapper">

    <!-- BARIS ATAS -->
    <div class="ttd-row">

        <!-- KIRI -->
        <div class="ttd-box">

            <div class="ttd-jabatan">
                Operator
            </div>

            <div class="ttd-space"></div>

            <div class="ttd-nama">
                Velia Irma Wionie
            </div>

            <div class="ttd-nip">
                NIP. 197710302009011004
            </div>

        </div>

        <!-- KANAN -->
        <div class="ttd-box">

            <div class="ttd-jabatan">
                Kaur Umum
            </div>

            <div class="ttd-space"></div>

            <div class="ttd-nama">
                ADE NOMI, S.Tr.A.P., M.H.
            </div>

            <div class="ttd-nip">
                NIP. 199112292010122001
            </div>

        </div>

    </div>

    <!-- BAWAH -->
    <div class="ttd-bottom">

        <div class="ttd-jabatan">
            Kasubag TU
        </div>

        <div class="ttd-space"></div>

        <div class="ttd-nama">
            DEVI SARITKA, A.Md.P., S.H., M.H
        </div>

        <div class="ttd-nip">
            NIP. 199112292010122001
        </div>

    </div>

</div>
</body>
</html>

<?php
exit;
}

/*
|--------------------------------------------------------------------------
| LOAD DATA
|--------------------------------------------------------------------------
*/

$barangs = Database::fetchAll(
    'SELECT * FROM barang_persediaan ORDER BY kode_barang ASC'
);

$totalStok = array_sum(array_column($barangs, 'stok'));

renderHeader('Stock Barang', 'persediaan-stock');
?>

<style>

.modal-custom{
    position:fixed;
    inset:0;
    z-index:99999;
    display:none;
    align-items:center;
    justify-content:center;
}

.modal-custom.show{
    display:flex;
}

.modal-overlay{
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.5);
}

.modal-box{
    position:relative;
    width:100%;
    max-width:560px;
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    z-index:2;
    box-shadow:0 20px 50px rgba(0,0,0,.25);
    animation:modalShow .2s ease;
}

@keyframes modalShow{

    from{
        opacity:0;
        transform:translateY(20px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }

}

.modal-header{
    padding:18px 22px;
    border-bottom:1px solid #eee;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.modal-title{
    font-size:18px;
    font-weight:700;
}

.modal-close{
    border:none;
    background:none;
    font-size:28px;
    cursor:pointer;
}

.modal-body{
    padding:22px;
}

.modal-footer{
    padding:18px 22px;
    border-top:1px solid #eee;
    display:flex;
    justify-content:flex-end;
    gap:10px;
}

.form-group{
    margin-bottom:16px;
}

.form-label{
    display:block;
    margin-bottom:6px;
    font-weight:600;
}

.form-control,
.form-select{
    width:100%;
    height:46px;
    border:1px solid #d1d5db;
    border-radius:10px;
    padding:0 14px;
    font-size:14px;
}

.btn-submit{
    border:none;
    background:#2563eb;
    color:#fff;
    height:44px;
    padding:0 18px;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
}

.btn-cancel{
    border:none;
    background:#e5e7eb;
    color:#111827;
    height:44px;
    padding:0 18px;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
}
.btn-icon{
    width:36px;
    height:36px;
    border:none;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    background:#eff6ff;
    color:#2563eb;
    font-size:16px;
    transition:all .2s ease;
}

.btn-icon:hover{
    background:#2563eb;
    color:#fff;
    transform:translateY(-2px);
}

.btn-icon.danger{
    background:#fef2f2;
    color:#dc2626;
}

.btn-icon.danger:hover{
    background:#dc2626;
    color:#fff;
}

</style>

<!-- HEADER -->

<div class="page-header"
     style="
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:20px;
        flex-wrap:wrap;
     ">

    <div>

        <div class="page-title">

            Stock Barang

        </div>

        <div class="page-subtitle">

            Barang Persediaan — <?= date('d/m/Y') ?>

        </div>

    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">

        <button type="button"
                class="btn-primary-custom"
                onclick="openModal('modalCetak')">

            <i class="bi bi-printer"></i>
            Cetak Laporan

        </button>

        <button type="button"
                class="btn-primary-custom"
                onclick="openModal('modalTambah')">

            <i class="bi bi-plus-lg"></i>
            Tambah Barang

        </button>

    </div>

</div>

<!-- STATS -->

<div style="
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:16px;
    margin-bottom:20px;
">

    <div class="stat-card">

        <div class="stat-icon blue">
            <i class="bi bi-box-seam-fill"></i>
        </div>

        <div class="stat-value">
            <?= count($barangs) ?>
        </div>

        <div class="stat-label">
            Total Jenis Barang
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon green">
            <i class="bi bi-layers-fill"></i>
        </div>

        <div class="stat-value">
            <?= number_format($totalStok) ?>
        </div>

        <div class="stat-label">
            Total Stock
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon orange">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>

        <div class="stat-value">

            <?= count(array_filter(
                $barangs,
                fn($b) => $b['stok'] < 10
            )) ?>

        </div>

        <div class="stat-label">
            Stock Rendah
        </div>

    </div>

</div>

<!-- TABLE -->

<div class="card">

    <div class="card-header">

        <div class="card-title">

            <i class="bi bi-list-ul me-2"></i>
            Daftar Stock Barang

        </div>

        <div class="search-bar">

            <i class="bi bi-search"></i>

            <input type="text"
                   id="searchInput"
                   placeholder="Cari barang...">

        </div>

    </div>

    <div class="table-wrapper">

        <table class="table" id="mainTable">

            <thead>

            <tr>

                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Stock</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>

            </tr>

            </thead>

            <tbody>

            <?php if(empty($barangs)): ?>

                <tr>

                    <td colspan="7" class="text-center">

                        Tidak ada data

                    </td>

                </tr>

            <?php else: ?>

                <?php foreach($barangs as $b): ?>

                    <?php $stok = (int)$b['stok']; ?>

                    <tr>

                        <td>
                            <?= sanitize($b['kode_barang']) ?>
                        </td>

                        <td>
                            <?= sanitize($b['nama_barang']) ?>
                        </td>

                        <td>
                            <?= sanitize($b['satuan']) ?>
                        </td>

                        <td>
                            <?= number_format($stok) ?>
                        </td>

                        <!-- TANGGAL -->

                        <td>

                            <?= !empty($b['tanggal'])
                                ? date('d/m/Y', strtotime($b['tanggal']))
                                : '-' ?>

                        </td>

                        <td>

                            <?php if($stok < 5): ?>

                                <span class="badge-custom"
                                      style="background:#fde8e8;color:red;">

                                    Kritis

                                </span>

                            <?php elseif($stok < 10): ?>

                                <span class="badge-custom"
                                      style="background:#fff3e0;color:#e67e22;">

                                    Rendah

                                </span>

                            <?php else: ?>

                                <span class="badge-custom badge-aktif">

                                    Aman

                                </span>

                            <?php endif; ?>

                        </td>

                     <td>

    <div style="display:flex;gap:6px;align-items:center;">

        <!-- BUTTON EDIT -->
       <button
    type="button"
    class="btn-icon"
    onclick="openEditModal(
        <?= (int)$b['id'] ?>,
        '<?= addslashes($b['kode_barang']) ?>',
        '<?= addslashes($b['nama_barang']) ?>',
        '<?= addslashes($b['satuan']) ?>',
        '<?= (int)$b['stok'] ?>',
        '<?= $b['tanggal'] ?>'
    )"
>
    <i class="bi bi-pencil-square"></i>
</button>
        <!-- BUTTON DELETE -->
        <button
            type="button"
            class="btn-icon danger"
            onclick="confirmDelete(
                '<?= BASE_PATH ?>/persediaan/stock.php?hapus=<?= $b['id'] ?>',
                '<?= addslashes($b['nama_barang']) ?>'
            )"
        >

            <i class="bi bi-trash"></i>

        </button>

    </div>

</td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<!-- MODAL CETAK -->

<div class="modal-custom" id="modalCetak">

    <div class="modal-overlay"
         onclick="closeModal('modalCetak')"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                Cetak Laporan

            </div>

            <button type="button"
                    class="modal-close"
                    onclick="closeModal('modalCetak')">

                &times;

            </button>

        </div>

        <form method="GET">

            <div class="modal-body">

                <div class="form-group">

                    <label class="form-label">

                        Jenis Laporan

                    </label>

                    <select name="filter"
                            id="filterCetak"
                            class="form-select">

                        <option value="harian">

                            Harian

                        </option>

                        <option value="bulanan">

                            Bulanan

                        </option>

                    </select>

                </div>

                <div class="form-group"
                     id="groupTanggal">

                    <label class="form-label">

                        Tanggal

                    </label>

                    <input type="date"
                           name="tanggal"
                           class="form-control"
                           value="<?= date('Y-m-d') ?>">

                </div>

                <div class="form-group"
                     id="groupBulan"
                     style="display:none;">

                    <label class="form-label">

                        Bulan

                    </label>

                    <input type="month"
                           name="bulan"
                           class="form-control"
                           value="<?= date('Y-m') ?>">

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn-cancel"
                        onclick="closeModal('modalCetak')">

                    Batal

                </button>

                <button type="submit"
                        name="cetak"
                        value="1"
                        class="btn-submit">

                    Cetak

                </button>

            </div>

        </form>

    </div>

</div>

<!-- MODAL EDIT -->
<div class="modal-custom" id="modalEdit">

    <div class="modal-overlay"
         onclick="closeModal('modalEdit')"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">
                <i class="bi bi-pencil-square"></i>
                Edit Stock Barang
            </div>

            <button type="button"
                    class="modal-close"
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

                <!-- JENIS TRANSAKSI -->
                <div class="form-group">

                    <label class="form-label">
                        Jenis Transaksi
                    </label>

                    <select name="jenis_transaksi"
                            class="form-control"
                            required>

                        <option value="masuk">
                            Barang Masuk
                        </option>

                        <option value="keluar">
                            Barang Keluar
                        </option>

                    </select>

                </div>

                <!-- ROW -->
                <div style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:16px;
                ">

                    <div class="form-group">

                        <label class="form-label">
                            Kode Barang
                        </label>

                        <input type="text"
                               id="editKode"
                               class="form-control"
                               readonly>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Satuan
                        </label>

                        <input type="text"
                               name="satuan"
                               id="editSatuan"
                               class="form-control"
                               required>

                    </div>

                </div>

                <!-- NAMA -->
                <div class="form-group">

                    <label class="form-label">
                        Nama Barang
                    </label>

                    <input type="text"
                           name="nama_barang"
                           id="editNama"
                           class="form-control"
                           required>

                </div>

                <!-- STOCK -->
                <div style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:16px;
                ">

                    <div class="form-group">

                        <label class="form-label">
                            Jumlah
                        </label>

                        <input type="number"
                               name="stok"
                               id="editStok"
                               class="form-control"
                               min="0"
                               required>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Tanggal
                        </label>

                        <input type="date"
                               name="tanggal"
                               id="editTanggal"
                               class="form-control"
                               required>

                    </div>

                </div>

                <!-- KETERANGAN -->
                <div class="form-group">

                    <label class="form-label">
                        Keterangan
                    </label>

                    <input type="text"
                           name="keterangan"
                           class="form-control"
                           placeholder="Contoh: Barang keluar ruangan TU">

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

                    Simpan

                </button>

            </div>

        </form>

    </div>

</div>

<!-- MODAL TAMBAH -->

<div class="modal-custom" id="modalTambah">

    <div class="modal-overlay"
         onclick="closeModal('modalTambah')"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">
                Tambah Barang
            </div>

            <button type="button"
                    class="modal-close"
                    onclick="closeModal('modalTambah')">

                &times;

            </button>

        </div>

        <form method="POST">

            <input type="hidden"
                   name="action"
                   value="tambah">

            <div class="modal-body">

                <!-- JENIS TRANSAKSI -->
                <div class="form-group">

                    <label class="form-label">
                        Jenis Transaksi
                    </label>

                    <select name="jenis_transaksi"
                            class="form-control"
                            required>

                        <option value="masuk">
                            Barang Masuk
                        </option>

                        <option value="keluar">
                            Barang Keluar
                        </option>

                    </select>

                </div>

                <!-- ROW -->
                <div style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:16px;
                ">

                    <!-- KODE -->
                    <div class="form-group">

                        <label class="form-label">
                            Kode Barang
                        </label>

                        <input type="text"
                               name="kode_barang"
                               class="form-control"
                               required>

                    </div>

                    <!-- SATUAN -->
                    <div class="form-group">

                        <label class="form-label">
                            Satuan
                        </label>

                        <input type="text"
                               name="satuan"
                               class="form-control"
                               required>

                    </div>

                </div>

                <!-- NAMA -->
                <div class="form-group">

                    <label class="form-label">
                        Nama Barang
                    </label>

                    <input type="text"
                           name="nama_barang"
                           class="form-control"
                           required>

                </div>

                <!-- JUMLAH -->
                <div class="form-group">

                    <label class="form-label">
                        Jumlah Stock
                    </label>

                    <input type="number"
                           name="stok"
                           class="form-control"
                           value="0"
                           min="0"
                           required>

                </div>

                <!-- KETERANGAN -->
                <div class="form-group">

                    <label class="form-label">
                        Keterangan
                    </label>

                    <input type="text"
                           name="keterangan"
                           class="form-control"
                           placeholder="Contoh: Penambahan stock">

                </div>

                <!-- TANGGAL -->
                <div class="form-group">

                    <label class="form-label">
                        Tanggal Barang
                    </label>

                    <input type="date"
                           name="tanggal"
                           class="form-control"
                           value="<?= date('Y-m-d') ?>"
                           required>

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

                    Simpan Barang

                </button>

            </div>

        </form>

    </div>

</div>
<script>

function openModal(id){

    let modal = document.getElementById(id);

    if(modal){

        modal.style.display = 'flex';

        setTimeout(() => {

            modal.classList.add('show');

        }, 10);

    }

}

function closeModal(id){

    let modal = document.getElementById(id);

    if(modal){

        modal.classList.remove('show');

        setTimeout(() => {

            modal.style.display = 'none';

        }, 200);

    }

}

function openEditModal(
    id,
    kode,
    nama,
    satuan,
    stok,
    tanggal
){

    document.getElementById('editId').value = id;
    document.getElementById('editKode').value = kode;
    document.getElementById('editNama').value = nama;
    document.getElementById('editSatuan').value = satuan;
    document.getElementById('editStok').value = stok;
    document.getElementById('editTanggal').value = tanggal;

    openModal('modalEdit');

}

function confirmDelete(url, nama){

    if(confirm('Yakin ingin menghapus "' + nama + '" ?')){

        window.location.href = url;

    }

}

const searchInput = document.getElementById('searchInput');

if(searchInput){

    searchInput.addEventListener('keyup', function(){

        let value = this.value.toLowerCase();

        let rows = document.querySelectorAll(
            '#mainTable tbody tr'
        );

        rows.forEach(row => {

            row.style.display =
                row.innerText.toLowerCase().includes(value)
                ? ''
                : 'none';

        });

    });

}

const filterCetak = document.getElementById('filterCetak');

if(filterCetak){

    filterCetak.addEventListener('change', function(){

        if(this.value === 'harian'){

            document.getElementById('groupTanggal')
                .style.display = 'block';

            document.getElementById('groupBulan')
                .style.display = 'none';

        } else {

            document.getElementById('groupTanggal')
                .style.display = 'none';

            document.getElementById('groupBulan')
                .style.display = 'block';

        }

    });

}

</script>

<?php renderFooter(); ?>