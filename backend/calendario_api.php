<?php
session_start();
require_once 'db.php';

// Prevent HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';
$data = json_decode(file_get_contents('php://input'), true);

// Se for GET, retornar disponibilidade
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $casa_id = isset($_GET['casa_id']) ? (int)$_GET['casa_id'] : null;
    $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
    $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

    if (!$casa_id) {
        echo json_encode(['error' => 'Casa não especificada']);
        exit;
    }

    // Verificar se usuário tem acesso à casa
    if ($tipo_utilizador === 'proprietario') {
        $query = $conn->prepare("SELECT id FROM casas WHERE id = ? AND proprietario_id = ?");
        $query->bind_param("ii", $casa_id, $user_id);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['error' => 'Acesso não autorizado']);
            exit;
        }
    }

    // Obter disponibilidade para o mês
    $start_date = "$ano-$mes-01";
    $end_date = date('Y-m-t', strtotime($start_date));

    $availability = [];

    // Gerar todas as datas do mês
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($current, $interval, $end->modify('+1 day'));

    foreach ($period as $date) {
        $date_str = $date->format('Y-m-d');
        $availability[$date_str] = [
            'status' => 'available',
            'special_price' => null,
            'reserva_id' => null,
            'reserva_status' => null
        ];
    }

    // Obter reservas para o mês
    $stmt = $conn->prepare("
        SELECT data_checkin, data_checkout, status, id as reserva_id
        FROM reservas
        WHERE casa_id = ? AND status IN ('confirmada', 'pendente')
        AND (
            (data_checkin <= ? AND data_checkout >= ?) OR
            (data_checkin >= ? AND data_checkin <= ?) OR
            (data_checkout >= ? AND data_checkout <= ?)
        )
    ");
    $stmt->bind_param("issssss", $casa_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $checkin = new DateTime($row['data_checkin']);
        $checkout = new DateTime($row['data_checkout']);
        $period = new DatePeriod($checkin, $interval, $checkout);

        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            if (isset($availability[$date_str])) {
                $availability[$date_str]['status'] = 'reserved';
                $availability[$date_str]['reserva_id'] = $row['reserva_id'];
                $availability[$date_str]['reserva_status'] = $row['status'];
            }
        }
    }

    // Obter bloqueios para o mês
    $stmt = $conn->prepare("
        SELECT data_inicio, data_fim
        FROM bloqueios
        WHERE casa_id = ? AND (
            (data_inicio <= ? AND data_fim >= ?) OR
            (data_inicio >= ? AND data_inicio <= ?) OR
            (data_fim >= ? AND data_fim <= ?)
        )
    ");
    $stmt->bind_param("issssss", $casa_id, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $inicio = new DateTime($row['data_inicio']);
        $fim = new DateTime($row['data_fim']);
        $fim->modify('+1 day');
        $period = new DatePeriod($inicio, $interval, $fim);

        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            if (isset($availability[$date_str]) && $availability[$date_str]['status'] !== 'reserved') {
                $availability[$date_str]['status'] = 'blocked';
            }
        }
    }

    echo json_encode($availability);
    exit;
}

// Se for POST, processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $data['action'] ?? '';
        $casa_id = $data['casa_id'] ?? null;

        if (!$casa_id) {
            echo json_encode(['error' => 'Casa não especificada']);
            exit;
        }

        // Verificar se é proprietário da casa
        $query = $conn->prepare("SELECT id FROM casas WHERE id = ? AND proprietario_id = ?");
        $query->bind_param("ii", $casa_id, $user_id);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['error' => 'Acesso não autorizado']);
            exit;
        }

        switch ($action) {
            case 'block':
            case 'block_single':
                $dates = $action === 'block_single' ? [$data['date']] : $data['dates'];
                blockDates($conn, $casa_id, $dates);
                break;

            case 'unblock':
            case 'unblock_single':
                $dates = $action === 'unblock_single' ? [$data['date']] : $data['dates'];
                unblockDates($conn, $casa_id, $dates);
                break;

            default:
                echo json_encode(['error' => 'Ação inválida']);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
        exit;
    }
}

function blockDates($conn, $casa_id, $dates)
{
    try {
        foreach ($dates as $date) {
            // Verificar se já existe bloqueio para esta data
            $check = $conn->prepare("SELECT id FROM bloqueios WHERE casa_id = ? AND ? BETWEEN data_inicio AND data_fim");
            $check->bind_param("is", $casa_id, $date);
            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {
                // Inserir bloqueio (data única = início e fim iguais)
                $stmt = $conn->prepare("INSERT INTO bloqueios (casa_id, data_inicio, data_fim, criado_por) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $casa_id, $date, $date, $_SESSION['user_id']);
                $stmt->execute();
            }
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro ao bloquear datas: ' . $e->getMessage()]);
    }
}

function unblockDates($conn, $casa_id, $dates)
{
    try {
        foreach ($dates as $date) {
            // Remover bloqueio da tabela bloqueios (onde a data está no intervalo)
            $stmt_block = $conn->prepare("DELETE FROM bloqueios WHERE casa_id = ? AND ? BETWEEN data_inicio AND data_fim");
            $stmt_block->bind_param("is", $casa_id, $date);
            $stmt_block->execute();
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro ao desbloquear datas: ' . $e->getMessage()]);
    }
}
