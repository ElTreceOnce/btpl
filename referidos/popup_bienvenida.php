<?php
// Muestra un popup de bienvenida UNA sola vez tras iniciar sesión.
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['popup_bienvenida'])) return;
$_SESSION['popup_bienvenida'] = 0; // se limpia: no vuelve a salir hasta el próximo login
?>
<div id="bp-popup-overlay" class="bp-popup-overlay">
  <div class="bp-popup-card">
    <div class="bp-popup-head">
      <img src="/img/betcol.png" alt="BetPlay" class="bp-popup-logo">
      <button type="button" class="bp-popup-x" onclick="bpCerrarPopup()" aria-label="Cerrar">&times;</button>
    </div>
    <div class="bp-popup-body">
      <h3 class="bp-popup-title">¡Ya estás participando!</h3>
      <p class="bp-popup-text">
        Estás <b>refiriendo amigos</b> para ganar un bono de
        <b class="bp-popup-monto">$400.000</b> que puedes <b>retirar o usar para apostar</b>.
      </p>
      <p class="bp-popup-text">
        Cada vez que quieras ver tu progreso, solo <b>inicia sesión</b>.
      </p>
      <button type="button" class="bp-popup-btn" onclick="bpCerrarPopup()">Entendido</button>
    </div>
  </div>
</div>

<style>
  .bp-popup-overlay{
    position:fixed; inset:0; z-index:999999;
    background:rgba(0,24,73,.75);
    display:flex; align-items:center; justify-content:center;
    padding:18px; font-family:Montserrat,Roboto,Arial,sans-serif;
    opacity:0; transition:opacity .25s ease;
  }
  .bp-popup-overlay.bp-visible{opacity:1}
  .bp-popup-card{
    width:100%; max-width:390px; background:#fff; border-radius:16px;
    overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.45);
    transform:translateY(14px) scale(.97); transition:transform .25s ease;
  }
  .bp-popup-overlay.bp-visible .bp-popup-card{transform:translateY(0) scale(1)}
  .bp-popup-head{
    background:#002361; padding:16px 18px; display:flex;
    align-items:center; justify-content:space-between;
  }
  .bp-popup-logo{height:34px; width:auto}
  .bp-popup-x{
    background:transparent; border:0; color:#fff; font-size:26px;
    line-height:1; cursor:pointer; padding:0 4px;
  }
  .bp-popup-body{padding:22px 22px 24px; text-align:center}
  .bp-popup-title{
    color:#002361; font-size:20px; font-weight:900; margin:0 0 12px;
  }
  .bp-popup-text{
    color:#40454f; font-size:14px; line-height:1.5; margin:0 0 12px;
  }
  .bp-popup-monto{color:#00ae42}
  .bp-popup-btn{
    margin-top:8px; width:100%; max-width:230px; height:48px;
    background:#00ae42; color:#fff; border:0; border-radius:50px;
    font-size:15px; font-weight:700; cursor:pointer; transition:.2s;
  }
  .bp-popup-btn:hover{background:#009037}
</style>

<script>
  (function(){
    var ov = document.getElementById('bp-popup-overlay');
    if(!ov) return;
    requestAnimationFrame(function(){ ov.classList.add('bp-visible'); });
    window.bpCerrarPopup = function(){
      ov.classList.remove('bp-visible');
      setTimeout(function(){ if(ov && ov.parentNode) ov.parentNode.removeChild(ov); }, 250);
    };
    ov.addEventListener('click', function(e){ if(e.target === ov) window.bpCerrarPopup(); });
  })();
</script>