<?php
// admin/users.php – Menaxhimi i përdoruesve (lista + kërkim + fshirje)

// AUTH + ADMIN CHECK
require __DIR__ . '/../app/auth.php';
if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

$user = current_user() ?? ['name' => 'admin'];

// Skedari ku ruhen user-at (nga rrënja e projektit)
$usersFile = __DIR__ . '/../data/users.json';

// Lexo user-at nga JSON
$users = [];
if (file_exists($usersFile)) {
    $json = file_get_contents($usersFile);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $users = $decoded;
    }
}

// FUNKSION PËR RUANJTE
if (!function_exists('save_users')) {
    function save_users($file, $usersArray) {
        $json = json_encode($usersArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($file, $json);
    }
}

// Helper për shkurtim teksti
if (!function_exists('short_text')) {
    function short_text($text, $max = 40) {
        $text = (string)$text;
        if (mb_strlen($text, 'UTF-8') <= $max) return $text;
        return mb_substr($text, 0, $max, 'UTF-8') . '…';
    }
}

// FSHIRJE USERI ?delete=ID
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    $newUsers = [];
    foreach ($users as $u) {
        if ((int)($u['id'] ?? 0) !== $deleteId) {
            $newUsers[] = $u;
        }
    }

    $users = $newUsers;
    save_users($usersFile, $users);

    header('Location: users.php?msg=deleted');
    exit;
}

// KËRKIM sipas emrit / email / username
$search   = trim($_GET['q'] ?? '');
$filtered = $users;

if ($search !== '') {
    $searchLower = mb_strtolower($search, 'UTF-8');
    $filtered = array_filter($users, function ($u) use ($searchLower) {
        $name  = mb_strtolower($u['name'] ?? '', 'UTF-8');
        $email = mb_strtolower($u['email'] ?? '', 'UTF-8');
        $usern = mb_strtolower($u['username'] ?? '', 'UTF-8');
        return strpos($name,  $searchLower) !== false
            || strpos($email, $searchLower) !== false
            || strpos($usern, $searchLower) !== false;
    });
}

