<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/email_utils.php';

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

$email = trim($input['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email inválido']);
    exit;
}

// Enviar confirmação ao subscritor
$subjectUser = 'Inscrição na Newsletter - AlugaTorres';
$bodyUser = "<p>Olá,</p><p>Obrigado por subscrever a newsletter do AlugaTorres. Em breve receberá novidades e ofertas.</p><p>Obrigado,<br>Equipa AlugaTorres</p>";
$userEmailResult = sendEmail($email, $subjectUser, $bodyUser);

// Notificar admin
$adminEmail = getAdminEmail() ?: 'suportealugatorres@gmail.com';
$subjectAdmin = 'Nova subscrição na newsletter';
$bodyAdmin = "<p>Novo subscritor: " . htmlspecialchars($email) . "</p>";
$adminEmailResult = sendEmail($adminEmail, $subjectAdmin, $bodyAdmin);

echo json_encode(['status' => 'success', 'message' => 'Subscrição registada', 'emails' => ['user' => $userEmailResult, 'admin' => $adminEmailResult]]);
