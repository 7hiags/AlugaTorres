<?php

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../root/init.php';
require_once __DIR__ . '/../backend/email_defin/email_utils.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Utilizador não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

// Obter dados da requisição
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true) ?? [];

// Obter ação da requisição
$action = $input['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '';

// Routing
switch ($action) {
    case 'create':
        createAvaliacao();
        break;
    case 'list':
        listAvaliacoes();
        break;
    case 'update':
        updateAvaliacao();
        break;
    case 'delete':
        deleteAvaliacao();
        break;
    case 'respond':
        respondAvaliacao();
        break;
    case 'check':
        checkCanAvaliar();
        break;
    case 'my':
        myAvaliacoes();
        break;
    case 'stats':
        getStats();
        break;
    default:
        echo json_encode(['error' => 'Ação inválida']);
        break;
}

// Criar nova avaliação
function createAvaliacao()
{
    global $conn, $user_id, $user_type;
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['error' => 'Dados inválidos']);
        return;
    }

    // Apenas arrendatários podem avaliar
    if ($user_type !== 'arrendatario') {
        echo json_encode(['error' => 'Apenas arrendatários podem avaliar propriedades']);
        return;
    }

    $casa_id = $data['casa_id'] ?? null;
    $rating = $data['rating'] ?? null;
    $comentario = $data['comentario'] ?? '';

    // Validações
    if (!$casa_id || !$rating) {
        echo json_encode(['error' => 'Casa e rating são obrigatórios']);
        return;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['error' => 'Rating deve ser entre 1 e 5']);
        return;
    }

    // Verificar se já avaliou esta casa (se já avaliou, pode atualizar)
    $stmt = $conn->prepare("
        SELECT id FROM avaliacoes 
        WHERE casa_id = ? AND arrendatario_id = ?
    ");
    $stmt->bind_param("ii", $casa_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['error' => 'Já avaliou esta casa. Use a ação de atualizar.']);
        return;
    }

    // Criar avaliação
    $stmt = $conn->prepare("
        INSERT INTO avaliacoes (casa_id, arrendatario_id, rating, comentario)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("iiis", $casa_id, $user_id, $rating, $comentario);

    if ($stmt->execute()) {
        $avaliacao_id = $conn->insert_id;

        // Enviar email ao proprietário
        enviarEmailProprietarioNovaAvaliacao($casa_id, $rating);

        // Atualizar média na casa
        atualizarMediaAvaliacao($casa_id);

        echo json_encode([
            'success' => true,
            'message' => 'Avaliação criada com sucesso',
            'avaliacao_id' => $avaliacao_id
        ]);
    } else {
        echo json_encode(['error' => 'Erro ao criar avaliação']);
    }
}

// Listar avaliações de uma casa
function listAvaliacoes()
{
    global $conn, $user_id, $user_type;

    $casa_id = $_GET['casa_id'] ?? null;
    $apenas_ativas = $_GET['apenas_ativas'] ?? true;
    $min_rating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;
    $sort = $_GET['sort'] ?? 'recentes'; // recentes, antigas, melhor, pior

    if (!$casa_id) {
        echo json_encode(['error' => 'ID da casa é obrigatório']);
        return;
    }

    $query = "
        SELECT a.*, u.utilizador as arrendatario_nome, u.foto_perfil
        FROM avaliacoes a
        JOIN utilizadores u ON a.arrendatario_id = u.id
        WHERE a.casa_id = ?
    ";

    if ($apenas_ativas) {
        $query .= " AND a.ativo = 1";
    }

    // Filtro por rating mínimo
    if ($min_rating > 0) {
        $query .= " AND a.rating >= " . $min_rating;
    }

    // Ordenação
    switch ($sort) {
        case 'antigas':
            $query .= " ORDER BY a.data_criacao ASC";
            break;
        case 'melhor':
            $query .= " ORDER BY a.rating DESC, a.data_criacao DESC";
            break;
        case 'pior':
            $query .= " ORDER BY a.rating ASC, a.data_criacao DESC";
            break;
        case 'recentes':
        default:
            $query .= " ORDER BY a.data_criacao DESC";
            break;
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $casa_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $avaliacoes = [];
    while ($row = $result->fetch_assoc()) {
        $avaliacoes[] = $row;
    }

    // Calcular média
    $stmt_media = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            COALESCE(AVG(rating), 0) as media,
            COALESCE(SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END), 0) as cinco_estrelas,
            COALESCE(SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END), 0) as quatro_estrelas,
            COALESCE(SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END), 0) as tres_estrelas,
            COALESCE(SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END), 0) as duas_estrelas,
            COALESCE(SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END), 0) as uma_estrella
        FROM avaliacoes 
        WHERE casa_id = ? AND ativo = 1
    ");
    $stmt_media->bind_param("i", $casa_id);
    $stmt_media->execute();
    $media = $stmt_media->get_result()->fetch_assoc();

    echo json_encode([
        'avaliacoes' => $avaliacoes,
        'media' => round($media['media'], 1),
        'total' => (int)$media['total'],
        'distribuicao' => [
            5 => (int)$media['cinco_estrelas'],
            4 => (int)$media['quatro_estrelas'],
            3 => (int)$media['tres_estrelas'],
            2 => (int)$media['duas_estrelas'],
            1 => (int)$media['uma_estrella']
        ]
    ]);
}

