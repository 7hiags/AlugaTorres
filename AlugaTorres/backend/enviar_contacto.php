<?php
// bootstrap inicial – inicia sessão, carrega db e notificações
require_once __DIR__ . '/../root/init.php';
header('Content-Type: application/json; charset=utf-8');

// email_utils não é carregado por init, precisamos dele para enviar correio
require_once __DIR__ . '/email_defin/email_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido']);
    exit;
}

try {
    // Conectar ao banco
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new \PDO($dsn, $user, $password, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    // (removido: logs de debug)

    // Aceitar tanto form-data (popula $_POST) quanto JSON no body
    $rawBody = file_get_contents('php://input');
    $input = $_POST;
    if (empty($input) && !empty($rawBody)) {
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }

    // Verificação explícita de campos vazios usando if
    if (!isset($input['nome']) || trim((string)$input['nome']) === '') {
        throw new \Exception('O campo nome é obrigatório');
    }
    if (!isset($input['email']) || trim((string)$input['email']) === '') {
        throw new \Exception('O campo email é obrigatório');
    }
    if (!isset($input['assunto']) || trim((string)$input['assunto']) === '') {
        throw new \Exception('O campo assunto é obrigatório');
    }
    if (!isset($input['mensagem']) || trim((string)$input['mensagem']) === '') {
        throw new \Exception('O campo mensagem é obrigatório');
    }

    // Captura e sanitiza (prioriza $input)
    $nome = trim((string)$input['nome']);
    $email = trim((string)$input['email']);
    $assunto = trim((string)$input['assunto']);
    $mensagem = trim((string)$input['mensagem']);
    $utilizador_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new \Exception('Formato de email inválido');
    }

    // Inserir no banco (nome da tabela: mensagens_contactos)
    $stmt = $pdo->prepare("INSERT INTO mensagens_contactos 
        (utilizador_id, nome, email, assunto, mensagem, data_envio, lida, ip_address, user_agent) 
        VALUES (:utilizador_id, :nome, :email, :assunto, :mensagem, NOW(), 0, :ip, :user_agent)");

    $success = $stmt->execute([
        ':utilizador_id' => $utilizador_id,
        ':nome' => $nome,
        ':email' => $email,
        ':assunto' => $assunto,
        ':mensagem' => $mensagem,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    ]);

    if ($success) {
        // Enviar email de confirmação ao remetente
        $subjectUser = 'Recebemos a sua mensagem - AlugaTorres';
        $messageUserHtml = '
            <p>Recebemos a sua mensagem com o assunto <strong>' . htmlspecialchars($assunto) . '</strong>.</p>
            <p>Vamos analisar e responder o mais rápido possível (normalmente em 24h).</p>
            <p><strong>Resumo enviado:</strong><br>' . nl2br(htmlspecialchars($mensagem)) . '</p>
            <p>Pode acompanhar pelo <a href="https://alugatorres.pt/AlugaTorres/contactos.php" style="color: #038e01;">formulário de contactos</a>.</p>';
        $bodyUser = EmailEstilizado('Mensagem Recebida', $messageUserHtml, null, $nome);

        $userEmailResult = sendEmail($email, $subjectUser, $bodyUser);

        // Enviar email de aviso para o suporte (SEM admin)
        $supportEmail = getSupportEmail();
        $subjectSupport = 'Novo contacto recebido';
        $messageSupportHtml = '
            <p>Nova mensagem de contacto recebida via formulário:</p>
            <ul style="color: #555; padding-left: 20px;">
                <li><strong>Nome:</strong> ' . htmlspecialchars($nome) . '</li>
                <li><strong>Email:</strong> ' . htmlspecialchars($email) . '</li>
                <li><strong>Assunto:</strong> ' . htmlspecialchars($assunto) . '</li>
            </ul>
            <p><strong>Mensagem completa:</strong><br>' . nl2br(htmlspecialchars($mensagem)) . '</p>
            <p><a href="https://alugatorres.pt/AlugaTorres/admin/contactos.php" style="background: #038e01; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ver Painel Admin</a></p>';
        $bodySupport = EmailEstilizado('Novo Contacto Recebido', $messageSupportHtml);

        $supportEmailResult = sendEmail($supportEmail, $subjectSupport, $bodySupport);

        echo json_encode(['status' => 'success', 'message' => 'Mensagem enviada com sucesso!', 'emails' => ['user' => $userEmailResult, 'support' => $supportEmailResult]]);
    } else {
        throw new \Exception('Falha ao inserir no banco de dados');
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
