<?php
function db() {
    $connString = getenv('DATABASE_URL');
    
    if (!$connString) {
        die("DATABASE_URL no configurada en Railway");
    }
    
    $url = parse_url($connString);
    
    $host = $url['host'];
    $port = $url['port'];
    $dbname = substr($url['path'], 1); // Eliminar el / inicial
    $user = $url['user'];
    $pass = $url['pass'];

    try {
        $pdo = new PDO(
            "pgsql:host=$host;port=$port;dbname=$dbname", 
            $user, 
            $pass
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

function get_db_connection() {
    $connString = getenv('DATABASE_URL');
    if (!$connString) {
        die("DATABASE_URL no configurada en Railway");
    }
    
    $conn = pg_connect($connString);
    if (!$conn) {
        die("Error en conexión PostgreSQL: " . pg_last_error());
    }
    return $conn;
}
?>