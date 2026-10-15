<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/helpers.php';

$c = preg_replace('/[^A-Za-z0-9]/', '', $_GET['c'] ?? '');
if ($c === '') {
    header('Location: ' . url_base() . '/index.php');
    exit;
}
setcookie('ref', $c, [
    'expires'  => time() + 60 * 60 * 24 * 30,
    'path'     => '/',
    'samesite' => 'Lax',
]);
header('Location: ' . url_base() . '/index.php?ref=' . $c, true, 302);
exit;

// Guardamos el referido en cookie (30 dias) y en sesion por si acaso
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION['ref'] = $codigo;
setcookie('ref', $codigo, [
    'expires'  => time() + 60 * 60 * 24 * 30,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Redirigir al index con el ref en la query (para que JS/HTML tambien lo vea)
$destino = base_url('/index.php') . '?ref=' . rawurlencode($codigo);
header('Location: ' . $destino, true, 302);
exit;