<?php

function EmailEstilizado($title, $messageHtml, $highlightCode = null, $userName = '')
{
    $logoUrl = 'https://alugatorres.pt/AlugaTorres/assets/style/img/Logo_AlugaTorres_semfundo.png';

    $personalization = !empty($userName) ? "Olá <strong>" . htmlspecialchars($userName) . "</strong>," : 'Olá,';

    $codeBlock = '';
    if ($highlightCode !== null) {
        $codeBlock = '
        <div style="background: white; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; border: 2px solid #038e01;">
            <span style="font-size: 32px; font-weight: bold; color: #038e01; letter-spacing: 8px;">' . htmlspecialchars($highlightCode) . '</span>
        </div>';
    }

    return '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <div style="background: linear-gradient(to right, #038e01, #00d85e); padding: 20px; border-radius: 10px 10px 0 0; text-align: center;">
            <img src="' . $logoUrl . '" alt="AlugaTorres" style="max-height: 50px; max-width: 200px;">
            <h1 style="color: white; margin: 10px 0 0 0; font-size: 24px;">AlugaTorres</h1>
        </div>
        <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #dee2e6;">
            <h2 style="color: #333; margin-top: 0; border-bottom: 2px solid #038e01; padding-bottom: 10px;">' . htmlspecialchars($title) . '</h2>
            <p style="color: #666; line-height: 1.6;">' . $personalization . '</p>
            ' . $codeBlock . '
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #038e01;">
                ' . $messageHtml . '
            </div>
            <hr style="border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;">
            <p style="color: #999; font-size: 14px; line-height: 1.6; text-align: center;">
                Se não reconhece esta mensagem, ignore-a com segurança.<br>
                © ' . date('Y') . ' AlugaTorres - Plataforma de Arrendamento em Torres Novas
            </p>
        </div>
    </div>';
}

if (session_status() === PHP_SESSION_NONE) session_start();

/** 
 * SMTP Class BREVO integrada - port 587 STARTTLS
 * Sem ficheiro separado
 */
class SMTP
{
    private $smtp_conn;
    private $errors = [];
    private $debug = true;
    public function __construct($host, $port, $username, $password, $fromEmail)
    {
        $this->connect($host, $port);
        $this->login($username, $password, $fromEmail);
    }
    private function connect($host, $port)
    {
        $this->smtp_conn = fsockopen($host, $port, $errno, $errstr, 30);
        if (!$this->smtp_conn) {
            $this->errors[] = "Falha conexão: $errstr ($errno)";
            return false;
        }
        $greeting = $this->readResponse();
        if (substr($greeting, 0, 3) !== '220') {
            $this->errors[] = "Sem greeting: $greeting";
            return false;
        }
        $resp = $this->sendCmd('EHLO alugatorres.local');
        if ($resp !== '250') {
            $this->errors[] = "EHLO failed: $resp";
            return false;
        }
        $resp = $this->sendCmd('STARTTLS');
        if (strpos($resp, '220') === 0) {
            stream_context_set_option(stream_context_get_default(), 'ssl', 'verify_peer', false);
            stream_context_set_option(stream_context_get_default(), 'ssl', 'verify_peer_name', false);
            if (!stream_socket_enable_crypto($this->smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->errors[] = 'TLS upgrade failed';
                return false;
            }
            $resp = $this->sendCmd('EHLO alugatorres.local');
            if ($resp !== '250') {
                $this->errors[] = "EHLO post-TLS: $resp";
                return false;
            }
        }
        $this->debugLog("Connected OK");
        return true;
    }
    private function login($username, $password, $fromEmail)
    {
        $resp = $this->sendCmd('AUTH LOGIN');
        if ($resp !== '334') {
            $this->errors[] = "AUTH LOGIN failed: $resp";
            return false;
        }
        $resp = $this->sendCmd(base64_encode($username));
        if ($resp !== '334') {
            $this->errors[] = "Username auth failed: $resp";
            return false;
        }
        $resp = $this->sendCmd(base64_encode($password));
        if ($resp !== '235') {
            $this->errors[] = "Password auth failed: $resp";
            return false;
        }
        $resp = $this->sendCmd("MAIL FROM:<$fromEmail>");
        if ($resp !== '250') {
            $this->errors[] = "MAIL FROM failed: $resp";
            return false;
        }
        $this->debugLog("Login OK");
        return true;
    }
    private function sendCmd($cmd)
    {
        if (!$this->smtp_conn) {
            $this->errors[] = 'No connection';
            return false;
        }
        $this->debugLog("SEND: $cmd");
        fputs($this->smtp_conn, $cmd . "\r\n");
        $code = $this->readResponse();
        $this->debugLog("RECV: $code");
        return $code;
    }
    private function readResponse()
    {
        $lines = '';
        while (($line = fgets($this->smtp_conn, 512)) !== false) {
            $lines .= $line;
            if (substr(rtrim($line), 3, 1) !== '-') break;
        }
        return substr(trim($lines), 0, 3);
    }
    public function sendMail($to, $subject, $body, $headers)
    {
        $resp = $this->sendCmd("RCPT TO:<$to>");
        if ($resp !== '250') {
            $this->errors[] = "RCPT failed: $resp";
            return false;
        }
        $resp = $this->sendCmd('DATA');
        if ($resp !== '354') {
            $this->errors[] = "DATA failed: $resp";
            return false;
        }
        $message = "Subject: $subject\r\n$headers\r\n\r\n$body\r\n.\r\n";
        fwrite($this->smtp_conn, $message);
        $resp = $this->readResponse();
        if ($resp !== '250') {
            $this->errors[] = "Send failed: $resp";
            return false;
        }
        $this->debugLog("Mail sent OK");
        return true;
    }
    private function debugLog($msg)
    {
        if ($this->debug) error_log("[SMTP] $msg");
    }
    public function close()
    {
        if ($this->smtp_conn) {
            $this->sendCmd('QUIT');
            fclose($this->smtp_conn);
        }
    }
    public function getErrors()
    {
        return $this->errors;
    }
}


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
loadEnv(__DIR__ . '.env');

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
    if (file_exists(__DIR__ . '/../email_config.php')) {
        $c = include __DIR__ . '/../email_config.php';
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
    if (file_exists(__DIR__ . '/../email_config.php')) {
        $fileConfig = include __DIR__ . '/../email_config.php';
        return array_merge($default, $envConfig, $fileConfig);
    }

    // Retornar apenas configuração do .env ou defaults
    // HARDCODED FALLBACK BREVO SMTP (creds dos logs - ATUALIZAR PASS completa do Brevo dashboard)
    if (empty($envConfig['smtp_host']) || empty($envConfig['smtp_user']) || empty($envConfig['smtp_pass'])) {
        error_log("[EMAIL] Using hardcoded Brevo fallback");
        $envConfig = array_merge($envConfig, [
            'smtp_host' => 'smtp-relay.brevo.com',
            'smtp_port' => 587,
            'smtp_user' => 'a4f557001@smtp-brevo.com',
            'smtp_pass' => 'xsmtpsib-44afbf6178161cfd7e111965307c897e90ccc17c72eb4a64688399d12eb4624a-9OLnPOQXwu7HE1eL', // Copiar full de app.brevo.com SMTP relay key
            'from_email' => 'suportealugatorres@gmail.com',
            'from_name' => 'AlugaTorres',
            'support_email' => 'suportealugatorres@gmail.com',
            'newsletter_email' => 'alugatorrespt@gmail.com',
            'admin_email' => 'admin@alugatorres.pt'
        ]);
    }
    return array_merge($default, $envConfig);
}

// Enviar email via SMTP
function sendEmailSMTP($to, $subject, $body, $fromEmail, $fromName)
{
    $config = getEmailConfig();

    // Verificar se SMTP está configurado
    if (empty($config['smtp_host']) || empty($config['smtp_user']) || empty($config['smtp_pass'])) {
        return ['ok' => false, 'status' => 'smtp-not-configured'];
    }

    try {
        // Configurar SMTP
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
            error_log("[SMTP FAIL] Errors: " . json_encode($errors));
            return ['ok' => false, 'status' => 'failed-smtp', 'errors' => $errors];
        }
    } catch (\Exception $e) {
        return ['ok' => false, 'status' => 'error', 'error' => $e->getMessage()];
    }
}

