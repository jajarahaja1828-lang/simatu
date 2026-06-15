<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| HANDLE TAMBAH
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {

    $barangId   = (int)($_POST['barang_id'] ?? 0);
    $jumlah     = (int)($_POST['jumlah'] ?? 0);
    $tanggal    = $_POST['tanggal'] ?? date('Y-m-d');
    $tujuan     = trim($_POST['tujuan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($barangId && $jumlah > 0) {

        $barang = Database::fetch(
            'SELECT stok FROM barang_persediaan WHERE id=?',
            [$barangId]
        );

        if ($barang && (int)$barang['stok'] >= $jumlah) {

           // AMBIL DATA BARANG
$barang = Database::fetch(
    'SELECT * FROM barang_persediaan WHERE id=?',
    [$barangId]
);

Database::execute(
    'INSERT INTO transaksi_keluar
    (
        barang_id,
        kode_barang,
        nama_barang,
        jumlah,
        tanggal,
        tujuan,
        keterangan,
        created_by
    )
    VALUES (?,?,?,?,?,?,?,?)',
    [
        $barangId,
        $barang['kode_barang'],
        $barang['nama_barang'],
        $jumlah,
        $tanggal,
        $tujuan,
        $keterangan,
        $_SESSION['user_id']
    ]
);

            Database::execute(
                'UPDATE barang_persediaan
                 SET stok = stok - ?
                 WHERE id=?',
                [$jumlah, $barangId]
            );

            flashSet('success', 'Barang keluar berhasil dicatat.');

        } else {

            flashSet('error', 'Stok tidak mencukupi.');
        }
    }

    redirect('/persediaan/keluar.php');
}

/*
|--------------------------------------------------------------------------
| HANDLE EDIT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    $id         = (int)($_POST['id'] ?? 0);
    $jumlahBaru = (int)($_POST['jumlah'] ?? 0);
    $tanggal    = $_POST['tanggal'] ?? '';
    $tujuan     = trim($_POST['tujuan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($id && $jumlahBaru > 0) {

        $old = Database::fetch(
            'SELECT * FROM transaksi_keluar WHERE id=?',
            [$id]
        );

        if ($old) {

            $jumlahLama = (int)$old['jumlah'];

            // CEK STOK SEKARANG
            $barang = Database::fetch(
                'SELECT stok FROM barang_persediaan WHERE id=?',
                [$old['barang_id']]
            );

            $stokSekarang = (int)$barang['stok'];

            /*
            |--------------------------------------------------------------------------
            | HITUNG STOK BARU
            |--------------------------------------------------------------------------
            */

            // kembalikan stok lama lalu kurangi stok baru
            $stokFinal =
                $stokSekarang
                + $jumlahLama
                - $jumlahBaru;

            if ($stokFinal < 0) {

                flashSet(
                    'error',
                    'Stok tidak mencukupi.'
                );

                redirect('/persediaan/keluar.php');
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE TRANSAKSI
            |--------------------------------------------------------------------------
            */

            Database::execute(
                'UPDATE transaksi_keluar
                 SET jumlah=?, tanggal=?, tujuan=?, keterangan=?
                 WHERE id=?',
                [
                    $jumlahBaru,
                    $tanggal,
                    $tujuan,
                    $keterangan,
                    $id
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE STOK BARANG
            |--------------------------------------------------------------------------
            */

            Database::execute(
                'UPDATE barang_persediaan
                 SET stok=?
                 WHERE id=?',
                [
                    $stokFinal,
                    $old['barang_id']
                ]
            );

            flashSet(
                'success',
                'Data berhasil diperbarui.'
            );
        }
    }

    redirect('/persediaan/keluar.php');
}

/*
|--------------------------------------------------------------------------
| HANDLE HAPUS
|--------------------------------------------------------------------------
*/
if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    $old = Database::fetch(
        'SELECT * FROM transaksi_keluar WHERE id=?',
        [$id]
    );

    if ($old) {

        Database::execute(
            'DELETE FROM transaksi_keluar WHERE id=?',
            [$id]
        );

        Database::execute(
            'UPDATE barang_persediaan
             SET stok = stok + ?
             WHERE id=?',
            [$old['jumlah'], $old['barang_id']]
        );

        flashSet('success', 'Data berhasil dihapus.');
    }

    redirect('/persediaan/keluar.php');
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/
$transaksis = Database::fetchAll(
    'SELECT
        tk.*,
        bp.nama_barang,
        bp.kode_barang,
        bp.satuan
     FROM transaksi_keluar tk
     JOIN barang_persediaan bp
        ON bp.id = tk.barang_id
     ORDER BY tk.tanggal DESC, tk.created_at DESC'
);

$barangs = Database::fetchAll(
    'SELECT * FROM barang_persediaan
     ORDER BY nama_barang ASC'
);

$totalKeluar = array_sum(array_column($transaksis, 'jumlah'));

renderHeader('Barang Keluar', 'persediaan-keluar');
?>

<!-- ========================================================= -->
<!-- PAGE HEADER -->
<!-- ========================================================= -->

<div class="page-header"
    style="
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:15px;
        flex-wrap:wrap;
    ">

    <div>

        <div class="page-title">
            Barang Keluar
        </div>

        <div class="page-subtitle">
            Transaksi pengeluaran barang persediaan
        </div>

    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">

        <!-- TAMBAH -->
        <button class="btn-primary-custom"
            onclick="openModal('modalTambah')">

            <i class="bi bi-plus-lg"></i>
            Tambah Barang

        </button>

        <!-- CETAK -->
        <button class="btn-primary-custom"
            onclick="openModal('modalCetak')">

            <i class="bi bi-printer"></i>
            Cetak Laporan

        </button>

    </div>

</div>

<!-- ========================================================= -->
<!-- STATISTIC -->
<!-- ========================================================= -->

<div class="stats-grid">

    <!-- TOTAL -->
    <div class="stat-card">

        <div class="stat-icon red">
            <i class="bi bi-arrow-up-right"></i>
        </div>

        <div class="stat-value">
            <?= count($transaksis) ?>
        </div>

        <div class="stat-label">
            Total Transaksi
        </div>

    </div>

    <!-- TOTAL ITEM -->
    <div class="stat-card">

        <div class="stat-icon orange">
            <i class="bi bi-stack"></i>
        </div>

        <div class="stat-value">
            <?= number_format($totalKeluar) ?>
        </div>

        <div class="stat-label">
            Total Item Keluar
        </div>

    </div>

    <!-- BULAN -->
    <div class="stat-card">

        <div class="stat-icon blue">
            <i class="bi bi-calendar3"></i>
        </div>

        <div class="stat-value">

            <?= count(array_filter(
                $transaksis,
                fn($t) => date('Y-m') === substr($t['tanggal'], 0, 7)
            )) ?>

        </div>

        <div class="stat-label">
            Transaksi Bulan Ini
        </div>

    </div>

</div>

<!-- ========================================================= -->
<!-- TABLE -->
<!-- ========================================================= -->

<div class="card" id="printArea">

    <div class="card-header"
        style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        ">

        <div class="card-title">

            <i class="bi bi-box-arrow-up me-2"
                style="color:var(--danger);"></i>

            Data Barang Keluar

        </div>

        <!-- SEARCH -->
        <div class="search-bar">

            <i class="bi bi-search"></i>

            <input type="text"
                id="searchInput"
                placeholder="Cari transaksi...">

        </div>

    </div>

    <!-- ===================================================== -->
    <!-- PRINT HEADER -->
    <!-- ===================================================== -->

    <div class="print-header">

        <div class="print-title" id="printTitle">
            LAPORAN BARANG KELUAR
        </div>

        <div class="print-subtitle">
            Sistem Informasi Manajemen Tata Usaha Bapas
        </div>

        <div class="print-date">
            Dicetak pada:
            <?= date('d F Y H:i:s') ?>
        </div>

        <hr style="margin-top:15px;">

    </div>

    <!-- ===================================================== -->
    <!-- TABLE -->
    <!-- ===================================================== -->

    <div class="table-wrapper">

    <table class="table" id="mainTable">

        <thead>

            <tr>

                <th>No</th>
                <th>Tanggal</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Tujuan</th>
                <th>Keterangan</th>
                <th class="no-print">Aksi</th>

            </tr>

        </thead>

        <tbody>

            <?php if (empty($transaksis)): ?>

                <tr>

                    <td colspan="8">

                        <div class="empty-state">

                            <i class="bi bi-inbox"></i>

                            <p>
                                Belum ada data transaksi keluar
                            </p>

                        </div>

                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($transaksis as $i => $t): ?>

                    <tr data-tanggal="<?= $t['tanggal'] ?>">

                        <td style="color:var(--text-muted);">
                            <?= $i + 1 ?>
                        </td>

                        <td>
                            <?= date('d/m/Y', strtotime($t['tanggal'])) ?>
                        </td>

                        <td>

                            <span style="
                                font-family:monospace;
                                font-size:12px;
                                background:#f4f6fb;
                                padding:2px 6px;
                                border-radius:4px;
                            ">

                                <?= sanitize($t['kode_barang']) ?>

                            </span>

                        </td>

                        <td>

                            <strong>
                                <?= sanitize($t['nama_barang']) ?>
                            </strong>

                        </td>

                        <td>

                            <span style="
                                color:var(--danger);
                                font-weight:700;
                            ">

                                -<?= number_format($t['jumlah']) ?>

                            </span>

                            <span style="
                                color:var(--text-muted);
                                font-size:12px;
                            ">

                                <?= sanitize($t['satuan']) ?>

                            </span>

                        </td>

                        <td>
                            <?= sanitize($t['tujuan'] ?: '-') ?>
                        </td>

                        <td style="color:var(--text-muted);">

                            <?= sanitize($t['keterangan'] ?: '-') ?>

                        </td>

                        <td class="no-print">

                            <div style="display:flex;gap:6px;">

                                <!-- EDIT -->
                                <button class="btn-icon"
                                    title="Edit"
                                    onclick="openEditModal(
                                        <?= $t['id'] ?>,
                                        <?= $t['barang_id'] ?>,
                                        <?= $t['jumlah'] ?>,
                                        '<?= $t['tanggal'] ?>',
                                        '<?= addslashes(sanitize($t['tujuan'])) ?>',
                                        '<?= addslashes(sanitize($t['keterangan'])) ?>'
                                    )">

                                    <i class="bi bi-pencil"></i>

                                </button>

                                <!-- DELETE -->
                                <button class="btn-icon danger"
                                    onclick="confirmDelete(
                                        'keluar.php?hapus=<?= $t['id'] ?>',
                                        'transaksi <?= sanitize($t['nama_barang']) ?>'
                                    )">

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

