<?php
// bootstrap comum para APIs
require_once __DIR__ . '/../root/init.php';

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
    $current = new \DateTime($start_date);
    $end = new \DateTime($end_date);
    $interval = new \DateInterval('P1D');
    $period = new \DatePeriod($current, $interval, $end->modify('+1 day'));

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
        $checkin = new \DateTime($row['data_checkin']);
        $checkout = new \DateTime($row['data_checkout']);
        $period = new \DatePeriod($checkin, $interval, $checkout);

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
        $inicio = new \DateTime($row['data_inicio']);
        $fim = new \DateTime($row['data_fim']);
        $fim->modify('+1 day');
        $period = new \DatePeriod($inicio, $interval, $fim);

        foreach ($period as $date) {
            $date_str = $date->format('Y-m-d');
            // Só bloqueia se não estiver reservado
            if (isset($availability[$date_str]) && $availability[$date_str]['status'] !== 'reserved') {
                $availability[$date_str]['status'] = 'blocked';
            }
        }
    }

    // Obter preços especiais para o mês
    $stmt = $conn->prepare("
        SELECT data, preco
        FROM precos_especiais
        WHERE casa_id = ? AND data >= ? AND data <= ?
    ");
    $stmt->bind_param("iss", $casa_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $date_str = $row['data'];
        if (isset($availability[$date_str])) {
            $availability[$date_str]['special_price'] = (float)$row['preco'];
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
                $dates = $action === 'block_single' ? [$data['date']] : ($data['dates'] ?? []);
                blockDates($conn, $casa_id, $dates);
                break;

            case 'unblock':
            case 'unblock_single':
                $dates = $action === 'unblock_single' ? [$data['date']] : ($data['dates'] ?? []);
                unblockDates($conn, $casa_id, $dates);
                break;

            case 'special_price':
                $dates = $data['dates'] ?? [];
                $price = $data['price'] ?? null;
                setSpecialPrice($conn, $casa_id, $dates, $price);
                break;

            default:
                echo json_encode(['error' => 'Ação inválida']);
                exit;
        }
    } catch (\Exception $e) {
        echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
        exit;
    }
}

