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
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($barangId && $jumlah > 0) {

Database::execute(
    'INSERT INTO transaksi_masuk
    (
        barang_id,
        jumlah,
        tanggal,
        keterangan,
        created_by
    )
    VALUES (?,?,?,?,?)',
    [
        $barangId,
        $jumlah,
        $tanggal,
        $keterangan,
        $_SESSION['user_id']
    ]
);

        Database::execute(
            'UPDATE barang_persediaan 
             SET stok = stok + ? 
             WHERE id = ?',
            [$jumlah, $barangId]
        );

        flashSet('success', 'Barang masuk berhasil dicatat.');
    }

    redirect(BASE_URL . '/persediaan/masuk.php');
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
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($id && $jumlahBaru > 0) {

        $old = Database::fetch(
            'SELECT * FROM transaksi_masuk WHERE id = ?',
            [$id]
        );

        if ($old) {

            $diff = $jumlahBaru - (int)$old['jumlah'];

            Database::execute(
                'UPDATE transaksi_masuk 
                 SET jumlah=?, tanggal=?, keterangan=? 
                 WHERE id=?',
                [
                    $jumlahBaru,
                    $tanggal,
                    $keterangan,
                    $id
                ]
            );

            Database::execute(
                'UPDATE barang_persediaan 
                 SET stok = stok + ? 
                 WHERE id=?',
                [$diff, $old['barang_id']]
            );

            flashSet('success', 'Data berhasil diperbarui.');
        }
    }

   redirect(BASE_URL . '/persediaan/masuk.php');
}

/*
|--------------------------------------------------------------------------
| HANDLE HAPUS
|--------------------------------------------------------------------------
*/
if (isset($_GET['hapus'])) {

    $id = (int)$_GET['hapus'];

    $old = Database::fetch(
        'SELECT * FROM transaksi_masuk WHERE id=?',
        [$id]
    );

    if ($old) {

        Database::execute(
    'DELETE FROM transaksi_masuk WHERE id=?',
    [$id]
);

        Database::execute(
            'UPDATE barang_persediaan 
             SET stok = stok - ? 
             WHERE id=?',
            [$old['jumlah'], $old['barang_id']]
        );

        flashSet('success', 'Data berhasil dihapus.');
    }

    redirect(BASE_URL . '/persediaan/masuk.php');
}