// Atualizar avaliação (apenas autor)
function updateAvaliacao()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);

    $avaliacao_id = $data['avaliacao_id'] ?? null;
    $rating = $data['rating'] ?? null;
    $comentario = $data['comentario'] ?? null;

    if (!$avaliacao_id) {
        echo json_encode(['error' => 'ID da avaliação é obrigatório']);
        return;
    }

    // Verificar se a avaliação pertence ao utilizador
    $stmt = $conn->prepare("SELECT * FROM avaliacoes WHERE id = ? AND arrendatario_id = ?");
    $stmt->bind_param("ii", $avaliacao_id, $user_id);
    $stmt->execute();
    $avaliacao = $stmt->get_result()->fetch_assoc();

    if (!$avaliacao) {
        echo json_encode(['error' => 'Avaliação não encontrada ou sem permissão']);
        return;
    }

    // Validar rating se fornecido
    if ($rating !== null && ($rating < 1 || $rating > 5)) {
        echo json_encode(['error' => 'Rating deve ser entre 1 e 5']);
        return;
    }

    // Atualizar apenas os campos fornecidos
    $updates = [];
    $params = [];
    $types = "";

    if ($rating !== null) {
        $updates[] = "rating = ?";
        $params[] = $rating;
        $types .= "i";
    }

    if ($comentario !== null) {
        $updates[] = "comentario = ?";
        $params[] = $comentario;
        $types .= "s";
    }

    if (empty($updates)) {
        echo json_encode(['error' => 'Nenhum campo para atualizar']);
        return;
    }

    $params[] = $avaliacao_id;
    $types .= "i";

    $query = "UPDATE avaliacoes SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // Atualizar média na casa
        atualizarMediaAvaliacao($avaliacao['casa_id']);

        echo json_encode([
            'success' => true,
            'message' => 'Avaliação atualizada com sucesso'
        ]);
    } else {
        echo json_encode(['error' => 'Erro ao atualizar avaliação']);
    }
}

