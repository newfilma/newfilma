<?php
// tmdb/tmdb_search.php – kërkon filma/seriale sipas titullit

require_once __DIR__ . '/tmdb_config.php';

header('Content-Type: application/json');

$query = $_GET['q']    ?? '';
$type  = $_GET['type'] ?? 'movie'; // movie ose tv

if ($query === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mungon q']);
    exit;
}

$endpoint = ($type === 'tv') ? '/search/tv' : '/search/movie';
$url = $TMDB_BASE_URL . $endpoint . '?api_key=' . urlencode($TMDB_API_KEY)
    . '&language=sq&query=' . urlencode($query);

$resp = file_get_contents($url);
if ($resp === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Gabim gjatë lidhjes me TMDb']);
    exit;
}

echo $resp;
