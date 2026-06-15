<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| STATS
|--------------------------------------------------------------------------
*/

$totalJenisBarang = Database::fetch(
    'SELECT COUNT(*) as cnt FROM barang_persediaan'
)['cnt'] ?? 0;

$totalAset = Database::fetch(
    'SELECT COUNT(*) as cnt FROM bmn_aset'
)['cnt'] ?? 0;

$totalPegawai = Database::fetch(
    'SELECT COUNT(*) as cnt FROM pegawai WHERE status = "Aktif"'
)['cnt'] ?? 0;

$anggaran = Database::fetch(
    'SELECT * FROM anggaran 
     WHERE tahun = YEAR(NOW()) 
     ORDER BY id DESC 
     LIMIT 1'
);

$pagu            = $anggaran['pagu_anggaran'] ?? 0;
$realPegawai     = $anggaran['realisasi_pegawai'] ?? 0;
$realBarang      = $anggaran['realisasi_barang'] ?? 0;
$totalRealisasi  = $realPegawai + $realBarang;

$serapan = $pagu > 0
    ? round(($totalRealisasi / $pagu) * 100, 1)
    : 0;

$sisaAnggaran = $pagu - $totalRealisasi;

/*
|--------------------------------------------------------------------------
| RECENT BARANG MASUK
|--------------------------------------------------------------------------
*/

$recentMasuk = Database::fetchAll(
    'SELECT tm.*, bp.nama_barang, bp.satuan
     FROM transaksi_masuk tm
     JOIN barang_persediaan bp
        ON bp.id = tm.barang_id
     ORDER BY tm.created_at DESC
     LIMIT 5'
);

/*
|--------------------------------------------------------------------------
| RECENT KENAIKAN PANGKAT
|--------------------------------------------------------------------------
*/

$recentKP = Database::fetchAll(
    'SELECT kp.*, p.nama, p.jabatan
     FROM kenaikan_pangkat kp
     JOIN pegawai p
        ON p.id = kp.pegawai_id
     ORDER BY kp.created_at DESC
     LIMIT 5'
);

/*
|--------------------------------------------------------------------------
| LOW STOCK
|--------------------------------------------------------------------------
*/

$lowStock = Database::fetchAll(
    'SELECT *
     FROM barang_persediaan
     WHERE stok < 10
     ORDER BY stok ASC
     LIMIT 5'
);

renderHeader('Dashboard', 'dashboard');
?>

<!-- GOOGLE FONT -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

/* =========================================================
   MODERN INTERNATIONAL UI
========================================================= */

body{
    background:#f4f7fb;
    font-family:'Inter',sans-serif;
    color:#1e293b;
}

/* PAGE */
.page-header{
    margin-bottom:24px;
}

.page-title{
    font-size:32px;
    font-weight:700;
    color:#0f172a;
    margin-bottom:6px;
}

.page-subtitle{
    font-size:14px;
    color:#64748b;
}

/* CARD */
.card{
    background:#ffffff;
    border-radius:18px;
    border:1px solid #e2e8f0;
    box-shadow:
        0 4px 12px rgba(15,23,42,0.04);
    overflow:hidden;
    transition:all .25s ease;
}

.card:hover{
    transform:translateY(-2px);
    box-shadow:
        0 10px 24px rgba(15,23,42,0.08);
}

.card-header{
    padding:18px 22px;
    border-bottom:1px solid #eef2f7;
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#fff;
}

.card-title{
    font-size:15px;
    font-weight:700;
    color:#0f172a;
    display:flex;
    align-items:center;
}

.card-body{
    padding:22px;
}

/* STAT CARDS */
.stat-cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:18px;
    margin-bottom:24px;
}

.stat-card{
    background:#fff;
    border-radius:20px;
    padding:24px;
    border:1px solid #e2e8f0;
    position:relative;
    overflow:hidden;
    transition:all .25s ease;
    box-shadow:
        0 4px 12px rgba(15,23,42,0.04);
}

