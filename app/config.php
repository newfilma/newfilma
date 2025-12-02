<?php
// app/config.php

// Rruga bazë e projektit (newfilma/)
define('BASE_PATH', __DIR__ . '/..');

// Rruga ku mbahen json-at
define('DATA_PATH', BASE_PATH . '/data');

// Base URL i faqes (NDËRROJE NË PRODUKSION)
$BASE_URL = '/newfilma'; // p.sh. '/newfilma' ose '' nëse je direkt në root domain