/* 
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/
$transaksis = Database::fetchAll(
    'SELECT 
        tm.*, 
        bp.nama_barang, 
        bp.kode_barang, 
        bp.satuan
     FROM transaksi_masuk tm
     JOIN barang_persediaan bp 
        ON bp.id = tm.barang_id
     ORDER BY tm.tanggal DESC, tm.created_at DESC'
);

$barangs = Database::fetchAll(
    'SELECT * FROM barang_persediaan 
     ORDER BY nama_barang ASC'
);

$totalMasuk = array_sum(array_column($transaksis, 'jumlah'));

renderHeader('Barang Masuk', 'persediaan-masuk');
?>

<!-- ========================================================= -->
<!-- PAGE HEADER -->
<!-- ========================================================= -->

<div class="page-header"
    style="display:flex;align-items:flex-start;justify-content:space-between;gap:15px;flex-wrap:wrap;">

    <div>
        <div class="page-title">Barang Masuk</div>

        <div class="page-subtitle">
            Transaksi penerimaan barang persediaan
        </div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;">

        <!-- TAMBAH -->
        <button class="btn-primary-custom"
            onclick="openModal('modalTambah')">

            <i class="bi bi-plus-lg"></i>
            Tambah Barang
        </button>

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

<div style="
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:16px;
    margin-bottom:20px;
">

  <!-- TOTAL TRANSAKSI -->
<div class="stat-card">

    <div class="stat-icon blue">
       <i class="bi bi-clipboard-data"></i>
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

        <div class="stat-icon green">
            <i class="bi bi-stack"></i>
        </div>

        <div class="stat-value">
            <?= number_format($totalMasuk) ?>
        </div>

        <div class="stat-label">
            Total Item Masuk
        </div>
    </div>

    <!-- BULAN INI -->
    <div class="stat-card">

        <div class="stat-icon orange">
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

            <i class="bi bi-box-arrow-in-down me-2"
                style="color:var(--success);"></i>

            Data Barang Masuk
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
            LAPORAN BARANG MASUK
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
                    <th>Keterangan</th>
                    <th class="no-print">Aksi</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($transaksis)): ?>

                    <tr>
                        <td colspan="7">

                            <div class="empty-state">

                                <i class="bi bi-inbox"></i>

                                <p>
                                    Belum ada data transaksi masuk
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
                                    color:var(--success);
                                    font-weight:700;
                                ">

                                    +<?= number_format($t['jumlah']) ?>

                                </span>

                                <span style="
                                    color:var(--text-muted);
                                    font-size:12px;
                                ">

                                    <?= sanitize($t['satuan']) ?>

                                </span>

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
                                            '<?= addslashes(sanitize($t['keterangan'])) ?>'
                                        )">

                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- DELETE -->
                                   <button class="btn-icon danger"
    title="Hapus"
    onclick="confirmDelete(
        '<?= BASE_URL ?>/persediaan/masuk.php?hapus=<?= $t['id'] ?>',
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

    <!-- BARIS ATAS -->
    <div class="ttd-row-top">

        <!-- OPERATOR -->
        <div class="ttd-box">

            <div class="ttd-jabatan">
                Operator
            </div>

            <div class="ttd-space"></div>

            <div class="ttd-nama">
                VELIA IRMA WONLELE
            </div>

            <div class="ttd-nip">
                NIP. 199207192012122001
            </div>

        </div>

        <!-- KAUR UMUM -->
        <div class="ttd-box">

            <div class="ttd-jabatan">
                Kaur Umum
            </div>

            <div class="ttd-space"></div>

            <div class="ttd-nama">
                ADE NOMI, S.Tr.PAS., M.H.
            </div>

            <div class="ttd-nip">
                NIP. 199409142012122001
            </div>

        </div>

    </div>

    <!-- BARIS BAWAH -->
    <div class="ttd-row-bottom">

        <div class="ttd-box center">

            <div class="ttd-jabatan">
                Kasubag TU
            </div>

            <div class="ttd-space"></div>

            <div class="ttd-nama">
                DEVI SARTIKA, A.Md.P., S.H., M.H.
            </div>

            <div class="ttd-nip">
                NIP. 199112292010122001
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
                Tambah Barang Masuk

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
                            placeholder="0"
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
                Edit Barang Masuk

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

                    <select name="barang_id_display"
                        id="editBarang"
                        class="form-select"
                        disabled>

                        <?php foreach ($barangs as $b): ?>

                            <option value="<?= $b['id'] ?>">
                                <?= sanitize($b['nama_barang']) ?>
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

<!-- ========================================================= -->
<!-- STYLE PRINT -->
<!-- ========================================================= -->

<style>

/* =========================================================
   GLOBAL PROFESSIONAL PRINT STYLE
   SIMATU ENTERPRISE EDITION
========================================================= */

:root{
    --primary:#1e3a8a;
    --secondary:#2563eb;
    --gray:#6b7280;
    --border:#111827;
    --light:#f8fafc;
}

/* =========================================================
   PRINT HEADER
========================================================= */

.print-header{
    display:none !important;
}

.ttd-wrapper{
    display:none !important;
}

/* =========================================================
   GLOBAL PRINT
========================================================= */

