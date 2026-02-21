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

session_start();
require_once '../backend/db.php';

require_once __DIR__ . '/../backend/email_utils.php';

// NOTE: utilitários de email movidos para backend/email_utils.php (sendEmail / logEmail)


// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Utilizador não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

// Obter dados da requisição (suporta tanto JSON quanto POST tradicional)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true) ?? [];

// Obter ação da requisição (de JSON, POST ou GET)
$action = $input['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '';


switch ($action) {
    case 'create':
        createReservation();
        break;
    case 'calculate':
        calculatePrice();
        break;
    case 'list':
        listReservations();
        break;
    case 'available_houses':
        listAvailableHouses();
        break;
    case 'cancel':
        cancelReservation();
        break;
    case 'confirmar':
        confirmarReserva();
        break;
    case 'concluir':
        concluirReserva();
        break;
    case 'rejeitar':
        rejeitarReserva();
        break;
    case 'eliminar':
        eliminarReserva();
        break;
    case 'get_casas':
        getCasasProprietario();
        break;
    case 'block':
        blockDates();
        break;
    case 'unblock':
        unblockDates();
        break;
    default:
        echo json_encode(['error' => 'Ação inválida']);
        break;
}


function createReservation()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['error' => 'Dados inválidos']);
        return;
    }

    $casa_id = $data['casa_id'] ?? null;
    $checkin = $data['checkin'] ?? null;
    $checkout = $data['checkout'] ?? null;
    $hospedes = $data['hospedes'] ?? 1;

    if (!$casa_id || !$checkin || !$checkout) {
        echo json_encode(['error' => 'Dados obrigatórios faltando']);
        return;
    }

    // Verificar se a casa existe
    $stmt = $conn->prepare("SELECT id, proprietario_id, preco_noite, preco_limpeza, taxa_seguranca, titulo FROM casas WHERE id = ?");

    $stmt->bind_param("i", $casa_id);
    $stmt->execute();
    $casa = $stmt->get_result()->fetch_assoc();

    if (!$casa) {
        echo json_encode(['error' => 'Casa não encontrada']);
        return;
    }

    // Verificar se o utilizador não é o proprietário
    if ($casa['proprietario_id'] == $user_id) {
        echo json_encode(['error' => 'Não pode reservar sua própria casa']);
        return;
    }

    // Verificar disponibilidade
    if (!checkAvailability($casa_id, $checkin, $checkout)) {
        echo json_encode(['error' => 'Datas não disponíveis']);
        return;
    }

    // Calcular preço total
    $preco_info = calculatePriceTotal($casa_id, $checkin, $checkout, $hospedes);
    $preco_total = $preco_info['total'];

    // Criar reserva e marcar como confirmada (pagamento não obrigatório)
    $conn->begin_transaction();

    // Usar valores detalhados do preco_info para corresponder à estrutura da BD
    $stmt = $conn->prepare("INSERT INTO reservas (casa_id, arrendatario_id, data_checkin, data_checkout, noites, total_hospedes, preco_noite, subtotal, taxa_limpeza, taxa_seguranca, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmada')");
    $stmt->bind_param("iissiiddddd", $casa_id, $user_id, $checkin, $checkout, $preco_info['noites'], $hospedes, $preco_info['preco_noite'], $preco_info['subtotal'], $preco_info['taxa_limpeza'], $preco_info['taxa_seguranca'], $preco_info['total']);


    if (!$stmt->execute()) {
        $conn->rollback();
        echo json_encode(['error' => 'Erro ao criar reserva']);
        return;
    }

    $reserva_id = $conn->insert_id;

    // Commit da transação
    try {
        $conn->commit();


        // Obter informações do arrendatário
        $userStmt = $conn->prepare("SELECT utilizador, email FROM utilizadores WHERE id = ?");
        $userStmt->bind_param("i", $user_id);
        $userStmt->execute();
        $userInfo = $userStmt->get_result()->fetch_assoc();
        $arrendatario_email = $userInfo['email'] ?? null;
        $arrendatario_nome = $userInfo['utilizador'] ?? 'Arrendatário';

        // Obter informações do proprietário da casa
        $propStmt = $conn->prepare("SELECT utilizador, email FROM utilizadores WHERE id = ?");
        $propStmt->bind_param("i", $casa['proprietario_id']);
        $propStmt->execute();
        $propInfo = $propStmt->get_result()->fetch_assoc();
        $proprietario_email = $propInfo['email'] ?? null;
        $proprietario_nome = $propInfo['utilizador'] ?? 'Proprietário';


        // Construir mensagens
        $tituloCasa = $casa['titulo'] ?? 'a propriedade';
        $subjectArr = "Reserva confirmada: " . $tituloCasa;
        $bodyArr = "<p>Olá " . htmlspecialchars($arrendatario_nome) . ",</p>" .
            "<p>Sua reserva na propriedade <strong>" . htmlspecialchars($tituloCasa) . "</strong> foi confirmada.</p>" .
            "<p><strong>Detalhes:</strong><br>Check-in: " . $checkin . "<br>Check-out: " . $checkout . "<br>Hóspedes: " . $hospedes . "<br>Total: " . number_format($preco_total, 2, ',', '.') . "€</p>" .
            "<p>ID da reserva: " . $reserva_id . "</p>" .
            "<p>Obrigado por usar o AlugaTorres.</p>";

        $subjectProp = "Nova reserva para sua propriedade: " . $tituloCasa;
        $bodyProp = "<p>Olá " . htmlspecialchars($proprietario_nome) . ",</p>" .
            "<p>Foi criada uma nova reserva para a sua propriedade <strong>" . htmlspecialchars($tituloCasa) . "</strong>.</p>" .
            "<p><strong>Detalhes:</strong><br>Arrendatário: " . htmlspecialchars($arrendatario_nome) . " (" . htmlspecialchars($arrendatario_email ?? '') . ")<br>Check-in: " . $checkin . "<br>Check-out: " . $checkout . "<br>Hóspedes: " . $hospedes . "<br>Total: " . number_format($preco_total, 2, ',', '.') . "€</p>" .
            "<p>ID da reserva: " . $reserva_id . "</p>";

        // Enviar emails (se existirem emails configurados)
        $arr_sent = $arrendatario_email ? sendEmail($arrendatario_email, $subjectArr, $bodyArr) : ['ok' => false, 'status' => 'no-email'];
        $prop_sent = $proprietario_email ? sendEmail($proprietario_email, $subjectProp, $bodyProp) : ['ok' => false, 'status' => 'no-email'];

        echo json_encode([
            'success' => true,
            'reserva_id' => $reserva_id,
            'message' => 'Reserva criada e confirmada com sucesso',
            'emails' => [
                'arrendatario' => $arr_sent,
                'proprietario' => $prop_sent
            ]
        ]);
    } catch (\Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Erro ao criar reserva: ' . $e->getMessage()]);
    }
}

