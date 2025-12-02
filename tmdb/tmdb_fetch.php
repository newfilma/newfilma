<?php
// tmdb/tmdb_fetch.php – merr info për 1 film/serial sipas TMDb ID

require_once __DIR__ . '/tmdb_config.php';

header('Content-Type: application/json');

$id   = $_GET['id']   ?? '';
$type = $_GET['type'] ?? 'movie'; // movie ose tv

if ($id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mungon id']);
    exit;
}

$endpoint = ($type === 'tv') ? '/tv/' : '/movie/';
$url = $TMDB_BASE_URL . $endpoint . urlencode($id) . '?api_key=' . urlencode($TMDB_API_KEY) . '&language=sq';

$resp = file_get_contents($url);
if ($resp === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Gabim gjatë lidhjes me TMDb']);
    exit;
}

echo $resp;