@media print {

    @page{
        size:A4 portrait;
        margin:12mm;
    }

    html,
    body{
        width:210mm;
        min-height:297mm;
        background:#ffffff !important;
        font-family:"Segoe UI", Arial, sans-serif;
        color:#111827;
        font-size:11px;
        line-height:1.4;
    }

    body{
        margin:0;
        padding:0;
    }

    /* =====================================================
       HIDE ELEMENT
    ===================================================== */

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
        background:#fff !important;
        margin:0 !important;
        padding:0 !important;
        box-shadow:none !important;
        border:none !important;
    }

    .page-header,
    .search-bar,
    .btn-primary-custom,
    .no-print,
    .modal-custom,
    .stat-card,
    .card-header,
    .sidebar,
    .navbar,
    .topbar,
    .footer{
        display:none !important;
    }

    /* =====================================================
       PRINT HEADER
    ===================================================== */

    .print-header{
        display:block !important;
        text-align:center;
        margin-bottom:22px;
        border-bottom:2px solid #111827;
        padding-bottom:12px;
    }

    .print-title{
        font-size:20px;
        font-weight:800;
        text-transform:uppercase;
        color:#111827;
        letter-spacing:.5px;
        margin-bottom:5px;
    }

    .print-subtitle{
        font-size:11px;
        color:#4b5563;
        margin-bottom:4px;
    }

    .print-date{
        font-size:10px;
        color:#6b7280;
    }

    /* =====================================================
       TABLE STYLE
    ===================================================== */

    .table-wrapper{
        overflow:visible !important;
    }

    table{
        width:100%;
        border-collapse:collapse;
        page-break-inside:auto;
        font-size:10px;
        margin-top:10px;
    }

    thead{
        display:table-header-group;
    }

    tbody{
        display:table-row-group;
    }

    tr{
        page-break-inside:avoid;
        page-break-after:auto;
    }

    table th{
        background:#1e293b !important;
        color:#ffffff !important;
        border:1px solid #000 !important;
        padding:7px 6px !important;
        text-align:center;
        font-weight:700;
        text-transform:uppercase;
        font-size:10px;
    }

    table td{
        border:1px solid #000 !important;
        padding:6px !important;
        vertical-align:middle;
        background:#fff !important;
    }

    table tbody tr:nth-child(even){
        background:#f8fafc !important;
    }

    /* =====================================================
       TABLE CONTENT STYLE
    ===================================================== */

    td strong{
        color:#111827;
    }

    td span{
        font-size:10px !important;
    }

    /* =====================================================
       CARD
    ===================================================== */

    .card{
        border:none !important;
        box-shadow:none !important;
        background:#fff !important;
    }

    /* =====================================================
       SIGNATURE AREA
    ===================================================== */

    .ttd-wrapper{
        display:block !important;
        width:100%;
        margin-top:60px;
        page-break-inside:avoid;
    }

    /* TOP */

    .ttd-row-top{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        margin-bottom:55px;
    }

    /* BOTTOM */

    .ttd-row-bottom{
        display:flex;
        justify-content:center;
    }

    .ttd-box{
        width:40%;
        text-align:center;
    }

    .ttd-row-bottom .ttd-box{
        width:50%;
    }

    .ttd-jabatan{
        font-size:12px;
        font-weight:600;
        color:#111827;
        margin-bottom:80px;
    }

    .ttd-nama{
        font-size:12px;
        font-weight:800;
        text-transform:uppercase;
        text-decoration:underline;
        color:#111827;
        white-space:nowrap;
        margin-bottom:4px;
    }

    .ttd-nip{
        font-size:10px;
        color:#374151;
        white-space:nowrap;
    }

    /* =====================================================
       PREVENT PAGE BREAK
    ===================================================== */

    .ttd-wrapper,
    .ttd-box,
    table,
    tr,
    td,
    th{
        page-break-inside:avoid !important;
        break-inside:avoid !important;
    }

    /* =====================================================
       REMOVE SHADOW & RADIUS
    ===================================================== */

    *{
        box-shadow:none !important;
    }

    /* =====================================================
       HIDE URL WHEN PRINT
    ===================================================== */

    a[href]:after{
        content:none !important;
    }
}

/* =========================================================
   MODAL SYSTEM
========================================================= */

.modal-custom{
    position:fixed;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
}

.modal-custom.show{
    display:flex;
}

.modal-overlay{
    position:absolute;
    inset:0;
    background:rgba(15,23,42,.55);
    backdrop-filter:blur(2px);
}

.modal-box{
    position:relative;
    background:#ffffff;
    width:95%;
    max-width:650px;
    border-radius:22px;
    overflow:hidden;
    z-index:2;
    box-shadow:0 25px 80px rgba(0,0,0,.25);
    animation:modalShow .25s ease;
}

@keyframes modalShow{

    from{
        transform:translateY(20px) scale(.98);
        opacity:0;
    }

    to{
        transform:translateY(0) scale(1);
        opacity:1;
    }
}

