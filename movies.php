<?php
// movies.php – Lista e filmave për përdoruesit

ini_set('display_errors', 1);
error_reporting(E_ALL);

// auth
require_once __DIR__ . '/app/auth.php';

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$user   = current_user();
$hasSub = has_active_subscription($user);

if (!$user) {
    header('Location: login.php');
    exit;
}

// lexojmë filmat
$moviesFile = __DIR__ . '/data/movies.json';
$movies     = [];

if (file_exists($moviesFile)) {
    $json    = file_get_contents($moviesFile);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $movies = $decoded;
    }
}

// filtro / kërkim
$movies = array_values($movies);

$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $lower = mb_strtolower($search, 'UTF-8');
    $movies = array_values(array_filter($movies, function ($m) use ($lower) {
        $title = mb_strtolower($m['title'] ?? '', 'UTF-8');
        return strpos($title, $lower) !== false;
    }));
}

$category = trim($_GET['cat'] ?? '');
if ($category !== '') {
    $movies = array_values(array_filter($movies, function ($m) use ($category) {
        return ($m['category'] ?? '') === $category;
    }));
}

// lista kategorive
$allCategories = [];
foreach ($movies as $m) {
    if (!empty($m['category'])) {
        $allCategories[$m['category']] = true;
    }
}
$allCategories = array_keys($allCategories);
sort($allCategories);

$page_title  = 'Filma - NewFilma';
$active_page = 'movies';

require __DIR__ . '/header.php';
?>
<main class="page-main">
    <div class="page-container">
        <div class="page-header">
            <div>
                <h1 class="page-title">🎬 Filmat</h1>
                <p class="page-subtitle">
                    Zgjidh një film për të parë.
                    <?php if (!$hasSub): ?>
                        <span class="badge-warning">
                            Nuk ke abonim aktiv – disa filma mund të jenë të kufizuar.
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="page-user-pill">
                <span>👤 <?= h($user['name'] ?? $user['email'] ?? 'Përdorues'); ?></span>
            </div>
        </div>

        <form class="movies-filters" method="get" action="movies.php">
            <div class="movies-filters-left">
                <input
                    type="text"
                    name="q"
                    class="input-pill"
                    placeholder="Kërko film sipas titullit..."
                    value="<?= h($search); ?>"
                >
                <?php if ($category !== ''): ?>
                    <input type="hidden" name="cat" value="<?= h($category); ?>">
                <?php endif; ?>
            </div>
            <div class="movies-filters-right">
                <select name="cat" class="input-pill" onchange="this.form.submit()">
                    <option value="">Të gjitha kategoritë</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= h($cat); ?>" <?= $cat === $category ? 'selected' : ''; ?>>
                            <?= h(ucfirst($cat)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary">🔍 Kërko</button>
            </div>
        </form>

        <?php if (empty($movies)): ?>
            <div class="empty-state">
                <p>Nuk u gjet asnjë film për këtë kërkim.</p>
            </div>
        <?php else: ?>
            <div class="movies-grid">
                <?php foreach ($movies as $m): ?>
                    <?php
                    $id     = (int)($m['id'] ?? 0);
                    $title  = $m['title'] ?? 'Pa titull';
                    $year   = $m['year'] ?? '';
                    $rating = $m['rating'] ?? '';
                    $qual   = $m['quality'] ?? '';
                    $cat    = $m['category'] ?? '';
                    $poster = $m['poster'] ?? '';

                    if ($poster === '' || $poster === 'N/A') {
                        $poster = 'assets/img/default-poster.jpg';
                    }
                    ?>
                    <a href="movie.php?id=<?= $id; ?>" class="movie-card-link">
                        <div class="movie-card">
                            <div class="movie-poster-wrap">
                                <img src="<?= h($poster); ?>" alt="<?= h($title); ?>" class="movie-poster">
                                <?php if ($qual): ?>
                                    <span class="badge-quality"><?= h($qual); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="movie-info">
                                <div class="movie-title-row">
                                    <h2 class="movie-title"><?= h($title); ?></h2>
                                    <?php if ($year): ?>
                                        <span class="movie-year"><?= h($year); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="movie-meta">
                                    <?php if ($rating): ?>
                                        <span class="badge-rating">⭐ <?= h($rating); ?></span>
                                    <?php endif; ?>
                                    <?php if ($cat): ?>
                                        <span class="badge-cat"><?= h(ucfirst($cat)); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/footer.php'; ?>
