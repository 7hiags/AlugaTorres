<?php
// Utilitários de email para todo o projeto
// Suporta both native mail() e SMTP
// Configurações carregadas do ficheiro .env para maior segurança

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/class.smtp.php';

/**
 * Carregar variáveis de ambiente do ficheiro .env
 */
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Carregar variáveis de ambiente
loadEnv(__DIR__ . '/.env');

/**
 * Obter email de suporte (recebe mensagens do formulário de contactos)
 */
function getSupportEmail()
{
    $config = getEmailConfig();
    if (!empty($config['support_email'])) {
        return $config['support_email'];
    }
    return 'suportealugatorres@gmail.com';
}

/**
 * Obter email para newsletters (notificações de subscrições)
 */
function getNewsletterEmail()
{
    $config = getEmailConfig();
    if (!empty($config['newsletter_email'])) {
        return $config['newsletter_email'];
    }
    return 'alugatorrespt@gmail.com';
}

/**
 * Obter email do admin (para notificações internas do sistema)
 */
function getAdminEmail()
{
    if (file_exists(__DIR__ . '/email_config.php')) {
        $c = include __DIR__ . '/email_config.php';
        return $c['admin_email'] ?? null;
    }
    return null;
}

/**
 * Log de email enviado
 */
function logEmail($to, $subject, $body, $status = 'sent')
{
    $logFile = __DIR__ . '/email_log.txt';
    $entry  = "[" . date('Y-m-d H:i:s') . "] STATUS=$status TO=" . ($to ?: 'NULL') . " SUBJECT=" . $subject . "\n";
    $entry .= $body . "\n\n";
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Carregar configurações de email
 * Primeiro tenta ler do .env, depois do email_config.php (retrocompatibilidade)
 */
function getEmailConfig()
{
    $default = [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
        'from_email' => 'no-reply@alugatorres.local',
        'from_name' => 'AlugaTorres',
        'support_email' => 'suportealugatorres@gmail.com',
        'newsletter_email' => 'alugatorrespt@gmail.com',
        'admin_email' => 'admin@alugatorres.pt',
        'mailer' => 'mail'
    ];

    // Primeiro: tentar carregar de variáveis de ambiente (.env)
    $envConfig = [];
    if (!empty(getenv('SMTP_HOST'))) {
        $envConfig = [
            'smtp_host' => getenv('SMTP_HOST'),
            'smtp_port' => getenv('SMTP_PORT') ?: 465,
            'smtp_user' => getenv('SMTP_USER'),
            'smtp_pass' => getenv('SMTP_PASS'),
            'from_email' => getenv('FROM_EMAIL'),
            'from_name' => getenv('FROM_NAME') ?: 'AlugaTorres',
            'support_email' => getenv('SUPPORT_EMAIL'),
            'newsletter_email' => getenv('NEWSLETTER_EMAIL'),
            'admin_email' => getenv('ADMIN_EMAIL'),
            'mailer' => getenv('MAILER') ?: 'smtp'
        ];
    }

    // Segundo: retrocompatibilidade com email_config.php
    if (file_exists(__DIR__ . '/email_config.php')) {
        $fileConfig = include __DIR__ . '/email_config.php';
        return array_merge($default, $envConfig, $fileConfig);
    }

    // Retornar apenas configuração do .env ou defaults
    return array_merge($default, $envConfig);
}

/**
 * Enviar email via SMTP
 */
function sendEmailSMTP($to, $subject, $body, $fromEmail, $fromName)
{
    $config = getEmailConfig();

    // Verificar se SMTP está configurado
    if (empty($config['smtp_host']) || empty($config['smtp_user']) || empty($config['smtp_pass'])) {
        return ['ok' => false, 'status' => 'smtp-not-configured'];
    }

    try {
        $smtp = new SMTP(
            $config['smtp_host'],
            $config['smtp_port'],
            $config['smtp_user'],
            $config['smtp_pass'],
            $fromEmail
        );

        // Cabeçalhos
        $headers = "From: $fromName <$fromEmail>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";

        $result = $smtp->sendMail($to, $subject, $body, $headers);
        $smtp->close();

        if ($result) {
            return ['ok' => true, 'status' => 'sent-smtp'];
        } else {
            $errors = $smtp->getErrors();
            return ['ok' => false, 'status' => 'failed-smtp', 'errors' => $errors];
        }
    } catch (\Exception $e) {
        return ['ok' => false, 'status' => 'error', 'error' => $e->getMessage()];
    }
}

/**
 * Enviar email (função principal)
 * Usa SMTP se configurado, caso contrário usa mail() nativo
 */
function sendEmail($to, $subject, $body, $from = null)
{
    if (!$to) return ['ok' => false, 'status' => 'no-recipient'];

    $config = getEmailConfig();

    $fromEmail = $from ?: ($config['from_email'] ?? 'no-reply@alugatorres.local');
    $fromName = $config['from_name'] ?? 'AlugaTorres';

    // Verificar se deve usar SMTP
    if (($config['mailer'] ?? 'mail') === 'smtp' && !empty($config['smtp_host'])) {
        // Usar SMTP
        $result = sendEmailSMTP($to, $subject, $body, $fromEmail, $fromName);

        // Log do resultado
        $status = $result['ok'] ? 'sent-smtp' : 'failed-smtp';
        logEmail($to, $subject, $body, $status);

        return $result;
    }

    // Usar mail() nativo do PHP
    $headers  = 'From: ' . $fromName . ' <' . $fromEmail . '>' . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

    $sent = false;
    try {
        $sent = (bool) @mail($to, $subject, $body, $headers);
    } catch (\Exception $e) {
        $sent = false;
    }

    $status = $sent ? 'sent-mail' : 'failed-mail';
    logEmail($to, $subject, $body, $status);

    return ['ok' => $sent, 'status' => $status];
}

/**
 * Enviar email de contacto para o suporte
 * Esta função é específica para o formulário de contactos
 */
function sendContactToSupport($nome, $email, $assunto, $mensagem)
{
    $supportEmail = getSupportEmail();

    $subject = 'Novo contacto recebido - ' . $assunto;
    $body = "<p>Foi recebida uma nova mensagem de contacto:</p>" .
        "<p><strong>Nome:</strong> " . htmlspecialchars($nome) . "<br>" .
        "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>" .
        "<strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>" .
        "<p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>";

    return sendEmail($supportEmail, $subject, $body);
}
