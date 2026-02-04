<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

session_start();
require_once 'db.php';

require_once __DIR__ . '/email_utils.php';

// NOTE: utilitários de email movidos para backend/email_utils.php (sendEmail / logEmail)


// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Utilizador não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

// Obter ação da requisição
$action = $_GET['action'] ?? $_POST['action'] ?? '';

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
    $stmt = $conn->prepare("SELECT id, proprietario_id, preco_noite, taxa_limpeza, taxa_seguranca FROM casas WHERE id = ?");
    $stmt->bind_param("i", $casa_id);
    $stmt->execute();
    $casa = $stmt->get_result()->fetch_assoc();

    if (!$casa) {
        echo json_encode(['error' => 'Casa não encontrada']);
        return;
    }

    // Verificar se o usuário não é o proprietário
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

    $stmt = $conn->prepare("INSERT INTO reservas (casa_id, arrendatario_id, checkin, checkout, hospedes, preco_total, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, 'confirmada', NOW())");
    $stmt->bind_param("iissid", $casa_id, $user_id, $checkin, $checkout, $hospedes, $preco_total);

    if (!$stmt->execute()) {
        $conn->rollback();
        echo json_encode(['error' => 'Erro ao criar reserva']);
        return;
    }

    $reserva_id = $conn->insert_id;

    // Bloquear datas no calendário (checkin até dia anterior ao checkout)
    try {
        $start = new DateTime($checkin);
        $end = new DateTime($checkout);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $date = $dt->format('Y-m-d');

            // Verificar se já existe registro
            $check_cal = $conn->prepare("SELECT id FROM calendario_disponibilidade WHERE casa_id = ? AND data = ?");
            $check_cal->bind_param("is", $casa_id, $date);
            $check_cal->execute();
            $res = $check_cal->get_result();

            if ($res && $res->num_rows > 0) {
                $stmt_cal = $conn->prepare("UPDATE calendario_disponibilidade SET disponivel = 0, reserva_id = ? WHERE casa_id = ? AND data = ?");
                $stmt_cal->bind_param("iis", $reserva_id, $casa_id, $date);
            } else {
                $stmt_cal = $conn->prepare("INSERT INTO calendario_disponibilidade (casa_id, data, disponivel, reserva_id) VALUES (?, ?, 0, ?)");
                $stmt_cal->bind_param("isi", $casa_id, $date, $reserva_id);
            }

            $stmt_cal->execute();
        }

        $conn->commit();

        // Obter informações do arrendatário
        $userStmt = $conn->prepare("SELECT nome, utilizador, email FROM utilizadores WHERE id = ?");
        $userStmt->bind_param("i", $user_id);
        $userStmt->execute();
        $userInfo = $userStmt->get_result()->fetch_assoc();
        $arrendatario_email = $userInfo['email'] ?? null;
        $arrendatario_nome = $userInfo['nome'] ?? $userInfo['utilizador'] ?? 'Arrendatário';

        // Obter informações do proprietário da casa
        $propStmt = $conn->prepare("SELECT nome, utilizador, email FROM utilizadores WHERE id = ?");
        $propStmt->bind_param("i", $casa['proprietario_id']);
        $propStmt->execute();
        $propInfo = $propStmt->get_result()->fetch_assoc();
        $proprietario_email = $propInfo['email'] ?? null;
        $proprietario_nome = $propInfo['nome'] ?? $propInfo['utilizador'] ?? 'Proprietário';

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
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Erro ao bloquear datas: ' . $e->getMessage()]);
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
    $stmt = $conn->prepare("SELECT preco_noite, taxa_limpeza, taxa_seguranca FROM casas WHERE id = ?");
    $stmt->bind_param("i", $casa_id);
    $stmt->execute();
    $casa = $stmt->get_result()->fetch_assoc();

    if (!$casa) {
        return ['error' => 'Casa não encontrada'];
    }

    // Calcular noites
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $noites = $checkin_date->diff($checkout_date)->days;

    if ($noites <= 0) {
        return ['error' => 'Data de checkout deve ser após checkin'];
    }

    // Calcular preço base
    $preco_noite = $casa['preco_noite'];
    $subtotal = $noites * $preco_noite;

    // Taxas
    $taxa_limpeza = $casa['taxa_limpeza'] ?? 0;
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
            (checkin <= ? AND checkout > ?) OR
            (checkin < ? AND checkout >= ?) OR
            (checkin >= ? AND checkout <= ?)
        )
    ");
    $stmt->bind_param("issssss", $casa_id, $checkin, $checkin, $checkout, $checkout, $checkin, $checkout);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['conflitos'] == 0;
}

