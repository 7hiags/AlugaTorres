<?php
require_once __DIR__ . '/../backend/check_admin.php';

// Processar ações de aprovação/rejeição
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

<?php
$pageTitle = 'AlugaTorres | Verificações Pendentes';
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'assets/style/admin_style.css">';
require_once __DIR__ . '/../root/head.php';
include '../root/header.php';
include '../root/sidebar.php';
?>

<body>
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
                <div class="admin-card" style="margin-bottom: 30px; overflow: hidden;">
                    <div style="display: flex; flex-wrap: wrap; gap: 25px;">
                        <!-- Galeria de Fotos -->
                        <div style="flex: 0 0 260px; max-width: 260px;">
                            <?php if (!empty($fotos)): ?>
                                <img src="<?php echo htmlspecialchars($fotos[0]); ?>" style="width: 100%; height: 170px; object-fit: cover; border-radius: 10px; margin-bottom: 10px;">
                                <div style="display: flex; gap: 5px;">
                                    <?php for ($i = 1; $i < min(4, count($fotos)); $i++): ?>
                                        <img src="<?php echo htmlspecialchars($fotos[$i]); ?>" style="width: 33%; height: 45px; object-fit: cover; border-radius: 5px;">
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Informações -->
                        <div style="flex: 1; min-width: 300px;">
                            <h3 style="margin: 0 0 12px 0;"><?php echo htmlspecialchars($casa['titulo']); ?></h3>

                            <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 12px;">
                                <div style="flex: 1; min-width: 200px;">
                                    <p style="margin: 0 0 6px 0;"><strong><i class="fas fa-user"></i> Proprietário:</strong> <?php echo htmlspecialchars($casa['proprietario_nome']); ?></p>
                                    <p style="margin: 0 0 6px 0;"><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($casa['proprietario_email']); ?></p>
                                    <p style="margin: 0 0 6px 0;"><strong><i class="fas fa-phone"></i> Telefone:</strong> <?php echo htmlspecialchars($casa['telefone'] ?? 'N/A'); ?></p>
                                </div>
                                <div style="flex: 1; min-width: 200px;">
                                    <p style="margin: 0 0 6px 0;"><strong><i class="fas fa-map-marker-alt"></i> Localização:</strong> <?php echo htmlspecialchars($casa['morada']); ?></p>
                                    <p style="margin: 0 0 6px 0;"><strong><i class="fas fa-euro-sign"></i> Preço/Noite:</strong> €<?php echo number_format($casa['preco_noite'], 2, ',', '.'); ?></p>
                                    <p style="margin: 0 0 6px 0;"><strong><i class="fas fa-users"></i> Capacidade:</strong> <?php echo $casa['capacidade']; ?> hóspedes</p>
                                </div>
                            </div>

                            <p style="margin: 0 0 4px 0;"><strong><i class="fas fa-align-left"></i> Descrição:</strong></p>
                            <p style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin: 0 0 12px 0; font-size: 0.9rem; line-height: 1.4;">
                                <?php echo nl2br(htmlspecialchars($casa['descricao'])); ?>
                            </p>

                            <!-- Ações -->
                            <div style="display: flex; gap: 12px; margin-top: 12px;">
                                <form method="POST" onsubmit="return confirm('APROVAR esta casa?');">
                                    <input type="hidden" name="acao" value="aprovar">
                                    <input type="hidden" name="casa_id" value="<?php echo $casa['id']; ?>">
                                    <button type="submit" class="btn btn-success" style="padding: 8px 20px;">
                                        <i class="fas fa-check"></i> Aprovar
                                    </button>
                                </form>

                                <button onclick="mostrarRejeicao(<?php echo $casa['id']; ?>)" class="btn btn-danger" style="padding: 8px 20px;">
                                    <i class="fas fa-times"></i> Rejeitar
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

    <!-- Event listener para modal click outside -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalRejeicao = document.getElementById('modalRejeicao');
            if (modalRejeicao) {
                modalRejeicao.addEventListener('click', function(e) {
                    if (e.target === this) {
                        fecharModal();
                    }
                });
            }
        });
    </script>

    <?php include '../root/footer.php'; ?>


</body>

</html>