<!-- ===================================================== -->
<!-- TANDA TANGAN -->
<!-- ===================================================== -->

<div class="ttd-wrapper">

    <div class="ttd-box">

        <div class="ttd-jabatan">
            Operator
        </div>

        <div class="ttd-space"></div>

        <div class="ttd-nama">
            VELIA IRMA WIONIE
        </div>

        <div class="ttd-nip">
            NIP. 197710302009011004
        </div>

    </div>

    <div class="ttd-box kasubag-box">

        <div class="ttd-jabatan">
            Kasubag TU
        </div>

        <div class="ttd-space"></div>

        <div class="ttd-nama">
            DEVI SARITKA, A.MD.P., S.H., M.H
        </div>

        <div class="ttd-nip">
            NIP. 1991129201012001
        </div>

    </div>

    <div class="ttd-box">

        <div class="ttd-jabatan">
            Kaur Umum
        </div>

        <div class="ttd-space"></div>

        <div class="ttd-nama">
            ADE NORMI, S.TR.A.P., M.H.
        </div>

        <div class="ttd-nip">
            NIP. 19911292010122001
        </div>

    </div>

</div>

</div>

<!-- ========================================================= -->
<!-- MODAL TAMBAH -->
<!-- ========================================================= -->

<div class="modal-custom" id="modalTambah">

    <div class="modal-overlay"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                <i class="bi bi-plus-circle me-2"></i>
                Tambah Barang Keluar

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

                <!-- BARANG -->
                <div class="form-group">

                    <label class="form-label">
                        Nama Barang
                        <span style="color:var(--danger);">*</span>
                    </label>

                    <select name="barang_id"
                        class="form-select"
                        required>

                        <option value="">
                            -- Pilih Barang --
                        </option>

                        <?php foreach ($barangs as $b): ?>

                            <option value="<?= $b['id'] ?>">

                                <?= sanitize($b['nama_barang']) ?>

                                (Stok:
                                <?= $b['stok'] ?>

                                <?= sanitize($b['satuan']) ?>)

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- JUMLAH & TANGGAL -->
                <div style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:12px;
                ">

                    <div class="form-group">

                        <label class="form-label">
                            Jumlah
                            <span style="color:var(--danger);">*</span>
                        </label>

                        <input type="number"
                            name="jumlah"
                            class="form-control"
                            min="1"
                            required>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Tanggal
                            <span style="color:var(--danger);">*</span>
                        </label>

                        <input type="date"
                            name="tanggal"
                            class="form-control"
                            value="<?= date('Y-m-d') ?>"
                            required>

                    </div>

                </div>

                <!-- TUJUAN -->
                <div class="form-group">

                    <label class="form-label">
                        Tujuan / Penerima
                    </label>

                    <input type="text"
                        name="tujuan"
                        class="form-control"
                        placeholder="Nama unit atau penerima">

                </div>

                <!-- KETERANGAN -->
                <div class="form-group"
                    style="margin-bottom:0;">

                    <label class="form-label">
                        Keterangan
                    </label>

                    <input type="text"
                        name="keterangan"
                        class="form-control"
                        placeholder="Keterangan opsional">

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

