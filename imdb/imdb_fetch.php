<?php
// imdb/imdb_fetch.php – merr info për 1 film sipas imdb_id

require_once __DIR__ . '/imdb_config.php';

header('Content-Type: application/json');

$imdbId = $_GET['imdb_id'] ?? '';
if ($imdbId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mungon imdb_id']);
    exit;
}

$url = $OMDB_BASE_URL . '?apikey=' . urlencode($OMDB_API_KEY) . '&i=' . urlencode($imdbId) . '&plot=full';

$resp = file_get_contents($url);
if ($resp === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Gabim gjatë lidhjes me OMDb']);
    exit;
}

echo $resp;
