<?php
require_once __DIR__ . '/init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../backend/autenticacao/login.php");
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
                    WHERE c.proprietario_id = ? AND r.status IN ('pendente', 'confirmada') AND r.data_checkout >= CURDATE()
                ");
            } else {
                $query = $conn->prepare("
                    SELECT COUNT(*) as reservas_ativas FROM reservas
                    WHERE arrendatario_id = ? AND status IN ('pendente', 'confirmada') AND data_checkout >= CURDATE()
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

                        // Deletar reservas
                        if (!empty($casas_ids)) {
                            $placeholders = str_repeat('?,', count($casas_ids) - 1) . '?';
                            $stmt = $conn->prepare("DELETE FROM reservas WHERE casa_id IN ($placeholders)");
                            if (!$stmt) throw new Exception('Prepare reservas failed: ' . $conn->error);
                            $stmt->bind_param(str_repeat('i', count($casas_ids)), ...$casas_ids);
                            if (!$stmt->execute()) throw new Exception('Delete reservas failed: ' . $stmt->error);
                            error_log("Deleted " . $stmt->affected_rows . " reservas");

                            // Deletar bloqueios
                            $stmt = $conn->prepare("DELETE FROM bloqueios WHERE casa_id IN ($placeholders)");
                            if ($stmt) {
                                $stmt->bind_param(str_repeat('i', count($casas_ids)), ...$casas_ids);
                                if ($stmt->execute()) {
                                    error_log("Deleted " . $stmt->affected_rows . " bloqueios");
                                }
                            } else {
                                error_log("bloqueios table/syntax skipped: " . $conn->error);
                            }

                            // Deletar casas
                            $stmt = $conn->prepare("DELETE FROM casas WHERE proprietario_id = ?");
                            if (!$stmt) throw new Exception('Prepare casas failed: ' . $conn->error);
                            $stmt->bind_param("i", $user_id);
                            if (!$stmt->execute()) throw new Exception('Delete casas failed: ' . $stmt->error);
                            error_log("Deleted " . $stmt->affected_rows . " casas");
                        } else {
                            error_log("No casas_ids for user $user_id - skipping casa-related deletes");
                        }
                    } else {
                        // Deletar reservas
                        $stmt = $conn->prepare("DELETE FROM reservas WHERE arrendatario_id = ?");
                        if (!$stmt) throw new Exception('Prepare reservas arrendatario: ' . $conn->error);
                        $stmt->bind_param("i", $user_id);
                        if (!$stmt->execute()) throw new Exception('Delete reservas arrendatario: ' . $stmt->error);
                        error_log("Deleted " . $stmt->affected_rows . " reservas (arrendatario)");
                    }

                    // Deletar utilizador FINAL (after cascading deletes)
                    $stmt = $conn->prepare("DELETE FROM utilizadores WHERE id = ?");
                    if (!$stmt) throw new Exception('Prepare utilizadores final: ' . $conn->error);
                    $stmt->bind_param("i", $user_id);
                    if (!$stmt->execute()) throw new Exception('Delete utilizadores failed: ' . $stmt->error);
                    error_log("Deleted user ID $user_id successfully");

                    // Explicit admin protection (ID 1)
                    $admin_check = $conn->prepare("SELECT tipo_utilizador FROM utilizadores WHERE id = ?");
                    $admin_check->bind_param("i", $user_id);
                    $admin_check->execute();
                    $admin_result = $admin_check->get_result()->fetch_assoc();
                    if ($admin_result && $admin_result['tipo_utilizador'] === 'admin') {
                        throw new Exception('Administradores não podem eliminar sua própria conta.');
                    }

                    $stmt = $conn->prepare("DELETE FROM utilizadores WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    $conn->commit();

                    // Notificação de sucesso ANTES de destruir sessão
                    $_SESSION['notification'] = [
                        'type' => 'success',
                        'message' => 'Sua conta foi eliminada permanentemente com sucesso. Obrigado por usar AlugaTorres!'
                    ];
                    session_destroy();
                    header("Location: index.php");
                    exit;
                } catch (\Exception $e) {
                    $conn->rollback();
                    $detailed_error = 'Erro ao eliminar conta: ' . $e->getMessage() . ' (SQL: ' . $conn->error . ')';
                    error_log("Account deletion failed for user $user_id: " . $detailed_error);
                    $error = $detailed_error;
                }
            }
        }
    }
}

$pageTitle = 'AlugaTorres | Eliminar Conta';
$metaDescription = 'Procedimento de exclusão de conta';
require_once __DIR__ . '/head.php';
include 'header.php';
include 'sidebar.php';
?>

<body>
    <div class="form-container">
        <div class="form-header">
            <h1 class="form-title" style="color: #dc3545;">
                <i class="fas fa-exclamation-triangle"></i> Eliminar Conta
            </h1>
            <p class="form-subtitle">Esta ação é irreversível. Por favor, considere as consequências.</p>
        </div>

        <?php if ($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.error(<?php echo json_encode(htmlspecialchars($error)); ?>);
                    }
                });
            </script>
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
                    <li>Histórico de reservas</li>
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
    <?php include 'footer.php'; ?>
</body>

</html>