.modal-header{
    padding:20px 24px;
    border-bottom:1px solid #e5e7eb;
    display:flex;
    align-items:center;
    justify-content:space-between;
    background:#f8fafc;
}

.modal-title{
    font-size:18px;
    font-weight:700;
    color:#111827;
}

.modal-close{
    border:none;
    background:none;
    font-size:28px;
    cursor:pointer;
    color:#6b7280;
}

.modal-close:hover{
    color:#111827;
}

.modal-body{
    padding:24px;
}

.modal-footer{
    padding:18px 24px;
    border-top:1px solid #e5e7eb;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    background:#f9fafb;
}

/* =========================================================
   BUTTON STYLE
========================================================= */

.btn-submit{
    border:none;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:#fff;
    padding:11px 20px;
    border-radius:12px;
    font-weight:700;
    cursor:pointer;
    transition:.25s;
}

.btn-submit:hover{
    transform:translateY(-1px);
    box-shadow:0 10px 20px rgba(37,99,235,.25);
}

.btn-cancel{
    border:none;
    background:#e5e7eb;
    color:#111827;
    padding:11px 18px;
    border-radius:12px;
    font-weight:600;
    cursor:pointer;
}

.btn-cancel:hover{
    background:#d1d5db;
}

.btn-primary-custom{
    border:none;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    color:#fff;
    padding:11px 18px;
    border-radius:12px;
    font-weight:700;
    cursor:pointer;
    transition:.25s;
}

.btn-primary-custom:hover{
    transform:translateY(-1px);
    box-shadow:0 10px 20px rgba(37,99,235,.25);
}
.btn-primary-custom{
    position: relative;
    z-index: 1000;
    pointer-events: auto;
}

.page-header{
    position: relative;
    z-index: 1000;
}

.card,
.table-wrapper,
table{
    position: relative;
    z-index: 1;
}
.btn-icon{
    border:none;
    background:#f1f5f9;
    width:36px;
    height:36px;
    border-radius:10px;
    cursor:pointer;
    transition:.2s;
}

.btn-icon:hover{
    transform:scale(1.05);
}

.btn-icon.danger{
    background:#fee2e2;
    color:#dc2626;
}

/* =========================================================
   FORM STYLE
========================================================= */

.form-group{
    margin-bottom:18px;
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
    border-radius:12px;
    background:#fff;
    font-size:14px;
    transition:.2s;
    outline:none;
}

.form-control:focus,
.form-select:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 4px rgba(37,99,235,.12);
}
</style>
<script>

/* =========================================================
   MODAL SYSTEM
========================================================= */

/*
|--------------------------------------------------------------------------
| OPEN MODAL
|--------------------------------------------------------------------------
*/
function openModal(id) {

    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.add('show');
    }
}

/*
|--------------------------------------------------------------------------
| CLOSE MODAL
|--------------------------------------------------------------------------
*/
function closeModal(id) {

    const modal = document.getElementById(id);

    if (modal) {
        modal.classList.remove('show');
    }
}

/*
|--------------------------------------------------------------------------
| CLOSE MODAL CLICK OUTSIDE
|--------------------------------------------------------------------------
*/
document.querySelectorAll('.modal-overlay').forEach(function(el){

    el.addEventListener('click', function(){

        const modal = this.closest('.modal-custom');

        if (modal) {
            modal.classList.remove('show');
        }
    });

});

/*
|--------------------------------------------------------------------------
| ESC BUTTON CLOSE
|--------------------------------------------------------------------------
*/
document.addEventListener('keydown', function(e){

    if (e.key === 'Escape') {

        document.querySelectorAll('.modal-custom.show')
        .forEach(function(modal){

            modal.classList.remove('show');

        });
    }
});


/* =========================================================
   DELETE CONFIRMATION
========================================================= */

/*
|--------------------------------------------------------------------------
| CONFIRM DELETE
|--------------------------------------------------------------------------
*/
function confirmDelete(url, nama){

    if (confirm('Yakin ingin menghapus ' + nama + ' ?')) {

        window.location.href = url;
    }
}


/* =========================================================
   EDIT MODAL
========================================================= */

