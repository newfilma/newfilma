<?php
// imdb/bulk_imdb_import.php – importon shumë filma njëherësh nga listë imdb_id-sh

require_once __DIR__ . '/../app/functions.php';
require_once __DIR__ . '/imdb_config.php';

header('Content-Type: application/json');

// Presim qe lista te vijë si ?ids=tt0111161,tt1375666,...
$idsParam = $_GET['ids'] ?? '';
$ids = array_filter(array_map('trim', explode(',', $idsParam)));

if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'Mungon lista ids']);
    exit;
}

$moviesPath = DATA_PATH . '/movies.json';
$movies = load_json($moviesPath);

// helper për gjenerim id të ri
function next_movie_id($movies)
{
    $max = 0;
    foreach ($movies as $m) {
        if (($m['id'] ?? 0) > $max) {
            $max = $m['id'];
        }
    }
    return $max + 1;
}

$imported = [];

foreach ($ids as $imdbId) {
    $url = $OMDB_BASE_URL . '?apikey=' . urlencode($OMDB_API_KEY) . '&i=' . urlencode($imdbId) . '&plot=full';
    $resp = file_get_contents($url);
    if ($resp === false) {
        continue;
    }
    $data = json_decode($resp, true);
    if (!is_array($data) || ($data['Response'] ?? 'False') !== 'True') {
        continue;
    }

    $newId = next_movie_id($movies);

    $movie = [
        'id'          => $newId,
        'imdb_id'     => $imdbId,
        'title'       => $data['Title'] ?? '',
        'year'        => $data['Year'] ?? '',
        'plot'        => $data['Plot'] ?? '',
        'poster'      => $data['Poster'] ?? '',
        'genre'       => $data['Genre'] ?? '',
        'imdb_rating' => $data['imdbRating'] ?? '',
        'status'      => 'aktiv',
    ];

    $movies[]   = $movie;
    $imported[] = $movie;
}

save_json($moviesPath, $movies);

echo json_encode([
    'success'   => true,
    'imported'  => $imported,
    'total_now' => count($movies),
]);
