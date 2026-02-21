<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/email_utils.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido']);
    exit;
}

// Aceitar JSON ou form-data
$raw = file_get_contents('php://input');
$input = $_POST;
if (empty($input) && !empty($raw)) {
    $d = json_decode($raw, true);
    if (is_array($d)) $input = $d;
}

// Obter email - aceitar múltiplos formatos
$email = '';
if (isset($input['email'])) {
    $email = trim($input['email']);
} elseif (isset($input['Email'])) {
    $email = trim($input['Email']);
}

// Validar email
if (empty($email)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email é obrigatório']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email inválido: ' . $email]);
    exit;
}

try {
    // Conectar à base de dados usando mysqli
    $mysqli = new mysqli($host, $user, $password, $dbname);

    if ($mysqli->connect_error) {
        throw new \Exception('Erro na conexão: ' . $mysqli->connect_error);
    }

    // Criar tabela se não existir
    $sqlCreateTable = "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        subscribed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        active TINYINT(1) NOT NULL DEFAULT 1,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!$mysqli->query($sqlCreateTable)) {
        error_log("Erro ao criar tabela: " . $mysqli->error);
    }

    // Verificar se o email já existe
    $checkStmt = $mysqli->prepare("SELECT id, active FROM newsletter_subscribers WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    $emailExists = false;
    $isActive = false;

    if ($row = $checkResult->fetch_assoc()) {
        $emailExists = true;
        $isActive = ($row['active'] == 1);
    }
    $checkStmt->close();

    if ($emailExists && $isActive) {
        // Email já está subscrito e ativo
        $mysqli->close();
        echo json_encode(['status' => 'info', 'message' => 'Este email já está subscrito na newsletter!']);
        exit;
    } elseif ($emailExists && !$isActive) {
        // Email foi subscrito mas desativado - reativar
        $updateStmt = $mysqli->prepare("UPDATE newsletter_subscribers SET active = 1, subscribed_at = CURRENT_TIMESTAMP WHERE email = ?");
        $updateStmt->bind_param("s", $email);
        $updateStmt->execute();
        $updateStmt->close();

        // Enviar confirmação ao subscritor
        $subjectUser = 'Inscrição na Newsletter - AlugaTorres';
        $bodyUser = "<p>Olá,</p><p>A sua subscrição da newsletter do AlugaTorres foi reativada. Em breve receberá novidades e ofertas.</p><p>Obrigado,<br>Equipa AlugaTorres</p>";
        sendEmail($email, $subjectUser, $bodyUser);

        $mysqli->close();
        echo json_encode(['status' => 'success', 'message' => 'Subscrição reativada com sucesso!']);
        exit;
    }

    // Inserir novo subscritor
    $stmt = $mysqli->prepare("INSERT INTO newsletter_subscribers (email, active) VALUES (?, 1)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();

    $mysqli->close();

    // Enviar confirmação ao subscritor
    $subjectUser = 'Inscrição na Newsletter - AlugaTorres';
    $bodyUser = "<p>Olá,</p><p>Obrigado por subscrever a newsletter do AlugaTorres. Em breve receberá novidades e ofertas.</p><p>Obrigado,<br>Equipa AlugaTorres</p>";
    sendEmail($email, $subjectUser, $bodyUser);

    // Notificar admin (newsletter)
    $adminEmail = getNewsletterEmail() ?: 'alugatorrespt@gmail.com';
    $subjectAdmin = 'Nova subscrição na newsletter';
    $bodyAdmin = "<p>Novo subscritor: " . htmlspecialchars($email) . "</p>";
    sendEmail($adminEmail, $subjectAdmin, $bodyAdmin);

    echo json_encode(['status' => 'success', 'message' => 'Obrigado! Subscreveu com sucesso a newsletter.']);
} catch (\Exception $e) {
    error_log("Erro ao guardar subscritor: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro ao processar subscrição. Tente novamente.']);
}
