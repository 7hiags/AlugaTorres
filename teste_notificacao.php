<?php
session_start();
require_once 'backend/notifications_helper.php';

// Adicionar uma notificação de teste
notifySuccess('Sistema de notificações funcionando! Esta é uma mensagem de teste.');
notifyInfo('Esta é uma notificação informativa.');
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Notificações</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/AlugaTorres/style/style.css">
    <script src="/AlugaTorres/backend/notifications.js"></script>
</head>

<body>
    <h1>Teste de Notificações</h1>
    <p>Se você vir notificações toast no canto superior direito, o sistema está funcionando!</p>

    <button onclick="AlugaTorresNotifications.success('Notificação de teste manual!')">
        Testar Notificação Manual
    </button>

    <?php
    // Renderizar notificações da sessão
    echo renderPendingNotifications();
    ?>
</body>

</html>