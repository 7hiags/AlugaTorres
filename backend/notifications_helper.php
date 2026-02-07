<?php

/**
 * Helper para Sistema de Notificações Toast do AlugaTorres
 * Inclua este arquivo no header.php ou em páginas específicas
 */

/**
 * Retorna o HTML/JS necessário para inicializar o sistema de notificações
 */
function getNotificationsSystem()
{
    $basePath = '/AlugaTorres'; // Ajuste conforme necessário

    return '
    <!-- Sistema de Notificações Toast -->
    <link rel="stylesheet" href="' . $basePath . '/style/style.css">
    <script src="' . $basePath . '/backend/notifications.js"></script>
    ';
}

/**
 * Gera uma notificação PHP que será convertida para JavaScript
 * @param string $message Mensagem da notificação
 * @param string $type Tipo: success, error, warning, info
 * @param int $duration Duração em ms
 */
function setNotification($message, $type = 'info', $duration = 5000)
{
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['notifications'])) {
        $_SESSION['notifications'] = [];
    }

    $_SESSION['notifications'][] = [
        'message' => $message,
        'type' => $type,
        'duration' => $duration
    ];
}

/**
 * Renderiza as notificações pendentes da sessão
 */
function renderPendingNotifications()
{
    if (!isset($_SESSION)) {
        session_start();
    }

    if (empty($_SESSION['notifications'])) {
        return '';
    }

    $script = '<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof AlugaTorresNotifications !== "undefined") {
    ';

    foreach ($_SESSION['notifications'] as $notification) {
        $message = addslashes($notification['message']);
        $type = $notification['type'];
        $duration = $notification['duration'];

        $script .= "AlugaTorresNotifications.show('{$message}', '{$type}', {$duration});\n";
    }

    $script .= '
        }
    });
    </script>';

    // Limpar notificações após renderizar
    $_SESSION['notifications'] = [];

    return $script;
}

/**
 * Wrapper para mostrar notificação de sucesso
 */
function notifySuccess($message, $duration = 5000)
{
    setNotification($message, 'success', $duration);
}

/**
 * Wrapper para mostrar notificação de erro
 */
function notifyError($message, $duration = 5000)
{
    setNotification($message, 'error', $duration);
}

/**
 * Wrapper para mostrar notificação de aviso
 */
function notifyWarning($message, $duration = 5000)
{
    setNotification($message, 'warning', $duration);
}

/**
 * Wrapper para mostrar notificação de informação
 */
function notifyInfo($message, $duration = 5000)
{
    setNotification($message, 'info', $duration);
}