function listReservations()
{
    global $conn, $user_id, $user_type;

    $tipo = $_GET['tipo'] ?? 'minhas'; // minhas, proprietario

    if ($tipo === 'proprietario' && $user_type === 'proprietario') {
        $stmt = $conn->prepare("
            SELECT r.*, c.titulo as casa_titulo, u.nome as arrendatario_nome
            FROM reservas r
            JOIN casas c ON r.casa_id = c.id
            JOIN utilizadores u ON r.arrendatario_id = u.id
            WHERE c.proprietario_id = ?
            ORDER BY r.criado_em DESC
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("
            SELECT r.*, c.titulo as casa_titulo
            FROM reservas r
            JOIN casas c ON r.casa_id = c.id
            WHERE r.arrendatario_id = ?
            ORDER BY r.criado_em DESC
        ");
        $stmt->bind_param("i", $user_id);
    }

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
        SELECT c.*, u.nome as proprietario_nome
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
            (r.checkin <= ? AND r.checkout > ?) OR
            (r.checkin < ? AND r.checkout >= ?) OR
            (r.checkin >= ? AND r.checkout <= ?)
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

    // Verificar se a reserva pertence ao usuário ou se é proprietário da casa
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
        return;
    }

    $pode_cancelar = ($reserva['arrendatario_id'] == $user_id) ||
        ($reserva['proprietario_id'] == $user_id && $user_type === 'proprietario');

    if (!$pode_cancelar) {
        echo json_encode(['error' => 'Sem permissão para cancelar']);
        return;
    }

    // Só pode cancelar se estiver pendente ou confirmada
    if (!in_array($reserva['status'], ['pendente', 'confirmada'])) {
        echo json_encode(['error' => 'Não é possível cancelar esta reserva']);
        return;
    }

    $stmt = $conn->prepare("UPDATE reservas SET status = 'cancelada' WHERE id = ?");
    $stmt->bind_param("i", $reserva_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Reserva cancelada']);
    } else {
        echo json_encode(['error' => 'Erro ao cancelar reserva']);
    }
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
    $tipo_utiliador = $data['tipo_utiliador'] ?? 'proprietario';

    if (!$casa_id || empty($dates)) {
        echo json_encode(['error' => 'Dados obrigatórios faltando']);
        return;
    }

    // Verificar se o usuário é o proprietário
    if ($tipo_utiliador === 'proprietario') {
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
        // Verificar se já existe
        $stmt = $conn->prepare("SELECT id FROM disponibilidade WHERE casa_id = ? AND data = ?");
        $stmt->bind_param("is", $casa_id, $date);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE disponibilidade SET status = 'blocked' WHERE id = ?");
            $stmt->bind_param("i", $existing['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO disponibilidade (casa_id, data, status) VALUES (?, ?, 'blocked')");
            $stmt->bind_param("is", $casa_id, $date);
        }

        if ($stmt->execute()) {
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
    $tipo_utiliador = $data['tipo_utiliador'] ?? 'proprietario';

    if (!$casa_id || empty($dates)) {
        echo json_encode(['error' => 'Dados obrigatórios faltando']);
        return;
    }

    // Verificar se o usuário é o proprietário
    if ($tipo_utiliador === 'proprietario') {
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
        $stmt = $conn->prepare("UPDATE disponibilidade SET status = 'available' WHERE casa_id = ? AND data = ?");
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
