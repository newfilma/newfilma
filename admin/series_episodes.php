<?php
// admin/series_episodes.php
// Nese ke bërë logjikë veç për episodet, mund ta zhvendosësh këtu.
// Për momentin thjesht përfshijmë admin.series.php

require_once __DIR__ . '/../app/auth.php';

$user = current_user();
if (!is_admin($user)) {
    redirect('../login.php');
}

require __DIR__ . '/../admin.series.php';
