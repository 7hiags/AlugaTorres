<?php

require_once __DIR__ . '/../root/init.php';

// [AUTENTICAÇÃO] Verifica se é proprietário
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] !== 'proprietario') {
    header("Location: ../backend/autenticacao/login.php");
    exit;
}

// [VALIDAÇÃO] Verifica se utilizador existe na base de dados
$declaracao = $conn->prepare("SELECT id FROM utilizadores WHERE id = ?");

$declaracao->bind_param("i", $_SESSION['user_id']);
$declaracao->execute();
$resultado = $declaracao->get_result();
if ($resultado->num_rows === 0) {
    session_destroy();
    header("Location: ../backend/autenticacao/login.php");
    exit;
}

// [VARIÁVEIS] Dados do utilizador
$id_utilizador = $_SESSION['user_id'];

// [PROCESSAMENTO AÇÕES] Trata submissões POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['action'] ?? '';
    $id_casa = $_POST['casa_id'] ?? null;

    if ($id_casa) {
        switch ($acao) {
            // [AÇÃO] Alternar disponibilidade da casa - PROTEGIDO por reservas
            case 'toggle_disponibilidade':
                // Verificar estado atual e reservas
                $check_stmt = $conn->prepare("
                    SELECT disponivel, 
(SELECT COUNT(*) FROM reservas WHERE casa_id = c.id AND status IN ('pendente', 'confirmada')) as total_reservas
                    FROM casas c 
                    WHERE id = ? AND proprietario_id = ?
                ");
                $check_stmt->bind_param("ii", $id_casa, $id_utilizador);
                $check_stmt->execute();
                $current = $check_stmt->get_result()->fetch_assoc();

                if ($current) {
                    // Só permite desativar se NÃO tem reservas
                    if ($current['disponivel'] == 1 && $current['total_reservas'] > 0) {
                        $_SESSION['error_msg'] = 'Não pode desativar a casa "' . htmlspecialchars($_POST['titulo'] ?? 'Casa') . '" pois tem reservas existentes. Cancele todas as reservas primeiro.';
                    } else {
                        // Permitir ativação sempre, ou desativação sem reservas
                        $declaracao = $conn->prepare("UPDATE casas SET disponivel = NOT disponivel WHERE id = ? AND proprietario_id = ?");
                        $declaracao->bind_param("ii", $id_casa, $id_utilizador);
                        $declaracao->execute();
                        $_SESSION['success_msg'] = 'Estado da casa atualizado com sucesso!';
                    }
                }
                break;

            // [AÇÃO] Eliminar casa
            case 'delete':
                $declaracao = $conn->prepare("DELETE FROM casas WHERE id = ? AND proprietario_id = ?");
                $declaracao->bind_param("ii", $id_casa, $id_utilizador);
                $declaracao->execute();
                break;
        }
    }
}

