<?php
// admin/series.php – Menaxhimi i serialeve & episodeve

require __DIR__ . '/../app/auth.php';
if (!is_admin()) {
    header('Location: ../login.php');
    exit;
}

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}

$dataDir    = __DIR__ . '/../data';
$seriesFile = $dataDir . '/series.json';

// krijo folderin data nëse s'ekziston
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// lexojmë serialet
$seriesList = [];
if (file_exists($seriesFile)) {
    $json = file_get_contents($seriesFile);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $seriesList = $decoded;
    }
}

function save_series($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function nextSeriesId($seriesList) {
    $max = 0;
    foreach ($seriesList as $s) {
        if (isset($s['id']) && $s['id'] > $max) {
            $max = (int)$s['id'];
        }
    }
    return $max + 1;
}

function nextEpisodeId($episodes) {
    $max = 0;
    foreach ($episodes as $e) {
        if (isset($e['id']) && $e['id'] > $max) {
            $max = (int)$e['id'];
        }
    }
    return $max + 1;
}

// DELETE serial
if (isset($_GET['delete_series'])) {
    $deleteId = (int)$_GET['delete_series'];
    $seriesList = array_values(array_filter($seriesList, function ($s) use ($deleteId) {
        return (int)($s['id'] ?? 0) !== $deleteId;
    }));
    save_series($seriesFile, $seriesList);
    header('Location: series.php?msg=deleted_series');
    exit;
}

// DELETE episod
if (isset($_GET['delete_ep'], $_GET['sid'])) {
    $sid = (int)$_GET['sid'];
    $eid = (int)$_GET['delete_ep'];

    foreach ($seriesList as $k => $s) {
        if ((int)($s['id'] ?? 0) === $sid) {
            if (!isset($seriesList[$k]['episodes']) || !is_array($seriesList[$k]['episodes'])) {
                $seriesList[$k]['episodes'] = [];
            }
            $seriesList[$k]['episodes'] = array_values(array_filter(
                $seriesList[$k]['episodes'],
                function ($ep) use ($eid) {
                    return (int)($ep['id'] ?? 0) !== $eid;
                }
            ));
            save_series($seriesFile, $seriesList);
            header('Location: series.php?msg=deleted_ep');
            exit;
        }
    }
}

$message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted_series') $message = '🗑 Seriali u fshi.';
    if ($_GET['msg'] === 'deleted_ep')     $message = '🗑 Episodi u fshi.';
}