<!-- ========================================================= -->
<!-- MODAL EDIT -->
<!-- ========================================================= -->

<div class="modal-custom" id="modalEdit">

    <div class="modal-overlay"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                <i class="bi bi-pencil me-2"></i>
                Edit Barang Keluar

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

                <!-- BARANG -->
                <div class="form-group">

                    <label class="form-label">
                        Barang
                    </label>

                    <select id="editBarang"
                        class="form-select"
                        disabled>

                        <?php foreach ($barangs as $b): ?>

                            <option value="<?= $b['id'] ?>">
                                <?= sanitize($b['nama_barang']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- JUMLAH -->
                <div style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:12px;
                ">

                    <div class="form-group">

                        <label class="form-label">
                            Jumlah
                        </label>

                        <input type="number"
                            name="jumlah"
                            id="editJumlah"
                            class="form-control"
                            min="1"
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

                <!-- TUJUAN -->
                <div class="form-group">

                    <label class="form-label">
                        Tujuan
                    </label>

                    <input type="text"
                        name="tujuan"
                        id="editTujuan"
                        class="form-control">

                </div>

                <!-- KETERANGAN -->
                <div class="form-group"
                    style="margin-bottom:0;">

                    <label class="form-label">
                        Keterangan
                    </label>

                    <input type="text"
                        name="keterangan"
                        id="editKet"
                        class="form-control">

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

<!-- ========================================================= -->
<!-- MODAL CETAK -->
<!-- ========================================================= -->

<div class="modal-custom" id="modalCetak">

    <div class="modal-overlay"></div>

    <div class="modal-box" style="max-width:450px;">

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

        <div class="modal-body">

            <!-- JENIS -->
            <div class="form-group">

                <label class="form-label">
                    Jenis Laporan
                </label>

                <select id="jenisLaporan"
                    class="form-select"
                    onchange="toggleJenisCetak()">

                    <option value="harian">
                        Harian
                    </option>

                    <option value="bulanan">
                        Bulanan
                    </option>

                    <option value="semua">
                        Semua Data
                    </option>

                </select>

            </div>

            <!-- HARIAN -->
            <div class="form-group"
                id="groupTanggal">

                <label class="form-label">
                    Tanggal
                </label>

                <input type="date"
                    id="filterTanggal"
                    class="form-control"
                    value="<?= date('Y-m-d') ?>">

            </div>

            <!-- BULAN -->
            <div class="form-group"
                id="groupBulan"
                style="display:none;">

                <label class="form-label">
                    Bulan
                </label>

                <input type="month"
                    id="filterBulan"
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

            <button type="button"
                class="btn-submit"
                onclick="prosesCetak()">

                <i class="bi bi-printer"></i>
                Cetak

            </button>

        </div>

    </div>

</div>

<style>
/* =========================================================
   PRINT STYLE
========================================================= */

.print-header{
    display:none;
}

@media print {

    @page{
        size:A4 portrait;
        margin:15mm;
    }

    html,
    body{
        background:#fff !important;
        font-family:Arial, Helvetica, sans-serif;
    }

    body *{
        visibility:hidden;
    }

    #printArea,
    #printArea *{
        visibility:visible;
    }

    #printArea{
        position:absolute;
        top:0;
        left:0;
        width:100%;
        background:#fff;
        padding:0;
        margin:0;
        box-shadow:none !important;
        border:none !important;
    }

    /* HIDE ELEMENT */
    .page-header,
    .search-bar,
    .btn-primary-custom,
    .no-print,
    .modal-custom,
    .stat-card,
    .card-header{
        display:none !important;
    }

    /* PRINT HEADER */
    .print-header{
        display:block !important;
        text-align:center;
        margin-bottom:20px;
    }

    .print-title{
        font-size:22px;
        font-weight:700;
        text-transform:uppercase;
        margin-bottom:5px;
    }

    .print-subtitle{
        font-size:13px;
        margin-bottom:4px;
    }

    .print-date{
        font-size:11px;
        color:#444;
    }

    .print-header hr{
        margin-top:15px;
        border:0;
        border-top:1px solid #000;
    }

    /* TABLE */
    .table-wrapper{
        overflow:visible !important;
    }

    table{
        width:100%;
        border-collapse:collapse;
        font-size:11px;
    }

    thead{
        display:table-header-group;
    }

    tr{
        page-break-inside:avoid;
    }

    table th,
    table td{
        border:1px solid #000 !important;
        padding:7px !important;
        vertical-align:middle;
    }

    table th{
        background:#efefef !important;
        color:#000 !important;
        text-align:center;
        font-weight:700;
    }

    table td{
        color:#000 !important;
    }

    /* TTD */
    .ttd-wrapper{
        display:flex !important;
        justify-content:space-between;
        align-items:flex-start;
        margin-top:60px;
        gap:20px;
        width:100%;
    }

    .ttd-box{
        width:33%;
        text-align:center;
    }

    .ttd-box:nth-child(2){
        margin-top:40px;
    }

    .ttd-jabatan{
        font-size:12px;
        font-weight:600;
        margin-bottom:70px;
    }

    .ttd-nama{
        font-size:12px;
        font-weight:700;
        text-transform:uppercase;
        text-decoration:underline;
    }

    .ttd-nip{
        font-size:11px;
        margin-top:4px;
    }

    /* CARD */
    .card{
        border:none !important;
        box-shadow:none !important;
    }
}

