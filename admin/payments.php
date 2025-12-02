<?php
// admin/payments.php – nëse ruan pagesat veç (mund të përdorësh të njëjtin subscriptions.json)

require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/functions.php';

$user = current_user();
if (!is_admin($user)) {
    redirect('../login.php');
}

// Për thjeshtësi, po lexojmë po atë subscriptions.json
$payments = load_json(DATA_PATH . '/subscriptions.json');

$page_title  = 'Admin – Pagesat';
$active_page = 'admin_payments';

require_once __DIR__ . '/../app/header.php';
?>

<h1 style="font-size:1.3rem; margin-bottom:1rem;">Pagesat (PayPal)</h1>

<table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
    <thead>
    <tr style="background:#020617;">
        <th style="padding:.4rem; border-bottom:1px solid #111827;">ID</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Email</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Order ID</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Shuma</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Status</th>
        <th style="padding:.4rem; border-bottom:1px solid #111827;">Krijuar më</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
        <tr>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($p['id'] ?? '') ?></td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($p['email'] ?? '') ?></td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($p['paypal_order_id'] ?? '') ?></td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($p['amount'] ?? '') ?> €</td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($p['status'] ?? '') ?></td>
            <td style="padding:.35rem; border-bottom:1px solid #111827;"><?= h($p['created_at'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
require_once __DIR__ . '/../app/footer.php';
