<?php
// admin/dashboard.php – Dashboard i thjeshtë & modern

require __DIR__ . '/../app/auth.php';

$user = current_user();
if (!$user || !is_admin()) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title>Admin – NewFilma</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top, #0f172a, #020617 55%);
            color: #e5e7eb;
        }

        /* TOPBAR */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 18px;
            border-bottom: 1px solid #1f2937;
            background: rgba(2,6,23,0.9);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-circle {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: radial-gradient(circle at 0 0, #22c55e, #0ea5e9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
        }
        .topbar-title {
            font-size: 16px;
            font-weight: 600;
        }
        .topbar-subtitle {
            font-size: 11px;
            color: #9ca3af;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        .badge {
            padding: 3px 10px;
            border-radius: 999px;
            background: #020617;
            border: 1px solid #1f2937;
            color: #9ca3af;
            font-size: 11px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            text-decoration: none;
            border: 1px solid #334155;
            background: #0f172a;
            color: #e5e7eb;
        }
        .btn:hover {
            background: #1f2937;
        }
        .btn-danger {
            border-color: #fca5a5;
            background: #b91c1c;
            color: #fee2e2;
        }
        .btn-danger:hover {
            background: #dc2626;
        }

        /* PAGE */
        .page {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 16px 40px 16px;
        }
        .page-header {
            margin-bottom: 18px;
        }
        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .page-subtitle {
            font-size: 13px;
            color: #9ca3af;
        }

        /* GRID TILES */
        .tiles {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .tile {
            display: block;
            text-decoration: none;
            padding: 14px 14px 16px 14px;
            border-radius: 14px;
            border: 1px solid rgba(31,41,55,0.9);
            background: radial-gradient(circle at top left, rgba(56,189,248,0.20), #020617 55%);
            color: #e5e7eb;
            transition: transform 0.12s ease, box-shadow 0.12s ease, border-color 0.12s ease, background 0.12s ease;
        }
        .tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            border-color: #22c55e;
            background: radial-gradient(circle at top left, rgba(34,197,94,0.28), #020617 60%);
        }
        .tile-head {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }
        .tile-emoji {
            font-size: 18px;
        }
        .tile-title {
            font-size: 14px;
            font-weight: 600;
        }
        .tile-desc {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 2px;
        }
        .tile-foot {
            margin-top: 10px;
            font-size: 11px;
            color: #6b7280;
        }
        .tile-foot strong {
            color: #9ca3af;
        }

        @media (max-width: 600px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .topbar-right {
                align-self: stretch;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-left">
        <div class="logo-circle">N</div>
        <div>
            <div class="topbar-title">NewFilma – Admin</div>
            <div class="topbar-subtitle">Panel i thjeshtë për menaxhim</div>
        </div>
    </div>
    <div class="topbar-right">
        <span class="badge">
            👤 <?= htmlspecialchars($user['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
        </span>
        <a class="btn" href="../index.php">🏠 newfilma</a>
        <a class="btn btn-danger" href="../logout.php">🚪 Dalje</a>
    </div>
</div>

<div class="page">
    <div class="page-header">
        <div class="page-title">
            Përshëndetje, <?= htmlspecialchars($user['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?> 👋
        </div>
        <div class="page-subtitle">
            Zgjidh një modul për ta menaxhuar. Çdo gjë është brenda këtyre tile-ve.
        </div>
    </div>

    <div class="tiles">

        <a class="tile" href="movies.php">
            <div class="tile-head">
                <div class="tile-emoji">🎬</div>
                <div class="tile-title">Filmat</div>
            </div>
            <div class="tile-desc">
                Shto / edito / fshi filma, posterat dhe link-un e videos.
            </div>
            <div class="tile-foot">
                Përdor <strong>data/movies.json</strong>.
            </div>
        </a>

        <a class="tile" href="series.php">
            <div class="tile-head">
                <div class="tile-emoji">📺</div>
                <div class="tile-title">Serialet</div>
            </div>
            <div class="tile-desc">
                Menaxho serialet dhe episodet (bashkë me <code>series_episodes.php</code>).
            </div>
            <div class="tile-foot">
                <strong>data/series.json</strong>, <strong>data/episodes.json</strong>.
            </div>
        </a>

        <a class="tile" href="tv.php">
            <div class="tile-head">
                <div class="tile-emoji">📡</div>
                <div class="tile-title">TV Live</div>
            </div>
            <div class="tile-desc">
                Shto / edito kanalet IPTV, URL m3u/m3u8 dhe logot.
            </div>
            <div class="tile-foot">
                Përdor <strong>data/tv.json</strong>.
            </div>
        </a>

        <a class="tile" href="player.php">
            <div class="tile-head">
                <div class="tile-emoji">🎛</div>
                <div class="tile-title">Player Settings</div>
            </div>
            <div class="tile-desc">
                Rregullo VideoJS/Nuevo, skin-in, gjuhën dhe videon preroll.
            </div>
            <div class="tile-foot">
                Përdor <strong>data/player_settings.json</strong>.
            </div>
        </a>

        <a class="tile" href="users.php">
            <div class="tile-head">
                <div class="tile-emoji">👥</div>
                <div class="tile-title">Përdoruesit</div>
            </div>
            <div class="tile-desc">
                Lista e user-ave, rolet (admin/user) dhe të dhënat bazë.
            </div>
            <div class="tile-foot">
                Përdor <strong>data/users.json</strong>.
            </div>
        </a>

        <a class="tile" href="subscriptions.php">
            <div class="tile-head">
                <div class="tile-emoji">💳</div>
                <div class="tile-title">Abonimet</div>
            </div>
            <div class="tile-desc">
                Planet 2€ / 5€ / 10€, afatet e skadimit dhe lidhja me user-at.
            </div>
            <div class="tile-foot">
                Përdor <strong>data/subscriptions.json</strong>.
            </div>
        </a>

        <a class="tile" href="payments.php">
            <div class="tile-head">
                <div class="tile-emoji">💰</div>
                <div class="tile-title">Pagesat</div>
            </div>
            <div class="tile-desc">
                Shiko pagesat nga PayPal dhe kontrollet e statusit.
            </div>
            <div class="tile-foot">
                File te <strong>payments/</strong>.
            </div>
        </a>

    </div>
</div>

</body>
</html>
