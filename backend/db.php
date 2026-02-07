<?php
// Suppress errors to prevent HTML output in JSON responses
error_reporting(0);
ini_set('display_errors', 0);

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