/* =========================================================
   MODAL STYLE
========================================================= */

.modal-custom{
    position:fixed;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
}

.modal-overlay{
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.45);
}

.modal-box{
    position:relative;
    width:95%;
    max-width:650px;
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    z-index:2;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
    animation:modalShow .25s ease;
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
    align-items:center;
    justify-content:space-between;
}

.modal-title{
    font-size:18px;
    font-weight:700;
}

.modal-close{
    border:none;
    background:none;
    font-size:28px;
    line-height:1;
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

/* BUTTON */
.btn-primary-custom{
    border:none;
    background:#2563eb;
    color:#fff;
    padding:11px 16px;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
    transition:.2s;
}

.btn-primary-custom:hover{
    background:#1d4ed8;
}

.btn-submit{
    border:none;
    background:#2563eb;
    color:#fff;
    padding:10px 18px;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
}

.btn-submit:hover{
    background:#1d4ed8;
}

.btn-cancel{
    border:none;
    background:#e5e7eb;
    color:#111827;
    padding:10px 18px;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
}

.btn-icon{
    width:34px;
    height:34px;
    border:none;
    border-radius:8px;
    background:#f1f5f9;
    cursor:pointer;
    transition:.2s;
}

.btn-icon:hover{
    background:#dbeafe;
}

.btn-icon.danger{
    background:#fee2e2;
    color:#dc2626;
}

.btn-icon.danger:hover{
    background:#fecaca;
}

/* FORM */
.form-group{
    margin-bottom:16px;
}

.form-label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:#111827;
}