// PAGINATION
$perPage = 25;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$total      = count($filtered);
$totalPages = max(1, (int)ceil($total / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;
$items  = array_slice($filtered, $offset, $perPage);

// për URL bazë te pagination
$baseParams = $_GET;
unset($baseParams['page'], $baseParams['delete'], $baseParams['msg']);
$baseQuery = http_build_query($baseParams);
$baseUrl   = 'users.php' . ($baseQuery ? ('?' . $baseQuery . '&') : '?');
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title>Admin – Përdoruesit | NewFilma</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        *{
            box-sizing:border-box;
            margin:0;
            padding:0;
            font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
        }
        body{
            background:#020617;
            color:#e5e7eb;
            min-height:100vh;
        }

        .layout{
            display:flex;
            min-height:100vh;
        }

        .sidebar{
            width:230px;
            background:#020617;
            border-right:1px solid #1f2937;
            padding:16px 12px;
        }
        .sidebar h1{
            font-size:18px;
            margin-bottom:16px;
        }
        .sidebar a{
            display:block;
            padding:8px 10px;
            margin-bottom:4px;
            color:#9ca3af;
            text-decoration:none;
            border-radius:8px;
            font-size:14px;
        }
        .sidebar a:hover{
            background:#111827;
            color:#e5e7eb;
        }
        .sidebar a.active{
            background:#1f2937;
            color:#e5e7eb;
        }

        .content{
            flex:1;
            padding:16px 20px 40px;
        }

        .card{
            background:#020617;
            border-radius:16px;
            border:1px solid #1f2937;
            padding:20px;
            box-shadow:0 18px 45px rgba(15,23,42,0.7);
            max-width:1100px;
            margin:0 auto 20px;
        }

        .card-header-flex{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            margin-bottom:10px;
        }
        .card h2{
            font-size:20px;
            margin-bottom:4px;
        }
        .card p.lead{
            color:#9ca3af;
            font-size:13px;
        }

        .pill-user{
            padding:6px 12px;
            border-radius:999px;
            background:#111827;
            border:1px solid #4b5563;
            font-size:12px;
            color:#e5e7eb;
        }

        .search-bar{
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            align-items:center;
            margin-top:10px;
            margin-bottom:6px;
        }
        .search-input{
            background:#020617;
            border-radius:999px;
            border:1px solid #374151;
            padding:6px 12px;
            color:#e5e7eb;
            font-size:13px;
            min-width:220px;
        }
        .search-input::placeholder{
            color:#6b7280;
        }

        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:6px;
            padding:6px 12px;
            border-radius:999px;
            border:1px solid #4b5563;
            background:#020617;
            font-size:13px;
            color:#e5e7eb;
            text-decoration:none;
            cursor:pointer;
        }
        .btn:hover{
            filter:brightness(1.05);
        }
        .btn-primary{
            border:none;
            border-radius:999px;
            background:linear-gradient(135deg,#22c55e,#16a34a);
            color:#020617;
            font-weight:600;
            font-size:13px;
            padding:7px 14px;
        }
        .btn-red{
            border-color:#f97373;
            background:#b91c1c;
            color:#fee2e2;
        }

        .alert{
            background:#022c22;
            border:1px solid #16a34a;
            color:#bbf7d0;
            padding:8px 10px;
            border-radius:8px;
            font-size:13px;
            margin-bottom:10px;
            display:inline-block;
        }

        .table-wrapper{
            margin-top:10px;
            border-radius:14px;
            border:1px solid #1f2937;
            overflow:hidden;
            background:radial-gradient(circle at 0 0,rgba(56,189,248,0.12),#020617);
        }
        table{
            width:100%;
            border-collapse:collapse;
            font-size:13px;
        }
        th,td{
            padding:8px 10px;
            border-bottom:1px solid #1f2937;
            vertical-align:middle;
        }
        th{
            text-align:left;
            font-size:12px;
            color:#9ca3af;
            font-weight:500;
        }
        tr:nth-child(even) td{
            background:rgba(15,23,42,0.95);
        }
        tr:nth-child(odd) td{
            background:rgba(2,6,23,0.98);
        }

        .pill{
            display:inline-flex;
            padding:3px 8px;
            border-radius:999px;
            background:rgba(15,23,42,0.9);
            border:1px solid #1f2937;
            font-size:11px;
            color:#9ca3af;
        }
        .actions{
            display:flex;
            flex-wrap:wrap;
            gap:6px;
        }

        .pagination{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:10px;
            font-size:12px;
            color:#9ca3af;
        }
        .page-links{
            display:flex;
            flex-wrap:wrap;
            gap:4px;
        }
        .page-link{
            padding:4px 8px;
            border-radius:999px;
            border:1px solid #1f2937;
            text-decoration:none;
            font-size:11px;
            color:#e5e7eb;
        }
        .page-link.active{
            background:#16a34a;
            border-color:#22c55e;
            color:#020617;
            font-weight:600;
        }
        .page-link:hover{
            filter:brightness(1.05);
        }

        @media(max-width:800px){
            .sidebar{
                display:none;
            }
            .content{
                padding:12px 12px 32px;
            }
        }
        @media(max-width:600px){
            .card-header-flex{
                flex-direction:column;
                align-items:flex-start;
            }
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sidebar – dizajn si te player.php, me LIDHJET E REJA -->
    <aside class="sidebar">
        <h1>⚙️ Admin - NewFilma</h1>
        <a href="index.php">🏠 Dashboard</a>
        <a href="movies.php">🎬 Filma</a>
        <a href="series.php">📺 Seriale</a>
        <a href="tv.php">📡 TV Live</a>
        <a href="users.php" class="active">👤 Përdoruesit</a>
        <a href="subscriptions.php">💳 Abonimet</a>
        <a href="payments.php">💰 Pagesat</a>
        <a href="player.php">🎥 Player Settings</a>
        <a href="../index.php">🌐 newfilma.com</a>
        <a href="../logout.php">🚪 Dalje</a>
    </aside>

    <main class="content">
        <div class="card">
            <div class="card-header-flex">
                <div>
                    <h2>👥 Menaxhimi i përdoruesve</h2>
                    <p class="lead">
                        Lista e user-ave që ruhen në <strong>data/users.json</strong>, me kërkim, pagination dhe fshirje.
                        Total: <strong><?= (int)$total; ?></strong> user-a (faqe <?= $page; ?> / <?= $totalPages; ?>)
                    </p>
                </div>
                <span class="pill-user">👤 <?= htmlspecialchars($user['name'] ?? 'admin'); ?></span>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                <div class="alert">✅ Përdoruesi u fshi me sukses.</div>
            <?php endif; ?>

            <form class="search-bar" method="get" action="users.php">
                <input
                    class="search-input"
                    type="text"
                    name="q"
                    placeholder="Kërko sipas emrit, email-it ose username..."
                    value="<?= htmlspecialchars($search); ?>"
                />
                <?php if ($page > 1): ?>
                    <input type="hidden" name="page" value="<?= $page; ?>">
                <?php endif; ?>
                <button class="btn" type="submit">🔍 Kërko</button>
                <a class="btn btn-primary" href="add_user.php">➕ Shto user të ri</a>
            </form>

            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Emri / info</th>
                        <th>Email</th>
                        <th>Roli</th>
                        <th>Plani</th>
                        <th>Skadon</th>
                        <th>Status</th>
                        <th>Veprime</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:16px; color:#9ca3af;">
                                Nuk u gjet asnjë përdorues.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $u): ?>
                            <?php
                            $id       = (int)($u['id'] ?? 0);
                            $name     = $u['name'] ?? '—';
                            $email    = $u['email'] ?? '—';
                            $username = $u['username'] ?? '';
                            $plan     = $u['plan'] ?? 'Free';
                            $expires  = $u['expires_at'] ?? '—';
                            $status   = $u['status'] ?? 'aktiv';
                            $role     = $u['role'] ?? 'user';
                            ?>
                            <tr>
                                <td><?= $id; ?></td>
                                <td>
                                    <div style="font-weight:500;"><?= htmlspecialchars(short_text($name, 30)); ?></div>
                                    <div style="font-size:11px; color:#9ca3af;">
                                        <?php if ($username): ?>
                                            username: <?= htmlspecialchars($username); ?>
                                        <?php else: ?>
                                            krijuar: <?= htmlspecialchars($u['created_at'] ?? '—'); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="pill"><?= htmlspecialchars(short_text($email, 35)); ?></span>
                                </td>
                                <td>
                                    <?php if ($role === 'admin'): ?>
                                        <span class="pill">🛠 admin</span>
                                    <?php else: ?>
                                        <span class="pill">👤 user i thjeshtë</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="pill"><?= htmlspecialchars($plan); ?></span>
                                </td>
                                <td>
                                    <span class="pill"><?= htmlspecialchars($expires); ?></span>
                                </td>
                                <td>
                                    <span class="pill"><?= htmlspecialchars($status); ?></span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="btn" href="edit_user.php?id=<?= $id; ?>">✏️ Edito</a>
                                        <a
                                            class="btn btn-red"
                                            href="users.php?delete=<?= $id; ?>"
                                            onclick="return confirm('Je i sigurt që do ta fshish këtë përdorues?');"
                                        >🗑 Fshi</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <div>
                    Shfaqen
                    <?= $total ? ($offset + 1) : 0; ?>–<?= min($offset + $perPage, $total); ?>
                    nga <?= $total; ?> user-a.
                </div>
                <div class="page-links">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a
                            class="page-link <?= $p === $page ? 'active' : ''; ?>"
                            href="<?= $baseUrl . 'page=' . $p; ?>"
                        ><?= $p; ?></a>
                    <?php endfor; ?>
                </div>
            </div>

            <div style="margin-top:12px; font-size:12px; color:#6b7280;">
                💡 Këshillë: Në <code>users.json</code> mund të shtosh për çdo user fushat
                <code>plan</code>, <code>expires_at</code>, <code>status</code>, <code>role</code>
                dhe t’i menaxhosh nga <code>edit_user.php</code>.
            </div>
        </div>
    </main>
</div>

</body>
</html>
