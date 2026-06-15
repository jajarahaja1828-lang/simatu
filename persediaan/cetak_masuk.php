<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$filter  = $_GET['filter'] ?? 'semua';
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$bulan   = $_GET['bulan'] ?? date('Y-m');

/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$query = "
SELECT 
    tm.*,
    bp.kode_barang,
    bp.nama_barang,
    bp.satuan
FROM transaksi_masuk tm
JOIN barang_persediaan bp 
    ON bp.id = tm.barang_id
WHERE 1=1
";

$params = [];

/*
|--------------------------------------------------------------------------
| FILTER HARIAN
|--------------------------------------------------------------------------
*/

if ($filter === 'harian') {

    $query .= " AND DATE(tm.tanggal) = ?";
    $params[] = $tanggal;
}

/*
|--------------------------------------------------------------------------
| FILTER BULANAN
|--------------------------------------------------------------------------
*/

if ($filter === 'bulanan') {

    $query .= " AND DATE_FORMAT(tm.tanggal,'%Y-%m') = ?";
    $params[] = $bulan;
}

$query .= " ORDER BY tm.tanggal DESC";

$data = Database::fetchAll($query, $params);

/*
|--------------------------------------------------------------------------
| JUDUL
|--------------------------------------------------------------------------
*/

$judul = "LAPORAN BARANG MASUK";

if ($filter === 'harian') {

    $judul .= "<br>Tanggal : " .
        date('d F Y', strtotime($tanggal));
}

if ($filter === 'bulanan') {

    $judul .= "<br>Bulan : " .
        date('F Y', strtotime($bulan . '-01'));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Barang Masuk</title>

<style>

body{
    font-family:Arial, sans-serif;
    margin:30px;
    color:#111;
}

/* =======================================================
   HEADER
======================================================= */

.judul{
    text-align:center;
    margin-bottom:25px;
}

.judul h1{
    margin:0;
    font-size:30px;
}

.judul p{
    margin-top:8px;
    font-size:16px;
    color:#555;
}

/* =======================================================
   BUTTON
======================================================= */

.button-area{
    margin-bottom:20px;
}

.btn{
    display:inline-block;
    padding:10px 18px;
    text-decoration:none;
    border-radius:6px;
    font-size:14px;
    font-weight:bold;
}

.btn-back{
    background:#e5e7eb;
    color:#111;
}

.btn-print{
    background:#2563eb;
    color:#fff;
    margin-left:10px;
}

/* =======================================================
   TABLE
======================================================= */

table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}

table th{
    background:#1e3a8a;
    color:#fff;
    padding:12px;
    border:1px solid #cbd5e1;
    font-size:14px;
}

table td{
    border:1px solid #cbd5e1;
    padding:10px;
    font-size:14px;
}

.text-center{
    text-align:center;
}

.text-danger{
    color:red;
    font-weight:bold;
}

/* =======================================================
   TTD
======================================================= */

.ttd-wrapper{
    margin-top:80px;
}

.ttd-top{
    display:flex;
    justify-content:space-between;
}

.ttd-bottom{
    margin-top:70px;
    text-align:center;
}

.ttd-box{
    width:35%;
    text-align:center;
}

.ttd-space{
    height:80px;
}

.ttd-name{
    font-weight:bold;
    text-decoration:underline;
}

.ttd-nip{
    font-size:14px;
}

/* =======================================================
   PRINT
======================================================= */

@media print{

    .button-area{
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

<!-- =======================================================
     BUTTON
======================================================= -->

<div class="button-area">

    <a href="masuk.php" class="btn btn-back">
        Kembali
    </a>

    <button onclick="window.print()" class="btn btn-print">
        Cetak Sekarang
    </button>

</div>

<!-- =======================================================
     HEADER
======================================================= -->

<div class="judul">

    <h1>Laporan Barang Masuk</h1>

    <?php if ($filter === 'harian'): ?>

        <p>
            Tanggal :
            <?= date('d F Y', strtotime($tanggal)) ?>
        </p>

    <?php elseif ($filter === 'bulanan'): ?>

        <p>
            Bulan :
            <?= date('F Y', strtotime($bulan . '-01')) ?>
        </p>

    <?php else: ?>

        <p>Semua Data Barang Masuk</p>

    <?php endif; ?>

</div>

<!-- =======================================================
     TABLE
======================================================= -->

<table>

    <thead>

        <tr>

            <th width="5%">No</th>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th width="10%">Jumlah</th>
            <th width="12%">Satuan</th>
            <th width="15%">Tanggal</th>
            <th>Keterangan</th>

        </tr>

    </thead>

    <tbody>

    <?php if(empty($data)): ?>

        <tr>

            <td colspan="7" class="text-center">
                Tidak ada data
            </td>

        </tr>

    <?php else: ?>

        <?php foreach($data as $i => $d): ?>

        <tr>

            <td class="text-center">
                <?= $i + 1 ?>
            </td>

            <td>
                <?= sanitize($d['kode_barang']) ?>
            </td>

            <td>
                <?= sanitize($d['nama_barang']) ?>
            </td>

            <td class="text-center">
                <?= number_format($d['jumlah']) ?>
            </td>

            <td class="text-center">
                <?= sanitize($d['satuan']) ?>
            </td>

            <td class="text-center">
                <?= date('d-m-Y', strtotime($d['tanggal'])) ?>
            </td>

            <td>
                <?= sanitize($d['keterangan'] ?: '-') ?>
            </td>

        </tr>

        <?php endforeach; ?>

    <?php endif; ?>

    </tbody>

</table>

<!-- =======================================================
     TTD
======================================================= -->

<div class="ttd-wrapper">

    <div class="ttd-top">

        <div class="ttd-box">

            <div>Operator</div>

            <div class="ttd-space"></div>

            <div class="ttd-name">
                VELIA IRMA WONLELE
            </div>

            <div class="ttd-nip">
                NIP. 199207192012122001
            </div>

        </div>

        <div class="ttd-box">

            <div>Kaur Umum</div>

            <div class="ttd-space"></div>

            <div class="ttd-name">
                ADE NOMI, S.Tr.PAS., M.H.
            </div>

            <div class="ttd-nip">
                NIP. 199409142012122001
            </div>

        </div>

    </div>

    <div class="ttd-bottom">

        <div>Kasubag TU</div>

        <div class="ttd-space"></div>

        <div class="ttd-name">
            DEVI SARTIKA, A.Md.P., S.H., M.H.
        </div>

        <div class="ttd-nip">
            NIP. 199112292010122001
        </div>

    </div>

</div>

</body>
</html>