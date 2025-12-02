<?php
// admin/player.php – Rregullimet e player-it (VideoJS + Nuevo)

require __DIR__ . '/../app/auth.php';  // auth + session

$user = current_user();
if (!$user || !is_admin()) {
    header('Location: ../login.php');
    exit;
}

// Skedari ku ruhen rregullimet
$settingsFile = __DIR__ . '/../data/player_settings.json';

// Vlerat default (ndërroji sipas dëshirës)
$defaultSettings = [
    'enabled'        => 1,
    'css_videojs'    => 'player/css/video-js.min.css',
    'css_nuevo'      => 'player/css/nuevo.css',
    'js_videojs'     => 'player/js/video.min.js',
    'js_nuevo'       => 'player/js/nuevo.min.js',
    'logo_url'       => 'assets/img/logo.svg',
    'poster_default' => 'assets/img/backgrounds/default-poster.jpg',
    'autoplay'       => 1,
    'muted'          => 1,
    'preload'        => 'auto',

    // Skin & gjuhë (folder-at që ke te /player/skins dhe /player/lang)
    'skin'           => 'default',
    'lang'           => 'en',

    // Video para filmit (preroll)
    'preroll_enabled'    => 0,
    'preroll_url'        => '',
    'preroll_skip_after' => 5,
];

// Lexo rregullimet ekzistuese nëse file ekziston
$settings = $defaultSettings;
if (file_exists($settingsFile)) {
    $json = file_get_contents($settingsFile);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $settings = array_merge($defaultSettings, $decoded);
    }
}

// Ruajtja nga forma
$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings['enabled']        = isset($_POST['enabled']) ? 1 : 0;
    $settings['css_videojs']    = trim($_POST['css_videojs'] ?? '');
    $settings['css_nuevo']      = trim($_POST['css_nuevo'] ?? '');
    $settings['js_videojs']     = trim($_POST['js_videojs'] ?? '');
    $settings['js_nuevo']       = trim($_POST['js_nuevo'] ?? '');
    $settings['logo_url']       = trim($_POST['logo_url'] ?? '');
    $settings['poster_default'] = trim($_POST['poster_default'] ?? '');
    $settings['autoplay']       = isset($_POST['autoplay']) ? 1 : 0;
    $settings['muted']          = isset($_POST['muted']) ? 1 : 0;
    $settings['preload']        = in_array($_POST['preload'] ?? 'auto', ['auto','metadata','none'])
                                  ? $_POST['preload'] : 'auto';

    // skin & lang
    $settings['skin'] = trim($_POST['skin'] ?? 'default');
    $settings['lang'] = trim($_POST['lang'] ?? 'en');

    // preroll
    $settings['preroll_enabled']    = isset($_POST['preroll_enabled']) ? 1 : 0;
    $settings['preroll_url']        = trim($_POST['preroll_url'] ?? '');
    $settings['preroll_skip_after'] = (int)($_POST['preroll_skip_after'] ?? 5);

    file_put_contents(
        $settingsFile,
        json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
    $saved = true;
}

