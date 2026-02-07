<?php
session_start();
require_once 'backend/notifications_helper.php';

// Adicionar uma notificação de teste
notifySuccess('Sistema de notificações funcionando! Esta é uma mensagem de teste.');

// Redirecionar para a página inicial para ver a notificação
header("Location: index.php");
exit;