// POST – Shto episod / serial
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $series_id = (int)($_POST['series_id'] ?? 0);

    $series_title  = trim($_POST['series_title'] ?? '');
    $series_imdb   = trim($_POST['series_imdb_id'] ?? '');
    $series_year   = trim($_POST['series_year'] ?? '');
    $series_poster = trim($_POST['series_poster'] ?? '');
    $series_plot   = trim($_POST['series_plot'] ?? '');
    $series_status = $_POST['series_status'] ?? 'aktiv';

    $season    = (int)($_POST['season'] ?? 1);
    $episodeNo = (int)($_POST['episode'] ?? 1);

    $ep_title    = trim($_POST['ep_title'] ?? '');
    $ep_plot     = trim($_POST['ep_plot'] ?? '');
    $ep_runtime  = trim($_POST['ep_runtime'] ?? '');
    $ep_poster   = trim($_POST['ep_poster'] ?? '');
    $ep_imdb_id  = trim($_POST['ep_imdb_id'] ?? '');
    $ep_videoUrl = trim($_POST['ep_videoUrl'] ?? '');

    if ($series_id === 0) {
        // Krijo serial të ri
        if ($series_title === '') {
            $message = '⚠ Duhet të vendosësh minimumi titullin e serialit.';
        } else {
            $series_id = nextSeriesId($seriesList);
            $seriesList[] = [
                'id'       => $series_id,
                'title'    => $series_title,
                'slug'     => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $series_title)),
                'imdb_id'  => $series_imdb,
                'year'     => $series_year,
                'poster'   => $series_poster,
                'plot'     => $series_plot,
                'status'   => $series_status ?: 'aktiv',
                'episodes' => []
            ];
            $message = '✅ U krijua seriali i ri dhe u shtua episodi.';
        }
    }

    // Gjej indexin e serialit
    if ($series_id > 0) {
        $idx = null;
        foreach ($seriesList as $k => $s) {
            if ((int)($s['id'] ?? 0) === $series_id) {
                $idx = $k;
                break;
            }
        }

        if ($idx !== null) {
            // përditëso info të serialit nëse duam
            if ($series_title !== '')  $seriesList[$idx]['title']  = $series_title;
            if ($series_imdb !== '')   $seriesList[$idx]['imdb_id'] = $series_imdb;
            if ($series_year !== '')   $seriesList[$idx]['year']   = $series_year;
            if ($series_poster !== '') $seriesList[$idx]['poster'] = $series_poster;
            if ($series_plot !== '')   $seriesList[$idx]['plot']   = $series_plot;
            $seriesList[$idx]['status'] = $series_status ?: 'aktiv';

            if (!isset($seriesList[$idx]['episodes']) || !is_array($seriesList[$idx]['episodes'])) {
                $seriesList[$idx]['episodes'] = [];
            }

            // shto episod nëse kemi sezon + episod + titull ose videoUrl
            if ($season > 0 && $episodeNo > 0 && ($ep_title !== '' || $ep_videoUrl !== '')) {
                $epId = nextEpisodeId($seriesList[$idx]['episodes']);

                $seriesList[$idx]['episodes'][] = [
                    'id'       => $epId,
                    'season'   => $season,
                    'episode'  => $episodeNo,
                    'title'    => $ep_title !== '' ? $ep_title : ("S{$season}E{$episodeNo}"),
                    'plot'     => $ep_plot,
                    'runtime'  => $ep_runtime,
                    'poster'   => $ep_poster,
                    'imdb_id'  => $ep_imdb_id,
                    'videoUrl' => $ep_videoUrl,
                ];

                if ($message === '') {
                    $message = "✅ U shtua episodi S{$season}E{$episodeNo}.";
                }
            } else {
                if ($message === '') {
                    $message = "ℹ Seriali u ruajt, por episodi nuk u shtua (kontrollo sezon/episod/titull).";
                }
            }

            save_series($seriesFile, $seriesList);
        } else {
            $message = '❌ Gabim: nuk u gjet seriali për të ruajtur episodin.';
        }
    }
}

