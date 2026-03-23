<?php
require_once __DIR__ . '/../../root/init.php';

// Adicionar notificação de sucesso antes de destruir a sessão
notifySuccess('Sessão terminada com sucesso! Até breve!');

// Guardar as notificações em uma variável temporária
$notifications = $_SESSION['notifications'] ?? [];

// Destruir a sessão
session_destroy();

// Reiniciar sessão apenas para as notificações
session_start();
$_SESSION['notifications'] = $notifications;

header("Location: ../../root/index.php");
exit;
