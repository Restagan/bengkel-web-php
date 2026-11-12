<?php
$menu_aktif   = $menu_aktif   ?? '';
$tanpa_navbar = $tanpa_navbar ?? false;
$body_class   = $body_class   ?? '';
$logo_ada     = is_file(__DIR__ . '/../assets/img/logo.png');

$menu = [
    'dashboard' => ['dashboard.php', 'Dashboard'],
    'pelanggan' => ['pelanggan.php', 'Pelanggan'],
    'kendaraan' => ['kendaraan.php', 'Kendaraan'],
    'servis'    => ['servis.php',    'Servis'],
    'restorasi' => ['restorasi.php', 'Restorasi'],
    'sparepart' => ['sparepart.php', 'Sparepart'],
    'kasir'     => ['kasir.php',     'Kasir'],
    'mekanik'   => ['mekanik.php',   'Mekanik'],
    'laporan'   => ['laporan.php',   'Laporan'],
];
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul_halaman); ?> - Bengkel System</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cpath fill='%23eb0a1e' d='M13 4h13l-7 24H6z'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:ital,wght@0,500;0,600;0,700;1,600;1,700&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/tema-gr.css" rel="stylesheet">
</head>
<body class="gr-body <?= htmlspecialchars($body_class); ?>">

<?php if (!$tanpa_navbar): ?>
    <nav class="navbar navbar-expand-xl gr-navbar">
        <div class="container">
            <a class="navbar-brand gr-brand" href="dashboard.php">
                <?php if ($logo_ada): ?>
                    <img src="assets/img/logo.png" alt="" class="gr-logo">
                <?php endif; ?>
                <span class="gr-mark" aria-hidden="true"></span>
                <span>Bengkel System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama" aria-controls="menuUtama" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menuUtama">
                <ul class="navbar-nav me-auto">
                    <?php foreach ($menu as $kunci => [$url, $label]): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $menu_aktif === $kunci ? ' active' : ''; ?>" href="<?= $url; ?>"<?= $menu_aktif === $kunci ? ' aria-current="page"' : ''; ?>><?= $label; ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="logout.php" class="btn btn-danger btn-sm btn-keluar">Keluar</a>
            </div>
        </div>
    </nav>
    <div class="gr-stripe" aria-hidden="true"></div>
<?php endif; ?>

<main class="gr-main">
