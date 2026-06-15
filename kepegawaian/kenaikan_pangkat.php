<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| SANITIZE
|--------------------------------------------------------------------------
*/

if (!function_exists('sanitize')) {

    function sanitize($data)
    {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/*
|--------------------------------------------------------------------------
| HANDLE TAMBAH
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {

    $pegId       = (int)($_POST['pegawai_id'] ?? 0);
    $pangLama    = trim($_POST['pangkat_lama'] ?? '');
    $pangBaru    = trim($_POST['pangkat_baru'] ?? '');
    $golLama     = trim($_POST['golongan_lama'] ?? '');
    $golBaru     = trim($_POST['golongan_baru'] ?? '');
    $tglEfektif  = $_POST['tanggal_efektif'] ?? '';
    $noSk        = trim($_POST['no_sk'] ?? '');

    if ($pegId && $pangBaru && $golBaru && $tglEfektif) {

        Database::execute(
            "INSERT INTO kenaikan_pangkat 
            (
                pegawai_id,
                pangkat_lama,
                pangkat_baru,
                golongan_lama,
                golongan_baru,
                tanggal_efektif,
                no_sk
            )
            VALUES (?,?,?,?,?,?,?)",
            [
                $pegId,
                $pangLama,
                $pangBaru,
                $golLama,
                $golBaru,
                $tglEfektif,
                $noSk
            ]
        );

        Database::execute(
            "UPDATE pegawai 
             SET pangkat=?, golongan=? 
             WHERE id=?",
            [
                $pangBaru,
                $golBaru,
                $pegId
            ]
        );

        flashSet('success', 'Data kenaikan pangkat berhasil ditambahkan.');
    }

    redirect('/kepegawaian/kenaikan_pangkat.php');
}

/*
|--------------------------------------------------------------------------
| HANDLE EDIT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    $id           = (int)($_POST['id'] ?? 0);
    $pangLama     = trim($_POST['pangkat_lama'] ?? '');
    $pangBaru     = trim($_POST['pangkat_baru'] ?? '');
    $golLama      = trim($_POST['golongan_lama'] ?? '');
    $golBaru      = trim($_POST['golongan_baru'] ?? '');
    $tglEfektif   = $_POST['tanggal_efektif'] ?? '';
    $noSk         = trim($_POST['no_sk'] ?? '');

    Database::execute(
        "UPDATE kenaikan_pangkat SET
            pangkat_lama=?,
            pangkat_baru=?,
            golongan_lama=?,
            golongan_baru=?,
            tanggal_efektif=?,
            no_sk=?
        WHERE id=?",
        [
            $pangLama,
            $pangBaru,
            $golLama,
            $golBaru,
            $tglEfektif,
            $noSk,
            $id
        ]
    );

    flashSet('success', 'Data berhasil diperbarui.');

    redirect('/kepegawaian/kenaikan_pangkat.php');
}

/*
|--------------------------------------------------------------------------
| HANDLE HAPUS
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    Database::execute(
        "DELETE FROM kenaikan_pangkat WHERE id=?",
        [(int)$_GET['hapus']]
    );

    flashSet('success', 'Data berhasil dihapus.');

    redirect('/kepegawaian/kenaikan_pangkat.php');
}

/*
|--------------------------------------------------------------------------
| GET DATA
|--------------------------------------------------------------------------
*/

$kpList = Database::fetchAll(
    "SELECT 
        kp.*,
        p.nama,
        p.nip,
        p.jabatan
    FROM kenaikan_pangkat kp
    JOIN pegawai p ON p.id = kp.pegawai_id
    ORDER BY kp.tanggal_efektif DESC"
);

$pegawais = Database::fetchAll(
    "SELECT * FROM pegawai
     WHERE status='Aktif'
     ORDER BY nama ASC"
);

$kpStats = Database::fetchAll(
    "SELECT 
        YEAR(tanggal_efektif) as thn,
        COUNT(*) as cnt
    FROM kenaikan_pangkat
    GROUP BY thn
    ORDER BY thn ASC"
);

/*
|--------------------------------------------------------------------------
| STATS
|--------------------------------------------------------------------------
*/

$totalKP            = count($kpList);

$totalTahunIni      = count(
    array_filter($kpList, function($k){

        return date('Y', strtotime($k['tanggal_efektif'])) == date('Y');
    })
);

$totalPegawaiNaik   = count(
    array_unique(array_column($kpList, 'pegawai_id'))
);

renderHeader('Kenaikan Pangkat', 'kepegawaian-kp');
?>

<style>

/*
|--------------------------------------------------------------------------
| GLOBAL PREMIUM STYLE
|--------------------------------------------------------------------------
*/

:root{

    --primary:#2563eb;
    --primary2:#1d4ed8;
    --success:#10b981;
    --danger:#ef4444;
    --warning:#f59e0b;
    --dark:#0f172a;

    --text:#0f172a;
    --muted:#64748b;

    --border:#e2e8f0;
    --border2:#f1f5f9;

    --bg:#f8fafc;
    --white:#ffffff;

    --shadow:
        0 10px 30px rgba(15,23,42,.06);

    --shadow-hover:
        0 18px 40px rgba(15,23,42,.10);

    --radius:22px;
}

/*
|--------------------------------------------------------------------------
| BODY
|--------------------------------------------------------------------------
*/

body{

    background:
        linear-gradient(
            180deg,
            #f8fbff 0%,
            #f1f5f9 100%
        );

    color:var(--text);
}

/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

.page-header{

    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:24px;
    gap:20px;
}

.page-title{

    font-size:30px;
    font-weight:900;
    color:var(--dark);
    letter-spacing:-.5px;
}

.page-subtitle{

    font-size:14px;
    color:var(--muted);
    margin-top:6px;
}

/*
|--------------------------------------------------------------------------
| BUTTON PRIMARY
|--------------------------------------------------------------------------
*/

.btn-primary-custom{

    border:none;
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    color:white;
    padding:13px 22px;
    border-radius:16px;
    font-size:14px;
    font-weight:800;
    cursor:pointer;

    display:flex;
    align-items:center;
    gap:10px;

    transition:.3s ease;

    box-shadow:
        0 12px 24px rgba(37,99,235,.25);
}

.btn-primary-custom:hover{

    transform:
        translateY(-3px);

    box-shadow:
        0 18px 34px rgba(37,99,235,.30);
}

/*
|--------------------------------------------------------------------------
| GRID
|--------------------------------------------------------------------------
*/

.stats-grid{

    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
    margin-bottom:24px;
}

.grid-2{

    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
    margin-bottom:24px;
}

/*
|--------------------------------------------------------------------------
| CARD
|--------------------------------------------------------------------------
*/

.card,
.stat-card{

    background:
        rgba(255,255,255,.92);

    backdrop-filter:
        blur(12px);

    border:
        1px solid rgba(255,255,255,.4);

    border-radius:
        var(--radius);

    box-shadow:
        var(--shadow);

    transition:.3s ease;
}

.card:hover,
.stat-card:hover{

    transform:
        translateY(-4px);

    box-shadow:
        var(--shadow-hover);
}

.stat-card{

    padding:24px;
}

.stat-icon{

    width:56px;
    height:56px;
    border-radius:18px;

    display:flex;
    align-items:center;
    justify-content:center;

    margin-bottom:16px;

    color:white;
    font-size:22px;
}

.stat-icon.blue{

    background:
        linear-gradient(135deg,#2563eb,#1d4ed8);
}

.stat-icon.green{

    background:
        linear-gradient(135deg,#10b981,#059669);
}

.stat-icon.orange{

    background:
        linear-gradient(135deg,#f59e0b,#d97706);
}

.stat-value{

    font-size:38px;
    font-weight:900;
    color:var(--dark);
}

.stat-label{

    margin-top:8px;
    color:var(--muted);
    font-size:13px;
    font-weight:700;
}

/*
|--------------------------------------------------------------------------
| CARD HEADER
|--------------------------------------------------------------------------
*/

.card-header{

    padding:20px 22px;

    border-bottom:
        1px solid var(--border2);

    display:flex;
    align-items:center;
    justify-content:space-between;
}

.card-title{

    font-size:15px;
    font-weight:800;

    display:flex;
    align-items:center;
    gap:10px;
}

/*
|--------------------------------------------------------------------------
| CARD BODY
|--------------------------------------------------------------------------
*/

.card-body{

    padding:22px;
}

/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

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

    padding:16px;
    font-size:12px;
    color:var(--muted);
    text-transform:uppercase;
    font-weight:800;
}

.table td{

    padding:16px;
    border-top:1px solid var(--border2);
    vertical-align:middle;
    font-size:13px;
}

.table tbody tr{

    transition:.2s ease;
}

.table tbody tr:hover{

    background:#f8fbff;
}

/*
|--------------------------------------------------------------------------
| BADGE
|--------------------------------------------------------------------------
*/

.badge-gol{

    background:#eff6ff;
    color:#2563eb;

    padding:6px 12px;
    border-radius:999px;

    font-size:12px;
    font-weight:800;
}

/*
|--------------------------------------------------------------------------
| BUTTON ACTION
|--------------------------------------------------------------------------
*/

.btn-action{

    width:40px;
    height:40px;

    border:none;
    border-radius:12px;

    color:white;
    cursor:pointer;

    display:flex;
    align-items:center;
    justify-content:center;

    transition:.25s ease;
}

.btn-action:hover{

    transform:
        translateY(-2px)
        scale(1.05);
}

.btn-edit{

    background:
        linear-gradient(
            135deg,
            #3b82f6,
            #2563eb
        );

    box-shadow:
        0 8px 18px rgba(37,99,235,.22);
}

.btn-delete{

    background:
        linear-gradient(
            135deg,
            #ef4444,
            #dc2626
        );

    box-shadow:
        0 8px 18px rgba(239,68,68,.20);
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

.search-bar{

    position:relative;
}

.search-bar i{

    position:absolute;
    left:14px;
    top:12px;

    color:#94a3b8;
}

.search-bar input{

    width:240px;
    height:44px;

    border:
        1px solid #dbeafe;

    border-radius:14px;

    padding-left:40px;

    background:white;

    outline:none;

    transition:.25s ease;
}

.search-bar input:focus{

    border-color:#2563eb;

    box-shadow:
        0 0 0 4px rgba(37,99,235,.10);
}

/*
|--------------------------------------------------------------------------
| MODAL
|--------------------------------------------------------------------------
*/

.modal-custom{

    position:fixed;
    inset:0;

    background:
        rgba(15,23,42,.55);

    display:none;
    justify-content:center;
    align-items:center;

    z-index:99999;

    padding:20px;

    backdrop-filter:blur(4px);
}

.modal-custom.show{

    display:flex !important;
}

.modal-box{

    width:100%;
    max-width:620px;

    background:white;

    border-radius:28px;

    overflow:hidden;

    animation:modalShow .25s ease;

    box-shadow:
        0 25px 60px rgba(0,0,0,.30);
}

@keyframes modalShow{

    from{

        opacity:0;
        transform:
            translateY(20px)
            scale(.98);
    }

    to{

        opacity:1;
        transform:
            translateY(0)
            scale(1);
    }
}

.modal-header{

    padding:22px;

    border-bottom:
        1px solid var(--border2);

    display:flex;
    justify-content:space-between;
    align-items:center;
}

.modal-title{

    font-size:18px;
    font-weight:900;

    display:flex;
    align-items:center;
    gap:10px;
}

.modal-close{

    border:none;

    width:42px;
    height:42px;

    border-radius:14px;

    background:#f1f5f9;

    cursor:pointer;

    font-size:20px;
    transition:.25s ease;
}

.modal-close:hover{

    background:#e2e8f0;
    transform:rotate(90deg);
}

.modal-body{

    padding:24px;
}

.modal-footer{

    padding:22px;

    border-top:
        1px solid var(--border2);

    display:flex;
    justify-content:flex-end;
    gap:12px;
}

/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

.form-group{

    margin-bottom:18px;
}

.form-label{

    display:block;
    margin-bottom:8px;

    font-size:13px;
    font-weight:800;

    color:#334155;
}

.form-control,
.form-select{

    width:100%;
    height:50px;

    border:
        1px solid #dbeafe;

    border-radius:16px;

    padding:0 16px;

    font-size:14px;

    background:white;

    outline:none;

    transition:.25s ease;
}

.form-control:focus,
.form-select:focus{

    border-color:#2563eb;

    box-shadow:
        0 0 0 4px rgba(37,99,235,.10);
}

.btn-submit{

    border:none;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d4ed8
        );

    color:white;

    height:48px;

    padding:0 24px;

    border-radius:16px;

    font-weight:800;

    cursor:pointer;

    transition:.25s ease;
}

.btn-submit:hover{

    transform:
        translateY(-2px);
}

.btn-cancel{

    border:none;

    background:#e2e8f0;

    color:#0f172a;

    height:48px;

    padding:0 24px;

    border-radius:16px;

    font-weight:800;

    cursor:pointer;
}

/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media(max-width:992px){

    .stats-grid,
    .grid-2{

        grid-template-columns:1fr;
    }

    .page-header{

        flex-direction:column;
    }

    .search-bar input{

        width:100%;
    }
}

</style>

<!-- PAGE HEADER -->

<div class="page-header">

    <div>

        <div class="page-title">
            Kenaikan Pangkat
        </div>

        <div class="page-subtitle">
            Rekap kenaikan pangkat pegawai Bapas Kelas I Jakarta Selatan
        </div>
    </div>

    <button type="button"
            class="btn-primary-custom"
            onclick="openModal('modalTambah')">

        <i class="bi bi-plus-lg"></i>
        Tambah Data
    </button>
</div>

<!-- STATS -->

<div class="stats-grid">

    <div class="stat-card">

        <div class="stat-icon blue">
            <i class="bi bi-arrow-up-circle-fill"></i>
        </div>

        <div class="stat-value">
            <?= $totalKP ?>
        </div>

        <div class="stat-label">
            TOTAL RIWAYAT KP
        </div>
    </div>

    <div class="stat-card">

        <div class="stat-icon green">
            <i class="bi bi-calendar-check-fill"></i>
        </div>

        <div class="stat-value">
            <?= $totalTahunIni ?>
        </div>

        <div class="stat-label">
            KP TAHUN <?= date('Y') ?>
        </div>
    </div>

    <div class="stat-card">

        <div class="stat-icon orange">
            <i class="bi bi-people-fill"></i>
        </div>

        <div class="stat-value">
            <?= $totalPegawaiNaik ?>
        </div>

        <div class="stat-label">
            PEGAWAI PERNAH NAIK PANGKAT
        </div>
    </div>
</div>

<!-- GRID -->

<div class="grid-2">

    <div class="card">

        <div class="card-header">

            <div class="card-title">
                <i class="bi bi-bar-chart-fill"></i>
                Grafik Kenaikan Pangkat
            </div>

            <div style="font-size:12px;color:#64748b;">
                Per Tahun
            </div>
        </div>

        <div class="card-body">

            <div style="height:260px;">
                <canvas id="kpChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">

        <div class="card-header">

            <div class="card-title">
                <i class="bi bi-table"></i>
                Rekap Kenaikan Pangkat
            </div>
        </div>

        <div class="table-wrapper">

            <table class="table">

                <thead>

                    <tr>
                        <th>Tahun</th>
                        <th>Jumlah KP</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach($kpStats as $s): ?>

                    <tr>

                        <td>
                            <?= $s['thn'] ?>
                        </td>

                        <td>

                            <strong style="color:#2563eb;">

                                <?= $s['cnt'] ?> orang

                            </strong>
                        </td>
                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>
        </div>
    </div>
</div>

<!-- TABLE -->

<div class="card">

    <div class="card-header">

        <div class="card-title">

            <i class="bi bi-list-check"></i>
            Data Terbaru Kenaikan Pangkat
        </div>

        <div class="search-bar">

            <i class="bi bi-search"></i>

            <input type="text"
                   id="searchInput"
                   placeholder="Cari pegawai...">
        </div>
    </div>

    <div class="table-wrapper">

        <table class="table" id="mainTable">

            <thead>

                <tr>

                    <th>No</th>
                    <th>Nama Pegawai</th>
                    <th>Jabatan</th>
                    <th>Pangkat Lama</th>
                    <th>Pangkat Baru</th>
                    <th>Gol</th>
                    <th>Tanggal Efektif</th>
                    <th>No SK</th>
                    <th>Aksi</th>

                </tr>
            </thead>

            <tbody>

                <?php foreach($kpList as $i => $kp): ?>

                <tr>

                    <td><?= $i + 1 ?></td>

                    <td>

                        <strong>
                            <?= sanitize($kp['nama']) ?>
                        </strong>

                        <br>

                        <small style="color:#64748b;">
                            <?= sanitize($kp['nip']) ?>
                        </small>
                    </td>

                    <td>
                        <?= sanitize($kp['jabatan']) ?>
                    </td>

                    <td>
                        <?= sanitize($kp['pangkat_lama']) ?>
                    </td>

                    <td>

                        <strong style="color:#16a34a;">

                            <?= sanitize($kp['pangkat_baru']) ?>

                        </strong>
                    </td>

                    <td>

                        <span class="badge-gol">

                            <?= sanitize($kp['golongan_lama']) ?>
                            →
                            <?= sanitize($kp['golongan_baru']) ?>

                        </span>
                    </td>

                    <td>

                        <?= date('d/m/Y', strtotime($kp['tanggal_efektif'])) ?>

                    </td>

                    <td>

                        <?= sanitize($kp['no_sk']) ?>

                    </td>

                    <td>

                        <div style="display:flex;gap:8px;">

                            <!-- EDIT -->

                            <button type="button"
                                    class="btn-action btn-edit"
                                    onclick='openEditModal(<?= json_encode($kp) ?>)'>

                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <!-- DELETE -->

                            <button type="button"
                                    class="btn-action btn-delete"
                                    onclick="confirmDelete(
                                        '/kepegawaian/kenaikan_pangkat.php?hapus=<?= $kp['id'] ?>',
                                        '<?= addslashes(sanitize($kp['nama'])) ?>'
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

<!-- MODAL TAMBAH -->

<div class="modal-custom" id="modalTambah">

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                <i class="bi bi-plus-circle"></i>
                Tambah Data Kenaikan Pangkat
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

                <div class="form-group">

                    <label class="form-label">
                        Nama Pegawai
                    </label>

                    <select name="pegawai_id"
                            class="form-select"
                            required>

                        <option value="">
                            -- Pilih Pegawai --
                        </option>

                        <?php foreach($pegawais as $p): ?>

                        <option
                            value="<?= $p['id'] ?>"
                            data-pangkat="<?= sanitize($p['pangkat']) ?>"
                            data-gol="<?= sanitize($p['golongan']) ?>">

                            <?= sanitize($p['nama']) ?>

                        </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

                    <div class="form-group">

                        <label class="form-label">
                            Pangkat Lama
                        </label>

                        <input type="text"
                               name="pangkat_lama"
                               id="pangkatLama"
                               class="form-control">
                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Golongan Lama
                        </label>

                        <input type="text"
                               name="golongan_lama"
                               id="golLama"
                               class="form-control">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

                    <div class="form-group">

                        <label class="form-label">
                            Pangkat Baru
                        </label>

                        <input type="text"
                               name="pangkat_baru"
                               class="form-control"
                               required>
                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Golongan Baru
                        </label>

                        <input type="text"
                               name="golongan_baru"
                               class="form-control"
                               required>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

                    <div class="form-group">

                        <label class="form-label">
                            Tanggal Efektif
                        </label>

                        <input type="date"
                               name="tanggal_efektif"
                               class="form-control"
                               value="<?= date('Y-m-d') ?>"
                               required>
                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Nomor SK
                        </label>

                        <input type="text"
                               name="no_sk"
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

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                <i class="bi bi-pencil-square"></i>
                Edit Data Kenaikan Pangkat
            </div>

            <button type="button"
                    class="modal-close"
                    onclick="closeModal('modalEdit')">

                &times;
            </button>
        </div>

        <form method="POST">

            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">

            <div class="modal-body">

                <div class="form-group">

                    <label class="form-label">
                        Pangkat Lama
                    </label>

                    <input type="text"
                           name="pangkat_lama"
                           id="editPangkatLama"
                           class="form-control">
                </div>

                <div class="form-group">

                    <label class="form-label">
                        Pangkat Baru
                    </label>

                    <input type="text"
                           name="pangkat_baru"
                           id="editPangkatBaru"
                           class="form-control">
                </div>

                <div class="form-group">

                    <label class="form-label">
                        Golongan Lama
                    </label>

                    <input type="text"
                           name="golongan_lama"
                           id="editGolLama"
                           class="form-control">
                </div>

                <div class="form-group">

                    <label class="form-label">
                        Golongan Baru
                    </label>

                    <input type="text"
                           name="golongan_baru"
                           id="editGolBaru"
                           class="form-control">
                </div>

                <div class="form-group">

                    <label class="form-label">
                        Tanggal Efektif
                    </label>

                    <input type="date"
                           name="tanggal_efektif"
                           id="editTanggal"
                           class="form-control">
                </div>

                <div class="form-group">

                    <label class="form-label">
                        Nomor SK
                    </label>

                    <input type="text"
                           name="no_sk"
                           id="editNoSk"
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
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<script>

/*
|--------------------------------------------------------------------------
| OPEN MODAL
|--------------------------------------------------------------------------
*/

function openModal(id){

    const modal = document.getElementById(id);

    if(modal){

        modal.style.display = 'flex';
        modal.classList.add('show');

        document.body.style.overflow = 'hidden';
    }
}

/*
|--------------------------------------------------------------------------
| CLOSE MODAL
|--------------------------------------------------------------------------
*/

function closeModal(id){

    const modal = document.getElementById(id);

    if(modal){

        modal.style.display = 'none';
        modal.classList.remove('show');

        document.body.style.overflow = 'auto';
    }
}

/*
|--------------------------------------------------------------------------
| OPEN EDIT MODAL
|--------------------------------------------------------------------------
*/

function openEditModal(data){

    document.getElementById('editId').value =
        data.id || '';

    document.getElementById('editPangkatLama').value =
        data.pangkat_lama || '';

    document.getElementById('editPangkatBaru').value =
        data.pangkat_baru || '';

    document.getElementById('editGolLama').value =
        data.golongan_lama || '';

    document.getElementById('editGolBaru').value =
        data.golongan_baru || '';

    document.getElementById('editTanggal').value =
        data.tanggal_efektif || '';

    document.getElementById('editNoSk').value =
        data.no_sk || '';

    openModal('modalEdit');
}

/*
|--------------------------------------------------------------------------
| CLICK OUTSIDE
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.modal-custom')
.forEach(function(modal){

    modal.addEventListener('click', function(e){

        if(e.target === modal){

            closeModal(modal.id);
        }
    });
});

/*
|--------------------------------------------------------------------------
| AUTO FILL
|--------------------------------------------------------------------------
*/

document.querySelector('select[name="pegawai_id"]')
?.addEventListener('change', function(){

    const opt =
        this.options[this.selectedIndex];

    document.getElementById('pangkatLama').value =
        opt.dataset.pangkat || '';

    document.getElementById('golLama').value =
        opt.dataset.gol || '';
});

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

document.getElementById('searchInput')
?.addEventListener('keyup', function(){

    const value =
        this.value.toLowerCase();

    document.querySelectorAll('#mainTable tbody tr')
    .forEach(function(row){

        row.style.display =
            row.innerText.toLowerCase().includes(value)
            ? ''
            : 'none';
    });
});

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/

function confirmDelete(url, nama){

    if(confirm('Hapus data ' + nama + ' ?')){

        window.location.href = url;
    }
}

/*
|--------------------------------------------------------------------------
| CHART
|--------------------------------------------------------------------------
*/

const ctx =
    document.getElementById('kpChart');

if(ctx){

    new Chart(ctx, {

        type:'bar',

        data:{

            labels:[
                <?= implode(',', array_map(fn($s) => "'".$s['thn']."'", $kpStats)) ?>
            ],

            datasets:[{

                label:'Kenaikan Pangkat',

                data:[
                    <?= implode(',', array_map(fn($s) => $s['cnt'], $kpStats)) ?>
                ],

                backgroundColor:[
                    '#2563eb',
                    '#3b82f6',
                    '#60a5fa',
                    '#93c5fd',
                    '#bfdbfe'
                ],

                borderRadius:14,
                borderSkipped:false
            }]
        },

        options:{

            responsive:true,
            maintainAspectRatio:false,

            plugins:{

                legend:{
                    display:false
                }
            },

            scales:{

                y:{

                    beginAtZero:true,

                    ticks:{
                        precision:0
                    },

                    grid:{
                        color:'#f1f5f9'
                    }
                },

                x:{

                    grid:{
                        display:false
                    }
                }
            }
        }
    });
}

</script>

<?php renderFooter(); ?>