/*
|--------------------------------------------------------------------------
| OPEN EDIT MODAL
|--------------------------------------------------------------------------
*/
function openEditModal(id, barangId, jumlah, tanggal, ket) {

    const editId       = document.getElementById('editId');
    const editBarang   = document.getElementById('editBarang');
    const editJumlah   = document.getElementById('editJumlah');
    const editTanggal  = document.getElementById('editTanggal');
    const editKet      = document.getElementById('editKet');

    if (editId)      editId.value      = id;
    if (editBarang)  editBarang.value  = barangId;
    if (editJumlah)  editJumlah.value  = jumlah;
    if (editTanggal) editTanggal.value = tanggal;
    if (editKet)     editKet.value     = ket;

    openModal('modalEdit');
}


/* =========================================================
   CETAK LAPORAN
========================================================= */

/*
|--------------------------------------------------------------------------
| TOGGLE JENIS CETAK
|--------------------------------------------------------------------------
*/
function toggleJenisCetak() {

    const jenis = document.getElementById('jenisLaporan').value;

    const groupTanggal = document.getElementById('groupTanggal');
    const groupBulan   = document.getElementById('groupBulan');

    /*
    |----------------------------------------------------------------------
    | HARIAN
    |----------------------------------------------------------------------
    */
    if (jenis === 'harian') {

        groupTanggal.style.display = 'block';
        groupBulan.style.display   = 'none';
    }

    /*
    |----------------------------------------------------------------------
    | BULANAN
    |----------------------------------------------------------------------
    */
    else if (jenis === 'bulanan') {

        groupTanggal.style.display = 'none';
        groupBulan.style.display   = 'block';
    }

    /*
    |----------------------------------------------------------------------
    | SEMUA
    |----------------------------------------------------------------------
    */
    else {

        groupTanggal.style.display = 'none';
        groupBulan.style.display   = 'none';
    }
}


/*
|--------------------------------------------------------------------------
| PROSES CETAK
|--------------------------------------------------------------------------
*/
function prosesCetak() {

    const jenis = document.getElementById('jenisLaporan').value;

    /*
    |----------------------------------------------------------------------
    | URL DASAR
    |----------------------------------------------------------------------
    */
    let url =
        'cetak_masuk.php?filter=' + jenis;

    /*
    |----------------------------------------------------------------------
    | HARIAN
    |----------------------------------------------------------------------
    */
    if (jenis === 'harian') {

        const tanggal =
            document.getElementById('filterTanggal').value;

        if (!tanggal) {

            alert('Pilih tanggal terlebih dahulu');
            return;
        }

        url += '&tanggal=' + tanggal;
    }

    /*
    |----------------------------------------------------------------------
    | BULANAN
    |----------------------------------------------------------------------
    */
    else if (jenis === 'bulanan') {

        const bulan =
            document.getElementById('filterBulan').value;

        if (!bulan) {

            alert('Pilih bulan terlebih dahulu');
            return;
        }

        url += '&bulan=' + bulan;
    }

    /*
    |----------------------------------------------------------------------
    | CLOSE MODAL
    |----------------------------------------------------------------------
    */
    closeModal('modalCetak');

    /*
    |----------------------------------------------------------------------
    | OPEN PAGE CETAK
    |----------------------------------------------------------------------
    */
    window.open(url, '_blank');
}


/* =========================================================
   SEARCH TABLE
========================================================= */

/*
|--------------------------------------------------------------------------
| SEARCH TABLE
|--------------------------------------------------------------------------
*/
const searchInput = document.getElementById('searchInput');

if (searchInput) {

    searchInput.addEventListener('keyup', function () {

        const keyword =
            this.value.toLowerCase();

        const rows =
            document.querySelectorAll('#mainTable tbody tr');

        rows.forEach(function(row){

            const text =
                row.innerText.toLowerCase();

            row.style.display =
                text.includes(keyword)
                ? ''
                : 'none';
        });
    });
}


/* =========================================================
   INIT
========================================================= */

/*
|--------------------------------------------------------------------------
| LOAD PERTAMA
|--------------------------------------------------------------------------
*/
document.addEventListener('DOMContentLoaded', function(){

    toggleJenisCetak();

});

</script>
<?php renderFooter(); ?>