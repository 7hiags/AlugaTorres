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

    $query = $conn->prepare("
        SELECT
            c.data,
            c.disponivel,
            c.preco_especial,
            c.bloqueio_proprietario,
            c.reserva_id,
            r.status as reserva_status,
            b.id as bloqueio_id
        FROM calendario_disponibilidade c
        LEFT JOIN reservas r ON c.reserva_id = r.id
        LEFT JOIN bloqueios b ON b.casa_id = c.casa_id AND b.data = c.data AND b.tipo = 'proprietario'
        WHERE c.casa_id = ?
        AND c.data BETWEEN ? AND ?
        ORDER BY c.data
    ");

    $query->bind_param("iss", $casa_id, $start_date, $end_date);
    $query->execute();
    $result = $query->get_result();

    $availability = [];

    while ($row = $result->fetch_assoc()) {
        $status = 'available';

        if ($row['reserva_id']) {
            $status = 'reserved';
        } elseif ($row['bloqueio_proprietario']) {
            $status = 'blocked';
        } elseif (!$row['disponivel']) {
            $status = 'unavailable';
        }

        $availability[$row['data']] = [
            'status' => $status,
            'special_price' => $row['preco_especial'],
            'reserva_id' => $row['reserva_id'],
            'reserva_status' => $row['reserva_status']
        ];
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

            case 'special_price':
            case 'special_price_single':
                $dates = $action === 'special_price_single' ? [$data['date']] : $data['dates'];
                $price = $data['price'] ? (float)$data['price'] : null;
                setSpecialPrice($conn, $casa_id, $dates, $price);
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
            // Verificar se já existe bloqueio
            $check = $conn->prepare("SELECT id FROM bloqueios WHERE casa_id = ? AND data = ?");
            $check->bind_param("is", $casa_id, $date);
            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {
                // Inserir bloqueio
                $stmt = $conn->prepare("INSERT INTO bloqueios (casa_id, data, tipo) VALUES (?, ?, 'proprietario')");
                $stmt->bind_param("is", $casa_id, $date);
                $stmt->execute();
            }

            // Atualizar ou inserir na tabela calendario_disponibilidade
            $check_cal = $conn->prepare("SELECT id FROM calendario_disponibilidade WHERE casa_id = ? AND data = ?");
            $check_cal->bind_param("is", $casa_id, $date);
            $check_cal->execute();
            $check_cal->store_result();

            if ($check_cal->num_rows > 0) {
                // Atualizar
                $stmt_cal = $conn->prepare("UPDATE calendario_disponibilidade SET disponivel = 0, bloqueio_proprietario = 1, reserva_id = NULL WHERE casa_id = ? AND data = ?");
                $stmt_cal->bind_param("is", $casa_id, $date);
            } else {
                // Inserir novo
                $stmt_cal = $conn->prepare("INSERT INTO calendario_disponibilidade (casa_id, data, disponivel, bloqueio_proprietario) VALUES (?, ?, 0, 1)");
                $stmt_cal->bind_param("is", $casa_id, $date);
            }

            $stmt_cal->execute();
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro ao bloquear datas: ' . $e->getMessage()]);
    }
}

function unblockDates($conn, $casa_id, $dates)
{
    foreach ($dates as $date) {
        // Remover bloqueio da tabela bloqueios
        $stmt_block = $conn->prepare("DELETE FROM bloqueios WHERE casa_id = ? AND data = ? AND tipo = 'proprietario'");
        $stmt_block->bind_param("is", $casa_id, $date);
        $stmt_block->execute();

        // Atualizar calendario_disponibilidade
        $stmt = $conn->prepare("UPDATE calendario_disponibilidade SET disponivel = 1, bloqueio_proprietario = 0 WHERE casa_id = ? AND data = ?");
        $stmt->bind_param("is", $casa_id, $date);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
}

function setSpecialPrice($conn, $casa_id, $dates, $price)
{
    foreach ($dates as $date) {
        // Verificar se já existe registro
        $check = $conn->prepare("SELECT id FROM calendario_disponibilidade WHERE casa_id = ? AND data = ?");
        $check->bind_param("is", $casa_id, $date);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            // Atualizar
            $stmt = $conn->prepare("UPDATE calendario_disponibilidade SET preco_especial = ? WHERE casa_id = ? AND data = ?");
            $stmt->bind_param("dis", $price, $casa_id, $date);
        } else {
            // Inserir novo
            $stmt = $conn->prepare("INSERT INTO calendario_disponibilidade (casa_id, data, preco_especial) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $casa_id, $date, $price);
        }

        $stmt->execute();
    }

    echo json_encode(['success' => true]);
}
