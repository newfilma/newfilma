<?php
// app/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

/**
 * Kthen user-in aktual nga session (ose null nqs nuk është loguar).
 */
function current_user()
{
    return $_SESSION['user'] ?? null;
}

/**
 * Kontrollon nëse user-i është admin.
 * Supozojmë që user-i ka fushën 'role' => 'admin'.
 */
function is_admin($user = null)
{
    if ($user === null) {
        $user = current_user();
    }
    if (!$user) {
        return false;
    }
    return (($user['role'] ?? '') === 'admin');
}

/**
 * Kontrollon nëse user-i ka abonim aktiv.
 * Lexon nga data/subscriptions.json
 *
 * Shembull strukture subscription:
 * [
 *   {
 *     "id": 1,
 *     "user_id": 3,
 *     "email": "user@example.com",
 *     "plan": "2_euro",
 *     "amount": 2,
 *     "status": "active",
 *     "created_at": "2025-12-01 20:00:00",
 *     "expires_at": "2026-01-01 20:00:00",
 *     "paypal_order_id": "..."
 *   }
 * ]
 */
function has_active_subscription($user = null)
{
    if ($user === null) {
        $user = current_user();
    }
    if (!$user) {
        return false;
    }

    $subs = load_json(DATA_PATH . '/subscriptions.json');
    if (!$subs) {
        return false;
    }

    $userEmail = strtolower(trim($user['email'] ?? ''));
    if ($userEmail === '') {
        return false;
    }

    $now = time();

    foreach ($subs as $sub) {
        $subEmail = strtolower(trim($sub['email'] ?? ''));
        if ($subEmail !== $userEmail) {
            continue;
        }

        if (($sub['status'] ?? '') !== 'active') {
            continue;
        }

        $exp = strtotime($sub['expires_at'] ?? '');
        if ($exp && $exp > $now) {
            return true;
        }
    }

    return false;
}