function calculatePrice()
{
    global $conn;

    $casa_id = $_GET['casa_id'] ?? null;
    $checkin = $_GET['checkin'] ?? null;
    $checkout = $_GET['checkout'] ?? null;
    $hospedes = $_GET['hospedes'] ?? 1;

    if (!$casa_id || !$checkin || !$checkout) {
        echo json_encode(['error' => 'Parâmetros obrigatórios faltando']);
        return;
    }

    $preco_info = calculatePriceTotal($casa_id, $checkin, $checkout, $hospedes);
    echo json_encode($preco_info);
}

function calculatePriceTotal($casa_id, $checkin, $checkout, $hospedes)
{
    global $conn;

    // Obter informações da casa
    $stmt = $conn->prepare("SELECT preco_noite, preco_limpeza, taxa_seguranca FROM casas WHERE id = ?");
    $stmt->bind_param("i", $casa_id);
    $stmt->execute();
    $casa = $stmt->get_result()->fetch_assoc();


    if (!$casa) {
        return ['error' => 'Casa não encontrada'];
    }

    // Calcular noites
    $checkin_date = new \DateTime($checkin);
    $checkout_date = new \DateTime($checkout);

    $noites = $checkin_date->diff($checkout_date)->days;

    if ($noites <= 0) {
        return ['error' => 'Data de checkout deve ser após checkin'];
    }

    // Calcular preço base
    $preco_noite = $casa['preco_noite'];
    $subtotal = $noites * $preco_noite;

    // Taxas
    $taxa_limpeza = $casa['preco_limpeza'] ?? 0;
    $taxa_seguranca = $casa['taxa_seguranca'] ?? 0;


    // Total
    $total = $subtotal + $taxa_limpeza + $taxa_seguranca;

    return [
        'preco_noite' => $preco_noite,
        'noites' => $noites,
        'subtotal' => $subtotal,
        'taxa_limpeza' => $taxa_limpeza,
        'taxa_seguranca' => $taxa_seguranca,
        'total' => $total
    ];
}