// ri-lexo serialet pas ruajtjes
if (file_exists($seriesFile)) {
    $json = file_get_contents($seriesFile);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $seriesList = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title>Admin – Seriale & Episode - NewFilma</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        body { background:#020617; color:#e5e7eb; min-height:100vh; }

        .layout { display:flex; min-height:100vh; }

        .sidebar {
            width:230px;
            background:#020617;
            border-right:1px solid #1f2937;
            padding:16px 12px;
        }
        .sidebar h1 {
            font-size:18px;
            margin-bottom:16px;
        }
        .sidebar a {
            display:block;
            padding:8px 10px;
            margin-bottom:4px;
            color:#9ca3af;
            text-decoration:none;
            border-radius:8px;
            font-size:14px;
        }
        .sidebar a:hover { background:#111827; color:#e5e7eb; }
        .sidebar a.active { background:#1f2937; color:#e5e7eb; }

        .content {
            flex:1;
            padding:16px 20px 40px;
        }
        .card {
            background:#020617;
            border-radius:16px;
            border:1px solid #1f2937;
            padding:20px;
            box-shadow:0 18px 45px rgba(15,23,42,0.7);
            max-width:1200px;
            margin:0 auto 20px;
        }
        .card h2 { font-size:20px; margin-bottom:6px; }
        .card p.lead { color:#9ca3af; font-size:14px; margin-bottom:14px; }

        .grid-2 {
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
            gap:16px;
            margin-top:10px;
        }

        label { font-size:13px; margin-bottom:4px; display:block; color:#e5e7eb; }
        input[type="text"], select, textarea, input[type="number"] {
            width:100%;
            background:#020617;
            border:1px solid #1f2937;
            border-radius:10px;
            padding:8px 10px;
            color:#e5e7eb;
            font-size:13px;
        }
        textarea { resize:vertical; min-height:70px; }

        .field { margin-bottom:12px; }

        .btn-primary {
            display:inline-block;
            padding:8px 16px;
            border-radius:10px;
            border:none;
            background:linear-gradient(135deg,#22c55e,#16a34a);
            color:#020617;
            font-weight:600;
            font-size:14px;
            cursor:pointer;
            margin-top:8px;
        }
        .btn-primary:hover { filter:brightness(1.05); }

        .btn-secondary {
            display:inline-block;
            padding:7px 14px;
            border-radius:999px;
            border:1px solid #1f2937;
            background:#020617;
            color:#e5e7eb;
            font-size:13px;
            cursor:pointer;
        }

        .alert {
            background:#022c22;
            border:1px solid #16a34a;
            color:#bbf7d0;
            padding:8px 10px;
            border-radius:8px;
            font-size:13px;
            margin-bottom:12px;
        }

        .alert-warning {
            background:#451a03;
            border:1px solid #f97316;
            color:#fed7aa;
            padding:8px 10px;
            border-radius:8px;
            font-size:13px;
            margin-bottom:12px;
        }

        table {
            width:100%;
            border-collapse:collapse;
            margin-top:10px;
            font-size:13px;
        }
        th, td {
            border-bottom:1px solid #1f2937;
            padding:6px 8px;
            text-align:left;
        }
        th { color:#9ca3af; font-weight:500; }
        .badge {
            display:inline-block;
            padding:2px 8px;
            border-radius:999px;
            font-size:11px;
            background:#111827;
        }
        .badge-green { background:#166534; color:#bbf7d0; }
        .badge-red { background:#7f1d1d; color:#fecaca; }

        a.link-small {
            color:#93c5fd;
            font-size:12px;
            text-decoration:none;
        }
        a.link-small:hover { text-decoration:underline; }

        .episodes-table td {
            font-size:12px;
        }

        .small-note {
            font-size:12px;
            color:#9ca3af;
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h1>⚙️ Admin - NewFilma</h1>
        <a href="index.php">🏠 Dashboard</a>
        <a href="movies.php">🎬 Filma</a>
        <a href="series.php" class="active">📺 Seriale</a>
        <a href="tvlive.php">📡 TV Live</a>
        <a href="users.php">👤 Përdorues</a>
        <a href="player.php">🎥 Player Settings</a>
        <a href="../logout.php">🚪 Dalja</a>
    </aside>

    <main class="content">

        <div class="card">
            <h2>📺 Seriale & Episode</h2>
            <p class="lead">
                Këtu shton seriale dhe episode. Për episodet mund të marrësh automatikisht
                titullin, përshkrimin, posterin dhe kohëzgjatjen nga IMDb (OMDb).
            </p>

            <?php if (!empty($message)): ?>
                <div class="alert"><?= h($message) ?></div>
            <?php endif; ?>

            <div class="alert-warning">
                ℹ Për import masiv nga emrat e file-ve (p.sh. <code>Young-Royals1x5.mp4</code>),
                përdor faqen: <a href="bulk_series_from_filenames.php" class="link-small">bulk_series_from_filenames.php</a>
            </div>

            <form method="post">
                <h3 style="font-size:14px; margin-bottom:6px;">1️⃣ Zgjidh ose krijo serialin</h3>

                <div class="field">
                    <label>Seriali ekzistues</label>
                    <select name="series_id">
                        <option value="0">➕ Krijo serial të ri</option>
                        <?php foreach ($seriesList as $s): ?>
                            <option value="<?= (int)$s['id'] ?>">
                                #<?= (int)$s['id'] ?> – <?= h($s['title'] ?? '') ?>
                                (<?= isset($s['episodes']) && is_array($s['episodes']) ? count($s['episodes']) : 0 ?> ep)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="small-note">
                        Nëse zgjedh "Krijo serial të ri", plotëso fushat më poshtë.
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label>Titulli i serialit</label>
                        <input type="text" name="series_title" placeholder="p.sh. Young Royals">
                    </div>
                    <div class="field">
                        <label>IMDb ID i serialit (tt...)</label>
                        <input type="text" name="series_imdb_id" placeholder="p.sh. tt11053070">
                    </div>
                    <div class="field">
                        <label>Vitet (p.sh. 2021– )</label>
                        <input type="text" name="series_year">
                    </div>
                    <div class="field">
                        <label>Statusi</label>
                        <select name="series_status">
                            <option value="aktiv">aktiv</option>
                            <option value="fshehur">fshehur</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Poster i serialit (URL)</label>
                        <input type="text" name="series_poster" placeholder="https://...jpg">
                    </div>
                    <div class="field">
                        <label>Përshkrimi i serialit</label>
                        <textarea name="series_plot" placeholder="Përmbledhje e shkurtër..."></textarea>
                    </div>
                </div>

                <hr style="border:none; border-top:1px solid #1f2937; margin:18px 0 12px;">

                <h3 style="font-size:14px; margin-bottom:6px;">2️⃣ Episodi (Marrje automatike nga IMDb + videoUrl)</h3>

                <div class="grid-2">
                    <div class="field">
                        <label>Sezoni</label>
                        <input type="number" name="season" value="1" min="1">
                    </div>
                    <div class="field">
                        <label>Epizoda</label>
                        <input type="number" name="episode" value="1" min="1">
                    </div>
                    <div class="field">
                        <label>URL e videos (MP4 / HLS)</label>
                        <input type="text" name="ep_videoUrl" placeholder="http://45.90.81.133:8080/1/seriale/Young-Royals1x1.mp4">
                        <div class="small-note">
                            Këtu vendos direkt link-un e episodit. Pjesa tjetër merret automatikisht nga IMDb.
                        </div>
                    </div>
                    <div class="field" style="display:flex; align-items:flex-end;">
                        <button type="button" class="btn-secondary" id="btnFetchImdb">
                            🎬 Merr nga IMDb (OMDb)
                        </button>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label>Titulli i episodit</label>
                        <input type="text" name="ep_title" id="ep_title" placeholder="Do mbushet automatikisht nga IMDb (ose shkruaje vetë)">
                    </div>
                    <div class="field">
                        <label>Runtime (p.sh. 45 min)</label>
                        <input type="text" name="ep_runtime" id="ep_runtime">
                    </div>
                    <div class="field">
                        <label>Poster i episodit (URL)</label>
                        <input type="text" name="ep_poster" id="ep_poster">
                    </div>
                    <div class="field">
                        <label>IMDb ID i episodit</label>
                        <input type="text" name="ep_imdb_id" id="ep_imdb_id">
                    </div>
                    <div class="field" style="grid-column:1/-1;">
                        <label>Përshkrimi i episodit</label>
                        <textarea name="ep_plot" id="ep_plot" placeholder="Do mbushet nga IMDb nëse gjendet."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-primary">💾 Ruaj serialin & episodin</button>
            </form>
        </div>

        <div class="card">
            <h2>📋 Lista e serialeve</h2>
            <p class="lead">
                Për çdo serial shikon sa episode ka. Mund të fshish serialin ose episode të veçanta.
            </p>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Titulli</th>
                    <th>IMDb</th>
                    <th>Vitet</th>
                    <th>Ep.</th>
                    <th>Status</th>
                    <th>Veprime</th>
                </tr>
                <?php if (empty($seriesList)): ?>
                    <tr><td colspan="7">Nuk ka ende seriale.</td></tr>
                <?php else: ?>
                    <?php foreach ($seriesList as $s):
                        $epCount = isset($s['episodes']) && is_array($s['episodes']) ? count($s['episodes']) : 0;
                        $status  = $s['status'] ?? 'aktiv';
                    ?>
                        <tr>
                            <td>#<?= (int)$s['id'] ?></td>
                            <td><?= h($s['title'] ?? '') ?></td>
                            <td><?= h($s['imdb_id'] ?? '') ?></td>
                            <td><?= h($s['year'] ?? '') ?></td>
                            <td><?= $epCount ?></td>
                            <td>
                                <span class="badge <?= $status === 'aktiv' ? 'badge-green':'badge-red' ?>">
                                    <?= h($status) ?>
                                </span>
                            </td>
                            <td>
                                <a class="link-small" href="../series_watch.php?id=<?= (int)$s['id'] ?>" target="_blank">👁 Shiko</a>
                                &nbsp;|&nbsp;
                                <a class="link-small" href="series.php?delete_series=<?= (int)$s['id'] ?>"
                                   onclick="return confirm('Je i sigurt që do ta fshish këtë serial me të gjitha episodet?');">
                                    🗑 Fshi
                                </a>
                            </td>
                        </tr>
                        <?php if ($epCount): ?>
                            <tr>
                                <td></td>
                                <td colspan="6">
                                    <table class="episodes-table">
                                        <tr>
                                            <th>EpID</th>
                                            <th>Sezoni</th>
                                            <th>Ep.</th>
                                            <th>Titulli</th>
                                            <th>Runtime</th>
                                            <th>IMDb</th>
                                            <th>Video</th>
                                            <th></th>
                                        </tr>
                                        <?php
                                        $episodes = $s['episodes'];
                                        usort($episodes, function($a,$b){
                                            $sa = $a['season'] ?? 0;
                                            $sb = $b['season'] ?? 0;
                                            if ($sa === $sb) {
                                                $ea = $a['episode'] ?? 0;
                                                $eb = $b['episode'] ?? 0;
                                                return $ea <=> $eb;
                                            }
                                            return $sa <=> $sb;
                                        });
                                        foreach ($episodes as $ep):
                                        ?>
                                            <tr>
                                                <td><?= (int)($ep['id'] ?? 0) ?></td>
                                                <td><?= (int)($ep['season'] ?? 0) ?></td>
                                                <td><?= (int)($ep['episode'] ?? 0) ?></td>
                                                <td><?= h($ep['title'] ?? '') ?></td>
                                                <td><?= h($ep['runtime'] ?? '') ?></td>
                                                <td><?= h($ep['imdb_id'] ?? '') ?></td>
                                                <td>
                                                    <?php if (!empty($ep['videoUrl'])): ?>
                                                        <a class="link-small" href="<?= h($ep['videoUrl']) ?>" target="_blank">▶ Hape</a>
                                                    <?php else: ?>
                                                        <span class="small-note">s'ka videoUrl</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a class="link-small"
                                                       href="series.php?sid=<?= (int)$s['id'] ?>&delete_ep=<?= (int)($ep['id'] ?? 0) ?>"
                                                       onclick="return confirm('Fshije këtë episod?');">
                                                        🗑
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>

    </main>
</div>

<script>
// "Merr nga IMDb" – përdor imdb_episode_fetch.php (brenda /admin)
document.getElementById('btnFetchImdb').addEventListener('click', function () {
    const imdbInput   = document.querySelector('input[name="series_imdb_id"]');
    const seasonInput = document.querySelector('input[name="season"]');
    const episodeInput= document.querySelector('input[name="episode"]');

    const imdbId  = (imdbInput.value || '').trim();
    const season  = (seasonInput.value || '').trim();
    const episode = (episodeInput.value || '').trim();

    if (!imdbId) {
        alert('Vendos IMDb ID të serialit (p.sh. tt11053070).');
        imdbInput.focus();
        return;
    }
    if (!season || !episode) {
        alert('Vendos sezonin dhe episodin.');
        return;
    }

    fetch('imdb_episode_fetch.php?imdb_id=' + encodeURIComponent(imdbId)
        + '&season=' + encodeURIComponent(season)
        + '&episode=' + encodeURIComponent(episode))
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert('Gabim: ' + (data.error || 'episodi nuk u gjet.'));
                return;
            }

            document.getElementById('ep_title').value   = data.title   || '';
            document.getElementById('ep_plot').value    = data.plot    || '';
            document.getElementById('ep_poster').value  = data.poster  || '';
            document.getElementById('ep_runtime').value = data.runtime || '';
            document.getElementById('ep_imdb_id').value = data.imdb_id || '';

            const seriesPosterInput = document.querySelector('input[name="series_poster"]');
            if (seriesPosterInput && !seriesPosterInput.value.trim() && data.poster) {
                seriesPosterInput.value = data.poster;
            }

            alert('✅ U morën të dhënat e episodit nga IMDb.');
        })
        .catch(err => {
            console.error(err);
            alert('Gabim gjatë komunikimit me imdb_episode_fetch.php');
        });
});
</script>

</body>
</html>
