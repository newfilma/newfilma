<?php
// ========================
//  app/header.php
// ========================

require_once __DIR__ . '/auth.php';  // auth.php është në të njëjtin folder /app/

$page_title  = $page_title  ?? 'NewFilma';
$active_page = $active_page ?? '';

$user    = current_user();
$isAdmin = is_admin();
$plan    = $user['plan'] ?? 'Free';
$isPremium = ($plan !== 'Free');
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ==== STYLES MENU ==== */
*{box-sizing:border-box;margin:0;padding:0;font-family:system-ui;}
body{background:#020617;color:#e5e7eb;}

.topbar{
    display:flex;justify-content:space-between;align-items:center;
    padding:10px 18px;background:#0f172a;border-bottom:1px solid #1e293b;
    position:sticky;top:0;z-index:100;
}

.logo{font-size:22px;font-weight:700;color:white;text-decoration:none;}
.logo span{color:#f97316;}

.nav-links{display:flex;gap:12px;align-items:center;}
.nav-link{
    padding:6px 12px;border-radius:999px;color:#e5e7eb;text-decoration:none;
}
.nav-link:hover{background:#1e293b;}
.nav-link.active{
    background:linear-gradient(90deg,#2563eb,#f97316);
    color:white;font-weight:600;
}

.right-side{display:flex;gap:10px;align-items:center;}

.user-pill{
    display:flex;align-items:center;gap:6px;
    padding:5px 10px;border-radius:999px;background:#0b1120;
    border:1px solid #334155;font-size:13px;color:#e5e7eb;
    text-decoration:none;
}

.plan-tag{padding:2px 6px;border-radius:999px;font-size:11px;
    background:rgba(37,99,235,0.2);color:#93c5fd;}
.plan-tag.premium{background:rgba(234,179,8,0.2);color:#facc15;}

.btn-outline{
    padding:5px 10px;border-radius:999px;border:1px solid #475569;
    color:#e5e7eb;text-decoration:none;font-size:13px;
}
.btn-outline:hover{background:#1e293b;}

.menu-toggle{
    display:none;width:36px;height:36px;border-radius:999px;
    border:1px solid #475569;background:#0f172a;color:white;
    align-items:center;justify-content:center;font-size:20px;
}

@media(max-width:700px){
    .menu-toggle{display:flex;}
    .nav-links{display:none;flex-direction:column;width:100%;margin-top:8px;}
    .nav-links.open{display:flex;}
    .topbar{flex-wrap:wrap;}
}

.page-wrap{padding:22px 16px;max-width:1250px;margin:auto;}

</style>

</head>
<body>

<!-- NAVBAR -->
<header class="topbar">
    <a href="/newfilma/index.php" class="logo">New<span>Filma</span></a>

    <nav class="nav-links" id="mainNav">
        <a href="/newfilma/movies.php" class="nav-link <?= $active_page=='movies'?'active':'' ?>">🎬 Filma</a>

        <a href="/newfilma/series.php" class="nav-link <?= $active_page=='series'?'active':'' ?>">📺 Seriale</a>

        <a href="/newfilma/tvlive.php" class="nav-link <?= $active_page=='tv'?'active':'' ?>">📡 TV Live</a>

        <?php if ($isAdmin): ?>
        <a href="/newfilma/admin/dashboard.php" class="nav-link <?= $active_page=='admin'?'active':'' ?>">🛠 Admin</a>
        <?php endif; ?>
    </nav>

    <div class="right-side">

        <button id="menuToggle" class="menu-toggle">☰</button>

        <?php if ($user): ?>
            <a href="/newfilma/profile.php" class="user-pill">
                👤 <?= htmlspecialchars($user['name']) ?>
                <span class="plan-tag <?= $isPremium?'premium':'' ?>"><?= htmlspecialchars($plan) ?></span>
            </a>

            <a href="/newfilma/logout.php" class="btn-outline">Dil</a>
        <?php else: ?>
            <a href="/newfilma/login.php" class="btn-outline">Hyr</a>
            <a href="/newfilma/register.php" class="btn-outline" style="border-color:#22c55e;color:#22c55e;">Regjistrohu</a>
        <?php endif; ?>
    </div>
</header>

<script>
document.getElementById('menuToggle').onclick = () => {
    document.getElementById('mainNav').classList.toggle('open');
};
</script>

<main class="page-wrap">