// Eliminar avaliação (apenas autor)
function deleteAvaliacao()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);
    $avaliacao_id = $data['avaliacao_id'] ?? null;

    if (!$avaliacao_id) {
        echo json_encode(['error' => 'ID da avaliação é obrigatório']);
        return;
    }

    // Verificar se a avaliação pertence ao utilizador
    $stmt = $conn->prepare("SELECT casa_id FROM avaliacoes WHERE id = ? AND arrendatario_id = ?");
    $stmt->bind_param("ii", $avaliacao_id, $user_id);
    $stmt->execute();
    $avaliacao = $stmt->get_result()->fetch_assoc();

    if (!$avaliacao) {
        echo json_encode(['error' => 'Avaliação não encontrada ou sem permissão']);
        return;
    }

    // Alternativamente, se for admin pode eliminar
    if ($user_type === 'admin') {
        $stmt = $conn->prepare("SELECT casa_id FROM avaliacoes WHERE id = ?");
        $stmt->bind_param("i", $avaliacao_id);
        $stmt->execute();
        $avaliacao = $stmt->get_result()->fetch_assoc();
    }

    $stmt = $conn->prepare("DELETE FROM avaliacoes WHERE id = ?");
    $stmt->bind_param("i", $avaliacao_id);

    if ($stmt->execute()) {
        // Atualizar média na casa
        atualizarMediaAvaliacao($avaliacao['casa_id']);

        echo json_encode([
            'success' => true,
            'message' => 'Avaliação eliminada com sucesso'
        ]);
    } else {
        echo json_encode(['error' => 'Erro ao eliminar avaliação']);
    }
}

/**
 * Responder a avaliação (apenas proprietário)
 */
function respondAvaliacao()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);

    $avaliacao_id = $data['avaliacao_id'] ?? null;
    $resposta = $data['resposta'] ?? null;

    if (!$avaliacao_id || !$resposta) {
        echo json_encode(['error' => 'ID da avaliação e resposta são obrigatórios']);
        return;
    }

    if ($user_type !== 'proprietario') {
        echo json_encode(['error' => 'Apenas proprietários podem responder a avaliações']);
        return;
    }

    // Verificar se a avaliação é desta casa do proprietário
    $stmt = $conn->prepare("
        SELECT a.*, c.proprietario_id 
        FROM avaliacoes a
        JOIN casas c ON a.casa_id = c.id
        WHERE a.id = ?
    ");
    $stmt->bind_param("i", $avaliacao_id);
    $stmt->execute();
    $avaliacao = $stmt->get_result()->fetch_assoc();

    if (!$avaliacao) {
        echo json_encode(['error' => 'Avaliação não encontrada']);
        return;
    }

    if ($avaliacao['proprietario_id'] != $user_id) {
        echo json_encode(['error' => 'Sem permissão para responder a esta avaliação']);
        return;
    }

    $stmt = $conn->prepare("
        UPDATE avaliacoes 
        SET resposta = ?, resposta_data = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("si", $resposta, $avaliacao_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Resposta adicionada com sucesso'
        ]);
    } else {
        echo json_encode(['error' => 'Erro ao adicionar resposta']);
    }
}

/**
 * Verificar se utilizador pode avaliar uma casa
 * Apenas utilizadores do tipo arrendatario podem avaliar
 */
function checkCanAvaliar()
{
    global $conn, $user_id, $user_type;

    $casa_id = $_GET['casa_id'] ?? null;

    if (!$casa_id) {
        echo json_encode(['error' => 'ID da casa é obrigatório']);
        return;
    }

    // Apenas arrendatários podem avaliar
    if ($user_type !== 'arrendatario') {
        echo json_encode([
            'pode_avaliar' => false,
            'ja_avaliou' => false,
            'motivo' => 'Apenas arrendatários podem avaliar propriedades'
        ]);
        return;
    }

    // Verificar se já avaliou
    $stmt = $conn->prepare("SELECT id FROM avaliacoes WHERE casa_id = ? AND arrendatario_id = ?");
    $stmt->bind_param("ii", $casa_id, $user_id);
    $stmt->execute();
    $ja_avaliou = $stmt->get_result()->num_rows > 0;

    echo json_encode([
        'pode_avaliar' => !$ja_avaliou,
        'ja_avaliou' => $ja_avaliou
    ]);
}

/**
 * Ver minhas avaliações (avaliações que fiz)
 */
