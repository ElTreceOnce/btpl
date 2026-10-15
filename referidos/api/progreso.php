<?php
// ============================================
//  referidos/api/progreso.php  -  solo porcentaje
// ============================================
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$uid = (int)($_SESSION['uid'] ?? 0);
if ($uid <= 0) {
    echo json_encode(['pct' => 0]);
    exit;
}

echo json_encode(['pct' => progreso_actual(db(), $uid)]);