<?php
// app/functions.php

require_once __DIR__ . '/config.php';

/**
 * Lexon një JSON dhe kthen array.
 */
function load_json($path)
{
    if (!file_exists($path)) {
        return [];
    }
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Ruajtje e një array në JSON (pretty print).
 */
function save_json($path, $data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($path, $json);
}

/**
 * Helper për escape output-i në HTML
 */
function h($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect i thjeshtë
 */
function redirect($url)
{
    header("Location: $url");
    exit;
}
