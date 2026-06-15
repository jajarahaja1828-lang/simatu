<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) redirect('/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {

        if (Auth::login($username, $password)) {

            redirect('/dashboard.php');

        } else {

            $error = 'Username atau password salah. Silakan coba lagi.';
        }

    } else {

        $error = 'Harap isi semua kolom.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login — SIMATU</title>

    <!-- BOOTSTRAP ICON -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- GOOGLE FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
          rel="stylesheet">

<style>

/* ==========================================================================
   RESET
========================================================================== */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

/* ==========================================================================
   ROOT
========================================================================== */

:root{

    --primary:#2563eb;
    --primary-dark:#1d4ed8;

    --dark:#020617;
    --dark-soft:#0f172a;

    --gray:#64748b;
    --light:#f8fafc;
    --white:#ffffff;

    --shadow-primary:
    0 16px 35px rgba(37,99,235,.35);

    --shadow-primary-hover:
    0 22px 45px rgba(37,99,235,.45);

    --shadow-main:
    0 35px 100px rgba(0,0,0,.45);
}

/* ==========================================================================
   BODY
========================================================================== */

body{

    font-family:'Inter',sans-serif;

    background:var(--dark);

    min-height:100vh;

    overflow-x:hidden;
}

/* ==========================================================================
   PAGE BACKGROUND
========================================================================== */

.auth-page{

    position:relative;

    width:100%;
    min-height:100vh;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:25px;

    background:
    linear-gradient(
        135deg,
        rgba(15,23,42,.95),
        rgba(30,41,59,.88)
    ),

    url('https://images.unsplash.com/photo-1497366754035-f200968a6e72?q=80&w=1600&auto=format&fit=crop')
    center center/cover no-repeat;
}

.auth-page::before{

    content:'';

    position:absolute;
    inset:0;

    background:
    radial-gradient(circle at top right,
    rgba(37,99,235,.30),
    transparent 35%);

    pointer-events:none;
}

/* ==========================================================================
   MAIN WRAPPER
========================================================================== */

.auth-wrapper{

    position:relative;
    z-index:10;

    width:100%;
    max-width:980px;

    display:grid;
    grid-template-columns:430px 1fr;

    overflow:hidden;

    border-radius:28px;

    background:rgba(255,255,255,.08);

    border:1px solid rgba(255,255,255,.12);

    backdrop-filter:blur(16px);

    box-shadow:var(--shadow-main);

    animation:fadeUp .7s ease;
}

/* ==========================================================================
   ANIMATION
========================================================================== */

@keyframes fadeUp{

    from{

        opacity:0;
        transform:translateY(40px);
    }

    to{

        opacity:1;
        transform:translateY(0);
    }
}

/* ==========================================================================
   LEFT SIDE
========================================================================== */

.auth-left{

    position:relative;

    padding:38px;

    background:
    linear-gradient(
        180deg,
        rgba(255,255,255,.98),
        rgba(248,250,252,.96)
    );
}

.auth-left::after{

    content:'';

    position:absolute;

    width:180px;
    height:180px;

    border-radius:50%;

    background:rgba(37,99,235,.08);

    top:-90px;
    right:-90px;

    filter:blur(10px);
}

/* ==========================================================================
   BADGE
========================================================================== */

.auth-badge{

    display:inline-flex;
    align-items:center;
    gap:8px;

    padding:8px 14px;

    border-radius:999px;

    background:#eff6ff;

    color:var(--primary);

    font-size:11px;
    font-weight:700;

    margin-bottom:24px;
}

/* ==========================================================================
   INTERNATIONAL ENTERPRISE LOGO
========================================================================== */

.auth-logo{

    display:flex;
    align-items:center;

    gap:14px;

    margin-bottom:26px;
}

/* ==========================================================================
   ICON
========================================================================== */

.auth-logo-icon{

    width:50px;
    height:50px;

    border-radius:18px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:
    linear-gradient(
        135deg,
        #2563eb,
        #1d4ed8
    );

    color:white;

    font-size:18px;

    box-shadow:
    0 12px 30px rgba(37,99,235,.25);

    position:relative;

    overflow:hidden;
}

.auth-logo-icon::before{

    content:'';

    position:absolute;

    inset:0;

    background:
    linear-gradient(
        135deg,
        rgba(255,255,255,.22),
        transparent
    );
}

/* ==========================================================================
   TEXT
========================================================================== */

.logo-text{

    display:flex;
    flex-direction:column;
}

.app-name{

    font-size:19px;

    font-weight:900;

    letter-spacing:6px;

    color:#0f172a;

    line-height:1;

    text-transform:uppercase;
}

.app-tagline{

    margin-top:7px;

    font-size:10px;

    font-weight:600;

    letter-spacing:1.8px;

    text-transform:uppercase;

    color:#64748b;
}

/* ==========================================================================
   TITLE
========================================================================== */

.auth-title{

    font-size:28px;
    font-weight:900;

    line-height:1.25;

    color:#0f172a;

    margin-bottom:14px;
}

.auth-subtitle{

    font-size:13px;
    line-height:1.9;

    color:#64748b;

    margin-bottom:26px;

    max-width:420px;
}

/* ==========================================================================
   ALERT
========================================================================== */

.alert-error{

    padding:14px 16px;

    border-radius:14px;

    background:#fef2f2;

    color:#dc2626;

    border:1px solid #fecaca;

    margin-bottom:20px;

    font-size:13px;
    font-weight:600;

    display:flex;
    align-items:center;
    gap:10px;
}

/* ==========================================================================
   FORM
========================================================================== */

.auth-form{

    width:100%;
}

.form-group{

    margin-bottom:18px;
}

.form-label{

    display:block;

    margin-bottom:9px;

    font-size:13px;
    font-weight:700;

    color:#334155;
}

/* ==========================================================================
   INPUT
========================================================================== */

.input-icon{

    position:relative;
}

.input-icon i{

    position:absolute;

    left:16px;
    top:50%;

    transform:translateY(-50%);

    color:#94a3b8;

    font-size:16px;
}

.form-control{

    width:100%;
    height:52px;

    border-radius:16px;

    border:1px solid #dbeafe;

    background:white;

    padding:0 50px;

    font-size:14px;

    outline:none;

    transition:.25s;

    box-shadow:
    inset 0 1px 2px rgba(0,0,0,.03);
}

.form-control:focus{

    border-color:var(--primary);

    box-shadow:
    0 0 0 4px rgba(37,99,235,.12);
}

.toggle-pass{

    position:absolute;

    right:16px;
    top:50%;

    transform:translateY(-50%);

    border:none;
    background:none;

    color:#64748b;

    font-size:16px;

    cursor:pointer;
}

/* ==========================================================================
   OPTIONS
========================================================================== */

.auth-options{

    display:flex;
    align-items:center;
    justify-content:space-between;

    margin-top:8px;
    margin-bottom:22px;
}

.auth-remember{

    display:flex;
    align-items:center;
    gap:8px;

    font-size:12px;
    color:#64748b;
}

.auth-options a{

    text-decoration:none;

    color:var(--primary);

    font-size:12px;
    font-weight:700;
}

/* ==========================================================================
   BUTTON
========================================================================== */

.btn-auth{

    width:100%;
    height:52px;

    border:none;

    border-radius:16px;

    background:
    linear-gradient(
        135deg,
        var(--primary),
        var(--primary-dark)
    );

    color:white;

    font-size:14px;
    font-weight:800;

    cursor:pointer;

    transition:.25s;

    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;

    box-shadow:var(--shadow-primary);
}

.btn-auth:hover{

    transform:translateY(-3px);

    box-shadow:var(--shadow-primary-hover);
}

/* ==========================================================================
   DIVIDER
========================================================================== */

.auth-divider{

    margin:22px 0;

    display:flex;
    align-items:center;
    gap:12px;

    color:#94a3b8;

    font-size:12px;
}

.auth-divider::before,
.auth-divider::after{

    content:'';

    flex:1;
    height:1px;

    background:#e2e8f0;
}

/* ==========================================================================
   SWITCH
========================================================================== */

.auth-switch{

    text-align:center;

    font-size:12px;

    color:#64748b;
}

.auth-switch a{

    color:var(--primary);

    text-decoration:none;

    font-weight:800;
}

/* ==========================================================================
   DEMO BOX
========================================================================== */

.demo-box{

    margin-top:20px;

    background:#eff6ff;

    border:1px solid #bfdbfe;

    border-radius:16px;

    padding:14px;

    color:#1e40af;

    font-size:12px;

    display:flex;
    align-items:center;
    gap:10px;
}

/* ==========================================================================
   RIGHT SIDE
========================================================================== */

.auth-right{

    position:relative;

    min-height:640px;

    overflow:hidden;
}

.auth-right img{

    width:100%;
    height:100%;

    object-fit:cover;
}

.auth-overlay{

    position:absolute;
    inset:0;

    background:
    linear-gradient(
        180deg,
        rgba(15,23,42,.10),
        rgba(15,23,42,.72)
    );
}

/* ==========================================================================
   RIGHT CONTENT
========================================================================== */

.auth-content{

    position:absolute;

    left:45px;
    bottom:45px;

    color:white;

    z-index:10;

    max-width:340px;
}

.auth-content h2{

    font-size:40px;
    line-height:1.2;

    font-weight:900;

    margin-bottom:14px;
}

.auth-content p{

    font-size:13px;
    line-height:1.8;

    color:rgba(255,255,255,.85);
}

/* ==========================================================================
   ULTRA MINI GLASS BOX
========================================================================== */

.glass-box{

    position:absolute;

    top:14px;
    right:14px;

    z-index:20;

    width:190px;

    padding:9px 12px;

    border-radius:16px;

    background:
    linear-gradient(
        135deg,
        rgba(255,255,255,.10),
        rgba(255,255,255,.03)
    );

    border:1px solid rgba(255,255,255,.10);

    backdrop-filter:blur(12px);

    -webkit-backdrop-filter:blur(12px);

    box-shadow:
    0 4px 14px rgba(0,0,0,.20);

    transition:.25s ease;
}

/* ==========================================================================
   CONTENT
========================================================================== */

.glass-top{

    display:flex;
    align-items:center;
    gap:8px;
}

.glass-logo{

    width:28px;
    height:28px;

    border-radius:9px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:
    linear-gradient(
        135deg,
        rgba(255,255,255,.12),
        rgba(255,255,255,.03)
    );

    border:1px solid rgba(255,255,255,.08);

    color:white;

    font-size:12px;
}

.glass-divider{

    width:1px;
    height:24px;

    background:
    linear-gradient(
        to bottom,
        transparent,
        rgba(255,255,255,.25),
        transparent
    );
}

.glass-text{

    display:flex;
    flex-direction:column;
}

.glass-text h4{

    font-size:10px;
    font-weight:800;

    letter-spacing:3px;

    color:white;

    margin-bottom:1px;

    text-transform:uppercase;
}

.glass-text span{

    font-size:8px;

    color:rgba(255,255,255,.75);

    font-weight:500;
}
/* ==========================================================================
   AUTH CONTENT IMPROVE
========================================================================== */

.auth-content{

    position:absolute;

    left:45px;
    bottom:45px;

    z-index:10;

    max-width:420px;
}

.auth-content p{

    font-size:14px;

    line-height:2;

    color:rgba(255,255,255,.88);

    font-weight:400;

    text-shadow:
    0 2px 10px rgba(0,0,0,.35);
}

/* ==========================================================================
   MOBILE
========================================================================== */

@media(max-width:768px){

    .glass-box{

        width:300px;

        top:20px;
        left:20px;

        padding:18px;
    }

    .glass-text h4{

        font-size:22px;

        letter-spacing:5px;
    }

    .glass-text span{

        font-size:13px;
    }
}

/* ==========================================================================
   RESPONSIVE
========================================================================== */

@media(max-width:980px){

    .auth-wrapper{

        grid-template-columns:1fr;

        max-width:500px;
    }

    .auth-right{

        display:none;
    }
}

@media(max-width:600px){

    .auth-page{

        padding:18px;
    }

    .auth-left{

        padding:30px 22px;
    }

    .app-name{

        font-size:26px;
    }

    .auth-title{

        font-size:24px;
    }

    .form-control{

        height:50px;
    }

    .btn-auth{

        height:50px;
    }
}
/* ==========================================================================
   ENTERPRISE INFO
========================================================================== */

.enterprise-info{

    position:absolute;

    left:42px;
    bottom:42px;

    z-index:10;

    max-width:360px;
}

/* ==========================================================================
   LABEL
========================================================================== */

.enterprise-label{

    display:inline-flex;
    align-items:center;

    padding:7px 14px;

    border-radius:999px;

    background:
    linear-gradient(
        135deg,
        rgba(255,255,255,.16),
        rgba(255,255,255,.04)
    );

    border:1px solid rgba(255,255,255,.12);

    backdrop-filter:blur(10px);

    color:rgba(255,255,255,.88);

    font-size:9px;

    font-weight:700;

    letter-spacing:2px;

    text-transform:uppercase;

    margin-bottom:18px;
}

/* ==========================================================================
   TITLE
========================================================================== */

.enterprise-info h3{

    font-size:24px;

    line-height:1.35;

    font-weight:800;

    color:white;

    margin-bottom:16px;

    letter-spacing:.3px;

    text-shadow:
    0 4px 18px rgba(0,0,0,.35);
}

/* ==========================================================================
   DESCRIPTION
========================================================================== */

.enterprise-info p{

    font-size:12px;

    line-height:2;

    color:rgba(255,255,255,.80);

    font-weight:400;

    letter-spacing:.3px;

    text-shadow:
    0 2px 12px rgba(0,0,0,.35);
}

/* ==========================================================================
   DECORATION LINE
========================================================================== */

.enterprise-info::before{

    content:'';

    position:absolute;

    left:-18px;
    top:54px;

    width:3px;
    height:88px;

    border-radius:999px;

    background:
    linear-gradient(
        to bottom,
        rgba(255,255,255,.95),
        rgba(255,255,255,.08)
    );
}

/* ==========================================================================
   MOBILE
========================================================================== */

@media(max-width:768px){

    .enterprise-info{

        left:24px;
        bottom:24px;

        max-width:280px;
    }

    .enterprise-info h3{

        font-size:18px;
    }

    .enterprise-info p{

        font-size:11px;
    }
}

/* ==========================================================================
   PREMIUM GLOBAL TITLE
========================================================================== */

.auth-title{

    position:relative;

    font-size:22px;

    line-height:1.5;

    font-weight:800;

    letter-spacing:.3px;

    color:#0f172a;

    margin-bottom:14px;

    max-width:340px;
}

.auth-title::after{

    content:'';

    display:block;

    width:52px;
    height:3px;

    margin-top:14px;

    border-radius:999px;

    background:
    linear-gradient(
        90deg,
        #2563eb,
        rgba(37,99,235,.15)
    );
}
</style>

</head>

<body>

<div class="auth-page">

    <div class="auth-wrapper">

        <!-- LEFT -->

        <div class="auth-left">

            <div class="auth-badge">

                <i class="bi bi-stars"></i>

                Premium Enterprise Internal System
            </div>

            <div class="auth-logo">

                <div class="auth-logo-icon">

                    <i class="bi bi-building-fill-lock"></i>

                </div>

                <div>

                    <div class="app-name">
                        SIMATU
                    </div>


                </div>

            </div>

<div class="auth-title">

    Modern Enterprise Administration Platform

</div>
            <?php if ($error): ?>

                <div class="alert-error">

                    <i class="bi bi-exclamation-triangle-fill"></i>

                    <?= sanitize($error) ?>

                </div>

            <?php endif; ?>

            <form method="POST"
                  class="auth-form">

                <div class="form-group">

                    <label class="form-label">

                        Username

                    </label>

                    <div class="input-icon">

                        <i class="bi bi-person-fill"></i>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="Masukkan username"
                            value="<?= sanitize($_POST['username'] ?? '') ?>"
                            required
                            autofocus
                        >

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">

                        Password

                    </label>

                    <div class="input-icon">

                        <i class="bi bi-lock-fill"></i>

                        <input
                            type="password"
                            name="password"
                            id="passInput"
                            class="form-control"
                            placeholder="Masukkan password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-pass"
                            onclick="togglePass()">

                            <i class="bi bi-eye"
                               id="eyeIcon"></i>

                        </button>

                    </div>

                </div>

                <button type="submit"
                        class="btn-auth">

                    <i class="bi bi-box-arrow-in-right"></i>

                    Login Sekarang

                </button>

            </form>

            <div class="auth-divider">

                atau

            </div>

            <div class="auth-switch">

                Belum punya akun?

                <a href="<?= BASE_PATH ?>/register.php">

                    Sign Up

                </a>
            </div>

        </div>

        <!-- RIGHT -->

        <div class="auth-right">

            <img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?q=80&w=1600&auto=format&fit=crop"
                 alt="Office">

            <div class="auth-overlay"></div>
<!-- PREMIUM GLASS BOX -->

<div class="glass-box">

    <div class="glass-top">

        <div class="glass-logo">

            <i class="bi bi-shield-check"></i>

        </div>

        <div class="glass-divider"></div>

        <div class="glass-text">

            <h4>
                SIMATU
            </h4>

            <span>
                Enterprise Digital Platform
            </span>

        </div>

    </div>

</div>
            <!-- CONTENT -->

            <div class="enterprise-info">

    <span class="enterprise-label">
        Enterprise Intelligence System
    </span>

    <p>

        Advanced enterprise ecosystem engineered to simplify
        modern administration through secure, intelligent,
        and high-efficiency digital management solutions.

    </p>

</div>


        </div>

    </div>

</div>

<script>

function togglePass(){

    const input =
        document.getElementById('passInput');

    const icon =
        document.getElementById('eyeIcon');

    if(input.type === 'password'){

        input.type = 'text';

        icon.className =
            'bi bi-eye-slash';

    }else{

        input.type = 'password';

        icon.className =
            'bi bi-eye';
    }
}

</script>

</body>
</html>