.form-control,
.form-select{
    width:100%;
    padding:12px 14px;
    border:1px solid #d1d5db;
    border-radius:10px;
    font-size:14px;
    outline:none;
    transition:.2s;
}

.form-control:focus,
.form-select:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 3px rgba(37,99,235,.15);
}

/* TTD SCREEN */
.ttd-wrapper{
    display:none;
}

@media print {

    .ttd-wrapper{
        display:flex !important;
    }
}
.stats-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
    margin-bottom:24px;
}

.stat-card{
    background:#ffffff;
    border:1px solid #e5e7eb;
    border-radius:18px;
    padding:20px;
    position:relative;
    overflow:hidden;
    min-height:120px;
    transition:all .25s ease;
    box-shadow:0 4px 14px rgba(15,23,42,.04);
}

.stat-card:hover{
    transform:translateY(-3px);
    box-shadow:0 12px 30px rgba(15,23,42,.08);
}

.stat-icon{
    width:52px;
    height:52px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    margin-bottom:16px;
}

.stat-icon.red{
    background:#fee2e2;
    color:#ef4444;
}

.stat-icon.orange{
    background:#ffedd5;
    color:#f97316;
}

.stat-icon.blue{
    background:#dbeafe;
    color:#2563eb;
}

.stat-value{
    font-size:38px;
    font-weight:800;
    line-height:1;
    color:#0f172a;
    margin-bottom:8px;
}

