<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/layout.php';

requireLogin();

$conn = Database::getInstance();

$user = currentUser();

/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

    if (
        empty($full_name) ||
        empty($email) ||
        empty($username)
    ) {

        $_SESSION['error'] =
            'Full Name, Email, dan Username wajib diisi';

        header("Location:index.php");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK USERNAME
    |--------------------------------------------------------------------------
    */

    $cekUsername = $conn->prepare("
        SELECT id
        FROM users
        WHERE username = ?
        AND id != ?
    ");

    $cekUsername->execute([
        $username,
        $user['id']
    ]);

    if ($cekUsername->fetch()) {

        $_SESSION['error'] =
            'Username sudah digunakan';

        header("Location:index.php");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK EMAIL
    |--------------------------------------------------------------------------
    */

    $cekEmail = $conn->prepare("
        SELECT id
        FROM users
        WHERE email = ?
        AND id != ?
    ");

    $cekEmail->execute([
        $email,
        $user['id']
    ]);

    if ($cekEmail->fetch()) {

        $_SESSION['error'] =
            'Email sudah digunakan';

        header("Location:index.php");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | PHOTO UPLOAD
    |--------------------------------------------------------------------------
    */

    $photoName = $user['photo'] ?? '';

    if (
        isset($_FILES['photo']) &&
        $_FILES['photo']['error'] === 0
    ) {

        $uploadDir =
            __DIR__ .
            '/../assets/img/profile/';

        if (!is_dir($uploadDir)) {

            mkdir($uploadDir, 0777, true);
        }

        $tmpName  = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileSize = $_FILES['photo']['size'];

        $ext = strtolower(
            pathinfo(
                $fileName,
                PATHINFO_EXTENSION
            )
        );

        $allowed = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];

        if (!in_array($ext, $allowed)) {

            $_SESSION['error'] =
                'Format foto harus JPG, PNG, atau WEBP';

            header("Location:index.php");
            exit;
        }

        if ($fileSize > 2 * 1024 * 1024) {

            $_SESSION['error'] =
                'Ukuran foto maksimal 2MB';

            header("Location:index.php");
            exit;
        }

        $photoName =
            'profile_' .
            time() .
            '_' .
            rand(1000,9999) .
            '.' .
            $ext;

        $upload = move_uploaded_file(
            $tmpName,
            $uploadDir . $photoName
        );

        if (!$upload) {

            $_SESSION['error'] =
                'Upload foto gagal';

            header("Location:index.php");
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE OLD PHOTO
        |--------------------------------------------------------------------------
        */

        if (
            !empty($user['photo']) &&
            file_exists(
                $uploadDir . $user['photo']
            )
        ) {

            unlink(
                $uploadDir . $user['photo']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE USER
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE users
        SET
            full_name = ?,
            email = ?,
            username = ?,
            phone = ?,
            address = ?,
            photo = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $full_name,
        $email,
        $username,
        $phone,
        $address,
        $photoName,
        $user['id']
    ]);

    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    if (
        !empty($_POST['new_password']) ||
        !empty($_POST['confirm_password'])
    ) {

        if (
            $_POST['new_password'] !==
            $_POST['confirm_password']
        ) {

            $_SESSION['error'] =
                'Konfirmasi password tidak cocok';

            header("Location:index.php");
            exit;
        }

        if (strlen($_POST['new_password']) < 6) {

            $_SESSION['error'] =
                'Password minimal 6 karakter';

            header("Location:index.php");
            exit;
        }

        $password = password_hash(
            $_POST['new_password'],
            PASSWORD_DEFAULT
        );

        $pass = $conn->prepare("
            UPDATE users
            SET password = ?
            WHERE id = ?
        ");

        $pass->execute([
            $password,
            $user['id']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REFRESH SESSION
    |--------------------------------------------------------------------------
    */

    $refresh = $conn->prepare("
        SELECT *
        FROM users
        WHERE id = ?
    ");

    $refresh->execute([
        $user['id']
    ]);

    $_SESSION['user'] =
        $refresh->fetch(PDO::FETCH_ASSOC);

    $_SESSION['success'] =
        'Profile berhasil diperbarui';

    header("Location:index.php");
    exit;
}

$user = currentUser();

renderHeader('Profile','profile');

$photo =
    !empty($user['photo'])
    ? BASE_PATH .
      '/assets/img/profile/' .
      $user['photo'] .
      '?v=' . time()
    : '';

?>

<style>

body{
    background:#f1f5f9;
}

/* =========================================================
   PROFILE COVER
========================================================= */

.profile-cover{
    height:260px;
    border-radius:32px;
    background:
    linear-gradient(
        135deg,
        #0f172a 0%,
        #1e293b 35%,
        #2563eb 100%
    );
    position:relative;
    overflow:hidden;
    margin-bottom:110px;
    box-shadow:
    0 20px 50px rgba(37,99,235,.18);
}

.profile-cover::before{
    content:'';
    position:absolute;
    width:420px;
    height:420px;
    background:rgba(255,255,255,.08);
    border-radius:50%;
    top:-180px;
    right:-100px;
}

.profile-cover::after{
    content:'';
    position:absolute;
    width:300px;
    height:300px;
    background:rgba(255,255,255,.06);
    border-radius:50%;
    bottom:-160px;
    left:-80px;
}

/* =========================================================
   CARD
========================================================= */

.profile-card,
.info-box{
    background:#fff;
    border-radius:30px;
    padding:32px;
    border:1px solid #e2e8f0;
    box-shadow:
    0 10px 40px rgba(15,23,42,.06);
}

.profile-card{
    position:sticky;
    top:20px;
}

/* =========================================================
   AVATAR
========================================================= */

.profile-avatar{
    width:180px;
    height:180px;
    border-radius:50%;
    overflow:hidden;
    margin:auto;
    margin-top:-130px;
    border:7px solid #fff;
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
    font-size:64px;
    font-weight:700;
    box-shadow:
    0 15px 35px rgba(37,99,235,.30);
}

.profile-avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
}

/* =========================================================
   TEXT
========================================================= */

.profile-name{
    font-size:30px;
    font-weight:800;
    color:#0f172a;
    margin-top:25px;
    margin-bottom:8px;
}

.profile-email{
    color:#64748b;
    font-size:15px;
    margin-bottom:25px;
}

.section-title{
    font-size:28px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:28px;
}

/* =========================================================
   FORM
========================================================= */

.form-control{
    border-radius:16px;
    min-height:56px;
    border:1px solid #dbe4f0;
    padding:14px 18px;
    font-size:15px;
    transition:.3s;
    box-shadow:none;
}

.form-control:focus{
    border-color:#2563eb;
    box-shadow:
    0 0 0 4px rgba(37,99,235,.12);
}

textarea.form-control{
    min-height:140px;
}

.label-title{
    font-weight:700;
    color:#0f172a;
    margin-bottom:10px;
    display:block;
    font-size:14px;
}

/* =========================================================
   BUTTON
========================================================= */

.btn-save{
    background:
    linear-gradient(
        135deg,
        #2563eb,
        #1d4ed8
    );
    border:none;
    color:#fff;
    padding:15px;
    border-radius:16px;
    font-weight:700;
    width:100%;
    transition:.3s;
    font-size:15px;
    box-shadow:
    0 10px 25px rgba(37,99,235,.25);
}

.btn-save:hover{
    transform:translateY(-2px);
    background:
    linear-gradient(
        135deg,
        #1d4ed8,
        #1e40af
    );
}

/* =========================================================
   ALERT
========================================================= */

.alert{
    border:none;
    border-radius:18px;
    padding:16px 20px;
    font-weight:600;
    box-shadow:
    0 6px 20px rgba(0,0,0,.05);
}

/* =========================================================
   FILE INFO
========================================================= */

.preview-text{
    font-size:13px;
    color:#64748b;
    margin-top:10px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:991px){

    .profile-cover{
        height:180px;
        margin-bottom:90px;
    }

    .profile-avatar{
        width:150px;
        height:150px;
        margin-top:-90px;
    }

    .profile-name{
        font-size:24px;
    }

    .section-title{
        font-size:24px;
    }
}

</style>

<div class="container-fluid py-4">

<div class="profile-cover"></div>

<form method="POST" enctype="multipart/form-data">

<div class="row">

<div class="col-lg-4 mb-4">

<div class="profile-card text-center">

<div class="profile-avatar" id="avatarPreview">

<?php if(!empty($photo)): ?>

<img
src="<?= $photo ?>"
id="previewImage">

<?php else: ?>

<span id="previewText">
<?= strtoupper(substr($user['full_name'] ?? 'U',0,1)) ?>
</span>

<?php endif; ?>

</div>

<h3 class="profile-name">
<?= sanitize($user['full_name']) ?>
</h3>

<div class="profile-email">
<?= sanitize($user['email']) ?>
</div>

<input
type="file"
name="photo"
class="form-control"
id="photoInput"
accept=".jpg,.jpeg,.png,.webp">

<div class="preview-text">
Format: JPG, PNG, WEBP (Max 2MB)
</div>

<button
type="submit"
class="btn-save mt-4">

<i class="bi bi-check-circle-fill me-2"></i>
Update Profile

</button>

</div>

</div>

<div class="col-lg-8">

<?php if(!empty($_SESSION['success'])): ?>

<div class="alert alert-success">
<i class="bi bi-check-circle-fill me-2"></i>
<?= $_SESSION['success'] ?>
</div>

<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if(!empty($_SESSION['error'])): ?>

<div class="alert alert-danger">
<i class="bi bi-exclamation-circle-fill me-2"></i>
<?= $_SESSION['error'] ?>
</div>

<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="info-box mb-4">

<h4 class="section-title">
Profile Information
</h4>

<div class="row g-4">

<div class="col-md-6">

<label class="label-title">
Full Name
</label>

<input
type="text"
name="full_name"
class="form-control"
required
value="<?= sanitize($user['full_name'] ?? '') ?>">

</div>

<div class="col-md-6">

<label class="label-title">
Email
</label>

<input
type="email"
name="email"
class="form-control"
required
value="<?= sanitize($user['email'] ?? '') ?>">

</div>

<div class="col-md-6">

<label class="label-title">
Username
</label>

<input
type="text"
name="username"
class="form-control"
required
value="<?= sanitize($user['username'] ?? '') ?>">

</div>

<div class="col-md-6">

<label class="label-title">
Phone Number
</label>

<input
type="text"
name="phone"
class="form-control"
value="<?= sanitize($user['phone'] ?? '') ?>">

</div>

<div class="col-12">

<label class="label-title">
Address
</label>

<textarea
name="address"
class="form-control"><?= sanitize($user['address'] ?? '') ?></textarea>

</div>

</div>

</div>




</div>

</form>

</div>

<script>

document
.getElementById('photoInput')
.addEventListener(
'change',
function(e){

const file = e.target.files[0];

if(file){

const reader = new FileReader();

reader.onload = function(ev){

document
.getElementById('avatarPreview')
.innerHTML =
`<img src="${ev.target.result}">`;

};

reader.readAsDataURL(file);

}

}
);

</script>

<?php renderFooter(); ?>