.stat-card:hover{
    transform:translateY(-4px);
    box-shadow:
        0 14px 28px rgba(15,23,42,0.08);
}

.stat-icon{
    width:56px;
    height:56px;
    border-radius:16px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    margin-bottom:18px;
}

.stat-icon.blue{
    background:#dbeafe;
    color:#2563eb;
}

.stat-icon.green{
    background:#dcfce7;
    color:#16a34a;
}

.stat-icon.orange{
    background:#ffedd5;
    color:#ea580c;
}

.stat-icon.purple{
    background:#f3e8ff;
    color:#9333ea;
}

.stat-value{
    font-size:38px;
    font-weight:800;
    line-height:1;
    margin-bottom:10px;
    color:#0f172a;
}

.stat-label{
    font-size:13px;
    color:#64748b;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.5px;
}

/* BUTTON */
.btn-primary-custom{
    background:linear-gradient(
        135deg,
        #2563eb,
        #1d4ed8
    );
    color:#fff;
    border:none;
    border-radius:10px;
    padding:8px 16px;
    font-size:13px;
    font-weight:600;
    text-decoration:none;
    transition:.2s ease;
    box-shadow:
        0 4px 10px rgba(37,99,235,.2);
}

.btn-primary-custom:hover{
    transform:translateY(-1px);
    color:#fff;
    box-shadow:
        0 8px 18px rgba(37,99,235,.3);
}

/* TABLE */
.table-wrapper{
    overflow-x:auto;
}

.table{
    width:100%;
    border-collapse:collapse;
}

.table thead{
    background:#f8fafc;
}

.table th{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:#64748b;
    padding:16px 18px;
    font-weight:700;
    border-bottom:1px solid #e2e8f0;
}

.table td{
    padding:16px 18px;
    border-bottom:1px solid #f1f5f9;
    font-size:14px;
    color:#1e293b;
}

.table tbody tr{
    transition:.2s ease;
}

.table tbody tr:hover{
    background:#f8fafc;
}

/* EMPTY STATE */
.empty-state{
    text-align:center;
    padding:40px;
}

.empty-state i{
    font-size:42px;
    margin-bottom:12px;
}

.empty-state p{
    color:#64748b;
    font-size:14px;
}

/* CHART */
.chart-container{
    position:relative;
}

/* RESPONSIVE */
@media(max-width:768px){

    .page-title{
        font-size:24px;
    }

    .card-header{
        flex-direction:column;
        gap:10px;
        align-items:flex-start;
    }

    .table th,
    .table td{
        white-space:nowrap;
    }
}

</style>

<div class="page-header">

    <div class="page-title">
        Dashboard
    </div>

    <div class="page-subtitle">
        Berikut adalah ringkasan informasi Tata Usaha —
        <?= date('d F Y') ?>
    </div>

</div>

<!-- STAT CARDS -->
<div class="stat-cards">

    <div class="stat-card">

        <div class="stat-icon blue">
            <i class="bi bi-box-seam-fill"></i>
        </div>

        <div class="stat-value">
            <?= number_format($totalJenisBarang) ?>
        </div>

        <div class="stat-label">
            Jenis Barang Persediaan
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon green">
            <i class="bi bi-building-fill"></i>
        </div>

        <div class="stat-value">
            <?= number_format($totalAset) ?>
        </div>

        <div class="stat-label">
            Total Aset BMN
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon orange">
            <i class="bi bi-graph-up-arrow"></i>
        </div>

        <div class="stat-value">
            <?= $serapan ?>%
        </div>

        <div class="stat-label">
            Serapan Anggaran <?= date('Y') ?>
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon purple">
            <i class="bi bi-people-fill"></i>
        </div>

        <div class="stat-value">
            <?= number_format($totalPegawai) ?>
        </div>

        <div class="stat-label">
            Pegawai Aktif
        </div>

    </div>

</div>

