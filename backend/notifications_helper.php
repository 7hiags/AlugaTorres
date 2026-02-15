<?php

/**
 * Helper de Notificações Toast para AlugaTorres
 * 
 * Este arquivo contém funções para gerenciar notificações toast
 * que serão exibidas ao usuário após operações (sucesso, erro, aviso, etc.)
 */

/**
 * Adiciona uma notificação à sessão
 * 
 * @param string $type Tipo da notificação: 'success', 'error', 'warning', 'info'
 * @param string $message Mensagem da notificação
 * @param int $duration Duração em milissegundos (padrão: 5000)
 */
function addNotification($type, $message, $duration = 5000)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['notifications'])) {
        $_SESSION['notifications'] = [];
    }

    $_SESSION['notifications'][] = [
        'type' => $type,
        'message' => $message,
        'duration' => $duration,
        'id' => uniqid('notif_')
    ];
}

/**
 * Notificação de sucesso
 */
function notifySuccess($message, $duration = 5000)
{
    addNotification('success', $message, $duration);
}

/**
 * Notificação de erro
 */
function notifyError($message, $duration = 8000)
{
    addNotification('error', $message, $duration);
}

/**
 * Notificação de aviso
 */
function notifyWarning($message, $duration = 6000)
{
    addNotification('warning', $message, $duration);
}

/**
 * Notificação informativa
 */
function notifyInfo($message, $duration = 5000)
{
    addNotification('info', $message, $duration);
}

/**
 * Renderiza as notificações pendentes da sessão e limpa a lista
 * 
 * @return string HTML das notificações
 */
function renderPendingNotifications()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['notifications'])) {
        return '';
    }

    $notifications = $_SESSION['notifications'];
    unset($_SESSION['notifications']);

    $html = '<script>' . "\n";
    $html .= 'document.addEventListener("DOMContentLoaded", function() {' . "\n";

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
 */
function getBasePath()
{
    return '/AlugaTorres/';
}

// Nota: getAdminEmail() está definido em email_utils.php