function checkAvailability($casa_id, $checkin, $checkout)
{
    global $conn;

    // Verificar se há reservas conflitantes
    $stmt = $conn->prepare("
        SELECT COUNT(*) as conflitos FROM reservas
        WHERE casa_id = ? AND status IN ('confirmada', 'pendente')
        AND (
            (data_checkin <= ? AND data_checkout > ?) OR
            (data_checkin < ? AND data_checkout >= ?) OR
            (data_checkin >= ? AND data_checkout <= ?)
        )
    ");
    $stmt->bind_param("issssss", $casa_id, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout);

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['conflitos'] > 0) {
        return false;
    }

    // Verificar se há bloqueios conflitantes
    $stmt_bloqueio = $conn->prepare("
        SELECT COUNT(*) as bloqueios FROM bloqueios
        WHERE casa_id = ?
        AND (
            (data_inicio <= ? AND data_fim >= ?) OR
            (data_inicio < ? AND data_fim > ?) OR
            (data_inicio >= ? AND data_fim <= ?)
        )
    ");
    $stmt_bloqueio->bind_param("issssss", $casa_id, $checkout, $checkin, $checkout, $checkin, $checkin, $checkout);

    $stmt_bloqueio->execute();
    $result_bloqueio = $stmt_bloqueio->get_result()->fetch_assoc();

    return $result_bloqueio['bloqueios'] == 0;
}

function listReservations()
{
    global $conn, $user_id, $user_type;

    $tipo = $_GET['tipo'] ?? 'minhas';
    $filtro_status = $_GET['filtro'] ?? 'todas';
    $casa_id = $_GET['casa_id'] ?? null;
    $periodo = $_GET['periodo'] ?? 'todos';

    $params = [];
    $types = "";

    if ($tipo === 'proprietario' && $user_type === 'proprietario') {
        $query = "
            SELECT r.*, c.titulo as casa_titulo, u.utilizador as arrendatario_nome, u.email as arrendatario_email
            FROM reservas r
            JOIN casas c ON r.casa_id = c.id
            JOIN utilizadores u ON r.arrendatario_id = u.id
            WHERE c.proprietario_id = ?
        ";
        $params[] = $user_id;
        $types .= "i";

        if ($casa_id) {
            $query .= " AND r.casa_id = ?";
            $params[] = $casa_id;
            $types .= "i";
        }
    } else {
        $query = "
            SELECT r.*, c.titulo as casa_titulo, u.utilizador as proprietario_nome
            FROM reservas r
            JOIN casas c ON r.casa_id = c.id
            JOIN utilizadores u ON c.proprietario_id = u.id
            WHERE r.arrendatario_id = ?
        ";
        $params[] = $user_id;
        $types .= "i";
    }

    if ($filtro_status !== 'todas') {
        $query .= " AND r.status = ?";
        $params[] = $filtro_status;
        $types .= "s";
    }

    // Filtro de período
    $hoje = date('Y-m-d');
    switch ($periodo) {
        case 'hoje':
            $query .= " AND ? BETWEEN r.data_checkin AND r.data_checkout";
            $params[] = $hoje;
            $types .= "s";
            break;
        case 'semana':
            $inicio_semana = date('Y-m-d', strtotime('monday this week'));
            $fim_semana = date('Y-m-d', strtotime('sunday this week'));
            $query .= " AND ((r.data_checkin BETWEEN ? AND ?) OR (r.data_checkout BETWEEN ? AND ?))";
            array_push($params, $inicio_semana, $fim_semana, $inicio_semana, $fim_semana);
            $types .= "ssss";
            break;
        case 'mes':
            $inicio_mes = date('Y-m-01');
            $fim_mes = date('Y-m-t');
            $query .= " AND ((r.data_checkin BETWEEN ? AND ?) OR (r.data_checkout BETWEEN ? AND ?))";
            array_push($params, $inicio_mes, $fim_mes, $inicio_mes, $fim_mes);
            $types .= "ssss";
            break;
        case 'futuro':
            $query .= " AND r.data_checkin >= ?";
            $params[] = $hoje;
            $types .= "s";
            break;
        case 'passado':
            $query .= " AND r.data_checkout < ?";
            $params[] = $hoje;
            $types .= "s";
            break;
    }

    $query .= " ORDER BY r.data_reserva DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservas = [];
    while ($row = $result->fetch_assoc()) {
        $reservas[] = $row;
    }

    echo json_encode(['reservas' => $reservas]);
}


function listAvailableHouses()
{
    global $conn;

    $checkin = $_GET['checkin'] ?? null;
    $checkout = $_GET['checkout'] ?? null;
    $hospedes = $_GET['hospedes'] ?? 1;
    $localizacao = $_GET['localizacao'] ?? null;

    if (!$checkin || !$checkout) {
        echo json_encode(['error' => 'Datas obrigatórias']);
        return;
    }

    $query = "
        SELECT c.*, u.utilizador as proprietario_nome
        FROM casas c
        JOIN utilizadores u ON c.proprietario_id = u.id
        WHERE c.status = 'ativa'
        AND c.capacidade_maxima >= ?
    ";


    $params = [$hospedes];
    $types = "i";

    if ($localizacao) {
        $query .= " AND c.localizacao LIKE ?";
        $params[] = "%$localizacao%";
        $types .= "s";
    }

    // Verificar disponibilidade (não há reservas conflitantes)
    $query .= " AND NOT EXISTS (
        SELECT 1 FROM reservas r
        WHERE r.casa_id = c.id AND r.status IN ('confirmada', 'pendente')
        AND (
            (r.data_checkin <= ? AND r.data_checkout > ?) OR
            (r.data_checkin < ? AND r.data_checkout >= ?) OR
            (r.data_checkin >= ? AND r.data_checkout <= ?)
        )
    )";


    array_push($params, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout);
    $types .= "ssssss";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $casas = [];
    while ($row = $result->fetch_assoc()) {
        $casas[] = $row;
    }

    echo json_encode(['casas' => $casas]);
}