//Enviar email (função principal) Usa SMTP se configurado, caso contrário usa mail() nativo
function sendEmail($to, $subject, $body, $from = null)
{
    // Verificar se há destinatário
    if (!$to) return ['ok' => false, 'status' => 'no-recipient'];

    $config = getEmailConfig();

    // Determinar email e nome do remetente
    $fromEmail = $from ?: ($config['from_email'] ?? 'no-reply@alugatorres.local');
    $fromName = $config['from_name'] ?? 'AlugaTorres';

    // FORCE SMTP (mail() unreliable on XAMPP)
    if (!empty($config['smtp_host']) && !empty($config['smtp_user']) && !empty($config['smtp_pass'])) {
        logEmail('SYSTEM', 'SMTP_OK', 'Using SMTP: ' . $config['smtp_host'], 'info');
        $result = sendEmailSMTP($to, $subject, $body, $fromEmail, $fromName);
        $status = $result['ok'] ? 'sent-smtp' : 'failed-smtp';
        logEmail($to, $subject, substr($body, 0, 200) . '...', $status, $result['errors'] ?? null);
        return $result;
    }

    // Usar mail() nativo do PHP
    $headers  = 'From: ' . $fromName . ' <' . $fromEmail . '>' . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

    $sent = false;
    try {
        // Enviar email usando mail() e capturar o resultado
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

    $messageHtml = '
        <p>Foi recebida uma nova mensagem de contacto:</p>
        <ul style="color: #555;">
            <li><strong>Nome:</strong> ' . htmlspecialchars($nome) . '</li>
            <li><strong>Email:</strong> ' . htmlspecialchars($email) . '</li>
            <li><strong>Assunto:</strong> ' . htmlspecialchars($assunto) . '</li>
        </ul>
        <p><strong>Mensagem:</strong><br>' . nl2br(htmlspecialchars($mensagem)) . '</p>
        <p>Aceda ao <a href="https://alugatorres.pt/AlugaTorres/admin/contactos.php" style="color: #038e01;">painel admin</a> para responder.</p>';

    $body = EmailEstilizado('Novo Contacto Recebido', $messageHtml, null, $nome);

    return sendEmail($supportEmail, $subject, $body);
}
