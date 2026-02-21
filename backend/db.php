<?php

/**
 * ========================================
 * Database Connection - Conexão com o Banco de Dados
 * ========================================
 * Este arquivo estabelece a conexão com o banco de dados MySQL
 * utilizado pelo sistema AlugaTorres.
 * 
 * Configurações:
 * - Host: localhost
 * - Usuário: root
 * - Senha: (vazia)
 * - Banco de dados: dbalugatorres
 * 
 * @author AlugaTorres
 * @version 1.0
 */

// ============================================
// Configurações de Erros
// ============================================

// Suprime erros para evitar saída HTML em respostas JSON
error_reporting(0);
ini_set('display_errors', 0);

// ============================================
// Configurações de Conexão
// ============================================

// Host do banco de dados (servidor local)
$host = "localhost";

// Nome de usuário do banco de dados
$user = "root";

// Senha do banco de dados (vazia para XAMPP padrão)
$password = "";

// Nome do banco de dados
$dbname = "dbalugatorres";

// ============================================
// Estabelecimento da Conexão
// ============================================

try {
    // Cria uma nova conexão MySQLi
    $conn = new mysqli($host, $user, $password, $dbname);

    // Verifica se houve erro na conexão
    if ($conn->connect_error) {
        // Lança exceção com mensagem de erro detalhada
        throw new \Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }

    // Configura o charset para UTF-8 (suporta caracteres portugueses)
    if (!$conn->set_charset("utf8mb4")) {
        // Lança exceção se houver erro na configuração do charset
        throw new \Exception("Erro ao configurar charset: " . $conn->error);
    }

    // ============================================
    // Tratamento de Exceções
    // ============================================    
} catch (\Exception $e) {
    // Em caso de erro, exibe mensagem e encerra a execução
    die("Erro na conexão: " . $e->getMessage());
}
