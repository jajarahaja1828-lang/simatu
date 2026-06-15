<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

/*
|--------------------------------------------------------------------------
| TAMBAH PEGAWAI
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {

    $nip       = trim($_POST['nip'] ?? '');
    $nama      = trim($_POST['nama'] ?? '');
    $pangkat   = trim($_POST['pangkat'] ?? '');
    $gol       = $_POST['golongan'] ?? 'III';
    $jab       = trim($_POST['jabatan'] ?? '');
    $unit      = trim($_POST['unit_kerja'] ?? '');
    $jk        = $_POST['jenis_kelamin'] ?? 'L';

    if ($nip && $nama) {

        try {

            Database::execute(
                'INSERT INTO pegawai
                (
                    nip,
                    nama,
                    pangkat,
                    golongan,
                    jabatan,
                    unit_kerja,
                    jenis_kelamin
                )
                VALUES (?,?,?,?,?,?,?)',
                [
                    $nip,
                    $nama,
                    $pangkat,
                    $gol,
                    $jab,
                    $unit,
                    $jk
                ]
            );

            flashSet(
                'success',
                'Data pegawai berhasil ditambahkan.'
            );

        } catch (PDOException $e) {

            flashSet(
                'error',
                'NIP sudah terdaftar.'
            );
        }
    }

    redirect('/kepegawaian/index.php');
}

/*
|--------------------------------------------------------------------------
| EDIT PEGAWAI
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    $id       = (int)($_POST['id'] ?? 0);
    $nama     = trim($_POST['nama'] ?? '');
    $pangkat  = trim($_POST['pangkat'] ?? '');
    $gol      = $_POST['golongan'] ?? 'III';
    $jab      = trim($_POST['jabatan'] ?? '');
    $unit     = trim($_POST['unit_kerja'] ?? '');
    $status   = $_POST['status'] ?? 'Aktif';

    if ($id && $nama) {

        Database::execute(
            'UPDATE pegawai
            SET
                nama=?,
                pangkat=?,
                golongan=?,
                jabatan=?,
                unit_kerja=?,
                status=?
            WHERE id=?',
            [
                $nama,
                $pangkat,
                $gol,
                $jab,
                $unit,
                $status,
                $id
            ]
        );

        flashSet(
            'success',
            'Data pegawai berhasil diperbarui.'
        );
    }

    redirect('/kepegawaian/index.php');
}

/*
|--------------------------------------------------------------------------
| HAPUS
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    Database::execute(
        'DELETE FROM pegawai WHERE id=?',
        [(int)$_GET['hapus']]
    );

    flashSet(
        'success',
        'Data pegawai berhasil dihapus.'
    );

    redirect('/kepegawaian/index.php');
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$pegawais = Database::fetchAll(
    'SELECT * FROM pegawai
    ORDER BY golongan DESC, nama ASC'
);

$totalAktif = count(
    array_filter(
        $pegawais,
        fn($p) => $p['status'] === 'Aktif'
    )
);

$golStats = Database::fetchAll(
    "SELECT
        golongan,
        COUNT(*) as cnt
    FROM pegawai
    WHERE status='Aktif'
    GROUP BY golongan
    ORDER BY golongan"
);

$golMap = [];

foreach ($golStats as $g) {

    $golMap[$g['golongan']] =
        (int)$g['cnt'];
}

renderHeader(
    'Data Kepegawaian',
    'kepegawaian'
);
?>

<style>
:root {
    --bg: #f8fafc;
    --surface: #ffffff;
    --surface-soft: #f1f5f9;
    --border: #e2e8f0;
    --text: #0f172a;
    --muted: #64748b;
    --primary: #2563eb;
    --primary-strong: #1d4ed8;
    --success: #10b981;
    --danger: #dc2626;
    --radius: 24px;
    --shadow: 0 24px 70px rgba(15, 23, 42, 0.08);
}

body {
    margin: 0;
    font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: linear-gradient(180deg, #f8fbff 0%, #eef2ff 100%);
    color: var(--text);
}

.page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 28px;
}

.page-title {
    font-size: 32px;
    font-weight: 900;
    margin: 0;
}

.page-subtitle {
    color: var(--muted);
    margin-top: 8px;
    max-width: 560px;
    line-height: 1.6;
}

.page-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.btn-primary-custom {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    height: 50px;
    padding: 0 22px;
    border: none;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--primary), var(--primary-strong));
    color: #fff;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 16px 40px rgba(37, 99, 235, 0.22);
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 22px 50px rgba(37, 99, 235, 0.28);
}

.section-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: var(--radius);
    padding: 24px;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 18px;
    align-items: center;
    box-shadow: var(--shadow);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.12);
}

.stat-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: var(--muted);
    margin-bottom: 8px;
}

.stat-value {
    font-size: 38px;
    font-weight: 900;
    color: var(--text);
    line-height: 1;
}

.stat-meta {
    font-size: 13px;
    color: var(--muted);
}

.stat-icon {
    width: 62px;
    height: 62px;
    border-radius: 20px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 24px;
}

.stat-icon.blue {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.stat-icon.green {
    background: linear-gradient(135deg, #10b981, #059669);
}

.card {
    background: var(--surface);
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.card-header {
    padding: 22px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.card-title {
    font-size: 16px;
    font-weight: 900;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-body {
    padding: 24px;
}

.search-bar {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 280px;
    height: 48px;
    padding: 0 16px;
    gap: 10px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #dbeafe;
}

.search-bar i {
    position: absolute;
    left: 16px;
    color: #94a3b8;
}

.search-bar input {
    width: 100%;
    height: 100%;
    padding-left: 42px;
    border: none;
    outline: none;
    background: transparent;
    color: var(--text);
    font-size: 14px;
}

.table-wrapper {
    overflow: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    min-width: 720px;
}

.table thead th,
.table tbody td {
    padding: 16px 18px;
}

.table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid var(--border);
}

.table tbody td {
    border-top: 1px solid #f1f5f9;
    color: #334155;
    font-size: 14px;
}

.table tbody tr:hover {
    background: #f5f9ff;
}

.avatar-pill {
    width: 36px;
    height: 36px;
    border-radius: 999px;
    background: #2563eb;
    color: white;
    display: grid;
    place-items: center;
    font-weight: 800;
    font-size: 14px;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.status-pill.active {
    background: #dcfce7;
    color: #166534;
}

.status-pill.off {
    background: #fee2e2;
    color: #991b1b;
}

.badge-gol {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    border-radius: 999px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 12px;
    font-weight: 700;
}

.btn-icon {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 14px;
    background: #eff6ff;
    color: #2563eb;
    cursor: pointer;
    display: grid;
    place-items: center;
    transition: transform 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.btn-icon:hover {
    transform: translateY(-2px);
    background: #2563eb;
    color: #fff;
}

.btn-icon.danger {
    background: #fee2e2;
    color: #dc2626;
}

.btn-icon.danger:hover {
    background: #dc2626;
    color: #fff;
}

.modal-custom {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(5px);
    z-index: 99999;
    padding: 20px;
}

.modal-custom.show {
    display: flex;
}

.modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
}

.modal-box {
    position: relative;
    width: 100%;
    max-width: 680px;
    background: var(--surface);
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.15);
    transform: translateY(24px);
    opacity: 0;
    animation: modalEnter 0.24s forwards;
}

.modal-header {
    padding: 24px 26px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 18px;
    font-weight: 900;
    color: var(--text);
}

.modal-close {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 16px;
    background: #f1f5f9;
    color: var(--text);
    cursor: pointer;
    font-size: 22px;
    transition: transform 0.2s ease, background 0.2s ease;
}

.modal-close:hover {
    transform: rotate(90deg);
    background: #e2e8f0;
}

.modal-body {
    padding: 24px 26px;
}

.modal-footer {
    padding: 20px 26px;
    background: #f8fafc;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.form-group {
    margin-bottom: 18px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}

.form-control,
.form-select {
    width: 100%;
    height: 50px;
    padding: 0 16px;
    border: 1px solid #dbe3f0;
    border-radius: 16px;
    background: #fff;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.10);
}

.btn-submit {
    height: 50px;
    padding: 0 22px;
    border: none;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--primary), var(--primary-strong));
    color: #fff;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.btn-submit:hover {
    transform: translateY(-2px);
}

.btn-cancel {
    height: 50px;
    padding: 0 22px;
    border: none;
    border-radius: 16px;
    background: #e2e8f0;
    color: var(--text);
    font-weight: 700;
    cursor: pointer;
}

@keyframes modalEnter {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 980px) {
    .section-grid {
        grid-template-columns: 1fr;
    }
    .page-header {
        align-items: flex-start;
    }
    .table {
        min-width: 0;
    }
    .search-bar {
        min-width: 100%;
    }
}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Data Kepegawaian</h1>
        <p class="page-subtitle">Kelola data pegawai dan status kepegawaian dengan tampilan dashboard profesional dan navigasi yang jelas.</p>
    </div>

    <div class="page-actions">
        <button type="button" class="btn-primary-custom" onclick="openModal('modalTambah')">
            <i class="bi bi-person-plus"></i>
            Tambah Pegawai
        </button>
    </div>
</div>
<!-- MODAL EDIT -->

<div class="modal-custom"
     id="modalEdit">

    <div class="modal-overlay"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                <i class="bi bi-pencil-square"></i>
                Edit Pegawai

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

                        Nama Pegawai

                    </label>

                    <input type="text"
                           name="nama"
                           id="editNama"
                           class="form-control"
                           required>

                </div>

                <div style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:14px;
                ">

                    <div class="form-group">

                        <label class="form-label">

                            Pangkat

                        </label>

                        <input type="text"
                               name="pangkat"
                               id="editPangkat"
                               class="form-control">

                    </div>

                    <div class="form-group">

                        <label class="form-label">

                            Golongan

                        </label>

                        <select name="golongan"
                                id="editGolongan"
                                class="form-select">

                            <option value="I">I</option>
                            <option value="II">II</option>
                            <option value="III">III</option>
                            <option value="IV">IV</option>

                        </select>

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">

                        Jabatan

                    </label>

                    <input type="text"
                           name="jabatan"
                           id="editJabatan"
                           class="form-control">

                </div>

                <div style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:14px;
                ">

                    <div class="form-group">

                        <label class="form-label">

                            Unit Kerja

                        </label>

                        <input type="text"
                               name="unit_kerja"
                               id="editUnit"
                               class="form-control">

                    </div>

                    <div class="form-group">

                        <label class="form-label">

                            Status

                        </label>

                        <select name="status"
                                id="editStatus"
                                class="form-select">

                            <option value="Aktif">
                                Aktif
                            </option>

                            <option value="Pensiun">
                                Pensiun
                            </option>

                        </select>

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

                    <i class="bi bi-check-circle"></i>
                    Update Data

                </button>

            </div>

        </form>

    </div>

</div>

<!-- STATS -->

<div class="section-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Jumlah Pegawai Aktif</div>
            <div class="stat-value"><?= $totalAktif ?></div>
            <div class="stat-meta">Total pegawai yang sedang aktif bertugas.</div>
        </div>
        <div class="stat-icon blue">
            <i class="bi bi-people-fill"></i>
        </div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-label">Distribusi Golongan</div>
            <div class="stat-value"><?= array_sum($golMap) ?></div>
            <div class="stat-meta">Jumlah pegawai aktif berdasarkan golongan.</div>
        </div>
        <div class="stat-icon green">
            <i class="bi bi-bar-chart-fill"></i>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <div class="card-title">
            <i class="bi bi-bar-chart-line-fill"></i>
            Grafik Distribusi Golongan
        </div>
    </div>
    <div class="card-body">
        <div style="height:220px;">
            <canvas id="golChart"></canvas>
        </div>
    </div>
</div>

<!-- TABLE -->

<div class="card">

    <div class="card-header">

        <div class="card-title">

            <i class="bi bi-people-fill me-2"></i>
            Daftar Pegawai

        </div>

        <div class="search-bar">

            <i class="bi bi-search"></i>

            <input type="text"
                   id="searchInput"
                   placeholder="Cari pegawai...">

        </div>

    </div>

    <div class="table-wrapper">

        <table class="table"
               id="mainTable">

            <thead>

            <tr>

                <th>No</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Pangkat / Golongan</th>
                <th>Jabatan</th>
                <th>Unit Kerja</th>
                <th>Status</th>
                <th>Aksi</th>

            </tr>

            </thead>

            <tbody>

            <?php foreach ($pegawais as $i => $p): ?>

            <tr>

                <td><?= $i + 1 ?></td>

                <td>

                    <span style="font-family:monospace;">

                        <?= sanitize($p['nip']) ?>

                    </span>

                </td>

                <td>

                    <div style="display:flex;align-items:center;gap:12px;">
                    <div class="avatar-pill">
                        <?= strtoupper(substr($p['nama'],0,1)) ?>
                    </div>
                    <strong><?= sanitize($p['nama']) ?></strong>
                </div>

                </td>

                <td>

                    <div>
                        <?= sanitize($p['pangkat']) ?>
                    </div>

                    <div style="margin-top:6px;">
                        <span class="badge-gol">Gol. <?= sanitize($p['golongan']) ?></span>
                    </div>

                </td>

                <td>
                    <?= sanitize($p['jabatan']) ?>
                </td>

                <td>
                    <?= sanitize($p['unit_kerja']) ?>
                </td>

                <td>

                    <span class="badge-custom <?= $p['status']==='Aktif'
                        ? 'badge-aktif'
                        : 'badge-pensiun' ?>">

                        <?= sanitize($p['status']) ?>

                    </span>

                </td>

                <td>

                    <div style="display:flex;gap:6px;">

                                <button type="button" class="btn-icon" onclick='openEditModal(<?= json_encode($p) ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn-icon danger" onclick="confirmDelete('/simatu/public/kepegawaian/index.php?hapus=<?= $p['id'] ?>', '<?= addslashes($p['nama']) ?>')">
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

<div class="modal-custom"
     id="modalTambah">

    <div class="modal-overlay"></div>

    <div class="modal-box">

        <div class="modal-header">

            <div class="modal-title">

                Tambah Data Pegawai

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
                            NIP
                        </label>

                        <input type="text"
                               name="nip"
                               class="form-control"
                               required>

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Jenis Kelamin
                        </label>

                        <select name="jenis_kelamin"
                                class="form-select">

                            <option value="L">
                                Laki-laki
                            </option>

                            <option value="P">
                                Perempuan
                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Nama Lengkap
                    </label>

                    <input type="text"
                           name="nama"
                           class="form-control"
                           required>

                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

                    <div class="form-group">

                        <label class="form-label">
                            Pangkat
                        </label>

                        <input type="text"
                               name="pangkat"
                               class="form-control">

                    </div>

                    <div class="form-group">

                        <label class="form-label">
                            Golongan
                        </label>

                        <select name="golongan"
                                class="form-select">

                            <option>I</option>
                            <option>II</option>
                            <option selected>III</option>
                            <option>IV</option>

                        </select>

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Jabatan
                    </label>

                    <input type="text"
                           name="jabatan"
                           class="form-control">

                </div>

                <div class="form-group">

                    <label class="form-label">
                        Unit Kerja
                    </label>

                    <input type="text"
                           name="unit_kerja"
                           class="form-control">

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

    const modal =
        document.getElementById(id);

    if(modal){

        modal.style.display = 'flex';

        setTimeout(() => {

            modal.classList.add('show');

        },10);

        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id){

    const modal =
        document.getElementById(id);

    if(modal){

        modal.classList.remove('show');

        setTimeout(() => {

            modal.style.display = 'none';

        },200);

        document.body.style.overflow = 'auto';
    }
}

/*
|--------------------------------------------------------------------------
| EDIT MODAL
|--------------------------------------------------------------------------
*/