// [CONSULTA] Obtém casas do proprietário com estatísticas
$consulta = $conn->prepare("
SELECT c.*, c.aprovado, 
COUNT(CASE WHEN r.status IN ('pendente', 'confirmada') THEN r.id END) as total_reservas,
           SUM(CASE WHEN r.status NOT IN ('cancelada', 'rejeitada') THEN r.total ELSE 0 END) as receita_total,
           (SELECT COUNT(*) FROM reservas rp WHERE rp.casa_id = c.id AND rp.status = 'pendente') as reservas_pendentes
    FROM casas c
    LEFT JOIN reservas r ON c.id = r.casa_id
    WHERE c.proprietario_id = ?
    GROUP BY c.id
    ORDER BY c.titulo ASC
");


$consulta->bind_param("i", $id_utilizador);
$consulta->execute();
$resultado = $consulta->get_result();
?>
<?php
$pageTitle = 'AlugaTorres | Minhas Casas';
$metaDescription = 'Gerencie as suas propriedades disponíveis';
?>
<?php include __DIR__ . '/../root/head.php';
include '../root/header.php';
include '../root/sidebar.php';
?>

<body>
    <div class="casas-container">
        <div class="casas-header">
            <h1 class="casas-title">Minhas Propriedades</h1>
            <a href="adicionar_casa.php" class="btn-nova-casa">
                <i class="fas fa-plus"></i> Adicionar Nova Casa
            </a>
        </div>
        <div class="filtros">
            <button class="filtro-btn active" data-filtro="todas">Todas</button>
            <button class="filtro-btn" data-filtro="disponiveis">Disponíveis</button>
            <button class="filtro-btn" data-filtro="indisponiveis">Indisponíveis</button>
            <button class="filtro-btn" data-filtro="destaque">Em Destaque</button>
        </div>
        <?php if ($resultado->num_rows > 0): ?>
            <div class="casas-grid" id="casasGrid">
                <?php while ($casa = $resultado->fetch_assoc()): ?>


                    <!-- [CARD] Card individual da casa -->
                    <div class="casa-card"
                        data-disponivel="<?php echo $casa['disponivel'] ? 'sim' : 'nao'; ?>"
                        data-destaque="<?php echo $casa['destaque'] ? 'sim' : 'nao'; ?>">

                        <!-- [BADGE] Indicador de destaque -->
                        <?php if ($casa['destaque']): ?>
                            <div class="casa-destaque">
                                <i class="fas fa-star"></i> Destaque
                            </div>
                        <?php endif; ?>

                        <!-- [STATUS] Indicador de disponibilidade -->
                        <?php if (isset($casa['aprovado']) && ($casa['aprovado'] == 0 || $casa['aprovado'] === null)): ?>
                            <div class="casa-status status-pendente">
                                <i class="fas fa-clock"></i> Aprovação Pendente
                            </div>
                        <?php else: ?>
                            <div class="casa-status status-<?php echo $casa['disponivel'] ? 'disponivel' : 'indisponivel'; ?>">
                                <?php echo $casa['disponivel'] ? 'Disponível' : 'Indisponível'; ?>
                            </div>
                        <?php endif; ?>

                        <!-- [IMAGEM] Foto principal da casa -->
                        <div class="casa-imagem">
                            <?php
                            $fotos_casa = json_decode($casa['fotos'] ?? '[]', true);
                            if (!empty($fotos_casa) && isset($fotos_casa[0])):
                                // Corrigir caminho da foto se necessário
                                $foto_path = $fotos_casa[0];
                                if (!empty($foto_path) && strpos($foto_path, 'assets/') !== 0) {
                                    $foto_path = 'assets/' . ltrim($foto_path, '/');
                                }
                            ?>
                                <img src="<?php echo BASE_URL . htmlspecialchars($foto_path); ?>" alt="<?php echo htmlspecialchars($casa['titulo']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-home fa-3x"></i>
                            <?php endif; ?>
                        </div>


                        <!-- [CONTEÚDO] Informações da casa -->
                        <div class="casa-conteudo">
                            <div class="casa-header">
                                <h3 class="casa-titulo"><?php echo htmlspecialchars($casa['titulo']); ?></h3>
                            </div>

                            <!-- [INFO] Detalhes da casa -->
                            <div class="casa-info">
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($casa['cidade']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-bed"></i>
                                    <span><?php echo $casa['quartos']; ?> quarto(s)</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-user-friends"></i>
                                    <span>Até <?php echo $casa['capacidade']; ?> hóspedes</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-euro-sign"></i>
                                    <span><?php echo number_format($casa['preco_noite'], 2, ',', ' '); ?>€/noite</span>
                                </div>
                            </div>

                            <!-- [MÉTRICAS] Estatísticas da casa -->
                            <div class="casa-metricas">
                                <div class="metrica">
                                    <span class="metrica-valor <?php echo $casa['total_reservas'] > 0 ? 'has-reservations' : ''; ?>">
                                        <?php echo $casa['total_reservas']; ?> <?php echo $casa['total_reservas'] == 1 ? 'reserva' : 'reservas'; ?>
                                    </span>
                                    <span class="metrica-label">Reservas</span>
                                </div>
                                <div class="metrica">
                                    <span class="metrica-valor"><?php echo number_format($casa['receita_total'] ?? 0, 0, ',', ' '); ?>€</span>
                                    <span class="metrica-label">Receita</span>
                                </div>
                            </div>

                            <!-- [AÇÕES] Botões principais -->
                            <div class="casa-acoes">
                                <button class="acao-btn btn-editar" onclick="window.location.href='editar_casa.php?id=<?php echo $casa['id']; ?>'">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="acao-btn btn-calendario" onclick="window.location.href='../root/calendario.php?casa_id=<?php echo $casa['id']; ?>'">
                                    <i class="fas fa-calendar-alt"></i> Calendário
                                </button>
                                <button class="acao-btn btn-reservas" onclick="window.location.href='../root/reservas.php'" style="position: relative;">
                                    <i class="fas fa-calendar-check"></i> Reservas
                                    <?php if ($casa['reservas_pendentes'] > 0): ?>
                                        <span class="notification-badge" style="position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                            <?php echo $casa['reservas_pendentes']; ?>
                                        </span>
                                    <?php endif; ?>
                                </button>
                            </div>


                            <!-- [AÇÕES EXTRAS] Botões de gestão -->
                            <div class="casa-acoes-extras">
                                <!-- [FORM] Alternar disponibilidade -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="casa_id" value="<?php echo $casa['id']; ?>">
                                    <input type="hidden" name="action" value="toggle_disponibilidade">
                                    <button type="submit" class="btn-toggle btn-disponibilidade <?php echo ($casa['disponivel'] == 1 && $casa['total_reservas'] > 0) ? 'protected' : ''; ?>"
                                        <?php if ($casa['disponivel'] == 1 && $casa['total_reservas'] > 0): ?>title="Casa protegida: tem <?php echo $casa['total_reservas']; ?> reserva(s)" disabled<?php endif; ?>>
                                        <i class="fas fa-toggle-<?php echo $casa['disponivel'] ? 'on' : 'off'; ?>"></i>
                                        <?php echo $casa['disponivel'] ? 'Desativar' : 'Ativar'; ?>
                                        <?php if ($casa['disponivel'] == 1 && $casa['total_reservas'] > 0): ?>
                                            <span class="protected-badge" title="<?php echo $casa['total_reservas']; ?> reservas">🔒</span>
                                        <?php endif; ?>
                                    </button>
                                </form>

                                <!-- [BOTÃO] Eliminar casa -->
                                <button type="button" class="btn-toggle btn-eliminar" onclick="mostrarModalEliminar(<?php echo $casa['id']; ?>, '<?php echo htmlspecialchars(addslashes($casa['titulo'])); ?>')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="pagination">
                <button class="page-btn active">1</button>
            </div>

        <?php else: ?>
            <!-- [ESTADO VAZIO] Mensagem quando não há casas -->
            <div class="empty-state">
                <i class="fas fa-home"></i>
                <h2>Nenhuma propriedade cadastrada</h2>
                <p>Comece a anunciar suas propriedades para receber reservas.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- [MODAL] Confirmação de eliminação -->
    <div class="modal" id="modalEliminar">
        <div class="modal-content">
            <button class="modal-close" onclick="fecharModalEliminar()" title="Fechar">
                <i class="fas fa-times"></i>
            </button>
            <h3 class="modal-title">Confirmar Eliminação</h3>
            <p id="mensagemModal">Tem certeza que deseja eliminar esta propriedade?</p>
            <p><strong>Atenção:</strong> Esta ação não pode ser desfeita e eliminará todas as reservas associadas.</p>

            <div class="modal-actions">
                <form method="POST" id="formularioEliminar">
                    <input type="hidden" name="casa_id" id="eliminarCasaId">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="modal-btn btn-confirmar" onclick="return confirm('ATENÇÃO: Esta ação é irreversível. Todas as reservas associadas serão eliminadas permanentemente. Confirmar?')">
                        <i class="fas fa-trash-alt"></i> Eliminar Permanentemente
                    </button>
                </form>
                <button type="button" class="modal-btn btn-cancelar" onclick="fecharModalEliminar()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>

    <?php include '../root/footer.php'; ?>

    <script>
        if (typeof AlugaTorresNotifications !== 'undefined') {
            <?php if (isset($_SESSION['success_msg'])): ?>
                AlugaTorresNotifications.success(<?php echo json_encode($_SESSION['success_msg']); ?>);
                <?php unset($_SESSION['success_msg']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_msg'])): ?>
                AlugaTorresNotifications.error(<?php echo json_encode($_SESSION['error_msg']); ?>);
                <?php unset($_SESSION['error_msg']); ?>
            <?php endif; ?>
        }

        function mostrarModalEliminar(idCasa, tituloCasa) {
            const modal = document.getElementById('modalEliminar');
            const inputId = document.getElementById('eliminarCasaId');
            const mensagem = document.getElementById('mensagemModal');

            if (inputId) inputId.value = idCasa;
            if (mensagem) mensagem.textContent = 'Tem certeza que deseja eliminar "' + tituloCasa + '"?';
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden'; // Prevent body scroll
            }
        }

        function fecharModalEliminar() {
            const modal = document.getElementById('modalEliminar');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = ''; // Restore body scroll
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const modal = document.getElementById('modalEliminar');
            if (!modal) return;

            // Clique fora do modal (backdrop)
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    e.preventDefault();
                    e.stopPropagation();
                    fecharModalEliminar();
                }
            });

            // ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('show')) {
                    e.preventDefault();
                    fecharModalEliminar();
                }
            });

            // Prevent form submit bubbling
            const deleteForm = document.getElementById('formularioEliminar');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    e.stopPropagation();
                });
            }

            const grid = document.getElementById('casasGrid');
            if (!grid) return;

            const cards = Array.from(grid.querySelectorAll('.casa-card'));

            cards.sort(function(a, b) {
                const aDestaque = a.dataset.destaque === 'sim' ? 1 : 0;
                const bDestaque = b.dataset.destaque === 'sim' ? 1 : 0;

                if (bDestaque !== aDestaque) {
                    return bDestaque - aDestaque;
                }

                const tituloA = a.querySelector('.casa-titulo').textContent.toLowerCase();
                const tituloB = b.querySelector('.casa-titulo').textContent.toLowerCase();
                return tituloA.localeCompare(tituloB);
            });

            cards.forEach(function(card) {
                grid.appendChild(card);
            });
        });
    </script>
</body>

</html>