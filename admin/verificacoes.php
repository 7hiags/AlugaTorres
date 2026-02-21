<?php
require_once __DIR__ . '/../backend/check_admin.php';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $casa_id = intval($_POST['casa_id'] ?? 0);

    if ($_POST['acao'] === 'aprovar') {
        $stmt = $conn->prepare("UPDATE casas SET aprovado = 1 WHERE id = ?");
        $stmt->bind_param("i", $casa_id);
        $stmt->execute();
        logAdminActivity('Aprovar Casa', 'ID: ' . $casa_id);
    } elseif ($_POST['acao'] === 'rejeitar') {
        $motivo = $_POST['motivo'] ?? '';
        $stmt = $conn->prepare("UPDATE casas SET aprovado = 2, motivo_rejeicao = ? WHERE id = ?");
        $stmt->bind_param("si", $motivo, $casa_id);
        $stmt->execute();
        logAdminActivity('Rejeitar Casa', 'ID: ' . $casa_id);
    }

    header("Location: verificacoes.php");
    exit;
}

// Buscar casas pendentes
$casas = $conn->query("
    SELECT c.*, u.utilizador as proprietario_nome, u.email as proprietario_email, u.telefone 
    FROM casas c 
    JOIN utilizadores u ON c.proprietario_id = u.id 
    WHERE c.aprovado = 0 OR c.aprovado IS NULL 
    ORDER BY c.data_criacao DESC

");

logAdminActivity('Acesso às Verificações Pendentes');
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <title>AlugaTorres | Verificações Pendentes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include '../header.php';
    include '../sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h2><i class="fas fa-clipboard-check"></i> Verificações Pendentes</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php if ($casas->num_rows === 0): ?>
            <div class="admin-card" style="text-align: center; padding: 60px;">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #28a745; margin-bottom: 20px;"></i>
                <h3>Todas as casas foram verificadas!</h3>
                <p>Não há casas pendentes de aprovação.</p>
            </div>
        <?php else: ?>

            <div class="stats-row" style="margin-bottom: 30px;">
                <div class="stat-detail-card" style="border-left: 4px solid #ffc107;">
                    <h4><i class="fas fa-clock"></i> Casas Pendentes</h4>
                    <p class="big-number" style="color: #ffc107;"><?php echo $casas->num_rows; ?></p>
                </div>
            </div>

            <?php while ($casa = $casas->fetch_assoc()):
                $fotos = json_decode($casa['fotos'] ?? '[]', true);
            ?>
                <div class="admin-card" style="margin-bottom: 30px;">
                    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 30px;">
                        <!-- Galeria de Fotos -->
                        <div>
                            <?php if (!empty($fotos)): ?>
                                <img src="<?php echo htmlspecialchars($fotos[0]); ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 10px;">
                                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px;">
                                    <?php for ($i = 1; $i < min(4, count($fotos)); $i++): ?>
                                        <img src="<?php echo htmlspecialchars($fotos[$i]); ?>" style="width: 100%; height: 60px; object-fit: cover; border-radius: 5px;">
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Informações -->
                        <div>
                            <h3 style="margin: 0 0 15px 0;"><?php echo htmlspecialchars($casa['titulo']); ?></h3>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                <div>
                                    <p><strong><i class="fas fa-user"></i> Proprietário:</strong><br>
                                        <?php echo htmlspecialchars($casa['proprietario_nome']); ?></p>
                                    <p><strong><i class="fas fa-envelope"></i> Email:</strong><br>
                                        <?php echo htmlspecialchars($casa['proprietario_email']); ?></p>
                                    <p><strong><i class="fas fa-phone"></i> Telefone:</strong><br>
                                        <?php echo htmlspecialchars($casa['telefone'] ?? 'N/A'); ?></p>
                                </div>
                                <div>
                                    <p><strong><i class="fas fa-map-marker-alt"></i> Localização:</strong><br>
                                        <?php echo htmlspecialchars($casa['localizacao']); ?></p>
                                    <p><strong><i class="fas fa-euro-sign"></i> Preço/Noite:</strong><br>
                                        €<?php echo number_format($casa['preco_noite'], 2, ',', '.'); ?></p>
                                    <p><strong><i class="fas fa-users"></i> Capacidade:</strong><br>
                                        <?php echo $casa['capacidade']; ?> hóspedes</p>
                                </div>
                            </div>

                            <p><strong><i class="fas fa-align-left"></i> Descrição:</strong></p>
                            <p style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <?php echo nl2br(htmlspecialchars($casa['descricao'])); ?>
                            </p>

                            <?php if ($casa['comodidades']): ?>
                                <p><strong><i class="fas fa-concierge-bell"></i> Comodidades:</strong></p>
                                <p style="margin-bottom: 20px;"><?php echo htmlspecialchars($casa['comodidades']); ?></p>
                            <?php endif; ?>

                            <!-- Ações -->
                            <div style="display: flex; gap: 15px;">
                                <form method="POST" onsubmit="return confirm('APROVAR esta casa?');">
                                    <input type="hidden" name="acao" value="aprovar">
                                    <input type="hidden" name="casa_id" value="<?php echo $casa['id']; ?>">
                                    <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                                        <i class="fas fa-check"></i> Aprovar Casa
                                    </button>
                                </form>

                                <button onclick="mostrarRejeicao(<?php echo $casa['id']; ?>)" class="btn btn-danger" style="padding: 12px 30px;">
                                    <i class="fas fa-times"></i> Rejeitar Casa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </main>

    <!-- Modal Rejeição -->
    <div class="modal-overlay" id="modalRejeicao">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle"></i> Rejeitar Casa</h3>
                <button class="modal-close" onclick="fecharModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="rejeitar">
                <input type="hidden" name="casa_id" id="rejeitarCasaId">
                <div class="form-group">
                    <label>Motivo da rejeição (será enviado ao proprietário):</label>
                    <textarea name="motivo" class="form-control" rows="4" required placeholder="Explique o motivo..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rejeitar</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>

</body>

</html>