.stat-label{
    font-size:13px;
    font-weight:600;
    letter-spacing:.5px;
    text-transform:uppercase;
    color:#64748b;
}

@media(max-width:992px){

    .stats-grid{
        grid-template-columns:1fr;
    }

}
</style>

<!-- ========================================================= -->
<!-- SCRIPT -->
<!-- ========================================================= -->

<script>

/*
|--------------------------------------------------------------------------
| OPEN MODAL
|--------------------------------------------------------------------------
*/
function openModal(id){

    let modal = document.getElementById(id);

    if(modal){
        modal.style.display = 'flex';
    }
}

/*
|--------------------------------------------------------------------------
| CLOSE MODAL
|--------------------------------------------------------------------------
*/
function closeModal(id){

    let modal = document.getElementById(id);

    if(modal){
        modal.style.display = 'none';
    }
}

/*
|--------------------------------------------------------------------------
| CLICK OVERLAY CLOSE
|--------------------------------------------------------------------------
*/
document.querySelectorAll('.modal-overlay')
.forEach(function(overlay){

    overlay.addEventListener('click', function(){

        let modal =
            overlay.closest('.modal-custom');

        if(modal){
            modal.style.display = 'none';
        }
    });
});

/*
|--------------------------------------------------------------------------
| CONFIRM DELETE
|--------------------------------------------------------------------------
*/
function confirmDelete(url,nama){

    let konfirmasi = confirm(
        'Yakin ingin menghapus ' + nama + ' ?'
    );

    if(konfirmasi){
        window.location.href = url;
    }
}