if (!function_exists('esc')) {
    function esc($s) {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <title>Rregullimet e Player-it - NewFilma</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        body {
            background: #020617;
            color: #e5e7eb;
            min-height: 100vh;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 230px;
            background: #020617;
            border-right: 1px solid #1f2937;
            padding: 16px 12px;
        }
        .sidebar-header {
            margin-bottom: 16px;
        }
        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .sidebar-sub {
            font-size: 11px;
            color: #6b7280;
        }
        .sidebar a {
            display: block;
            padding: 8px 10px;
            margin-bottom: 4px;
            color: #9ca3af;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
        }
        .sidebar a:hover {
            background: #111827;
            color: #e5e7eb;
        }
        .sidebar a.active {
            background: #1f2937;
            color: #e5e7eb;
        }

        .content {
            flex: 1;
            padding: 16px 20px 40px;
        }

        .top-info {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 12px;
        }
        .badge {
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid #1f2937;
            background: #0b1120;
            color: #9ca3af;
            font-size: 11px;
        }
        .btn-small {
            text-decoration: none;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            border: 1px solid #334155;
            background: #0f172a;
            color: #e5e7eb;
        }
        .btn-small:hover { background:#1f2937; }
        .btn-danger {
            border-color:#fca5a5;
            background:#b91c1c;
            color:#fee2e2;
        }
        .btn-danger:hover { background:#dc2626; }

        .card {
            background: #020617;
            border-radius: 16px;
            border: 1px solid #1f2937;
            padding: 20px;
            box-shadow: 0 18px 45px rgba(15,23,42,0.7);
            max-width: 1000px;
            margin: 0 auto;
        }
        .card h2 {
            font-size: 20px;
            margin-bottom: 6px;
        }
        .card p.lead {
            color: #9ca3af;
            font-size: 14px;
            margin-bottom: 14px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(240px,1fr));
            gap: 16px;
            margin-top: 8px;
        }

        label {
            font-size: 13px;
            margin-bottom: 4px;
            display: block;
            color: #e5e7eb;
        }
        input[type="text"], select, textarea {
            width: 100%;
            background: #020617;
            border: 1px solid #1f2937;
            border-radius: 10px;
            padding: 8px 10px;
            color: #e5e7eb;
            font-size: 13px;
        }
        textarea { resize: vertical; min-height: 70px; }

        .field { margin-bottom: 12px; }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .btn-primary {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg,#22c55e,#16a34a);
            color: #020617;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            margin-top: 8px;
        }
        .btn-primary:hover { filter: brightness(1.05); }

        .alert {
            background:#022c22;
            border:1px solid #16a34a;
            color:#bbf7d0;
            padding:8px 10px;
            border-radius:8px;
            font-size:13px;
            margin-bottom:12px;
        }

        .section-title {
            font-size: 14px;
            margin: 16px 0 4px;
            font-weight: 600;
        }
        .field small {
            font-size: 11px;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .layout {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #1f2937;
            }
        }
    </style>
</head>
<body>

<div class="layout">
    <!-- SIDEBAR – dizajn si i vjetri, por me linket e reja -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">⚙️ Admin - NewFilma</div>
            <div class="sidebar-sub">Paneli i konfigurimit të player-it</div>
        </div>

        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="movies.php">🎬 Filmat</a>
        <a href="series.php">📺 Serialet</a>
        <a href="tv.php">📡 TV Live</a>
        <a href="users.php">👥 Përdoruesit</a>
        <a href="subscriptions.php">💳 Abonimet</a>
        <a href="payments.php">💰 Pagesat</a>
        <a href="player.php" class="active">🎛 Player Settings</a>

        <hr style="border:none;border-top:1px solid #1f2937;margin:10px 0;">
        <a href="../index.php">🌐 newfilma.com</a>
        <a href="../logout.php">🚪 Dalje</a>
    </aside>

    <main class="content">
        <div class="top-info">
            <span class="badge">👤 <?= esc($user['name'] ?? 'Admin') ?></span>
        </div>

        <div class="card">
            <h2>🎥 Rregullimet e Player-it (VideoJS + Nuevo)</h2>
            <p class="lead">
                Këtu kontrollon CSS/JS të player-it, autoplay, poster-in default, skin-in, gjuhën
                dhe videon preroll para filmit, pa prekur kodin e <code>movie.php</code> dhe <code>series_watch.php</code>.
            </p>

            <?php if ($saved): ?>
                <div class="alert">✔ Rregullimet u ruajtën me sukses.</div>
            <?php endif; ?>

            <form method="post">
                <div class="checkbox-row">
                    <input type="checkbox" id="enabled" name="enabled" value="1"
                        <?= !empty($settings['enabled']) ? 'checked' : '' ?>>
                    <label for="enabled">Aktivo player-in e personalizuar</label>
                </div>

                <div class="section-title">📁 Rrugët e skedarëve</div>
                <div class="grid-2">
                    <div class="field">
                        <label for="css_videojs">CSS i VideoJS</label>
                        <input type="text" id="css_videojs" name="css_videojs"
                               value="<?= esc($settings['css_videojs']) ?>">
                        <small>p.sh. <code>player/css/video-js.min.css</code></small>
                    </div>

                    <div class="field">
                        <label for="css_nuevo">CSS i Nuevo</label>
                        <input type="text" id="css_nuevo" name="css_nuevo"
                               value="<?= esc($settings['css_nuevo']) ?>">
                        <small>p.sh. <code>player/css/nuevo.css</code></small>
                    </div>

                    <div class="field">
                        <label for="js_videojs">JS i VideoJS</label>
                        <input type="text" id="js_videojs" name="js_videojs"
                               value="<?= esc($settings['js_videojs']) ?>">
                        <small>p.sh. <code>player/js/video.min.js</code></small>
                    </div>

                    <div class="field">
                        <label for="js_nuevo">JS i Nuevo</label>
                        <input type="text" id="js_nuevo" name="js_nuevo"
                               value="<?= esc($settings['js_nuevo']) ?>">
                        <small>p.sh. <code>player/js/nuevo.min.js</code></small>
                    </div>
                </div>

                <div class="section-title">🎛 Opsionet bazë</div>
                <div class="checkbox-row">
                    <input type="checkbox" id="autoplay" name="autoplay" value="1"
                        <?= !empty($settings['autoplay']) ? 'checked' : '' ?>>
                    <label for="autoplay">Autoplay kur hapet filmi</label>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="muted" name="muted" value="1"
                        <?= !empty($settings['muted']) ? 'checked' : '' ?>>
                    <label for="muted">Start i heshtur (muted)</label>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="preload">Preload</label>
                        <select id="preload" name="preload">
                            <option value="auto" <?= ($settings['preload'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>auto</option>
                            <option value="metadata" <?= ($settings['preload'] ?? '') === 'metadata' ? 'selected' : '' ?>>metadata</option>
                            <option value="none" <?= ($settings['preload'] ?? '') === 'none' ? 'selected' : '' ?>>none</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="poster_default">Poster default (nëse filmi/episodi s'ka poster)</label>
                        <input type="text" id="poster_default" name="poster_default"
                               placeholder="assets/img/backgrounds/default-poster.jpg"
                               value="<?= esc($settings['poster_default']) ?>">
                    </div>
                </div>

                <div class="section-title">🏷 Logo / watermark</div>
                <div class="field">
                    <label for="logo_url">URL e logos / watermark</label>
                    <input type="text" id="logo_url" name="logo_url"
                           placeholder="assets/img/logo.svg ose https://newfilma.com/logo.png"
                           value="<?= esc($settings['logo_url']) ?>">
                </div>

                <div class="section-title">🎨 Skin & gjuhë</div>
                <div class="grid-2">
                    <div class="field">
                        <label for="skin">Skin i player-it</label>
                        <input type="text" id="skin" name="skin"
                               placeholder="default"
                               value="<?= esc($settings['skin']) ?>">
                        <small>Emri i skin-it te <code>player/skins/</code> (pa .css).</small>
                    </div>

                    <div class="field">
                        <label for="lang">Gjuha (lang)</label>
                        <input type="text" id="lang" name="lang"
                               placeholder="en, sq, it..."
                               value="<?= esc($settings['lang']) ?>">
                        <small>File gjuhësh nga <code>player/lang/</code> p.sh. <code>en</code>, <code>sq</code>.</small>
                    </div>
                </div>

                <div class="section-title">⏯ Video para filmit (Preroll)</div>
                <div class="checkbox-row">
                    <input type="checkbox" id="preroll_enabled" name="preroll_enabled" value="1"
                        <?= !empty($settings['preroll_enabled']) ? 'checked' : '' ?>>
                    <label for="preroll_enabled">Aktivizo preroll (video reklame para filmit)</label>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="preroll_url">URL e videos preroll</label>
                        <input type="text" id="preroll_url" name="preroll_url"
                               placeholder="https://serveri.com/video_intro.mp4"
                               value="<?= esc($settings['preroll_url']) ?>">
                        <small>Mund të jetë MP4 nga serveri yt ose URL e jashtme.</small>
                    </div>

                    <div class="field">
                        <label for="preroll_skip_after">Skip pas sa sekondash?</label>
                        <input type="text" id="preroll_skip_after" name="preroll_skip_after"
                               value="<?= esc($settings['preroll_skip_after']) ?>">
                        <small>p.sh. <code>5</code> = butoni "Skip" pas 5 sekondash.</small>
                    </div>
                </div>

                <button type="submit" class="btn-primary">💾 Ruaj rregullimet</button>
            </form>
        </div>
    </main>
</div>

</body>
</html>
