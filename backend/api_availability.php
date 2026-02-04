<?php
header('Content-Type: application/json');
require_once 'db.php';

$casa_id = $_GET['casa_id'] ?? null;
$mes = $_GET['mes'] ?? date('n');
$ano = $_GET['ano'] ?? date('Y');

if (!$casa_id) {
    echo json_encode(['error' => 'ID da casa não fornecido']);
    exit;
}

// Obter reservas para o mês
$stmt = $conn->prepare("
    SELECT data_checkin, data_checkout, status
    FROM reservas
    WHERE casa_id = ? AND (
        YEAR(data_checkin) = ? AND MONTH(data_checkin) = ? OR
        YEAR(data_checkout) = ? AND MONTH(data_checkout) = ? OR
        (data_checkin <= ? AND data_checkout >= ?)
    )
");
$data_inicio_mes = sprintf('01-%02d-%04d', $mes, $ano);
$data_fim_mes = date('t-m-Y', strtotime($data_inicio_mes));

$stmt->bind_param("iiiiiss", $casa_id, $ano, $mes, $ano, $mes, $data_inicio_mes, $data_fim_mes);
$stmt->execute();
$result = $stmt->get_result();

$datas_reservadas = [];
while ($row = $result->fetch_assoc()) {
    $checkin = new DateTime($row['data_checkin']);
    $checkout = new DateTime($row['data_checkout']);

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($checkin, $interval, $checkout);

    foreach ($period as $date) {
        $data_str = $date->format('d-m-Y');
        $datas_reservadas[$data_str] = [
            'status' => $row['status'],
            'tipo' => 'reserva'
        ];
    }
}

// Obter bloqueios para o mês
$stmt = $conn->prepare("
    SELECT data_inicio, data_fim
    FROM bloqueios
    WHERE casa_id = ? AND (
        YEAR(data_inicio) = ? AND MONTH(data_inicio) = ? OR
        YEAR(data_fim) = ? AND MONTH(data_fim) = ? OR
        (data_inicio <= ? AND data_fim >= ?)
    )
");

$stmt->bind_param("iiiiiss", $casa_id, $ano, $mes, $ano, $mes, $data_inicio_mes, $data_fim_mes);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $inicio = new DateTime($row['data_inicio']);
    $fim = new DateTime($row['data_fim']);

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($inicio, $interval, $fim->modify('+1 day'));

    foreach ($period as $date) {
        $data_str = $date->format('d-m-Y');
        $datas_reservadas[$data_str] = [
            'status' => 'bloqueado',
            'tipo' => 'bloqueio'
        ];
    }
}

echo json_encode($datas_reservadas);