function cancelReservation()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);
    $reserva_id = $data['reserva_id'] ?? null;

    if (!$reserva_id) {
        echo json_encode(['error' => 'ID da reserva obrigatório']);
        return;
    }

    $reserva = getReservaWithPermissionCheck($reserva_id, $user_id, $user_type);
    if (!$reserva) {
        return;
    }

    if (!in_array($reserva['status'], ['pendente', 'confirmada'])) {
        echo json_encode(['error' => 'Não é possível cancelar esta reserva']);
        return;
    }

    $stmt = $conn->prepare("UPDATE reservas SET status = 'cancelada', data_cancelamento = NOW() WHERE id = ?");
    $stmt->bind_param("i", $reserva_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reserva cancelada']);
    } else {
        echo json_encode(['error' => 'Erro ao cancelar reserva']);
    }
}

function confirmarReserva()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);
    $reserva_id = $data['reserva_id'] ?? null;

    if (!$reserva_id) {
        echo json_encode(['error' => 'ID da reserva obrigatório']);
        return;
    }

    if ($user_type !== 'proprietario') {
        echo json_encode(['error' => 'Apenas proprietários podem confirmar reservas']);
        return;
    }

    $reserva = getReservaWithPermissionCheck($reserva_id, $user_id, $user_type);
    if (!$reserva) {
        return;
    }

    if ($reserva['status'] !== 'pendente') {
        echo json_encode(['error' => 'Apenas reservas pendentes podem ser confirmadas']);
        return;
    }

    $stmt = $conn->prepare("UPDATE reservas SET status = 'confirmada', data_confirmacao = NOW() WHERE id = ?");
    $stmt->bind_param("i", $reserva_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reserva confirmada']);
    } else {
        echo json_encode(['error' => 'Erro ao confirmar reserva']);
    }
}

