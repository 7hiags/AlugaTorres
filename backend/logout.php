<?php
session_start();
require_once 'notifications_helper.php';

// Adicionar notificação de sucesso antes de destruir a sessão
notifySuccess('Sessão terminada com sucesso! Até breve!');

// Guardar as notificações em uma variável temporária
$notifications = $_SESSION['notifications'] ?? [];

// Destruir a sessão
session_destroy();

// Reiniciar sessão apenas para as notificações
session_start();
$_SESSION['notifications'] = $notifications;

header("Location: ../index.php");
exit;
