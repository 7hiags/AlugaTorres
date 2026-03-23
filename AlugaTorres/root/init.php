<?php
// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// constante de URL base
if (!defined('BASE_URL')) {
    define('BASE_URL', '/AlugaTorres/');
}

// helpers úteis
require_once __DIR__ . '/../backend/notifications_helper.php';
require_once __DIR__ . '/../backend/email_defin/email_utils.php';

// ligação à base de dados (disponibiliza $conn)
require_once __DIR__ . '/../backend/db.php';