<!-- TABLE -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">

    <!-- BARANG MASUK -->
    <div class="card">

        <div class="card-header">

            <div class="card-title">
                <i class="bi bi-box-arrow-in-down me-2"
                   style="color:var(--success);"></i>

                Barang Masuk Terbaru
            </div>

            <a href="<?= BASE_PATH ?>/persediaan/masuk.php"
               class="btn-primary-custom"
               style="font-size:12px;padding:5px 12px;">

                Lihat Semua
            </a>

        </div>

        <div class="table-wrapper">

            <table class="table">

                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($recentMasuk)): ?>

                    <tr>
                        <td colspan="3"
                            class="text-center"
                            style="color:var(--text-muted);padding:20px;">

                            Belum ada data
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($recentMasuk as $r): ?>

                    <tr>

                        <td>
                            <?= sanitize($r['nama_barang']) ?>
                        </td>

                        <td>
                            <span style="color:var(--success);font-weight:600;">
                                +<?= $r['jumlah'] ?>
                            </span>

                            <?= sanitize($r['satuan']) ?>
                        </td>

                        <td>
                            <?= date('d/m/Y', strtotime($r['tanggal'])) ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <!-- STOK RENDAH -->
    <div class="card">

        <div class="card-header">

            <div class="card-title">
                <i class="bi bi-exclamation-triangle-fill me-2"
                   style="color:orange;"></i>

                Stok Barang Rendah
            </div>

            <a href="<?= BASE_PATH ?>/persediaan/stock.php"
               class="btn-primary-custom"
               style="font-size:12px;padding:5px 12px;">

                Kelola Stok
            </a>

        </div>

        <div class="table-wrapper">

            <?php if (empty($lowStock)): ?>

                <div class="empty-state">

                    <i class="bi bi-check-circle-fill"
                       style="color:#22c55e;"></i>

                    <p>
                        Semua stok dalam kondisi aman
                    </p>

                </div>

            <?php else: ?>

            <table class="table">

                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Stok</th>
                        <th>Satuan</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($lowStock as $s): ?>

                    <tr>

                        <td>
                            <?= sanitize($s['nama_barang']) ?>
                        </td>

                        <td>
                            <span style="color:red;font-weight:700;">
                                <?= $s['stok'] ?>
                            </span>
                        </td>

                        <td style="color:#64748b;">
                            <?= sanitize($s['satuan']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

            <?php endif; ?>

        </div>

    </div>

</div>

<!-- KENAIKAN PANGKAT -->
<div class="card">

    <div class="card-header">

        <div class="card-title">
            <i class="bi bi-arrow-up-circle-fill me-2"
               style="color:#2563eb;"></i>

            Data Terbaru Kenaikan Pangkat
        </div>

        <a href="<?= BASE_PATH ?>/kepegawaian/kenaikan_pangkat.php"
           class="btn-primary-custom"
           style="font-size:12px;padding:5px 12px;">

            Lihat Semua
        </a>

    </div>

    <div class="table-wrapper">

        <table class="table">

            <thead>
                <tr>
                    <th>Nama Pegawai</th>
                    <th>Jabatan</th>
                    <th>Pangkat Lama</th>
                    <th>Pangkat Baru</th>
                    <th>Tanggal Efektif</th>
                    <th>No. SK</th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($recentKP)): ?>

                <tr>
                    <td colspan="6"
                        class="text-center"
                        style="padding:20px;color:#64748b;">

                        Belum ada data
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($recentKP as $kp): ?>

                <tr>

                    <td>
                        <strong>
                            <?= sanitize($kp['nama']) ?>
                        </strong>
                    </td>

                    <td>
                        <?= sanitize($kp['jabatan']) ?>
                    </td>

                    <td>
                        <?= sanitize($kp['pangkat_lama'] ?? '-') ?>
                    </td>

                    <td>
                        <span style="color:#16a34a;font-weight:600;">
                            <?= sanitize($kp['pangkat_baru']) ?>
                        </span>
                    </td>

                    <td>
                        <?= date('d/m/Y', strtotime($kp['tanggal_efektif'])) ?>
                    </td>

                    <td>
                        <?= sanitize($kp['no_sk'] ?? '-') ?>
                    </td>

                </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php renderFooter(); ?>