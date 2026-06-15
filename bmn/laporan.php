<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}

$printMode   = isset($_GET['print']);
$filterJenis = $_GET['jenis'] ?? 'semua';
$search      = trim($_GET['search'] ?? '');

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    Database::execute("DELETE FROM bmn_aset WHERE id=?", [$id]);
    header("Location: laporan.php");
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    ($_POST['action'] ?? '') === 'edit'
) {
    Database::execute(
        "UPDATE bmn_aset SET
            nama_aset=?,
            ruangan=?,
            jumlah=?,
            kondisi=?,
            nilai_perolehan=?
        WHERE id=?",
        [
            $_POST['nama_aset'],
            $_POST['ruangan'],
            $_POST['jumlah'],
            $_POST['kondisi'],
            $_POST['nilai_perolehan'],
            $_POST['id']
        ]
    );

    header("Location: laporan.php");
    exit;
}

$where = [];
$params = [];

if ($filterJenis !== 'semua') {
    $where[] = "k.jenis = ?";
    $params[] = $filterJenis;
}

if ($search !== '') {
    $where[] = "(a.nama_aset LIKE ? OR k.nama_kategori LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$summary = Database::fetchAll(
    "SELECT
        k.id AS kategori_id,
        k.nama_kategori,
        k.jenis,
        COUNT(a.id) AS jenis_aset,
        COALESCE(SUM(a.jumlah), 0) AS total_unit,
        COALESCE(SUM(CASE WHEN a.kondisi='Baik' THEN a.jumlah ELSE 0 END), 0) AS baik,
        COALESCE(SUM(CASE WHEN a.kondisi='Rusak Ringan' THEN a.jumlah ELSE 0 END), 0) AS rusak_ringan,
        COALESCE(SUM(CASE WHEN a.kondisi='Rusak Berat' THEN a.jumlah ELSE 0 END), 0) AS rusak_berat,
        COALESCE(SUM(a.nilai_perolehan * a.jumlah), 0) AS total_nilai
    FROM bmn_kategori k
    LEFT JOIN bmn_aset a ON a.kategori_id = k.id
    $whereSql
    GROUP BY k.id
    ORDER BY k.jenis, k.id ASC",
    $params
);

$detailAset = Database::fetchAll(
    "SELECT
        a.*, 
        k.nama_kategori,
        k.jenis
    FROM bmn_aset a
    LEFT JOIN bmn_kategori k ON a.kategori_id = k.id
    $whereSql
    ORDER BY k.jenis, a.id DESC",
    $params
);

$totals = [
    'bergerak' => ['unit' => 0, 'nilai' => 0],
    'tidak_bergerak' => ['unit' => 0, 'nilai' => 0],
];

foreach ($summary as $s) {
    if (isset($totals[$s['jenis']])) {
        $totals[$s['jenis']]['unit'] += (int)$s['total_unit'];
        $totals[$s['jenis']]['nilai'] += (float)$s['total_nilai'];
    }
}

renderHeader('Laporan BMN', 'bmn-laporan');
?>

