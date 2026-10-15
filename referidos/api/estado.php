<?php
// ============================================
//  referidos/api/estado.php  -  estado de verificacion
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
    echo json_encode(['estado' => 'rechazado', 'motivo' => 'Sin sesion']);
    exit;
}

$st = db()->prepare('SELECT estado, motivo FROM usuarios WHERE id = ? LIMIT 1');
$st->execute([$uid]);
$u = $st->fetch();

if (!$u) {
    echo json_encode(['estado' => 'rechazado', 'motivo' => 'Usuario no encontrado']);
    exit;
}

$estado = $u['estado'];
if (!in_array($estado, ['pendiente', 'verificado', 'rechazado'], true)) {
    $estado = 'pendiente';
}

echo json_encode(['estado' => $estado, 'motivo' => $u['motivo'] ?? null]);