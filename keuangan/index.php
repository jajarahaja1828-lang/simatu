<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| HANDLE FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | UPDATE DATA
    |--------------------------------------------------------------------------
    */

    if ($action === 'update') {

        $tahun = (int) ($_POST['tahun'] ?? date('Y'));

        $pagu = (float) str_replace('.', '', $_POST['pagu_anggaran'] ?? 0);

        $pegawai = (float) str_replace('.', '', $_POST['realisasi_pegawai'] ?? 0);

        $barang = (float) str_replace('.', '', $_POST['realisasi_barang'] ?? 0);

        $user = currentUser()['nama'] ?? 'Administrator';

        $cek = Database::fetch(
            "SELECT * FROM anggaran WHERE tahun=?",
            [$tahun]
        );

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATA
        |--------------------------------------------------------------------------
        */

        if ($cek) {

            Database::execute(
                "UPDATE anggaran
                SET
                    pagu_anggaran=?,
                    realisasi_pegawai=?,
                    realisasi_barang=?
                WHERE tahun=?",
                [
                    $pagu,
                    $pegawai,
                    $barang,
                    $tahun
                ]
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | INSERT DATA BARU
            |--------------------------------------------------------------------------
            */

            Database::execute(
                "INSERT INTO anggaran
                (
                    tahun,
                    pagu_anggaran,
                    realisasi_pegawai,
                    realisasi_barang
                )
                VALUES (?,?,?,?)",
                [
                    $tahun,
                    $pagu,
                    $pegawai,
                    $barang
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | HISTORI
        |--------------------------------------------------------------------------
        */

        Database::execute(
            "INSERT INTO anggaran_history
            (
                tahun,
                pagu_anggaran,
                realisasi_pegawai,
                realisasi_barang,
                edited_by
            )
            VALUES (?,?,?,?,?)",
            [
                $tahun,
                $pagu,
                $pegawai,
                $barang,
                $user
            ]
        );

        flashSet('success', 'Data berhasil diperbarui');

        redirect('/keuangan/index.php');
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $id = (int) ($_POST['id'] ?? 0);

        Database::execute(
            "DELETE FROM anggaran_history WHERE id=?",
            [$id]
        );

        flashSet('success', 'Data berhasil dihapus');

        redirect('/keuangan/index.php');
    }
}

/*
|--------------------------------------------------------------------------
| LOAD DATA
|--------------------------------------------------------------------------
*/

$tahun = isset($_GET['tahun'])
    ? (int) $_GET['tahun']
    : (int) date('Y');

$anggaran = Database::fetch(
    "SELECT * FROM anggaran WHERE tahun=?",
    [$tahun]
);

if (!$anggaran) {

    $anggaran = [
        'tahun' => $tahun,
        'pagu_anggaran' => 0,
        'realisasi_pegawai' => 0,
        'realisasi_barang' => 0
    ];
}

$pagu = (float) $anggaran['pagu_anggaran'];

$pegawai = (float) $anggaran['realisasi_pegawai'];

$barang = (float) $anggaran['realisasi_barang'];

$totalReal = $pegawai + $barang;

$sisa = $pagu - $totalReal;

$pct = $pagu > 0
    ? round(($totalReal / $pagu) * 100, 1)
    : 0;

/*
|--------------------------------------------------------------------------
| HISTORY
|--------------------------------------------------------------------------
*/

$history = Database::fetchAll(
    "SELECT *
    FROM anggaran_history
    ORDER BY created_at DESC
    LIMIT 20"
);

renderHeader('Keuangan', 'keuangan');

?>

<style>

:root {
    --primary: #2563eb;
    --primary-light: #dbeafe;
    --primary-dark: #1d4ed8;
    --success: #059669;
    --success-dark: #047857;
    --warning: #d97706;
    --danger: #dc2626;
    --danger-light: #fee2e2;
    --muted: #64748b;
    --muted-light: #94a3b8;
    --text: #0f172a;
    --bg: #f8fafc;
    --border: #e2e8f0;
    --shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
    --radius: 16px;
}

* {
    box-sizing: border-box;
}

.keuangan-wrapper {
    width: 100%;
    background: linear-gradient(135deg, #f0f4f8 0%, #f8fafc 100%);
    min-height: 100vh;
    padding: 24px;
}

.header-page {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 16px;
}

.header-page h2 {
    font-size: 32px;
    font-weight: 900;
    color: var(--text);
    margin: 0;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.header-page > div:first-child > div {
    color: var(--muted);
    font-size: 14px;
    margin-top: 8px;
    font-weight: 500;
}

.btn-group {
    display: flex;
    gap: 12px;
}

.btn-primary, .btn-print {
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.35);
}

.btn-print {
    background: linear-gradient(135deg, var(--text), #1e293b);
    color: #fff;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
}

.btn-print:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.35);
}

.grid-card {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.card-stat {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.card-stat::before {
    content: '';
    position: absolute;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    opacity: 0.08;
    top: -40px;
    right: -40px;
}

.card-stat:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
    border-color: var(--border);
}

.card-label {
    font-size: 11px;
    font-weight: 800;
    color: var(--muted);
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card-number {
    font-size: 32px;
    font-weight: 900;
    color: var(--text);
    line-height: 1.2;
    position: relative;
    z-index: 1;
}

.card-number + div {
    font-size: 12px;
    font-weight: 700;
    margin-top: 8px;
    position: relative;
    z-index: 1;
}

.chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.chart-card {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: var(--radius);
    padding: 24px;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
}

.chart-card:hover {
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
    transform: translateY(-2px);
}

.chart-card h3 {
    font-size: 18px;
    font-weight: 800;
    color: var(--text);
    margin: 0 0 20px 0;
}

.chart-box {
    height: 320px;
    position: relative;
}

.table-card {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: var(--radius);
    padding: 28px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.table-card h3 {
    font-size: 20px;
    font-weight: 800;
    color: var(--text);
    margin: 0 0 8px 0;
}

.table-card > div:last-child > div:nth-child(2) {
    font-size: 13px;
    color: var(--muted-light);
}

.table-custom {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.table-custom th {
    background: linear-gradient(135deg, var(--text), #1e293b);
    color: #fff;
    padding: 16px;
    font-size: 12px;
    font-weight: 800;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-custom td {
    padding: 16px;
    border-top: 1px solid var(--border);
    font-size: 13px;
    text-align: center;
    font-weight: 500;
    color: var(--text);
}

.table-custom tbody tr {
    transition: all 0.2s ease;
}

.table-custom tbody tr:hover {
    background: var(--bg);
}

.action-group {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.action-btn {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-eye {
    background: var(--primary-light);
    color: var(--primary);
}

.btn-eye:hover {
    background: var(--primary);
    color: #fff;
    transform: scale(1.05);
}

.btn-edit-small {
    background: #fef3c7;
    color: var(--warning);
}

.btn-edit-small:hover {
    background: var(--warning);
    color: #fff;
    transform: scale(1.05);
}

.btn-delete {
    background: var(--danger-light);
    color: var(--danger);
}

.btn-delete:hover {
    background: var(--danger);
    color: #fff;
    transform: scale(1.05);
}

.modal-custom {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    align-items: center;
    justify-content: center;
}

.modal-custom.active {
    display: flex;
}

.modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    transition: opacity 0.3s ease;
}

.modal-box {
    position: relative;
    width: 90%;
    max-width: 500px;
    background: rgba(255, 255, 255, 0.98);
    border-radius: var(--radius);
    padding: 28px;
    z-index: 10000;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-box h3 {
    font-size: 22px;
    font-weight: 800;
    color: var(--text);
    margin: 0 0 24px 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.2s ease;
    background: rgba(255, 255, 255, 0.8);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    background: #fff;
}

.form-control[type="text"],
.form-control[type="number"] {
    color: var(--text);
}

.form-control option {
    color: var(--text);
}

.form-group button {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 800;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
}

.form-group button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(37, 99, 235, 0.35);
}

.detail-box {
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.5), rgba(226, 232, 240, 0.3));
    border: 1px solid rgba(226, 232, 240, 0.5);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 14px;
}

.detail-box strong {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: var(--muted);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-box > div {
    font-size: 16px;
    font-weight: 700;
    color: var(--text);
}

@media(max-width: 1200px) {
    .chart-grid {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 768px) {
    .keuangan-wrapper {
        padding: 16px;
    }

    .grid-card {
        grid-template-columns: 1fr;
    }

    .header-page {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-group {
        flex-direction: column;
    }

    .btn-primary, .btn-print {
        justify-content: center;
    }

    .modal-box {
        width: 95%;
        padding: 20px;
    }

    .table-custom th, .table-custom td {
        padding: 12px;
        font-size: 11px;
    }

}

</style>

<div class="keuangan-wrapper">

    <div class="header-page">
        <div>
            <h2>
                <i class="bi bi-bank2" style="margin-right: 12px;"></i>Dashboard Keuangan
            </h2>
            <div>Sistem Management Tata Usaha</div>
        </div>

        <div class="btn-group">
            <button type="button" class="btn-print" onclick="openPrintModal()">
                <i class="bi bi-printer-fill"></i>
                Cetak Laporan
            </button>
            <button class="btn-primary" onclick="openModal()">
                <i class="bi bi-pencil-square"></i>
                Edit Data
            </button>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="grid-card">
        <div class="card-stat">
            <div class="card-label">
                <i class="bi bi-wallet2" style="margin-right: 6px;"></i>Pagu Anggaran
            </div>
            <div class="card-number" style="color: var(--primary);">
                <?= formatRupiah($pagu) ?>
            </div>
        </div>

        <div class="card-stat">
            <div class="card-label">
                <i class="bi bi-check-circle" style="margin-right: 6px;"></i>Realisasi
            </div>
            <div class="card-number" style="color: var(--success);">
                <?= formatRupiah($totalReal) ?>
            </div>
            <div style="color: var(--success); font-weight: 700;">
                <?= $pct ?>% dari pagu
            </div>
        </div>

        <div class="card-stat">
            <div class="card-label">
                <i class="bi bi-cash-coin" style="margin-right: 6px;"></i>Sisa Anggaran
            </div>
            <div class="card-number" style="color: var(--primary);">
                <?= formatRupiah($sisa) ?>
            </div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="chart-grid">
        <div class="chart-card">
            <h3><i class="bi bi-pie-chart" style="margin-right: 8px;"></i>Realisasi vs Anggaran</h3>
            <div class="chart-box">
                <canvas id="donutChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <h3><i class="bi bi-bar-chart" style="margin-right: 8px;"></i>Komposisi Belanja</h3>
            <div class="chart-box">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>

    <!-- HISTORY TABLE -->
    <div class="table-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h3 style="margin: 0;">
                    <i class="bi bi-clock-history" style="margin-right: 8px;"></i>Data yang Telah Diinput
                </h3>
                <div style="font-size: 13px; color: var(--muted-light); margin-top: 4px;">
                    Riwayat data keuangan tersimpan
                </div>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun</th>
                        <th>Tanggal Input</th>
                        <th>Pagu Anggaran</th>
                        <th>Realisasi</th>
                        <th>Sisa Anggaran</th>
                        <th>Belanja Pegawai</th>
                        <th>Belanja Barang</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if(count($history) > 0): ?>
                        <?php foreach($history as $i => $h): ?>
                            <?php
                            $real = $h['realisasi_pegawai'] + $h['realisasi_barang'];
                            $sisaHistory = $h['pagu_anggaran'] - $real;
                            $persenReal = $h['pagu_anggaran'] > 0
                                ? round(($real / $h['pagu_anggaran']) * 100,1)
                                : 0;
                            ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td>
                                    <strong><?= $h['tahun'] ?></strong>
                                </td>
                                <td>
                                    <?= date('d M Y H:i', strtotime($h['created_at'])) ?>
                                </td>
                                <td>
                                    <strong><?= formatRupiah($h['pagu_anggaran']) ?></strong>
                                </td>
                                <td>
                                    <strong style="color: var(--success);">
                                        <?= formatRupiah($real) ?>
                                    </strong><br>
                                    <span style="font-size: 11px; color: var(--muted);">
                                        <?= $persenReal ?>%
                                    </span>
                                </td>
                                <td style="color: var(--primary); font-weight: 700;">
                                    <?= formatRupiah($sisaHistory) ?>
                                </td>
                                <td>
                                    <?= formatRupiah($h['realisasi_pegawai']) ?>
                                </td>
                                <td>
                                    <?= formatRupiah($h['realisasi_barang']) ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <button type="button" class="action-btn btn-eye" onclick="showDetail('<?= $h['tahun'] ?>', '<?= formatRupiah($h['pagu_anggaran']) ?>', '<?= formatRupiah($h['realisasi_pegawai']) ?>', '<?= formatRupiah($h['realisasi_barang']) ?>', '<?= sanitize($h['edited_by']) ?>', '<?= date('d M Y H:i', strtotime($h['created_at'])) ?>')" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Hapus data ini?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                            <button type="submit" class="action-btn btn-delete" title="Hapus Data">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--muted);">
                                <i class="bi bi-inbox" style="font-size: 32px; display: block; margin-bottom: 12px; opacity: 0.5;"></i>
                                Belum ada data
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL EDIT -->

<div class="modal-custom" id="modalEdit">

    <div class="modal-overlay" onclick="closeModal()"></div>

    <div class="modal-box">

        <h3>
            <i class="bi bi-pencil-square" style="margin-right: 10px;"></i>Edit Data Keuangan
        </h3>

        <form method="POST">

            <input type="hidden" name="action" value="update">

            <input type="hidden" name="tahun" value="<?= $tahun ?>">

            <div class="form-group">

                <label>Pagu Anggaran</label>

                <input
                    type="text"
                    class="form-control"
                    name="pagu_anggaran"
                    value="<?= number_format($pagu,0,',','.') ?>"
                    placeholder="Masukkan pagu anggaran">

            </div>

            <div class="form-group">

                <label>Belanja Pegawai</label>

                <input
                    type="text"
                    class="form-control"
                    name="realisasi_pegawai"
                    value="<?= number_format($pegawai,0,',','.') ?>"
                    placeholder="Masukkan belanja pegawai">

            </div>

            <div class="form-group">

                <label>Belanja Barang</label>

                <input
                    type="text"
                    class="form-control"
                    name="realisasi_barang"
                    value="<?= number_format($barang,0,',','.') ?>"
                    placeholder="Masukkan belanja barang">

            </div>

            <button type="submit" style="width: 100%; padding: 14px; margin-top: 8px;">
                <i class="bi bi-check2" style="margin-right: 8px;"></i>Simpan Data
            </button>

        </form>

    </div>

</div>

<!-- MODAL PRINT -->

<div class="modal-custom" id="modalPrint">

    <div class="modal-overlay" onclick="closePrintModal()"></div>

    <div class="modal-box">

        <h3>
            <i class="bi bi-printer" style="margin-right: 10px;"></i>Cetak Laporan
        </h3>

        <form action="print.php" method="GET" target="_blank">

            <div class="form-group">

                <label>Jenis Cetakan</label>

                <select name="filter" class="form-control">

                    <option value="all">Semua Data</option>
                    <option value="harian">Harian</option>
                    <option value="bulanan">Bulanan</option>
                    <option value="tahunan">Tahunan</option>

                </select>

            </div>

            <div class="form-group">

                <label>Tanggal</label>

                <input type="date" name="tanggal" class="form-control">

            </div>

            <div class="form-group">

                <label>Bulan</label>

                <input type="month" name="bulan" class="form-control">

            </div>

            <div class="form-group">

                <label>Tahun</label>

                <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>">

            </div>

            <button type="submit" style="width: 100%; padding: 14px; margin-top: 8px;">
                <i class="bi bi-printer-fill" style="margin-right: 8px;"></i>Cetak Sekarang
            </button>

        </form>

    </div>

</div>

<!-- MODAL DETAIL -->

<div class="modal-custom" id="modalDetail">

    <div class="modal-overlay" onclick="closeDetail()"></div>

    <div class="modal-box">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">

            <h3 style="margin: 0;">
                <i class="bi bi-info-circle" style="margin-right: 10px;"></i>Detail Data Keuangan
            </h3>

            <button type="button" onclick="closeDetail()" style="background: var(--danger-light); color: var(--danger); border: none; width: 35px; height: 35px; border-radius: 10px; cursor: pointer; font-size: 20px; font-weight: bold; display: flex; align-items: center; justify-content: center;">
                ×
            </button>

        </div>

        <div class="detail-box">
            <strong>Tahun</strong>
            <div id="dTahun"></div>
        </div>

        <div class="detail-box">
            <strong>Pagu Anggaran</strong>
            <div id="dPagu"></div>
        </div>

        <div class="detail-box">
            <strong>Belanja Pegawai</strong>
            <div id="dPegawai"></div>
        </div>

        <div class="detail-box">
            <strong>Belanja Barang</strong>
            <div id="dBarang"></div>
        </div>

        <div class="detail-box">
            <strong>Editor</strong>
            <div id="dEditor"></div>
        </div>

        <div class="detail-box">
            <strong>Tanggal Input</strong>
            <div id="dTanggal"></div>
        </div>

    </div>

</div>

<script>

function openModal()
{
    document
        .getElementById('modalEdit')
        .classList.add('active');
}

function closeModal()
{
    document
        .getElementById('modalEdit')
        .classList.remove('active');
}

function showDetail(
    tahun,
    pagu,
    pegawai,
    barang,
    editor,
    tanggal
){
    document.getElementById('dTahun').innerHTML = tahun;
    document.getElementById('dPagu').innerHTML = pagu;
    document.getElementById('dPegawai').innerHTML = pegawai;
    document.getElementById('dBarang').innerHTML = barang;
    document.getElementById('dEditor').innerHTML = editor;
    document.getElementById('dTanggal').innerHTML = tanggal;

    document
        .getElementById('modalDetail')
        .classList.add('active');
}

function closeDetail()
{
    document
        .getElementById('modalDetail')
        .classList.remove('active');
}

new Chart(
    document.getElementById('donutChart'),
    {
        type:'doughnut',
        data:{
            labels:['Realisasi','Sisa'],
            datasets:[{
                data:[
                    <?= $totalReal ?>,
                    <?= $sisa ?>
                ],
                backgroundColor:[
                    '#2563eb',
                    '#dbe2ea'
                ],
                borderWidth:0
            }]
        },
        options:{
            responsive:true,
            maintainAspectRatio:false,
            cutout:'70%'
        }
    }
);

function openPrintModal()
{
    document
        .getElementById('modalPrint')
        .classList.add('active');
}

function closePrintModal()
{
    document
        .getElementById('modalPrint')
        .classList.remove('active');
}
new Chart(
    document.getElementById('barChart'),
    {
        type:'bar',
        data:{
            labels:[
                'Belanja Pegawai',
                'Belanja Barang'
            ],
            datasets:[{
                data:[
                    <?= $pegawai ?>,
                    <?= $barang ?>
                ],
                backgroundColor:[
                    '#7c3aed',
                    '#16a34a'
                ],
                borderRadius:12
            }]
        },
        options:{
            responsive:true,
            maintainAspectRatio:false,
            plugins:{
                legend:{
                    display:false
                }
            }
        }
    }
);

</script>

<?php renderFooter(); ?>