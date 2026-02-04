<?php
header('Content-Type: application/json');
require_once 'db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';
$action = $_GET['action'] ?? '';

if ($action === 'get_user_stats') {
    $stats = [];

    if ($tipo_utilizador === 'proprietario') {
        // Propriedades
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM casas WHERE proprietario_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['propriedades'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Reservas Totais
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['reservas_totais'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Avaliação Média (placeholder)
        $stats['avaliacao_media'] = 'N/A';

        // Receita Total
        $stmt = $conn->prepare("SELECT SUM(r.preco_total) as total FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ? AND r.status = 'concluida'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $receita_total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stats['receita_total'] = '€' . number_format($receita_total, 2, ',', '.');
    } else {
        // Reservas Feitas
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservas WHERE arrendatario_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['reservas_feitas'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Favoritos (placeholder)
        $stats['favoritos'] = 0;

        // Avaliações (placeholder)
        $stats['avaliacoes'] = 0;

        // Total Gastos
        $stmt = $conn->prepare("SELECT SUM(preco_total) as total FROM reservas WHERE arrendatario_id = ? AND status = 'concluida'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total_gastos = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stats['total_gastos'] = '€' . number_format($total_gastos, 2, ',', '.');
    }

    echo json_encode(['success' => true, 'stats' => $stats]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ação inválida']);
}
