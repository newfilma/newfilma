<?php
// imdb/imdb_update.php – rifreskon të dhënat e një filmi ekzistues në movies.json

require_once __DIR__ . '/../app/functions.php';
require_once __DIR__ . '/imdb_config.php';

header('Content-Type: application/json');

$imdbId = $_GET['imdb_id'] ?? '';
if ($imdbId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mungon imdb_id']);
    exit;
}

$moviesPath = DATA_PATH . '/movies.json';
$movies = load_json($moviesPath);

$movieKey = null;
foreach ($movies as $k => $m) {
    if (($m['imdb_id'] ?? '') === $imdbId) {
        $movieKey = $k;
        break;
    }
}
if ($movieKey === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Filmi nuk u gjet në movies.json']);
    exit;
}

// marrim info nga OMDb
$url = $OMDB_BASE_URL . '?apikey=' . urlencode($OMDB_API_KEY) . '&i=' . urlencode($imdbId) . '&plot=full';
$resp = file_get_contents($url);
if ($resp === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Gabim gjatë lidhjes me OMDb']);
    exit;
}

$data = json_decode($resp, true);
if (!is_array($data) || ($data['Response'] ?? 'False') !== 'True') {
    http_response_code(500);
    echo json_encode(['error' => 'Përgjigje e pavlefshme nga OMDb']);
    exit;
}

// përditëso disa fusha
$movies[$movieKey]['title']   = $data['Title'] ?? $movies[$movieKey]['title'] ?? '';
$movies[$movieKey]['year']    = $data['Year'] ?? '';
$movies[$movieKey]['plot']    = $data['Plot'] ?? '';
$movies[$movieKey]['poster']  = $data['Poster'] ?? '';
$movies[$movieKey]['genre']   = $data['Genre'] ?? '';
$movies[$movieKey]['imdb_rating'] = $data['imdbRating'] ?? '';

save_json($moviesPath, $movies);

echo json_encode(['success' => true, 'movie' => $movies[$movieKey]]);
