<?php
session_start();
require_once 'backend/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: backend/login.php");
    exit;
}

// Verificar se o usuário ainda existe na base de dados
$stmt = $conn->prepare("SELECT id FROM utilizadores WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    session_destroy();
    header("Location: backend/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user'];
$email = $_SESSION['email'];
$tipo_utilizador = $_SESSION['tipo_utilizador'] ?? 'arrendatario';

// Calcular estatísticas dinâmicas
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

    // Avaliação Média (se existir tabela de avaliações)
    $stats['avaliacao_media'] = 'N/A'; // Placeholder até implementar avaliações

    // Receita Total
    $stmt = $conn->prepare("SELECT SUM(r.total) as total FROM reservas r JOIN casas c ON r.casa_id = c.id WHERE c.proprietario_id = ? AND r.status = 'concluida'");
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

    // Favoritos (se existir tabela de favoritos)
    $stats['favoritos'] = 0; // Placeholder até implementar favoritos

    // Avaliações (se existir tabela de avaliações)
    $stats['avaliacoes'] = 0; // Placeholder até implementar avaliações

    // Total Gastos
    $stmt = $conn->prepare("SELECT SUM(total) as total FROM reservas WHERE arrendatario_id = ? AND status = 'concluida'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_gastos = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stats['total_gastos'] = '€' . number_format($total_gastos, 2, ',', '.');
}

// Obter dados do utilizador
$query = $conn->prepare("SELECT * FROM utilizadores WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user_data = $result->fetch_assoc();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $novo_nome = trim($_POST['nome']);
        $telefone = trim($_POST['telefone']);
        $morada = trim($_POST['morada']);
        $nif = trim($_POST['nif']);

        $stmt = $conn->prepare("UPDATE utilizadores SET utilizador = ?, telefone = ?, morada = ?, nif = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $novo_nome, $telefone, $morada, $nif, $user_id);

        if ($stmt->execute()) {
            $_SESSION['user'] = $novo_nome;
            $success = 'Perfil atualizado com sucesso!';
            $user_data['utilizador'] = $novo_nome;
            $user_data['telefone'] = $telefone;
            $user_data['morada'] = $morada;
            $user_data['nif'] = $nif;
        } else {
            $error = 'Erro ao atualizar perfil: ' . $conn->error;
        }
    } elseif ($action === 'change_password') {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        // Verificar senha atual
        if (!password_verify($senha_atual, $user_data['palavrapasse_hash'])) {
            $error = 'Senha atual incorreta!';
        } elseif ($nova_senha !== $confirmar_senha) {
            $error = 'As novas senhas não coincidem!';
        } elseif (strlen($nova_senha) < 6) {
            $error = 'A nova senha deve ter pelo menos 6 caracteres!';
        } else {
            $hash_nova_senha = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE utilizadores SET palavrapasse_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $hash_nova_senha, $user_id);

            if ($stmt->execute()) {
                $success = 'Senha alterada com sucesso!';
            } else {
                $error = 'Erro ao alterar senha: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - AlugaTorres</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style/style.css">
    <link rel="website icon" type="png" href="style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h1 class="profile-title">Meu Perfil</h1>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-type">
                        <?php echo $tipo_utilizador === 'proprietario' ? '🏠 Proprietário' : '👤 Arrendatário'; ?>
                    </div>
                </div>

                <ul class="profile-menu">
                    <li><a href="#dados-pessoais" class="active">
                            <i class="fas fa-user-circle"></i> Dados Pessoais
                        </a>
                    </li>
                    <li>
                        <?php if ($tipo_utilizador === 'proprietario'): ?>
                    <li><a href="#minhas-casas">
                            <i class="fas fa-home"></i> Minhas Casas
                        </a>
                    </li>
                <?php else: ?>
                    <a href="#minhas-reservas">
                        <i class="fas fa-calendar-check"></i> Minhas Reservas
                    </a>
                <?php endif; ?>
                </li>
                <li><a href="#alterar-senha">
                        <i class="fas fa-lock"></i> Alterar Senha
                    </a>
                </li>
                <li><a href="#configuracoes">
                        <i class="fas fa-cog"></i> Configurações
                    </a>
                </li>
                </ul>

                <div style="align-items: center;margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
                    <a href="backend/logout.php" class="btn-save logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Terminar Sessão
                    </a>
                </div>
            </div>

            <div class="profile-content">
                <!-- Dados Pessoais -->
                <section id="dados-pessoais">
                    <h2 class="section-title"><i class="fas fa-user-circle"></i> Dados Pessoais</h2>

                    <?php if ($tipo_utilizador === 'proprietario'): ?>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $stats['propriedades']; ?></span>
                                <span class="stat-label">Propriedades</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $stats['reservas_totais']; ?></span>
                                <span class="stat-label">Reservas Totais</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $stats['avaliacao_media']; ?></span>
                                <span class="stat-label">Avaliação Média</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $stats['receita_total']; ?></span>
                                <span class="stat-label">Receita Total</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $stats['reservas_feitas']; ?></span>
                                <span class="stat-label">Reservas Feitas</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $stats['favoritos']; ?></span>
                                <span class="stat-label">Favoritos</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $stats['avaliacoes']; ?></span>
                                <span class="stat-label">Avaliações</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-number"><?php echo $stats['total_gastos']; ?></span>
                                <span class="stat-label">Total Gastos</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="perfil.php">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" name="nome" class="form-input"
                                    value="<?php echo htmlspecialchars($user_data['utilizador'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-input" value="<?php echo htmlspecialchars($email); ?>" readonly>
                                <small style="color: #666; display: block; margin-top: 5px;">O email não pode ser alterado</small>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Telefone</label>
                                <input type="tel" name="telefone" class="form-input"
                                    value="<?php echo htmlspecialchars($user_data['telefone'] ?? ''); ?>">
                            </div>

                            <?php if ($tipo_utilizador === 'proprietario'): ?>
                                <div class="form-group">
                                    <label class="form-label">NIF</label>
                                    <input type="text" name="nif" class="form-input"
                                        value="<?php echo htmlspecialchars($user_data['nif'] ?? ''); ?>">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Morada</label>
                            <textarea name="morada" class="form-input" rows="3"><?php echo htmlspecialchars($user_data['morada'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Guardar Alterações
                        </button>
                    </form>
                </section>

                <hr style="margin: 40px 0; border: none; border-top: 1px solid #eee;">

                <!-- Alterar Senha -->
                <section id="alterar-senha">
                    <h2 class="section-title"><i class="fas fa-lock"></i> Alterar Senha</h2>

                    <form method="POST" action="perfil.php">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Senha Atual</label>
                                <input type="password" name="senha_atual" class="form-input" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Nova Senha</label>
                                <input type="password" name="nova_senha" class="form-input" required minlength="6">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Confirmar Nova Senha</label>
                                <input type="password" name="confirmar_senha" class="form-input" required minlength="6">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-key"></i> Alterar Senha
                        </button>
                    </form>
                </section>

                <?php if ($tipo_utilizador === 'proprietario'): ?>
                    <hr style="margin: 40px 0; border: none; border-top: 1px solid #eee;">

                    <!-- Área do Proprietário -->
                    <section id="minhas-casas">
                        <h2 class="section-title"><i class="fas fa-home"></i> Minhas Propriedades</h2>

                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h3 style="margin-top: 0;">Gestão de Propriedades</h3>
                            <p>Gerencie suas casas, calendário de disponibilidade e reservas.</p>

                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <a href="proprietario/minhas_casas.php" class="btn-save btn-success">
                                    <i class="fas fa-list"></i> Ver Todas as Casas
                                </a>
                                <a href="proprietario/adicionar_casa.php" class="btn-save btn-info">
                                    <i class="fas fa-plus"></i> Adicionar Nova Casa
                                </a>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Zona de Perigo -->
                <div class="danger-zone">
                    <h3><i class="fas fa-exclamation-triangle"></i> Zona de Perigo</h3>
                    <p>Esta ação não pode ser desfeita. Ao eliminar sua conta, perderá todos os dados.</p>

                    <button class="btn-danger" onclick="if(confirm('Tem certeza que deseja eliminar sua conta? Esta ação é irreversível!')) { window.location.href='eliminar_conta.php'; }">
                        <i class="fas fa-trash"></i> Eliminar Minha Conta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>AlugaTorres</h4>
                <p>Sua plataforma de arrendamento em Torres Novas</p>
            </div>
            <div class="footer-section">
                <h4>Ajuda</h4>
                <ul>
                    <li><a href="#">Central de Ajuda</a></li>
                    <li><a href="#">Contactar Suporte</a></li>
                    <li><a href="#">Política de Privacidade</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contactos</h4>
                <p><i class="fas fa-map-marker-alt"></i> Torres Novas, Portugal</p>
                <p><i class="fas fa-phone"></i> +351 929 326 577</p>
                <p><i class="fas fa-envelope"></i> suporte@alugatorres.pt</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <span id="ano"></span> AlugaTorres. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script src="backend/script.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const profileToggle = document.getElementById("profile-toggle");
            const sidebar = document.getElementById("sidebar");
            const sidebarOverlay = document.getElementById("sidebar-overlay");
            const closeSidebar = document.getElementById("close-sidebar");

            if (profileToggle) {
                profileToggle.addEventListener("click", function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    sidebar.classList.toggle("active");
                    sidebarOverlay.classList.toggle("active");
                });
            }

            if (closeSidebar) {
                closeSidebar.addEventListener("click", function() {
                    sidebar.classList.remove("active");
                    sidebarOverlay.classList.remove("active");
                });
            }

            // Close sidebar when clicking outside
            document.addEventListener("click", function(event) {
                if (
                    !sidebar.contains(event.target) &&
                    !profileToggle.contains(event.target)
                ) {
                    sidebar.classList.remove("active");
                    sidebarOverlay.classList.remove("active");
                }
            });
        });
    </script>
    <script>
        window.perfilTipoUsuario = '<?php echo $tipo_utilizador; ?>';
    </script>
</body>

</html>