function myAvaliacoes()
{
    global $conn, $user_id, $user_type;

    $stmt = $conn->prepare("
        SELECT a.*, c.titulo as casa_titulo
        FROM avaliacoes a
        JOIN casas c ON a.casa_id = c.id
        WHERE a.arrendatario_id = ?
        ORDER BY a.data_criacao DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $avaliacoes = [];
    while ($row = $result->fetch_assoc()) {
        $avaliacoes[] = $row;
    }

    echo json_encode(['avaliacoes' => $avaliacoes]);
}

/**
 * Obter estatísticas de avaliações
 */
function getStats()
{
    global $conn, $user_id, $user_type;

    $casa_id = $_GET['casa_id'] ?? null;

    if (!$casa_id) {
        echo json_encode(['error' => 'ID da casa é obrigatório']);
        return;
    }

    // Verificar se é o proprietário
    $stmt = $conn->prepare("SELECT proprietario_id FROM casas WHERE id = ?");
    $stmt->bind_param("i", $casa_id);
    $stmt->execute();
    $casa = $stmt->get_result()->fetch_assoc();

    if (!$casa || ($casa['proprietario_id'] != $user_id && $user_type !== 'admin')) {
        echo json_encode(['error' => 'Sem permissão']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            COALESCE(AVG(rating), 0) as media,
            COALESCE(SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END), 0) as cinco_estrelas,
            COALESCE(SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END), 0) as quatro_estrelas,
            COALESCE(SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END), 0) as tres_estrelas,
            COALESCE(SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END), 0) as duas_estrelas,
            COALESCE(SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END), 0) as uma_estrella
        FROM avaliacoes 
        WHERE casa_id = ? AND ativo = 1
    ");
    $stmt->bind_param("i", $casa_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'total' => (int)$stats['total'],
        'media' => round($stats['media'], 1),
        'distribuicao' => [
            5 => (int)$stats['cinco_estrelas'],
            4 => (int)$stats['quatro_estrelas'],
            3 => (int)$stats['tres_estrelas'],
            2 => (int)$stats['duas_estrelas'],
            1 => (int)$stats['uma_estrella']
        ]
    ]);
}

/**
 * Enviar email ao proprietário sobre nova avaliação
 */
function enviarEmailProprietarioNovaAvaliacao($casa_id, $rating)
{
    global $conn;

    // Obter informações da casa e proprietário
    $stmt = $conn->prepare("
        SELECT c.titulo, c.proprietario_id, u.email, u.utilizador
        FROM casas c
        JOIN utilizadores u ON c.proprietario_id = u.id
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $casa_id);
    $stmt->execute();
    $casa = $stmt->get_result()->fetch_assoc();

    if (!$casa || !$casa['email']) {
        return;
    }

    $estrelas = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

    $subject = "⭐ Nova Avaliação Recebida: " . $casa['titulo'];
    $messageHtml = '
        <p>Recebeu uma <strong>nova avaliação</strong> para <strong>' . htmlspecialchars($casa['titulo']) . '</strong>.</p>
        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; text-align: center;">
            <span style="font-size: 28px;">' . $estrelas . '</span>
            <p style="margin: 10px 0 0 0; font-weight: bold; color: #0c5460;">(' . $rating . '/5 estrelas)</p>
        </div>
        <p>Responda à avaliação no <a href="https://alugatorres.pt/AlugaTorres/proprietario/minhas_casas.php" style="color: #038e01;">painel proprietário</a>.</p>';
    $body = EmailEstilizado('Nova Avaliação Recebida', $messageHtml, $rating . '/5', $casa['utilizador']);

    sendEmail($casa['email'], $subject, $body);
}

/**
 * Atualizar média de avaliações na tabela de casas
 * Automaticamente define destaque se média >= 4
 */
function atualizarMediaAvaliacao($casa_id)
{
    global $conn;

    $stmt = $conn->prepare("
        UPDATE casas 
        SET media_avaliacao = (
            SELECT COALESCE(AVG(rating), 0) 
            FROM avaliacoes 
            WHERE casa_id = ? AND ativo = 1
        ),
        destaque = (
            SELECT CASE WHEN COALESCE(AVG(rating), 0) >= 4 THEN 1 ELSE 0 END
            FROM avaliacoes 
            WHERE casa_id = ? AND ativo = 1
        )
        WHERE id = ?
    ");
    $stmt->bind_param("iii", $casa_id, $casa_id, $casa_id);
    $stmt->execute();
}
