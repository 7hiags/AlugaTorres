<?php

// bootstrap comum e helpers
require_once __DIR__ . '/../root/init.php';
// init.php já inicia a sessão, define BASE_URL e carrega db/notifications

// Verificar se o utilizador está logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_utilizador'])) {
    // Log para debug
    error_log("Admin access denied: Session not set. user_id=" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set') . ", tipo=" . (isset($_SESSION['tipo_utilizador']) ? $_SESSION['tipo_utilizador'] : 'not set'));
    notifyError('Acesso negado. Faça login para continuar.');
    header("Location: " . BASE_URL . "backend/autenticacao/login.php");
    exit;
}

// Verificar se é administrador
if ($_SESSION['tipo_utilizador'] !== 'admin') {
    // Log para debug
    error_log("Admin access denied: User is not admin. tipo_utilizador=" . $_SESSION['tipo_utilizador']);
    notifyError('Acesso restrito. Apenas administradores podem aceder a esta área.');
    header("Location: " . BASE_URL . "root/index.php");
    exit;
}

// Log de sucesso
error_log("Admin access granted: user_id=" . $_SESSION['user_id'] . ", tipo=" . $_SESSION['tipo_utilizador']);


// Definir variáveis úteis para as páginas admin
$admin_id = $_SESSION['user_id'];
$admin_nome = $_SESSION['user'];
$admin_email = $_SESSION['email'];

// Função para verificar permissão específica
// avalia diretamente os dados de sessão sem funções auxiliares
function hasPermission($requiredType)
{
    $currentType = $_SESSION['tipo_utilizador'] ?? 'guest';

    // Admin tem acesso a tudo
    if ($currentType === 'admin') {
        return true;
    }

    // Verificar tipo específico
    return $currentType === $requiredType;
}

// Função para redirecionar se não tiver permissão
function requirePermission($requiredType, $redirectUrl = null)
{

    if (!hasPermission($requiredType)) {
        $redirect = $redirectUrl ?? BASE_URL . 'root/index.php';

        notifyError('Não tem permissão para aceder a esta funcionalidade.');
        header("Location: " . $redirect);
        exit;
    }
}

// Função para log de atividades admin
function logAdminActivity($acao, $detalhes = '')
{
    global $conn, $admin_id;

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');

    // Inserir log na base de dados (se a tabela existir)
    try {
        $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, acao, detalhes, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssss", $admin_id, $acao, $detalhes, $ip, $user_agent, $timestamp);
            $stmt->execute();
        }
    } catch (\Exception $e) {
        // Silenciar erro se a tabela não existir ainda
        error_log("Erro ao logar atividade admin: " . $e->getMessage());
    }

    // Também logar no arquivo
    $logMessage = sprintf(
        "[%s] Admin ID: %d | Ação: %s | Detalhes: %s | IP: %s\n",
        $timestamp,
        $admin_id,
        $acao,
        $detalhes,
        $ip
    );

    $logFile = __DIR__ . '/admin_activity.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}
