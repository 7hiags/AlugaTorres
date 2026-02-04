<?php
// Habilita a exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$dbname = "dbalugatorres";

try {
    $conn = new mysqli($host, $user, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }

    // Configura o charset para UTF-8
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Erro ao configurar charset: " . $conn->error);
    }
} catch (Exception $e) {
    die("Erro na conexão: " . $e->getMessage());
}
