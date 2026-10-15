<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['cedula'])) { header('Location: /index.php'); exit; }

$pdo = db();
$st = $pdo->prepare('SELECT * FROM usuarios WHERE cedula = ? LIMIT 1');
$st->execute([$_SESSION['cedula']]);
$u = $st->fetch();
if (!$u) { header('Location: /index.php'); exit; }

$codigo = (string)$u['codigo_corto'];
$link   = url_base() . '/r/' . $codigo;
$pct    = progreso_actual($pdo, $codigo);
$logo   = url_base() . '/img/betcol.png';

// Solo referidos VALIDOS
$st = $pdo->prepare('SELECT cedula, creado_en FROM referidos WHERE usuario_id = ? AND estado = "valido" ORDER BY id DESC');
$st->execute([(int)$u['id']]);
$referidos = $st->fetchAll();
?>
<!DOCTYPE html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tus referidos</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:#eef1f6;min-height:100vh;font-family:Montserrat,system-ui,sans-serif;padding-bottom:40px}

  .topbar{background:#002361;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
  .topbar img{height:34px}
  .logout{
    border:1px solid #3cb4e5;background:transparent;color:#fff;font-weight:700;font-size:13px;
    padding:9px 18px;border-radius:6px;cursor:pointer;font-family:inherit;text-decoration:none;
  }
  .logout:hover{background:#3cb4e5;color:#002361}

  .wrap{max-width:560px;margin:28px auto;padding:0 20px}
  .card{background:#fff;border-radius:16px;padding:28px 26px;box-shadow:0 14px 40px rgba(0,20,60,.10)}

  .title{color:#002361;font-weight:800;font-size:24px}
  .cedula{color:#6b7a90;font-weight:700;font-size:14px;margin-top:8px}

  .label{color:#002361;font-weight:800;font-size:13px;text-transform:uppercase;letter-spacing:.5px;margin:24px 0 10px}
  .linkrow{display:flex;gap:10px}
  .link{
    flex:1;min-width:0;border:1px solid #d7dee8;border-radius:10px;padding:13px 15px;font-size:15px;
    font-weight:700;color:#002361;font-family:inherit;background:#f6f8fb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  }
  .copy{border:none;border-radius:10px;background:#002361;color:#fff;font-weight:800;font-size:14px;padding:0 20px;cursor:pointer;font-family:inherit}
  .copy:hover{background:#0a367f}
  .wa{width:100%;margin-top:12px;border:none;border-radius:10px;background:#00ae42;color:#fff;font-weight:800;font-size:14px;padding:14px;cursor:pointer;font-family:inherit}
  .wa:hover{background:#009037}

  .barwrap{margin-top:24px;height:16px;background:#e6eaf0;border-radius:999px;overflow:hidden}
  .bar{height:100%;width:0;background:linear-gradient(90deg,#00ae42,#3cb4e5);border-radius:999px;transition:width 1s cubic-bezier(.25,.8,.25,1)}
  .pct{text-align:right;color:#002361;font-weight:800;font-size:17px;margin-top:10px}
  .hint{color:#6b7a90;font-weight:600;font-size:13px;margin-top:12px}

  .listtitle{color:#002361;font-weight:800;font-size:13px;text-transform:uppercase;letter-spacing:.5px;margin:28px 0 12px}
  .item{display:flex;align-items:center;justify-content:space-between;padding:13px 14px;border:1px solid #e6eaf0;border-radius:10px;margin-bottom:8px}
  .item .ced{color:#002361;font-weight:700;font-size:14px}
  .item .fecha{color:#9aa7b8;font-weight:600;font-size:11px;margin-top:2px}
  .empty{color:#9aa7b8;font-size:13px;font-weight:600;padding:8px 2px}

  .toast{position:fixed;left:50%;bottom:28px;transform:translateX(-50%) translateY(20px);background:#002361;color:#fff;padding:11px 20px;border-radius:10px;font-weight:700;font-size:13px;opacity:0;transition:.25s;pointer-events:none}
  .toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
</style>
</head>
<body>

<header class="topbar">
  <img src="<?= htmlspecialchars($logo) ?>" alt="BetPlay">
  <a class="logout" href="/index.php?logout=1">Cerrar sesión</a>
</header>

<div class="wrap">
  <div class="card">
    <div class="title">Tus referidos</div>
    <div class="cedula">Cédula: <?= htmlspecialchars((string)$u['cedula']) ?></div>

    <div class="label">Tu enlace</div>
    <div class="linkrow">
      <input class="link" id="pnLink" readonly value="<?= htmlspecialchars($link) ?>">
      <button class="copy" id="pnCopy">Copiar</button>
    </div>
    <button class="wa" id="pnWa">Compartir por WhatsApp</button>

    <div class="barwrap"><div class="bar" id="pnBar"></div></div>
    <div class="pct" id="pnPct">0%</div>
    <div class="hint">Completa la barra y desbloqueas tu bono retirable.</div>

    <div class="listtitle">Referidos válidos (<?= count($referidos) ?>)</div>
    <?php if (!$referidos): ?>
      <div class="empty">Aún no tienes referidos válidos. Comparte tu enlace para empezar.</div>
    <?php else: ?>
      <?php foreach ($referidos as $r): ?>
        <div class="item">
          <div>
            <div class="ced"><?= htmlspecialchars((string)$r['cedula']) ?></div>
            <div class="fecha"><?= htmlspecialchars((string)($r['creado_en'] ?? '')) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="toast" id="pnToast">Enlace copiado</div>

<script>
(function(){
  var target = <?= (float)$pct ?>;
  var bar = document.getElementById('pnBar');
  var pct = document.getElementById('pnPct');

  var t0 = performance.now(), dur = 900;
  function anim(now){
    var k = Math.min((now - t0) / dur, 1);
    var v = target * (1 - Math.pow(1 - k, 3));
    bar.style.width = v.toFixed(2) + '%';
    pct.textContent = Math.round(v) + '%';
    if (k < 1) requestAnimationFrame(anim); else pct.textContent = Math.round(target) + '%';
  }
  requestAnimationFrame(anim);

  var toast = document.getElementById('pnToast');
  function show(t){ toast.textContent = t; toast.classList.add('show'); setTimeout(function(){ toast.classList.remove('show'); }, 1600); }

  document.getElementById('pnCopy').onclick = function(){
    var v = document.getElementById('pnLink').value;
    navigator.clipboard.writeText(v).then(function(){ show('Enlace copiado'); }).catch(function(){
      var el = document.getElementById('pnLink'); el.select(); document.execCommand('copy'); show('Enlace copiado');
    });
  };

  document.getElementById('pnWa').onclick = function(){
    var v = document.getElementById('pnLink').value;
    window.open('https://wa.me/?text=' + encodeURIComponent('Regístrate con mi enlace: ' + v), '_blank');
  };
})();
</script>
<?php require __DIR__ . '/popup_bienvenida.php'; ?>
</body>
</html>