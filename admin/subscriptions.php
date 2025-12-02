<?php
// admin/subscriptions.php – thjesht lexon subscriptions.json

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/functions.php';

$user = current_user();
if (!is_admin($user)) {
    redirect('../login.php');
}

$subs = load_json(DATA_PATH . '/subscriptions.json');

$page_title  = 'Admin – Abonimet';
$active_page = 'admin_subscriptions';

require_once __DIR__ . '/../app/header.php';
?>

<h1 style="font-size:1.3rem; margin-bottom:1rem;">Abonimet</h1>

<table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
    <thead>
    <tr style="background:#020617;">
        <th style="padding:.4rem; border-bottom:1px solid #111827;">ID</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Email</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Plani</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Shuma</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Status</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Skadon më</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($subs as $s): ?>
        <tr>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($s['id'] ?? '') ?></td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($s['email'] ?? '') ?></td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($s['plan'] ?? '') ?></td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($s['amount'] ?? '') ?> €</td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($s['status'] ?? '') ?></td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($s['expires_at'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
require_once __DIR__ . '/../app/footer.php';
