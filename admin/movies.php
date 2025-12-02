<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * admin/movies.php
 * Menaxhimi i filmave:
 * - Shto / edito manualisht
 * - Shto automatikisht nga IMDb (OMDb)
 * - Fshi film
 * - Lista e filmave
 */

// AUTH + ADMIN CHECK
require __DIR__ . '/../app/auth.php';
if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

$currentUser = current_user() ?? ['name' => 'admin'];

/* OMDb API KEY – ndryshoje nëse do tjetër */
$OMDB_API_KEY = 'dda8ecfb';

/* Rruga e movies.json (nga rrënja) */
$moviesFile = __DIR__ . '/../data/movies.json';

/* krijo folderin data nëse nuk ekziston */
if (!is_dir(dirname($moviesFile))) {
    mkdir(dirname($moviesFile), 0777, true);
}

/* ngarko filmat ekzistues */
$movies = [];
if (file_exists($moviesFile)) {
    $json = file_get_contents($moviesFile);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $movies = $decoded;
    }
} else {
    file_put_contents(
        $moviesFile,
        json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

/* FUNKSIONE NDIHMËSE */
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

function omdb_request_admin($params, $apiKey) {
    if (empty($apiKey)) return null;

    $base = 'https://www.omdbapi.com/?apikey=' . urlencode($apiKey) . '&plot=full&' . $params;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);

    if (!$res) return null;

    $data = json_decode($res, true);
    if (!is_array($data) || empty($data['Response']) || $data['Response'] !== 'True') {
        return null;
    }
    return $data;
}

function map_genre_to_category_admin($genreStr, $currentCat = 'aksion') {
    if (!$genreStr) return $currentCat;

    $genres  = explode(',', $genreStr);
    $primary = strtolower(trim($genres[0]));

    $map = [
        'action'          => 'aksion',
        'adventure'       => 'aventura',
        'comedy'          => 'komedi',
        'drama'           => 'drame',
        'horror'          => 'horror',
        'thriller'        => 'thriller',
        'sci-fi'          => 'scifi',
        'science fiction' => 'scifi',
        'fantasy'         => 'fantazi',
        'crime'           => 'krim',
        'romance'         => 'romance',
        'animation'       => 'animacion',
        'family'          => 'familjar',
        'documentary'     => 'dokumentar',
    ];

    foreach ($map as $g => $cat) {
        if (strpos($primary, $g) !== false) {
            return $cat;
        }
    }
    return $currentCat ?: 'aksion';
}

/* =======================================
   DELETE – FSHIRJA E FILMIT
   ======================================= */
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];

    $json    = file_get_contents($moviesFile);
    $decoded = json_decode($json, true);
    $movies  = is_array($decoded) ? $decoded : [];

    $newMovies = [];
    foreach ($movies as $m) {
        $mid = isset($m['id']) ? (int)$m['id'] : 0;
        if ($mid !== $delId) {
            $newMovies[] = $m;
        }
    }

    if (count($newMovies) !== count($movies)) {
        file_put_contents(
            $moviesFile,
            json_encode($newMovies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    header('Location: movies.php');
    exit;
}

$success = '';
$error   = '';

/* =======================================
   SAVE – SHTO / EDITO MANUAL
   ======================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_movie'])) {

    $json    = file_get_contents($moviesFile);
    $decoded = json_decode($json, true);
    $movies  = is_array($decoded) ? $decoded : [];

    $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $isEdit = $id > 0;

    if (!$isEdit) {
        $maxId = 0;
        foreach ($movies as $m) {
            if (isset($m['id']) && $m['id'] > $maxId) {
                $maxId = (int)$m['id'];
            }
        }
        $id = $maxId + 1;
    }

    $movieData = [
        'id'          => $id,
        'title'       => $_POST['title']       ?? '',
        'year'        => $_POST['year']        ?? '',
        'duration'    => $_POST['duration']    ?? '',
        'quality'     => $_POST['quality']     ?? '',
        'rating'      => $_POST['rating']      ?? '',
        'category'    => $_POST['category']    ?? '',
        'url'         => $_POST['url']         ?? '',
        'videoUrl'    => $_POST['videoUrl']    ?? '',
        'description' => $_POST['description'] ?? '',
        'actors'      => $_POST['actors']      ?? '',
        'imdbID'      => $_POST['imdbID']      ?? '',
        'imdbGenre'   => $_POST['imdbGenre']   ?? '',
        'poster'      => '',
    ];

    $found = false;
    if ($isEdit) {
        foreach ($movies as &$m) {
            if ((int)($m['id'] ?? 0) === $id) {
                if (!empty($m['poster'])) {
                    $movieData['poster'] = $m['poster'];
                }
                $m     = $movieData;
                $found = true;
                break;
            }
        }
        unset($m);

        $success = $found ? 'Filmi u përditësua.' : 'Filmi nuk u gjet për editim.';
        if (!$found) {
            $error = $success;
            $success = '';
        }
    } else {
        $movies[] = $movieData;
        $success  = 'Filmi u shtua.';
    }

    file_put_contents(
        $moviesFile,
        json_encode($movies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    $json    = file_get_contents($moviesFile);
    $decoded = json_decode($json, true);
    $movies  = is_array($decoded) ? $decoded : [];
}

/* =======================================
   SHTO NGA IMDb (OMDb)
   ======================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_imdb'])) {

    $json    = file_get_contents($moviesFile);
    $decoded = json_decode($json, true);
    $movies  = is_array($decoded) ? $decoded : [];

    $imdbId   = trim($_POST['imdb_id']   ?? '');
    $videoUrl = trim($_POST['video_url'] ?? '');

    if ($imdbId === '' || $videoUrl === '') {
        $error = 'Plotëso IMDb ID dhe Video URL.';
    } else {
        $info = omdb_request_admin('i=' . urlencode($imdbId), $OMDB_API_KEY);
        if (!$info) {
            $error = 'Nuk u gjet film për këtë IMDb ID ose OMDb API KEY është gabim.';
        } else {
            $maxId = 0;
            foreach ($movies as $m) {
                if (isset($m['id']) && $m['id'] > $maxId) {
                    $maxId = (int)$m['id'];
                }
            }
            $newId = $maxId + 1;

            $genreStr = $info['Genre'] ?? '';
            $cat      = map_genre_to_category_admin($genreStr, 'aksion');

            $newMovie = [
                'id'          => $newId,
                'title'       => $info['Title']      ?? '',
                'year'        => $info['Year']       ?? '',
                'duration'    => $info['Runtime']    ?? '',
                'quality'     => 'FHD',
                'rating'      => $info['imdbRating'] ?? '',
                'category'    => $cat,
                'poster'      => (!empty($info['Poster']) && $info['Poster'] !== 'N/A') ? $info['Poster'] : '',
                'url'         => '',
                'videoUrl'    => $videoUrl,
                'description' => (isset($info['Plot']) && $info['Plot'] !== 'N/A') ? $info['Plot'] : '',
                'actors'      => $info['Actors']     ?? '',
                'imdbID'      => $info['imdbID']     ?? $imdbId,
                'imdbGenre'   => $genreStr,
            ];

            $movies[] = $newMovie;
            file_put_contents(
                $moviesFile,
                json_encode($movies, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            $success = 'Filmi u shtua automatikisht nga IMDb (OMDb).';

            $json    = file_get_contents($moviesFile);
            $decoded = json_decode($json, true);
            $movies  = is_array($decoded) ? $decoded : [];
        }
    }
}

/* =======================================
   GJEJ FILMIN PËR EDIT NËSE KA ?edit=
   ======================================= */
$editMovie = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($movies as $m) {
        if ((int)($m['id'] ?? 0) === $editId) {
            $editMovie = $m;
            break;
        }
    }
}

/* Vlerat default për formën */
$f = [
    'id'          => $editMovie['id']          ?? 0,
    'title'       => $editMovie['title']       ?? '',
    'year'        => $editMovie['year']        ?? '',
    'duration'    => $editMovie['duration']    ?? '',
    'quality'     => $editMovie['quality']     ?? '',
    'rating'      => $editMovie['rating']      ?? '',
    'category'    => $editMovie['category']    ?? 'aksion',
    'url'         => $editMovie['url']         ?? '',
    'videoUrl'    => $editMovie['videoUrl']    ?? '',
    'description' => $editMovie['description'] ?? '',
    'actors'      => $editMovie['actors']      ?? '',
    'imdbID'      => $editMovie['imdbID']      ?? '',
    'imdbGenre'   => $editMovie['imdbGenre']   ?? '',
    'poster'      => $editMovie['poster']      ?? '',
];

/* kategoritë */
$cats = [
    'aksion'     => 'Aksion',
    'komedi'     => 'Komedi',
    'drame'      => 'Dramë',
    'horror'     => 'Horror',
    'thriller'   => 'Thriller',
    'scifi'      => 'Sci-Fi',
    'aventura'   => 'Aventurë',
    'fantazi'    => 'Fantazi',
    'krim'       => 'Krim',
    'romance'    => 'Romancë',
    'animacion'  => 'Animacion',
    'familjar'   => 'Familjar',
    'dokumentar' => 'Dokumentar',
];
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title>Admin – Filmat | NewFilma</title>
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

        .card h2{
            font-size:20px;
            margin-bottom:6px;
        }
        .card p.lead{
            color:#9ca3af;
            font-size:13px;
            margin-bottom:10px;
        }

        .pill-user{
            padding:6px 12px;
            border-radius:999px;
            background:#111827;
            border:1px solid #4b5563;
            font-size:12px;
            color:#e5e7eb;
        }

        .card-header-flex{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            margin-bottom:12px;
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
        .alert-error{
            background:#451a1a;
            border:1px solid #b91c1c;
            color:#fecaca;
        }

        input, textarea, select{
            padding:8px 10px;
            border-radius:8px;
            border:1px solid #1f2937;
            background:#020617;
            color:#e5e7eb;
            font-size:13px;
            width:100%;
        }
        textarea{
            resize:vertical;
            min-height:80px;
        }
        label{
            font-size:13px;
            margin-bottom:4px;
            display:block;
        }
        .field{
            margin-bottom:10px;
        }

        .grid-2{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:12px;
        }

        button{
            padding:8px 16px;
            border:none;
            border-radius:999px;
            background:#f97316;
            color:white;
            font-weight:600;
            cursor:pointer;
            font-size:13px;
        }

        .btn-link{
            display:inline-block;
            padding:8px 14px;
            border-radius:999px;
            background:#1e293b;
            border:1px solid #334155;
            color:#e5e7eb;
            text-decoration:none;
            font-size:13px;
        }
        .btn-link:hover{
            filter:brightness(1.05);
        }

        table{
            width:100%;
            border-collapse:collapse;
            font-size:13px;
        }
        th,td{
            padding:8px 10px;
            border-bottom:1px solid #111827;
            vertical-align:middle;
        }
        th{
            text-align:left;
            color:#9ca3af;
            font-weight:500;
            font-size:12px;
        }
        a{
            color:#93c5fd;
            text-decoration:none;
        }
        a:hover{
            text-decoration:underline;
        }

        @media(max-width:800px){
            .sidebar{ display:none; }
            .content{ padding:12px 12px 32px; }
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sidebar si te admin/users.php -->
    <aside class="sidebar">
        <h1>⚙️ Admin - NewFilma</h1>
        <a href="index.php">🏠 Dashboard</a>
        <a href="movies.php" class="active">🎬 Filma</a>
        <a href="series.php">📺 Seriale</a>
        <a href="tv.php">📡 TV Live</a>
        <a href="users.php">👤 Përdoruesit</a>
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
                    <h2>🎬 Menaxhimi i filmave</h2>
                    <p class="lead">
                        Shto / edito filma manualisht ose nga IMDb (OMDb). Ruhet në <code>data/movies.json</code>.
                    </p>
                </div>
                <span class="pill-user">👤 <?= h($currentUser['name'] ?? 'admin'); ?></span>
            </div>

            <?php if ($success): ?>
                <div class="alert"><?= h($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= h($error); ?></div>
            <?php endif; ?>

            <h3 style="font-size:15px;margin-bottom:6px;">1️⃣ Shto / edito film manualisht</h3>

            <form method="post">
                <input type="hidden" name="save_movie" value="1">
                <input type="hidden" name="id" value="<?= (int)$f['id']; ?>">

                <div class="grid-2">
                    <div class="field">
                        <label>Titulli *</label>
                        <input type="text" name="title" required value="<?= h($f['title']); ?>">
                    </div>
                    <div class="field">
                        <label>Viti</label>
                        <input type="text" name="year" value="<?= h($f['year']); ?>">
                    </div>
                    <div class="field">
                        <label>Kohëzgjatja (p.sh. 2h 15m)</label>
                        <input type="text" name="duration" value="<?= h($f['duration']); ?>">
                    </div>
                    <div class="field">
                        <label>Cilësia</label>
                        <input type="text" name="quality" placeholder="HD / FHD / 4K" value="<?= h($f['quality']); ?>">
                    </div>
                    <div class="field">
                        <label>Rating (p.sh. 8.2)</label>
                        <input type="text" name="rating" value="<?= h($f['rating']); ?>">
                    </div>
                    <div class="field">
                        <label>Kategoria</label>
                        <select name="category">
                            <?php foreach ($cats as $k => $v): ?>
                                <option value="<?= h($k); ?>" <?= ($f['category'] === $k ? 'selected' : ''); ?>>
                                    <?= h($v); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Trailer URL (opsionale)</label>
                        <input type="text" name="url" value="<?= h($f['url']); ?>">
                    </div>
                    <div class="field">
                        <label>Video URL *</label>
                        <input type="text" name="videoUrl" required value="<?= h($f['videoUrl']); ?>" placeholder="/videos/filmi.mp4">
                    </div>
                    <div class="field">
                        <label>Aktorët (opsionale)</label>
                        <input type="text" name="actors" value="<?= h($f['actors']); ?>">
                    </div>
                    <div class="field">
                        <label>IMDb ID (p.sh. tt3896198)</label>
                        <input type="text" name="imdbID" value="<?= h($f['imdbID']); ?>">
                    </div>
                    <div class="field">
                        <label>Genre nga IMDb</label>
                        <input type="text" name="imdbGenre" value="<?= h($f['imdbGenre']); ?>">
                    </div>
                    <div class="field" style="grid-column:1/-1;">
                        <label>Përshkrimi</label>
                        <textarea name="description"><?= h($f['description']); ?></textarea>
                    </div>
                </div>

                <button type="submit" style="margin-top:6px;">
                    <?= $editMovie ? '💾 Ruaj ndryshimet' : '➕ Shto filmin'; ?>
                </button>
            </form>

            <hr style="border:none;border-top:1px solid #1f2937;margin:18px 0 12px;">

            <h3 style="font-size:15px;margin-bottom:6px;">2️⃣ Shto film automatikisht nga IMDb (OMDb)</h3>
            <p class="lead" style="margin-bottom:8px;">
                Vendos <strong>IMDb ID</strong> (p.sh. <code>tt3896198</code>) dhe <strong>Video URL</strong>.
            </p>

            <form method="post" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
                <input type="hidden" name="add_imdb" value="1">

                <div class="field" style="min-width:220px;">
                    <label>IMDb ID</label>
                    <input type="text" name="imdb_id" required placeholder="tt3896198">
                </div>
                <div class="field" style="min-width:260px;">
                    <label>Video URL</label>
                    <input type="text" name="video_url" required placeholder="/videos/filmi.mp4 ose link absolut">
                </div>
                <div class="field">
                    <button type="submit">➕ Shto nga IMDb</button>
                </div>
            </form>

            <p style="margin-top:12px;">
                <a href="bulk_from_filenames.php" class="btn-link">
                    📥 Importo filma vetëm nga emrat e file-ve
                </a>
                &nbsp;
                <a href="scan_videos.php" class="btn-link">
                    🔍 Scan videos
                </a>
                &nbsp;
                <a href="update_imdb.php" class="btn-link">
                    🔄 Update IMDb info
                </a>
            </p>
        </div>

        <div class="card">
            <h2>📋 Lista e filmave ekzistues</h2>
            <p class="lead">Kliko "Hape" për ta parë filmin si përdorues.</p>

            <?php if (empty($movies)): ?>
                <p style="margin-top:10px;">Ende nuk ka filma. Shto një më sipër.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Titulli</th>
                        <th>Viti</th>
                        <th>Kategoria</th>
                        <th>Rating</th>
                        <th>Cilësia</th>
                        <th>Veprime</th>
                    </tr>
                    <?php foreach ($movies as $m): ?>
                        <tr>
                            <td><?= (int)($m['id'] ?? 0); ?></td>
                            <td><?= h($m['title'] ?? ''); ?></td>
                            <td><?= h($m['year'] ?? ''); ?></td>
                            <td><?= h($m['category'] ?? ''); ?></td>
                            <td><?= h($m['rating'] ?? ''); ?></td>
                            <td><?= h($m['quality'] ?? ''); ?></td>
                            <td>
                                <a href="../movie.php?id=<?= (int)($m['id'] ?? 0); ?>" target="_blank">▶ Hape</a>
                                &nbsp;|&nbsp;
                                <a href="movies.php?edit=<?= (int)($m['id'] ?? 0); ?>">✏ Edito</a>
                                &nbsp;|&nbsp;
                                <a
                                    href="movies.php?delete=<?= (int)($m['id'] ?? 0); ?>"
                                    onclick="return confirm('Je i sigurt që do ta fshish këtë film?');"
                                >🗑 Fshi</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

    </main>
</div>

</body>
</html>
