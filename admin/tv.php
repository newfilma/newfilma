<?php
// admin/tv.php – delegon te admin.tvlive.php ekzistues

require_once __DIR__ . '/../app/auth.php';

$user = current_user();
if (!is_admin($user)) {
    redirect('../login.php');
}

require __DIR__ . '/../admin.tvlive.php';