function blockDates($conn, $casa_id, $dates)
{
    try {
        $success_count = 0;
        $skipped_reserved = 0;
        foreach ($dates as $date) {
            $dateFormatted = toISODate($date);

            // Verificar se a data já está reservada
            $check_reserva = $conn->prepare("
                SELECT id FROM reservas 
                WHERE casa_id = ? AND status IN ('confirmada', 'pendente')
                AND ? >= data_checkin AND ? < data_checkout
            ");
            $check_reserva->bind_param("iss", $casa_id, $dateFormatted, $dateFormatted);
            $check_reserva->execute();
            $check_reserva->store_result();

            if ($check_reserva->num_rows > 0) {
                $skipped_reserved++;
                continue; // Pula datas já reservadas
            }

            // Verificar se já existe bloqueio para esta data
            $check = $conn->prepare("SELECT id FROM bloqueios WHERE casa_id = ? AND ? BETWEEN data_inicio AND data_fim");
            $check->bind_param("is", $casa_id, $dateFormatted);
            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {
                // Inserir bloqueio (data única = início e fim iguais)
                $stmt = $conn->prepare("INSERT INTO bloqueios (casa_id, data_inicio, data_fim, criado_por) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $casa_id, $dateFormatted, $dateFormatted, $_SESSION['user_id']);
                $stmt->execute();
                $success_count++;
            } else {
                $success_count++; // Já está bloqueado, considerar sucesso
            }
        }

        $total_dates = count($dates);
        if ($success_count === 0 && $skipped_reserved > 0) {
            echo json_encode(['error' => "Não é possível bloquear uma data reservada"]);
            return;
        }

        $message = "$success_count/$total_dates datas bloqueadas";
        if ($skipped_reserved > 0) {
            $message .= " ($skipped_reserved datas reservadas ignoradas)";
        }
        echo json_encode(['success' => true, 'message' => $message, 'skipped_reserved' => $skipped_reserved]);
    } catch (\Exception $e) {
        echo json_encode(['error' => 'Erro ao bloquear datas: ' . $e->getMessage()]);
    }
}

function unblockDates($conn, $casa_id, $dates)
{
    try {
        $success_count = 0;
        foreach ($dates as $date) {
            $dateFormatted = toISODate($date);

            // Remover bloqueio da tabela bloqueios (onde a data está no intervalo)
            $stmt_block = $conn->prepare("DELETE FROM bloqueios WHERE casa_id = ? AND ? BETWEEN data_inicio AND data_fim");
            $stmt_block->bind_param("is", $casa_id, $dateFormatted);
            $stmt_block->execute();

            if ($stmt_block->affected_rows > 0) {
                $success_count++;
            }
        }

        echo json_encode(['success' => true, 'message' => "$success_count datas desbloqueadas"]);
    } catch (\Exception $e) {
        echo json_encode(['error' => 'Erro ao desbloquear datas: ' . $e->getMessage()]);
    }
}

function setSpecialPrice($conn, $casa_id, $dates, $price)
{
    try {
        $skipped_reserved = 0;
        $total_processed = 0;

        // Se o preço for nulo ou vazio, remover preços especiais para as datas
        if ($price === null || $price === '' || $price === '0') {
            $success_count = 0;
            $failed_count = 0;

            foreach ($dates as $date) {
                $dateFormatted = toISODate($date);

                // Check if reserved
                $check_reserva = $conn->prepare("
                    SELECT id FROM reservas 
                    WHERE casa_id = ? AND status IN ('confirmada', 'pendente')
                    AND ? >= data_checkin AND ? < data_checkout
                ");
                $check_reserva->bind_param("iss", $casa_id, $dateFormatted, $dateFormatted);
                $check_reserva->execute();
                $check_reserva->store_result();

                if ($check_reserva->num_rows > 0) {
                    $skipped_reserved++;
                    continue;
                }

                $total_processed++;

                // Verificar se existe preço especial para esta data antes de remover
                $check = $conn->prepare("SELECT id FROM precos_especiais WHERE casa_id = ? AND data = ?");
                $check->bind_param("is", $casa_id, $dateFormatted);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    $stmt = $conn->prepare("DELETE FROM precos_especiais WHERE casa_id = ? AND data = ?");
                    $stmt->bind_param("is", $casa_id, $dateFormatted);

                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $failed_count++;
                    }
                } else {
                    // Data não tinha preço especial, considerar como sucesso (nada para remover)
                    $success_count++;
                }
            }

            $total_dates = count($dates);
            if ($total_processed === 0 && $skipped_reserved > 0) {
                echo json_encode(['error' => "Não é possível alterar preço especial em data reservada"]);
                return;
            }

            $message = "Preço especial removido de $success_count data(s)";
            if ($skipped_reserved > 0) {
                $message .= " ($skipped_reserved datas reservadas ignoradas)";
            }
            echo json_encode([
                'success' => true,
                'message' => $message,
                'skipped_reserved' => $skipped_reserved
            ]);
            return;
        }

        // Validar preço
        if (!is_numeric($price) || $price <= 0) {
            echo json_encode(['error' => 'Preço inválido']);
            return;
        }

        $success_count = 0;
        $update_count = 0;
        $insert_count = 0;

        foreach ($dates as $date) {
            $dateFormatted = toISODate($date);

            // Check if reserved
            $check_reserva = $conn->prepare("
                SELECT id FROM reservas 
                WHERE casa_id = ? AND status IN ('confirmada', 'pendente')
                AND ? >= data_checkin AND ? < data_checkout
            ");
            $check_reserva->bind_param("iss", $casa_id, $dateFormatted, $dateFormatted);
            $check_reserva->execute();
            $check_reserva->store_result();

            if ($check_reserva->num_rows > 0) {
                $skipped_reserved++;
                continue;
            }

            $total_processed++;

            // Verificar se já existe preço especial para esta data
            $check = $conn->prepare("SELECT id FROM precos_especiais WHERE casa_id = ? AND data = ?");
            $check->bind_param("is", $casa_id, $dateFormatted);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                // Atualizar preço existente
                $stmt = $conn->prepare("UPDATE precos_especiais SET preco = ? WHERE casa_id = ? AND data = ?");
                $stmt->bind_param("dis", $price, $casa_id, $dateFormatted);
                if ($stmt->execute()) {
                    $success_count++;
                    $update_count++;
                }
            } else {
                // Inserir novo preço
                $stmt = $conn->prepare("INSERT INTO precos_especiais (casa_id, data, preco) VALUES (?, ?, ?)");
                $stmt->bind_param("isd", $casa_id, $dateFormatted, $price);
                if ($stmt->execute()) {
                    $success_count++;
                    $insert_count++;
                }
            }
        }

        $total_dates = count($dates);
        if ($success_count === 0 && $skipped_reserved > 0) {
            echo json_encode(['error' => "Não é possível definir preço especial em data reservada"]);
            return;
        }

        $message = "Preço especial aplicado a $success_count data(s) ($insert_count novas, $update_count atualizadas)";
        if ($skipped_reserved > 0) {
            $message .= " ($skipped_reserved datas reservadas ignoradas)";
        }
        echo json_encode([
            'success' => true,
            'message' => $message,
            'skipped_reserved' => $skipped_reserved
        ]);
    } catch (\Exception $e) {
        echo json_encode(['error' => 'Erro ao definir preço especial: ' . $e->getMessage()]);
    }
}

// Função auxiliar para converter datas
function toISODate($dateStr)
{
    // Se já estiver no formato Y-m-d, retornar
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        return $dateStr;
    }

    // Converter de d-m-Y para Y-m-d
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $dateStr, $matches)) {
        return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
    }

    // Converter de d/m/Y para Y-m-d
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
        return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
    }

    // Tentar com DateTime
    $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
    if ($date) {
        return $date->format('Y-m-d');
    }

    // Último recurso: assumir que é uma data válida
    return date('Y-m-d', strtotime($dateStr));
}
