<?php
session_start();
require_once '../backend/db.php';

// Verificar se é proprietário
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] !== 'proprietario') {
    header("Location: ../backend/login.php");
    exit;
}

// Verificar se o usuário ainda existe na base de dados
$stmt = $conn->prepare("SELECT id FROM utilizadores WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    session_destroy();
    header("Location: ../backend/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $casa_id = $_POST['casa_id'] ?? null;

    if ($casa_id) {
        switch ($action) {
            case 'toggle_disponibilidade':
                $stmt = $conn->prepare("UPDATE casas SET disponivel = NOT disponivel WHERE id = ? AND proprietario_id = ?");
                $stmt->bind_param("ii", $casa_id, $user_id);
                $stmt->execute();
                break;

            case 'toggle_destaque':
                $stmt = $conn->prepare("UPDATE casas SET destaque = NOT destaque WHERE id = ? AND proprietario_id = ?");
                $stmt->bind_param("ii", $casa_id, $user_id);
                $stmt->execute();
                break;

            case 'delete':
                $stmt = $conn->prepare("DELETE FROM casas WHERE id = ? AND proprietario_id = ?");
                $stmt->bind_param("ii", $casa_id, $user_id);
                $stmt->execute();
                break;
        }
    }
}

// Obter casas do proprietário
$query = $conn->prepare("
    SELECT c.*, 
           COUNT(r.id) as total_reservas,
           SUM(CASE WHEN r.status NOT IN ('cancelada', 'rejeitada') THEN r.total ELSE 0 END) as receita_total
    FROM casas c
    LEFT JOIN reservas r ON c.id = r.casa_id
    WHERE c.proprietario_id = ?
    GROUP BY c.id
    ORDER BY c.data_criacao DESC
");


$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Casas - AlugaTorres</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">

</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

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

        <?php if ($result->num_rows > 0): ?>
            <div class="casas-grid" id="casasGrid">
                <?php while ($casa = $result->fetch_assoc()):
                    $fotos = $casa['fotos'] ? json_decode($casa['fotos'], true) : [];
                ?>

                    <div class="casa-card"
                        data-disponivel="<?php echo $casa['disponivel'] ? 'sim' : 'nao'; ?>"
                        data-destaque="<?php echo $casa['destaque'] ? 'sim' : 'nao'; ?>">

                        <?php if ($casa['destaque']): ?>
                            <div class="casa-destaque">
                                <i class="fas fa-star"></i> Destaque
                            </div>
                        <?php endif; ?>

                        <div class="casa-status status-<?php echo $casa['disponivel'] ? 'disponivel' : 'indisponivel'; ?>">
                            <?php echo $casa['disponivel'] ? 'Disponível' : 'Indisponível'; ?>
                        </div>

                        <div class="casa-imagem">
                            <?php if (!empty($fotos) && is_array($fotos)): ?>
                                <img src="<?php echo htmlspecialchars($fotos[0]); ?>"
                                    alt="<?php echo htmlspecialchars($casa['titulo']); ?>"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-home fa-3x"></i>
                            <?php endif; ?>
                        </div>

                        <div class="casa-conteudo">
                            <div class="casa-header">
                                <h3 class="casa-titulo"><?php echo htmlspecialchars($casa['titulo']); ?></h3>
                            </div>


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

                            <div class="casa-metricas">
                                <div class="metrica">
                                    <span class="metrica-valor"><?php echo $casa['total_reservas']; ?></span>
                                    <span class="metrica-label">Reservas</span>
                                </div>
                                <div class="metrica">
                                    <span class="metrica-valor"><?php echo number_format($casa['receita_total'] ?? 0, 0, ',', ' '); ?>€</span>
                                    <span class="metrica-label">Receita</span>
                                </div>
                            </div>


                            <div class="casa-acoes">
                                <button class="acao-btn btn-editar" onclick="window.location.href='editar_casa.php?id=<?php echo $casa['id']; ?>'">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="acao-btn btn-calendario" onclick="window.location.href='../calendario.php?casa_id=<?php echo $casa['id']; ?>'">
                                    <i class="fas fa-calendar-alt"></i> Calendário
                                </button>
                                <button class="acao-btn btn-reservas" onclick="window.location.href='../arrendatario/reservas.php?casa_id=<?php echo $casa['id']; ?>'">
                                    <i class="fas fa-calendar-check"></i> Reservas
                                </button>
                            </div>

                            <div class="casa-acoes-extras">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="casa_id" value="<?php echo $casa['id']; ?>">
                                    <input type="hidden" name="action" value="toggle_disponibilidade">
                                    <button type="submit" class="btn-toggle btn-disponibilidade">
                                        <i class="fas fa-toggle-<?php echo $casa['disponivel'] ? 'on' : 'off'; ?>"></i>
                                        <?php echo $casa['disponivel'] ? 'Desativar' : 'Ativar'; ?>
                                    </button>
                                </form>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="casa_id" value="<?php echo $casa['id']; ?>">
                                    <input type="hidden" name="action" value="toggle_destaque">
                                    <button type="submit" class="btn-toggle btn-destaque">
                                        <i class="fas fa-star"></i>
                                        <?php echo $casa['destaque'] ? 'Remover Destaque' : 'Destacar'; ?>
                                    </button>
                                </form>

                                <button type="button" class="btn-toggle btn-eliminar" onclick="showDeleteModal(<?php echo $casa['id']; ?>, '<?php echo htmlspecialchars(addslashes($casa['titulo'])); ?>')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="pagination">
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
                <span>...</span>
                <button class="page-btn">10</button>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-home"></i>
                <h2>Nenhuma propriedade cadastrada</h2>
                <p>Comece a anunciar suas propriedades para receber reservas.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h3 class="modal-title">Confirmar Eliminação</h3>
            <p id="modalMessage">Tem certeza que deseja eliminar esta propriedade?</p>
            <p><strong>Atenção:</strong> Esta ação não pode ser desfeita e eliminará todas as reservas associadas.</p>

            <div class="modal-actions">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="casa_id" id="deleteCasaId">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="modal-btn btn-confirmar">
                        <i class="fas fa-check"></i> Sim, Eliminar
                    </button>
                </form>
                <button class="modal-btn btn-cancelar" onclick="closeModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </div>
    </div>

    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>

</body>


</html>