/*
|--------------------------------------------------------------------------
| OPEN EDIT MODAL
|--------------------------------------------------------------------------
*/
function openEditModal(id,barangId,jumlah,tanggal,tujuan,ket){

    document.getElementById('editId').value = id;
    document.getElementById('editBarang').value = barangId;
    document.getElementById('editJumlah').value = jumlah;
    document.getElementById('editTanggal').value = tanggal;
    document.getElementById('editTujuan').value = tujuan;
    document.getElementById('editKet').value = ket;

    openModal('modalEdit');
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/
document.getElementById('searchInput')
.addEventListener('keyup', function () {

    let keyword = this.value.toLowerCase();

    let rows =
        document.querySelectorAll('#mainTable tbody tr');

    rows.forEach(function(row){

        let text = row.innerText.toLowerCase();

        row.style.display =
            text.includes(keyword)
            ? ''
            : 'none';
    });
});

/*
|--------------------------------------------------------------------------
| TOGGLE CETAK
|--------------------------------------------------------------------------
*/
function toggleJenisCetak() {

    let jenis =
        document.getElementById('jenisLaporan').value;

    document.getElementById('groupTanggal').style.display =
        jenis === 'harian'
        ? 'block'
        : 'none';

    document.getElementById('groupBulan').style.display =
        jenis === 'bulanan'
        ? 'block'
        : 'none';
}

/*
|--------------------------------------------------------------------------
| PROSES CETAK
|--------------------------------------------------------------------------
*/
function prosesCetak() {

    let jenis = document.getElementById('jenisLaporan').value;

    let rows = document.querySelectorAll('#mainTable tbody tr');

    let adaData = false;

    // tampilkan semua dulu
    rows.forEach(function(row){
        row.style.display = '';
    });

    /*
    |--------------------------------------------------------------------------
    | CETAK HARIAN
    |--------------------------------------------------------------------------
    */
    if (jenis === 'harian') {

        let tanggal = document.getElementById('filterTanggal').value;

        if (!tanggal) {
            alert('Pilih tanggal terlebih dahulu');
            return;
        }

        rows.forEach(function(row){

            let tgl = row.getAttribute('data-tanggal');

            if (tgl === tanggal) {

                row.style.display = '';
                adaData = true;

            } else {

                row.style.display = 'none';
            }
        });

        let tanggalFormat = new Date(tanggal);

        document.getElementById('printTitle').innerHTML =
            'LAPORAN BARANG MASUK HARIAN<br>' +
            tanggalFormat.toLocaleDateString('id-ID');
    }

    /*
    |--------------------------------------------------------------------------
    | CETAK BULANAN
    |--------------------------------------------------------------------------
    */
    else if (jenis === 'bulanan') {

        let bulan = document.getElementById('filterBulan').value;

        if (!bulan) {
            alert('Pilih bulan terlebih dahulu');
            return;
        }

        rows.forEach(function(row){

            let tgl = row.getAttribute('data-tanggal');

            if (tgl && tgl.startsWith(bulan)) {

                row.style.display = '';
                adaData = true;

            } else {

                row.style.display = 'none';
            }
        });

        let pecah = bulan.split('-');

        let namaBulan = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];

        document.getElementById('printTitle').innerHTML =
            'LAPORAN BARANG MASUK BULANAN<br>' +
            namaBulan[parseInt(pecah[1]) - 1] +
            ' ' +
            pecah[0];
    }

    /*
    |--------------------------------------------------------------------------
    | SEMUA DATA
    |--------------------------------------------------------------------------
    */
    else {

        adaData = true;

        document.getElementById('printTitle').innerHTML =
            'LAPORAN BARANG MASUK';
    }

    /*
    |--------------------------------------------------------------------------
    | JIKA TIDAK ADA DATA
    |--------------------------------------------------------------------------
    */
    if (!adaData) {

        alert('Data tidak ditemukan untuk filter tersebut');

        rows.forEach(function(row){
            row.style.display = '';
        });

        return;
    }

    closeModal('modalCetak');

    window.print();

    // kembalikan semua row
    rows.forEach(function(row){
        row.style.display = '';
    });
}

/*
|--------------------------------------------------------------------------
| SHOW ROWS
|--------------------------------------------------------------------------
*/
function showAllRows() {

    let rows =
        document.querySelectorAll('#mainTable tbody tr');

    rows.forEach(function(row){
        row.style.display = '';
    });
}

</script>

<?php renderFooter(); ?>