function concluirReserva()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);
    $reserva_id = $data['reserva_id'] ?? null;

    if (!$reserva_id) {
        echo json_encode(['error' => 'ID da reserva obrigatório']);
        return;
    }

    if ($user_type !== 'proprietario') {
        echo json_encode(['error' => 'Apenas proprietários podem concluir reservas']);
        return;
    }

    $reserva = getReservaWithPermissionCheck($reserva_id, $user_id, $user_type);
    if (!$reserva) {
        return;
    }

    if ($reserva['status'] !== 'confirmada') {
        echo json_encode(['error' => 'Apenas reservas confirmadas podem ser concluídas']);
        return;
    }

    $stmt = $conn->prepare("UPDATE reservas SET status = 'concluida' WHERE id = ?");
    $stmt->bind_param("i", $reserva_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reserva concluída']);
    } else {
        echo json_encode(['error' => 'Erro ao concluir reserva']);
    }
}

function rejeitarReserva()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);
    $reserva_id = $data['reserva_id'] ?? null;

    if (!$reserva_id) {
        echo json_encode(['error' => 'ID da reserva obrigatório']);
        return;
    }

    if ($user_type !== 'proprietario') {
        echo json_encode(['error' => 'Apenas proprietários podem rejeitar reservas']);
        return;
    }

    $reserva = getReservaWithPermissionCheck($reserva_id, $user_id, $user_type);
    if (!$reserva) {
        return;
    }

    if ($reserva['status'] !== 'pendente') {
        echo json_encode(['error' => 'Apenas reservas pendentes podem ser rejeitadas']);
        return;
    }

    $stmt = $conn->prepare("UPDATE reservas SET status = 'rejeitada' WHERE id = ?");
    $stmt->bind_param("i", $reserva_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reserva rejeitada']);
    } else {
        echo json_encode(['error' => 'Erro ao rejeitar reserva']);
    }
}

function eliminarReserva()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);
    $reserva_id = $data['reserva_id'] ?? null;

    if (!$reserva_id) {
        echo json_encode(['error' => 'ID da reserva obrigatório']);
        return;
    }

    $reserva = getReservaWithPermissionCheck($reserva_id, $user_id, $user_type);
    if (!$reserva) {
        return;
    }

    $stmt = $conn->prepare("DELETE FROM reservas WHERE id = ?");
    $stmt->bind_param("i", $reserva_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reserva eliminada']);
    } else {
        echo json_encode(['error' => 'Erro ao eliminar reserva']);
    }
}

