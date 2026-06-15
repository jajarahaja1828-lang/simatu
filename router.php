<?php
// PHP Built-in server router
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    $ext = pathinfo($uri, PATHINFO_EXTENSION);
    $staticExts = ['css', 'js', 'ico', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'map'];
    if (in_array($ext, $staticExts)) {
        return false; // Serve the file directly
    }
}

// Map clean URLs to PHP files
$routes = [
    '/'                             => '/index.php',
    '/login'                        => '/login.php',
    '/register'                     => '/register.php',
    '/logout'                       => '/logout.php',
    '/dashboard'                    => '/dashboard.php',
    '/persediaan'                   => '/persediaan/stock.php',
    '/persediaan/stock'             => '/persediaan/stock.php',
    '/persediaan/masuk'             => '/persediaan/masuk.php',
    '/persediaan/keluar'            => '/persediaan/keluar.php',
    '/bmn'                          => '/bmn/index.php',
    '/bmn/bergerak'                 => '/bmn/index.php',
    '/bmn/tidak_bergerak'           => '/bmn/tidak_bergerak.php',
    '/bmn/laporan'                  => '/bmn/laporan.php',
    '/keuangan'                     => '/keuangan/index.php',
    '/kepegawaian'                  => '/kepegawaian/index.php',
    '/kepegawaian/kenaikan_pangkat' => '/kepegawaian/kenaikan_pangkat.php',
];

// Strip trailing slash
$path = rtrim($uri, '/') ?: '/';

if (isset($routes[$path])) {
    require __DIR__ . $routes[$path];
} elseif (file_exists(__DIR__ . $uri . '.php')) {
    require __DIR__ . $uri . '.php';
} elseif (file_exists(__DIR__ . $uri . '/index.php')) {
    require __DIR__ . $uri . '/index.php';
} elseif (file_exists(__DIR__ . $uri)) {
    require __DIR__ . $uri;
} else {
    http_response_code(404);
    echo '<div style="font-family:Inter,sans-serif;text-align:center;padding:60px;color:#6c757d;">
        <h2 style="color:#1b3d6e;">404 — Halaman tidak ditemukan</h2>
        <p>Halaman <code>' . htmlspecialchars($uri) . '</code> tidak tersedia.</p>
        <a href="/" style="color:#3b7fe8;text-decoration:none;font-weight:600;">← Kembali ke beranda</a>
    </div>';
}
