<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$filter  = $_GET['filter'] ?? 'all';
$tanggal = $_GET['tanggal'] ?? '';
$bulan   = $_GET['bulan'] ?? '';
$tahun   = $_GET['tahun'] ?? date('Y');

/*
|--------------------------------------------------------------------------
| QUERY
|--------------------------------------------------------------------------
*/

$where  = [];
$params = [];

/*
|--------------------------------------------------------------------------
| FILTER HARIAN
|--------------------------------------------------------------------------
*/

if ($filter === 'harian' && !empty($tanggal)) {

    $where[]  = "DATE(created_at) = ?";
    $params[] = $tanggal;
}

/*
|--------------------------------------------------------------------------
| FILTER BULANAN
|--------------------------------------------------------------------------
*/

if ($filter === 'bulanan' && !empty($bulan)) {

    $exp = explode('-', $bulan);

    if (count($exp) === 2) {

        $where[]  = "MONTH(created_at)=?";
        $where[]  = "YEAR(created_at)=?";

        $params[] = $exp[1];
        $params[] = $exp[0];
    }
}

/*
|--------------------------------------------------------------------------
| FILTER TAHUNAN
|--------------------------------------------------------------------------
*/

if ($filter === 'tahunan') {

    $where[]  = "YEAR(created_at)=?";
    $params[] = $tahun;
}

/*
|--------------------------------------------------------------------------
| SQL
|--------------------------------------------------------------------------
*/

$sql = "
SELECT *
FROM anggaran_history
";

if (!empty($where)) {

    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY created_at DESC";

$data = Database::fetchAll($sql, $params);

/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

$totalPagu       = 0;
$totalPegawai    = 0;
$totalBarang     = 0;
$totalRealisasi  = 0;
$totalSisa       = 0;

foreach ($data as $d) {

    $real = $d['realisasi_pegawai']
          + $d['realisasi_barang'];

    $sisa = $d['pagu_anggaran']
          - $real;

    $totalPagu      += $d['pagu_anggaran'];
    $totalPegawai   += $d['realisasi_pegawai'];
    $totalBarang    += $d['realisasi_barang'];
    $totalRealisasi += $real;
    $totalSisa      += $sisa;
}

/*
|--------------------------------------------------------------------------
| FORMAT RUPIAH
|--------------------------------------------------------------------------
*/

function rupiah($angka)
{
    return 'Rp ' .
        number_format($angka, 0, ',', '.');
}

/*
|--------------------------------------------------------------------------
| JUDUL FILTER
|--------------------------------------------------------------------------
*/

$judulFilter = 'Semua Data';

if ($filter === 'harian' && !empty($tanggal)) {

    $judulFilter =
        'Laporan Harian - ' .
        date('d F Y', strtotime($tanggal));
}

if ($filter === 'bulanan' && !empty($bulan)) {

    $judulFilter =
        'Laporan Bulanan - ' .
        $bulan;
}

if ($filter === 'tahunan') {

    $judulFilter =
        'Laporan Tahunan - ' .
        $tahun;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>
    Cetak Laporan Keuangan
</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{

    font-family:
    Inter,
    Arial,
    sans-serif;

    background:
    #f1f5f9;

    padding:30px;

    color:#0f172a;
}

/*
|--------------------------------------------------------------------------
| TOPBAR
|--------------------------------------------------------------------------
*/

.topbar{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:25px;

    gap:15px;

    flex-wrap:wrap;
}

.btn{

    border:none;

    padding:13px 22px;

    border-radius:14px;

    font-weight:700;

    cursor:pointer;

    font-size:14px;

    transition:.3s;

    display:flex;

    align-items:center;

    gap:10px;

    text-decoration:none;
}

.btn:hover{

    transform:translateY(-2px);
}

.btn-back{

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #1d4ed8
    );

    color:#fff;

    box-shadow:
    0 8px 20px rgba(37,99,235,.25);
}

.btn-print{

    background:
    linear-gradient(
    135deg,
    #0f172a,
    #1e293b
    );

    color:#fff;

    box-shadow:
    0 8px 20px rgba(15,23,42,.25);
}

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.header{

    background:#fff;

    border-radius:28px;

    padding:40px;

    text-align:center;

    margin-bottom:25px;

    border:1px solid #e2e8f0;

    position:relative;

    overflow:hidden;
}

.header::before{

    content:'';

    position:absolute;

    width:280px;

    height:280px;

    border-radius:50%;

    background:
    rgba(37,99,235,.05);

    top:-120px;

    right:-100px;
}

.logo-badge{

    width:90px;

    height:90px;

    margin:auto;

    margin-bottom:20px;

    border-radius:24px;

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #1d4ed8
    );

    display:flex;

    align-items:center;

    justify-content:center;

    color:#fff;

    font-size:40px;

    box-shadow:
    0 10px 30px rgba(37,99,235,.25);
}

