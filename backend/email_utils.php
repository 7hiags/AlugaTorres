<?php
// Utilitários simples de email para todo o projeto (usa mail() do PHP)
if (session_status() === PHP_SESSION_NONE) session_start();

function logEmail($to, $subject, $body, $status = 'sent')
{
    $logFile = __DIR__ . '/email_log.txt';
    $entry  = "[" . date('Y-m-d H:i:s') . "] STATUS=$status TO=" . ($to ?: 'NULL') . " SUBJECT=" . $subject . "\n";
    $entry .= $body . "\n\n";
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

function sendEmail($to, $subject, $body, $from = null)
{
    if (!$to) return ['ok' => false, 'status' => 'no-recipient'];

    $config = [];
    if (file_exists(__DIR__ . '/email_config.php')) {
        $config = include __DIR__ . '/email_config.php';
    }

    $fromEmail = $from ?: ($config['from_email'] ?? 'no-reply@alugatorres.local');
    $fromName = $config['from_name'] ?? 'AlugaTorres';

    $headers  = 'From: ' . $fromName . ' <' . $fromEmail . '>' . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

    $sent = false;
    try {
        $sent = (bool) @mail($to, $subject, $body, $headers);
    } catch (Exception $e) {
        $sent = false;
    }

    $status = $sent ? 'sent-mail' : 'failed-mail';
    logEmail($to, $subject, $body, $status);

    return ['ok' => $sent, 'status' => $status];
}

// helper to get admin email from config
function getAdminEmail()
{
    if (file_exists(__DIR__ . '/email_config.php')) {
        $c = include __DIR__ . '/email_config.php';
        return $c['admin_email'] ?? null;
    }
    return null;
}