function openEditModal(p){

    document.getElementById('editId').value =
        p.id || '';

    document.getElementById('editNama').value =
        p.nama || '';

    document.getElementById('editPangkat').value =
        p.pangkat || '';

    document.getElementById('editGolongan').value =
        p.golongan || 'III';

    document.getElementById('editJabatan').value =
        p.jabatan || '';

    document.getElementById('editUnit').value =
        p.unit_kerja || '';

    document.getElementById('editStatus').value =
        p.status || 'Aktif';

    openModal('modalEdit');
}
/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/

function confirmDelete(url,nama){

    if(confirm(
        'Yakin ingin menghapus data pegawai:\n\n'
        + nama + ' ?'
    )){
        window.location.href = url;
    }
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', () => {
        const modal = overlay.closest('.modal-custom');
        if (modal) {
            closeModal(modal.id);
        }
    });
});

document.getElementById('searchInput').addEventListener('keyup', function(){
    const value = this.value.toLowerCase();
    document.querySelectorAll('#mainTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});

/*
|--------------------------------------------------------------------------
| CHART
|--------------------------------------------------------------------------
*/

new Chart(document.getElementById('golChart'), {

    type: 'bar',

    data: {

        labels: [
            'Gol. I',
            'Gol. II',
            'Gol. III',
            'Gol. IV'
        ],

        datasets: [{

            data: [
                <?= implode(',', [
                    $golMap['I'] ?? 0,
                    $golMap['II'] ?? 0,
                    $golMap['III'] ?? 0,
                    $golMap['IV'] ?? 0
                ]) ?>
            ],

            backgroundColor: [
                '#dbeafe',
                '#93c5fd',
                '#3b82f6',
                '#1e3a8a'
            ],

            borderRadius: 8,
            borderSkipped: false
        }]
    },

    options: {

        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                display: false
            }
        },

        scales: {

            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            },

            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

</script>

<?php renderFooter(); ?>