.header h1{

    font-size:34px;

    font-weight:900;

    margin-bottom:10px;
}

.header p{

    color:#64748b;

    margin:4px 0;

    font-size:15px;
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

.filter-box{

    background:#fff;

    border-radius:22px;

    padding:22px;

    margin-bottom:25px;

    border:1px solid #e2e8f0;

    box-shadow:
    0 2px 10px rgba(0,0,0,.03);
}

.filter-title{

    font-size:13px;

    color:#64748b;

    margin-bottom:8px;

    font-weight:700;

    text-transform:uppercase;
}

.filter-value{

    font-size:24px;

    font-weight:800;
}

/*
|--------------------------------------------------------------------------
| MODERN STATS
|--------------------------------------------------------------------------
*/

.stats{

    display:grid;

    grid-template-columns:
    repeat(auto-fit,minmax(260px,1fr));

    gap:22px;

    margin-bottom:30px;
}

.stat-card{

    position:relative;

    overflow:hidden;

    border-radius:26px;

    padding:28px;

    min-height:150px;

    display:flex;

    align-items:center;

    gap:18px;

    color:#fff;

    box-shadow:
    0 15px 35px rgba(0,0,0,.08);

    transition:.3s;
}

.stat-card:hover{

    transform:translateY(-5px);
}

.stat-card::before{

    content:'';

    position:absolute;

    width:170px;

    height:170px;

    border-radius:50%;

    background:
    rgba(255,255,255,.08);

    top:-50px;

    right:-50px;
}

.stat-card:nth-child(1){

    background:
    linear-gradient(
    135deg,
    #2563eb,
    #1d4ed8
    );
}

.stat-card:nth-child(2){

    background:
    linear-gradient(
    135deg,
    #059669,
    #047857
    );
}

.stat-card:nth-child(3){

    background:
    linear-gradient(
    135deg,
    #7c3aed,
    #6d28d9
    );
}

.stat-card:nth-child(4){

    background:
    linear-gradient(
    135deg,
    #0f172a,
    #1e293b
    );
}

.stat-icon{

    width:72px;

    height:72px;

    border-radius:20px;

    background:
    rgba(255,255,255,.15);

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:30px;

    flex-shrink:0;

    backdrop-filter:blur(4px);
}

.stat-title{

    font-size:13px;

    letter-spacing:1px;

    font-weight:700;

    opacity:.9;

    margin-bottom:8px;
}

.stat-value{

    font-size:28px;

    font-weight:900;

    line-height:1.4;

    word-break:break-word;
}

/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

.table-wrapper{

    background:#fff;

    border-radius:26px;

    overflow:hidden;

    border:1px solid #e2e8f0;

    box-shadow:
    0 4px 18px rgba(0,0,0,.04);

    margin-top:20px;
}

table{

    width:100%;

    border-collapse:collapse;

    table-layout:fixed;
}

thead{

    background:
    linear-gradient(
    135deg,
    #0f172a,
    #1e293b
    );

    color:#fff;
}

th{

    padding:12px;

    text-align:center;

    font-size:11px;

    font-weight:700;

    white-space:normal;

    word-break:break-word;

    line-height:1.4;
}

td{

    padding:12px;

    border-top:1px solid #f1f5f9;

    font-size:10px;

    text-align:center;

    white-space:normal;

    word-break:break-word;

    line-height:1.5;
}

tbody tr:nth-child(even){

    background:#f8fafc;
}

tbody tr:hover{

    background:#eef4ff;
}

/*
|--------------------------------------------------------------------------
| RESPONSIVE TABLE
|--------------------------------------------------------------------------
*/

.table-responsive{

    width:100%;

    overflow-x:auto;
}
/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

.footer{

    margin-top:60px;

    text-align:right;

    color:#334155;
}

.signature{

    margin-top:90px;

    font-weight:800;
}

/*
|--------------------------------------------------------------------------
| PRINT
|--------------------------------------------------------------------------
*/

@media print{

    @page{

        size:landscape;

        margin:10mm;
    }

    .topbar{
        display:none;
    }

    body{

        background:#fff;

        padding:0;
    }

    .header,
    .filter-box,
    .table-wrapper{

        box-shadow:none;
    }

    .table-wrapper{

        overflow:visible;
    }

    table{

        width:100%;

        table-layout:fixed;
    }

    th{

        font-size:10px;

        padding:8px;
    }

    td{

        font-size:9px;

        padding:8px;
    }

}
/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media(max-width:900px){

    body{
        padding:15px;
    }

    .stats{
        grid-template-columns:1fr;
    }

    .header{
        padding:30px 20px;
    }

    .header h1{
        font-size:28px;
    }

    .filter-value{
        font-size:20px;
    }

    .stat-value{
        font-size:24px;
    }

}

</style>

</head>

<body>

<!-- TOPBAR -->

<div class="topbar">

<button
    class="btn btn-back"
    onclick="window.location.href='index.php'">

    <i class="bi bi-arrow-left"></i>
    Kembali

</button>

<button
    class="btn btn-print"
    onclick="window.print()">

    <i class="bi bi-printer-fill"></i>
    Cetak

</button>

</div>

<!-- HEADER -->

<div class="header">

<div class="logo-badge">

    <i class="bi bi-bank2"></i>

</div>

<h1>
LAPORAN KEUANGAN
</h1>

<p>
Sistem Management Tata Usaha
</p>

<p>
Balai Pemasyarakatan Kelas I Jakarta Selatan
</p>

</div>

<!-- FILTER -->

<div class="filter-box">

<div class="filter-title">
Jenis Laporan
</div>

<div class="filter-value">
<?= $judulFilter ?>
</div>

</div>

<!-- TABLE -->

<div class="table-wrapper">

<table>

<thead>

<tr>

<th>No</th>
<th>Tahun</th>
<th>Tanggal</th>
<th>Pagu</th>
<th>Pegawai</th>
<th>Barang</th>
<th>Realisasi</th>
<th>Sisa</th>

</tr>

</thead>

<tbody>

<?php if(count($data) > 0): ?>

<?php foreach($data as $i => $d): ?>

<?php

$real = $d['realisasi_pegawai']
      + $d['realisasi_barang'];

$sisa = $d['pagu_anggaran']
      - $real;

?>

<tr>

<td><?= $i + 1 ?></td>

<td>
<?= $d['tahun'] ?>
</td>

<td>
<?= date('d M Y H:i', strtotime($d['created_at'])) ?>
</td>

<td>
<?= rupiah($d['pagu_anggaran']) ?>
</td>

<td>
<?= rupiah($d['realisasi_pegawai']) ?>
</td>

<td>
<?= rupiah($d['realisasi_barang']) ?>
</td>

<td>
<?= rupiah($real) ?>
</td>

<td>
<?= rupiah($sisa) ?>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="8" style="text-align:center;padding:40px;">

Tidak ada data ditemukan

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

<!-- FOOTER -->

<div class="footer">

<div>

Jakarta,
<?= date('d F Y') ?>

</div>

<div class="signature">

<strong>
Administrator
</strong>

</div>

</div>

</body>
</html>