<style>
:root {
    color-scheme: light;
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

body {
    background: #f1f5f9;
    color: #0f172a;
}

.page-top {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.page-title-custom {
    font-size: 32px;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.page-subtitle {
    color: #475569;
    font-size: 14px;
    margin-top: 6px;
}

.top-action {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.search-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.filter-box {
    min-width: 180px;
    flex: 1 1 180px;
}

.search-box {
    position: relative;
    width: min(100%, 320px);
}

.search-box input {
    width: 100%;
    height: 48px;
    padding: 0 18px 0 46px;
    border: 1px solid #cbd5e1;
    border-radius: 14px;
    background: #ffffff;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.search-box input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.search-box i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
}

.btn-primary-custom {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    padding: 14px 20px;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-primary-custom:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 40px rgba(37, 99, 235, 0.18);
}

.btn-secondary {
    background: #0f172a;
}

.btn-secondary:hover {
    background: #111827;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    display: flex;
    align-items: center;
    gap: 18px;
}

.summary-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    font-size: 24px;
}

.bg-blue { background: #eff6ff; color: #2563eb; }
.bg-green { background: #ecfdf5; color: #16a34a; }
.bg-orange { background: #fff7ed; color: #ea580c; }
.bg-purple { background: #f5f3ff; color: #7c3aed; }

.summary-details h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
}

.summary-label {
    margin-top: 6px;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
}

.report-card {
    background: white;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 15px 35px rgba(15, 23, 42, 0.06);
    margin-bottom: 30px;
    overflow: hidden;
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    padding: 22px 26px;
    border-bottom: 1px solid #f1f5f9;
    background: #ffffff;
}

.report-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
}

.report-title i {
    font-size: 20px;
    color: #475569;
}

.report-total-value {
    font-size: 14px;
    color: #475569;
}

.report-total-value strong {
    color: #0f172a;
    font-size: 16px;
}

.table-modern {
    width: 100%;
    border-collapse: collapse;
}

.table-modern th,
.table-modern td {
    padding: 16px 22px;
}

.table-modern th {
    background: #f8fafc;
    color: #475569;
    font-size: 13px;
    font-weight: 700;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
}

.table-modern td {
    color: #334155;
    font-size: 14px;
    border-top: 1px solid #f1f5f9;
    vertical-align: middle;
}

.table-modern tbody tr:hover {
    background: #f8fbff;
}

.sub-table-card {
    margin: 10px 20px 20px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
}

.sub-table-card h4 {
    margin: 0 0 16px;
    font-size: 15px;
    font-weight: 700;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 8px;
}

.table-sub {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}

.table-sub th,
.table-sub td {
    padding: 14px 18px;
    font-size: 13px;
}

.table-sub th {
    background: #f1f5f9;
    color: #475569;
    font-weight: 700;
}

.table-sub td {
    border-top: 1px solid #e2e8f0;
}

.badge-good,
.badge-light,
.badge-heavy {
    padding: 5px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.badge-good { background: #dcfce7; color: #166534; }
.badge-light { background: #fef9c3; color: #92400e; }
.badge-heavy { background: #fee2e2; color: #991b1b; }

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-table {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #334155;
    color: white;
    cursor: pointer;
    transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.btn-table:hover {
    transform: translateY(-1px);
}

.btn-view { background: #2563eb; }
.btn-view:hover { background: #1d4ed8; }
.btn-edit { background: #f59e0b; }
.btn-edit:hover { background: #d97706; }
.btn-delete { background: #ef4444; }
.btn-delete:hover { background: #dc2626; }

.modal-custom,
.print-modal {
    position: fixed;
    inset: 0;
    display: none;
    visibility: hidden;
    opacity: 0;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(6px);
    z-index: 100000;
    transition: opacity 0.2s ease, visibility 0.2s ease;
}

.modal-custom.show,
.print-modal.show {
    display: flex !important;
    visibility: visible;
    opacity: 1;
}

.modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.35);
}

.modal-box,
.print-box {
    position: relative;
    width: min(100%, 560px);
    background: white;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.16);
    border: 1px solid rgba(148, 163, 184, 0.2);
}

.modal-header,
.print-header {
    padding: 24px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    font-size: 18px;
    font-weight: 800;
    color: #0f172a;
}

.modal-body,
.print-body {
    padding: 24px;
}

.modal-footer,
.print-footer {
    padding: 20px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #475569;
    font-weight: 600;
    font-size: 13px;
}

.form-control {
    width: 100%;
    min-height: 46px;
    padding: 0 14px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: white;
    font-size: 14px;
    color: #1f2937;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}

.btn-save,
.btn-cancel {
    min-width: 120px;
}

.btn-save {
    border: none;
    border-radius: 12px;
    padding: 12px 22px;
    background: #2563eb;
    color: white;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease;
}

.btn-save:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.btn-cancel {
    border: 1px solid #cbd5e1;
    background: white;
    color: #475569;
    border-radius: 12px;
    padding: 12px 22px;
    cursor: pointer;
}

.btn-cancel:hover {
    background: #f8fafc;
}

.print-notice {
    padding: 14px 18px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    border-radius: 14px;
    font-size: 14px;
    color: #1d4ed8;
    margin-bottom: 18px;
}

.ttd-container {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-top: 40px;
}

.ttd-box {
    padding: 22px 20px;
    border-radius: 18px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    text-align: center;
    font-size: 14px;
    color: #334155;
}

.ttd-name {
    margin-top: 22px;
    font-weight: 800;
    line-height: 1.2;
}

.ttd-space {
    height: 84px;
}

@media (max-width: 900px) {
    .summary-grid,
    .ttd-container {
        grid-template-columns: 1fr;
    }

    .page-top {
        align-items: flex-start;
    }
}

@media print {
    body {
        background: white !important;
        color: #000 !important;
    }

    .page-top,
    .summary-grid,
    .btn-primary-custom,
    .action-buttons,
    .modal-custom,
    .print-modal,
    .top-action,
    form,
    .search-box {
        display: none !important;
    }

    .report-card {
        border: 1px solid #ccc !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    .table-modern th,
    .table-modern td,
    .table-sub th,
    .table-sub td {
        color: #000 !important;
        border-color: #d1d5db !important;
    }

    .ttd-container {
        page-break-inside: avoid;
    }
}
</style>

<div class="page-top">
    <div>
        <div class="page-title-custom">Laporan BMN</div>
        <div class="page-subtitle">Ringkasan aset dan kondisi Barang Milik Negara — <?= date('d F Y') ?></div>
    </div>

    <div class="top-action">
        <form method="GET" class="search-form">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" name="search" placeholder="Cari aset, kategori, atau lokasi..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-box">
                <select name="jenis" class="form-control">
                    <option value="semua" <?= $filterJenis === 'semua' ? 'selected' : '' ?>>Semua Kategori</option>
                    <option value="bergerak" <?= $filterJenis === 'bergerak' ? 'selected' : '' ?>>Barang Bergerak</option>
                    <option value="tidak_bergerak" <?= $filterJenis === 'tidak_bergerak' ? 'selected' : '' ?>>Barang Tidak Bergerak</option>
                </select>
            </div>
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </form>

        <button class="btn-primary-custom btn-secondary" onclick="openPrintModal()" type="button">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>
</div>

<div class="summary-grid">
    <div class="summary-card">
        <div class="summary-icon bg-blue"><i class="bi bi-truck"></i></div>
        <div>
            <h2><?= number_format($totals['bergerak']['unit']) ?></h2>
            <p class="summary-label">Jumlah Barang Bergerak</p>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon bg-green"><i class="bi bi-building"></i></div>
        <div>
            <h2><?= number_format($totals['tidak_bergerak']['unit']) ?></h2>
            <p class="summary-label">Jumlah Barang Tidak Bergerak</p>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon bg-orange"><i class="bi bi-cash-stack"></i></div>
        <div>
            <h2><?= formatRupiah($totals['bergerak']['nilai'] + $totals['tidak_bergerak']['nilai']) ?></h2>
            <p class="summary-label">Total Nilai Aset</p>
        </div>
    </div>
    <div class="summary-card">
        <div class="summary-icon bg-purple"><i class="bi bi-calendar-event"></i></div>
        <div>
            <h2><?= date('Y') ?></h2>
            <p class="summary-label">Tahun Pelaporan</p>
        </div>
    </div>
</div>

<?php
$sections = [
    ['jenis' => 'bergerak', 'label' => 'Barang Bergerak', 'icon' => 'bi-truck'],
    ['jenis' => 'tidak_bergerak', 'label' => 'Barang Tidak Bergerak', 'icon' => 'bi-building'],
];

foreach ($sections as $section):
    $jenis = $section['jenis'];
    if ($filterJenis !== 'semua' && $filterJenis !== $jenis) {
        continue;
    }
    $rows = array_filter($summary, fn($s) => $s['jenis'] === $jenis);
?>
    <div class="report-card">
        <div class="report-header">
            <div class="report-title">
                <i class="bi <?= $section['icon'] ?>"></i>
                Laporan Kategori <?= $section['label'] ?>
            </div>
            <div class="report-total-value">
                Total Nilai: <strong><?= formatRupiah($totals[$jenis]['nilai']) ?></strong>
            </div>
        </div>

        <div style="overflow:auto;">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Ruangan</th>
                        <th>Jenis Aset</th>
                        <th>Total Unit</th>
                        <th>Baik</th>
                        <th>Rusak Ringan</th>
                        <th>Rusak Berat</th>
                        <th>Total Nilai</th>
                        <?php if (!$printMode): ?><th style="text-align:center;">Aksi</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?= $printMode ? 8 : 9 ?>" style="text-align:center; color:#94a3b8; padding:24px;">Tidak ada data kategori.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['nama_kategori']) ?></strong></td>
                                <td><span class="text-muted">-</span></td>
                                <td><?= (int)$s['jenis_aset'] ?> Jenis</td>
                                <td><?= number_format($s['total_unit']) ?></td>
                                <td><?= number_format($s['baik']) ?></td>
                                <td><?= number_format($s['rusak_ringan']) ?></td>
                                <td><?= number_format($s['rusak_berat']) ?></td>
                                <td><strong><?= formatRupiah($s['total_nilai']) ?></strong></td>
                                <?php if (!$printMode): ?>
                                    <td style="text-align:center;">
                                        <button type="button" class="btn-table btn-view" onclick="toggleDetail('detail<?= $s['kategori_id'] ?>')">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>

                            <tr id="detail<?= $s['kategori_id'] ?>" style="display:none; background:#f8fafc;">
                                <td colspan="9" style="padding:0;">
                                    <div class="sub-table-card">
                                        <h4><i class="bi bi-list-nested"></i> Detail Item Aset - <?= htmlspecialchars($s['nama_kategori']) ?></h4>
                                        <table class="table-sub">
                                            <thead>
                                                <tr>
                                                    <th>Nama Aset</th>
                                                    <th>Ruangan</th>
                                                    <th>Jumlah</th>
                                                    <th>Kondisi</th>
                                                    <th>Nilai Satuan</th>
                                                    <th>Tanggal Perolehan</th>
                                                    <?php if (!$printMode): ?><th width="110" style="text-align:center;">Aksi</th><?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $hasDetail = false; ?>
                                                <?php foreach ($detailAset as $d): ?>
                                                    <?php if ($d['kategori_id'] !== $s['kategori_id']) continue; ?>
                                                    <?php $hasDetail = true; ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($d['nama_aset']) ?></strong></td>
                                                        <td><?= htmlspecialchars($d['ruangan'] ?: '-') ?></td>
                                                        <td><?= number_format($d['jumlah']) ?></td>
                                                        <td>
                                                            <?php if ($d['kondisi'] === 'Baik'): ?>
                                                                <span class="badge-good">Baik</span>
                                                            <?php elseif ($d['kondisi'] === 'Rusak Ringan'): ?>
                                                                <span class="badge-light">Rusak Ringan</span>
                                                            <?php else: ?>
                                                                <span class="badge-heavy">Rusak Berat</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= formatRupiah($d['nilai_perolehan']) ?></td>
                                                        <td><?= date('d/m/Y', strtotime($d['tanggal_perolehan'])) ?></td>
                                                        <?php if (!$printMode): ?>
                                                            <td>
                                                                <div class="action-buttons">
                                                                    <button type="button" class="btn-table btn-edit" onclick="event.stopPropagation(); openEditModal(<?= json_encode($d['id']) ?>, <?= json_encode($d['nama_aset']) ?>, <?= json_encode($d['ruangan']) ?>, <?= json_encode((int)$d['jumlah']) ?>, <?= json_encode($d['kondisi']) ?>, <?= json_encode((float)$d['nilai_perolehan']) ?>)">
                                                                        <i class="bi bi-pencil-fill"></i>
                                                                    </button>
                                                                    <a href="?hapus=<?= $d['id'] ?>" class="btn-table btn-delete" onclick="event.stopPropagation(); return confirm('Hapus aset ini?')">
                                                                        <i class="bi bi-trash-fill"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!$hasDetail): ?>
                                                    <tr>
                                                        <td colspan="7" style="text-align:center; color:#94a3b8; padding:18px;">Belum ada item aset di dalam kategori ini.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal-custom" id="modalEdit">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-box">
        <div class="modal-header">Edit Data Aset</div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nama Komponen Aset</label>
                    <input type="text" name="nama_aset" id="edit_nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Penempatan Ruangan</label>
                    <input type="text" name="ruangan" id="edit_ruangan" class="form-control">
                </div>
                <div class="form-group">
                    <label>Jumlah (Unit)</label>
                    <input type="number" name="jumlah" id="edit_jumlah" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kondisi Fisik</label>
                    <select name="kondisi" id="edit_kondisi" class="form-control">
                        <option value="Baik">Baik</option>
                        <option value="Rusak Ringan">Rusak Ringan</option>
                        <option value="Rusak Berat">Rusak Berat</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nilai Perolehan (Rp)</label>
                    <input type="number" name="nilai_perolehan" id="edit_nilai" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div class="print-modal" id="printModal">
    <div class="modal-overlay" onclick="closePrintModal()"></div>
    <div class="print-box">
        <div class="print-header"><i class="bi bi-printer"></i> Cetak Laporan BMN</div>
        <form method="GET">
            <div class="print-body">
                <p class="print-notice">Pilih jenis aset yang ingin dicetak dan klik Cetak Sekarang.</p>
                <div class="form-group">
                    <label>Jenis Aset</label>
                    <select name="jenis" class="form-control">
                        <option value="semua">Semua Klasifikasi Barang</option>
                        <option value="bergerak">Barang Bergerak</option>
                        <option value="tidak_bergerak">Barang Tidak Bergerak</option>
                    </select>
                </div>
                <input type="hidden" name="print" value="1">
            </div>
            <div class="print-footer">
                <button type="button" class="btn-cancel" onclick="closePrintModal()">Batal</button>
                <button type="submit" class="btn-save"><i class="bi bi-printer"></i> Cetak Sekarang</button>
            </div>
        </form>
    </div>
</div>

<?php if ($printMode): ?>
<div class="ttd-container">
    <div class="ttd-box">
        <div>Operator</div>
        <div class="ttd-space"></div>
        <div class="ttd-name"><u>Velia Irna Wionie</u></div>
        <div>NIP. 197710302009011004</div>
    </div>
    <div class="ttd-box">
        <div>Kaur Umum</div>
        <div class="ttd-space"></div>
        <div class="ttd-name"><u>ADE NOMI, S.Tr.A.P., M.H.</u></div>
        <div>NIP. 1991122922010122001</div>
    </div>
    <div class="ttd-box">
        <div>Jakarta, <?= date('d F Y') ?></div>
        <div class="ttd-space"></div>
        <div class="ttd-name"><u>DEVI SARITKA, A.Md.P., S.H., M.H</u></div>
        <div>NIP. 1991122922010122001</div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleDetail(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
}

function openEditModal(id, nama, ruangan, jumlah, kondisi, nilai) {
    const modal = document.getElementById('modalEdit');
    modal.classList.add('show');
    modal.style.display = 'flex';
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_ruangan').value = ruangan;
    document.getElementById('edit_jumlah').value = jumlah;
    document.getElementById('edit_kondisi').value = kondisi;
    document.getElementById('edit_nilai').value = nilai;
}

function closeModal() {
    const modal = document.getElementById('modalEdit');
    modal.classList.remove('show');
    modal.style.display = 'none';
}

function openPrintModal() {
    const modal = document.getElementById('printModal');
    modal.classList.add('show');
    modal.style.display = 'flex';
}

function closePrintModal() {
    const modal = document.getElementById('printModal');
    modal.classList.remove('show');
    modal.style.display = 'none';
}

<?php if ($printMode): ?>
window.onload = function() {
    window.print();
};
<?php endif; ?>
</script>

<?php renderFooter(); ?>
