<?php

/**
 * ========================================
 * Helper de Notificações Toast - AlugaTorres
 * ========================================
 * 
 * Este arquivo contém funções para gerenciar notificações toast
 * que serão exibidas ao usuário após operações (sucesso, erro, aviso, etc.)
 * 
 * As notificações são armazenadas na sessão e renderizadas via JavaScript
 * utilizando o sistema AlugaTorresNotifications.
 * 
 * @author AlugaTorres
 * @version 1.0
 */

// ============================================
// Funções de Notificação
// ============================================

/**
 * Adiciona uma notificação à sessão
 * 
 * Esta função cria uma nova notificação e a armazena na sessão
 * para ser renderizada posteriormente na página.
 * 
 * @param string $type Tipo da notificação: 'success', 'error', 'warning', 'info'
 * @param string $message Mensagem da notificação
 * @param int $duration Duração em milissegundos (padrão: 5000)
 * @return void
 */
function addNotification($type, $message, $duration = 5000)
{
    // Inicia sessão se necessário
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Inicializa o array de notificações se não existir
    if (!isset($_SESSION['notifications'])) {
        $_SESSION['notifications'] = [];
    }

    // Adiciona a nova notificação com ID único
    $_SESSION['notifications'][] = [
        'type' => $type,
        'message' => $message,
        'duration' => $duration,
        'id' => uniqid('notif_')
    ];
}

/**
 * Notificação de sucesso (cor verde)
 * 
 * Utilizada para operações que foram concluídas com êxito.
 * 
 * @param string $message Mensagem a ser exibida
 * @param int $duration Duração em milissegundos (padrão: 5000)
 * @return void
 */
function notifySuccess($message, $duration = 5000)
{
    addNotification('success', $message, $duration);
}

/**
 * Notificação de erro (cor vermelha)
 * 
 * Utilizada quando ocorre um erro na operação.
 * Duração padrão maior (8000ms) para garantir que o utilizador veja o erro.
 * 
 * @param string $message Mensagem a ser exibida
 * @param int $duration Duração em milissegundos (padrão: 8000)
 * @return void
 */
function notifyError($message, $duration = 8000)
{
    addNotification('error', $message, $duration);
}

/**
 * Notificação de aviso (cor amarela/laranja)
 * 
 * Utilizada para alertas que não bloqueiam a operação
 * mas merecem atenção do utilizador.
 * 
 * @param string $message Mensagem a ser exibida
 * @param int $duration Duração em milissegundos (padrão: 6000)
 * @return void
 */
function notifyWarning($message, $duration = 6000)
{
    addNotification('warning', $message, $duration);
}

/**
 * Notificação informativa (cor azul)
 * 
 * Utilizada para informações gerais que não são
 * erros nem avisos, apenas informativo.
 * 
 * @param string $message Mensagem a ser exibida
 * @param int $duration Duração em milissegundos (padrão: 5000)
 * @return void
 */
function notifyInfo($message, $duration = 5000)
{
    addNotification('info', $message, $duration);
}

/**
 * Renderiza as notificações pendentes da sessão e limpa a lista
 * 
 * Esta função é chamada no header para exibir as notificações
 * armazenadas na sessão. Após renderizar, as notificações são
 * removidas da sessão para evitar duplicatas.
 * 
 * @return string HTML das notificações (script JS para exibir)
 */
function renderPendingNotifications()
{
    // Inicia sessão se necessário
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Se não há notificações, retorna string vazia
    if (empty($_SESSION['notifications'])) {
        return '';
    }

    // Recupera as notificações da sessão
    $notifications = $_SESSION['notifications'];

    // Remove as notificações da sessão (após serem renderizadas)
    unset($_SESSION['notifications']);

    // ============================================
    // Geração do Script JavaScript
    // ============================================

    $html = '<script>' . "\n";
    $html .= 'document.addEventListener("DOMContentLoaded", function() {' . "\n";

    // Loop pelas notificações e gera chamada JS para cada uma
    foreach ($notifications as $notif) {
        $html .= sprintf(
            '    if (typeof AlugaTorresNotifications !== "undefined") {
        AlugaTorresNotifications.%s("%s", %d);
    }' . "\n",
            $notif['type'],
            addslashes($notif['message']),
            $notif['duration']
        );
    }

    $html .= '});' . "\n";
    $html .= '</script>' . "\n";

    return $html;
}

/**
 * Retorna o caminho base para assets
 * 
 * Utilizado para construir URLs absolutas para recursos estáticos.
 * 
 * @return string Caminho base do projeto
 */
function getBasePath()
{
    return '/AlugaTorres/';
}

// Nota: getAdminEmail() e getSupportEmail() estão definidos em email_utils.php
