<?php
session_start();
require_once 'backend/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: backend/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user'];
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $confirmacao = $_POST['confirmacao'] ?? '';

    if ($action === 'delete_account') {
        if ($confirmacao !== 'ELIMINAR') {
            $error = 'Digite "ELIMINAR" para confirmar a exclusão da conta.';
        } else {
            // Verificar se o usuário tem reservas ativas
            if ($tipo_utilizador === 'proprietario') {
                $query = $conn->prepare("
                    SELECT COUNT(*) as reservas_ativas FROM reservas r
                    JOIN casas c ON r.casa_id = c.id
                    WHERE c.proprietario_id = ? AND r.status IN ('pendente', 'confirmada') AND r.checkout >= CURDATE()
                ");
            } else {
                $query = $conn->prepare("
                    SELECT COUNT(*) as reservas_ativas FROM reservas
                    WHERE arrendatario_id = ? AND status IN ('pendente', 'confirmada') AND checkout >= CURDATE()
                ");
            }
            $query->bind_param("i", $user_id);
            $query->execute();
            $result = $query->get_result();
            $reservas_ativas = $result->fetch_assoc()['reservas_ativas'];

            if ($reservas_ativas > 0) {
                $error = 'Você não pode eliminar sua conta enquanto tiver reservas ativas. Cancele todas as reservas futuras primeiro.';
            } else {
                // Iniciar transação
                $conn->begin_transaction();

                try {
                    if ($tipo_utilizador === 'proprietario') {
                        // Obter IDs das casas do proprietário
                        $query = $conn->prepare("SELECT id FROM casas WHERE proprietario_id = ?");
                        $query->bind_param("i", $user_id);
                        $query->execute();
                        $result = $query->get_result();
                        $casas_ids = [];
                        while ($row = $result->fetch_assoc()) {
                            $casas_ids[] = $row['id'];
                        }

                        // Deletar mensagens relacionadas às reservas das casas
                        if (!empty($casas_ids)) {
                            $placeholders = str_repeat('?,', count($casas_ids) - 1) . '?';
                            $stmt = $conn->prepare("
                                DELETE m FROM mensagens m
                                JOIN reservas r ON m.reserva_id = r.id
                                WHERE r.casa_id IN ($placeholders)
                            ");
                            $stmt->bind_param(str_repeat('i', count($casas_ids)), ...$casas_ids);
                            $stmt->execute();

                            // Deletar reservas
                            $stmt = $conn->prepare("DELETE FROM reservas WHERE casa_id IN ($placeholders)");
                            $stmt->bind_param(str_repeat('i', count($casas_ids)), ...$casas_ids);
                            $stmt->execute();

                            // Deletar bloqueios
                            $stmt = $conn->prepare("DELETE FROM bloqueios WHERE casa_id IN ($placeholders)");
                            $stmt->bind_param(str_repeat('i', count($casas_ids)), ...$casas_ids);
                            $stmt->execute();

                            // Deletar casas
                            $stmt = $conn->prepare("DELETE FROM casas WHERE proprietario_id = ?");
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                        }
                    } else {
                        // Para arrendatário, deletar mensagens das suas reservas
                        $stmt = $conn->prepare("
                            DELETE m FROM mensagens m
                            JOIN reservas r ON m.reserva_id = r.id
                            WHERE r.arrendatario_id = ?
                        ");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();

                        // Deletar reservas
                        $stmt = $conn->prepare("DELETE FROM reservas WHERE arrendatario_id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                    }

                    // Deletar configurações
                    $stmt = $conn->prepare("DELETE FROM configuracoes_usuario WHERE utilizador_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    // Deletar usuário
                    $stmt = $conn->prepare("DELETE FROM utilizadores WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    $conn->commit();

                    // Destruir sessão e redirecionar
                    session_destroy();
                    header("Location: index.php?msg=conta_eliminada");
                    exit;
                } catch (\Exception $e) {
                    $conn->rollback();
                    $error = 'Erro ao eliminar conta. Tente novamente.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Eliminar Conta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">

</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="form-container">
        <div class="form-header">
            <h1 class="form-title" style="color: #dc3545;">
                <i class="fas fa-exclamation-triangle"></i> Eliminar Conta
            </h1>
            <p class="form-subtitle">Esta ação é irreversível. Por favor, considere as consequências.</p>
        </div>

        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="delete-warning">
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Atenção!</h3>
            <p>A eliminação da conta é uma ação permanente e não pode ser desfeita. Todos os seus dados serão removidos permanentemente do sistema.</p>

            <div class="delete-list">
                <strong>O que será eliminado:</strong>
                <ul>
                    <li>Seu perfil e informações pessoais</li>
                    <li>Histórico de reservas e mensagens</li>
                    <?php if ($tipo_utilizador === 'proprietario'): ?>
                        <li>Todas as suas casas cadastradas</li>
                        <li>Reservas associadas às suas casas</li>
                        <li>Bloqueios de datas</li>
                    <?php endif; ?>
                    <li>Suas configurações e preferências</li>
                </ul>
            </div>

            <p><strong>Importante:</strong> Você não poderá eliminar sua conta se tiver reservas ativas (pendentes ou confirmadas) com check-out futuro.</p>
        </div>

        <form method="POST" class="casa-form">
            <input type="hidden" name="action" value="delete_account">

            <div class="form-group confirmation-input">
                <label style="display: block; margin-bottom: 10px; font-weight: bold;">
                    Digite <strong>"ELIMINAR"</strong> para confirmar:
                </label>
                <input type="text" name="confirmacao" class="form-control" required
                    placeholder="Digite ELIMINAR" style="max-width: 200px; margin: 0 auto;">
            </div>

            <div class="form-actions" style="justify-content: center;">
                <button type="submit" class="btn-danger" style="background: #dc3545; border-color: #dc3545;">
                    <i class="fas fa-trash-alt"></i> Eliminar Conta Permanentemente
                </button>
                <a href="perfil.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Cancelar e Voltar
                </a>
            </div>
        </form>
    </div>

    <script src="js/script.js"></script>

</body>

</html>