function getReservaWithPermissionCheck($reserva_id, $user_id, $user_type)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT r.*, c.proprietario_id
        FROM reservas r
        JOIN casas c ON r.casa_id = c.id
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $reserva_id);
    $stmt->execute();
    $reserva = $stmt->get_result()->fetch_assoc();

    if (!$reserva) {
        echo json_encode(['error' => 'Reserva não encontrada']);
        return false;
    }

    $pode_acessar = ($reserva['arrendatario_id'] == $user_id) ||
        ($reserva['proprietario_id'] == $user_id && $user_type === 'proprietario');

    if (!$pode_acessar) {
        echo json_encode(['error' => 'Sem permissão para aceder a esta reserva']);
        return false;
    }

    return $reserva;
}

function getCasasProprietario()
{
    global $conn, $user_id, $user_type;

    if ($user_type !== 'proprietario') {
        echo json_encode(['error' => 'Apenas proprietários podem listar casas']);
        return;
    }

    $stmt = $conn->prepare("SELECT id, titulo FROM casas WHERE proprietario_id = ? ORDER BY titulo");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $casas = [];
    while ($row = $result->fetch_assoc()) {
        $casas[] = $row;
    }

    echo json_encode(['casas' => $casas]);
}


function blockDates()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['error' => 'Dados inválidos']);
        return;
    }

    $casa_id = $data['casa_id'] ?? null;
    $dates = $data['dates'] ?? [];
    $tipo_utilizador = $data['tipo_utilizador'] ?? 'proprietario';

    if (!$casa_id || empty($dates)) {
        echo json_encode(['error' => 'Dados obrigatórios faltando']);
        return;
    }

    // Verificar se o usuário é o proprietário
    if ($tipo_utilizador === 'proprietario') {
        $stmt = $conn->prepare("SELECT proprietario_id FROM casas WHERE id = ?");
        $stmt->bind_param("i", $casa_id);
        $stmt->execute();
        $casa = $stmt->get_result()->fetch_assoc();

        if (!$casa || $casa['proprietario_id'] != $user_id) {
            echo json_encode(['error' => 'Sem permissão']);
            return;
        }
    }

    $success_count = 0;
    foreach ($dates as $date) {
        // Verificar se já existe bloqueio para esta data
        $stmt = $conn->prepare("SELECT id FROM bloqueios WHERE casa_id = ? AND ? BETWEEN data_inicio AND data_fim");
        $stmt->bind_param("is", $casa_id, $date);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if (!$existing) {
            // Inserir bloqueio (data única = início e fim iguais)
            $stmt = $conn->prepare("INSERT INTO bloqueios (casa_id, data_inicio, data_fim, criado_por) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $casa_id, $date, $date, $user_id);

            if ($stmt->execute()) {
                $success_count++;
            }
        } else {
            $success_count++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "$success_count datas bloqueadas"
    ]);
}


function unblockDates()
{
    global $conn, $user_id, $user_type;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        echo json_encode(['error' => 'Dados inválidos']);
        return;
    }

    $casa_id = $data['casa_id'] ?? null;
    $dates = $data['dates'] ?? [];
    $tipo_utilizador = $data['tipo_utilizador'] ?? 'proprietario';

    if (!$casa_id || empty($dates)) {
        echo json_encode(['error' => 'Dados obrigatórios faltando']);
        return;
    }

    // Verificar se o usuário é o proprietário
    if ($tipo_utilizador === 'proprietario') {
        $stmt = $conn->prepare("SELECT proprietario_id FROM casas WHERE id = ?");
        $stmt->bind_param("i", $casa_id);
        $stmt->execute();
        $casa = $stmt->get_result()->fetch_assoc();

        if (!$casa || $casa['proprietario_id'] != $user_id) {
            echo json_encode(['error' => 'Sem permissão']);
            return;
        }
    }

    $success_count = 0;
    foreach ($dates as $date) {
        // Remover bloqueio da tabela bloqueios (onde a data está no intervalo)
        $stmt = $conn->prepare("DELETE FROM bloqueios WHERE casa_id = ? AND ? BETWEEN data_inicio AND data_fim");
        $stmt->bind_param("is", $casa_id, $date);

        if ($stmt->execute()) {
            $success_count++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "$success_count datas desbloqueadas"
    ]);
}
