<?php
// Script para verificar logs de erro
echo "<h1>Logs de Erro</h1>";

// Verificar o log de erros do PHP
$error_log = ini_get('error_log');
if (empty($error_log)) {
    $error_log = 'C:/xampp/php/logs/php_error_log'; // Default para XAMPP
}

echo "<h2>Caminho do log: $error_log</h2>";

if (file_exists($error_log)) {
    $logs = file_get_contents($error_log);

    // Filtrar apenas logs relacionados ao AlugaTorres
    $lines = explode("\n", $logs);
    $relevant_logs = [];

    foreach ($lines as $line) {
        if (
            strpos($line, 'AlugaTorres') !== false ||
            strpos($line, 'admin') !== false ||
            strpos($line, 'Login') !== false ||
            strpos($line, 'Admin access') !== false
        ) {
            $relevant_logs[] = $line;
        }
    }

    // Mostrar os últimos 50 logs relevantes
    $relevant_logs = array_slice($relevant_logs, -50);

    echo "<pre>";
    echo implode("\n", $relevant_logs);
    echo "</pre>";
} else {
    echo "<p>Arquivo de log não encontrado em: $error_log</p>";

    // Tentar encontrar o log
    $possible_paths = [
        'C:/xampp/php/logs/php_error_log',
        'C:/xampp/apache/logs/error.log',
        'C:/xampp/php/logs/php_errors.log',
        __DIR__ . '/backend/admin_activity.log'
    ];

    echo "<h3>Tentando outros caminhos:</h3>";
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            echo "<p>✓ Encontrado: $path</p>";
        } else {
            echo "<p>✗ Não encontrado: $path</p>";
        }
    }
}

// Verificar sessão atual
echo "<h2>Sessão Atual</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Verificar se o admin existe na base de dados
echo "<h2>Verificação do Admin na Base de Dados</h2>";
require_once 'backend/db.php';

$result = $conn->query("SELECT id, utilizador, email, tipo_utilizador, ativo FROM utilizadores WHERE email = 'admin@alugatorres.pt'");
if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<pre>";
    print_r($admin);
    echo "</pre>";

    if ($admin['tipo_utilizador'] === 'admin') {
        echo "<p style='color: green;'>✓ O utilizador é admin</p>";
    } else {
        echo "<p style='color: red;'>✗ O utilizador NÃO é admin. Tipo: " . $admin['tipo_utilizador'] . "</p>";
    }

    if ($admin['ativo'] == 1) {
        echo "<p style='color: green;'>✓ O utilizador está ativo</p>";
    } else {
        echo "<p style='color: red;'>✗ O utilizador NÃO está ativo</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Admin não encontrado na base de dados!</p>";
}

echo "<h2>Links Úteis</h2>";
echo "<p><a href='test_login.php'>Testar login automático</a></p>";
echo "<p><a href='admin/debug_session.php'>Verificar sessão no admin</a></p>";
echo "<p><a href='backend/login.php'>Página de login</a></p>";
echo "<p><a href='backend/logout.php'>Fazer logout</a></p>";
