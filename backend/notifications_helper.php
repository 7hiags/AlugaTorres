<?php

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

// Notificação de sucesso (cor verde) Utilizada para operações que foram concluídas com êxito.
 
function notifySuccess($message, $duration = 5000)
{
    addNotification('success', $message, $duration);
}

// Notificação de erro (cor vermelha) Utilizada quando ocorre um erro na operação. Duração padrão maior (8000ms) para garantir que o utilizador veja o erro.
 
function notifyError($message, $duration = 8000)
{
    addNotification('error', $message, $duration);
}

// Notificação de aviso (cor amarela/laranja) Utilizada para alertas que não bloqueiam a operação mas merecem atenção do utilizador.
function notifyWarning($message, $duration = 6000)
{
    addNotification('warning', $message, $duration);
}

// Notificação informativa (cor azul) Utilizada para informações gerais que não são erros nem avisos, apenas informativo.
function notifyInfo($message, $duration = 5000)
{
    addNotification('info', $message, $duration);
}

// Função para renderizar as notificações armazenadas na sessão como um script JavaScript
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

    // Geração do Script JavaScript
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

// Função para obter o caminho base do projeto
function getBasePath()
{
    return '/AlugaTorres/';
}
