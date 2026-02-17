<?php
require_once __DIR__ . '/../backend/check_admin.php';

// Processar ações
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        $casa_id = intval($_POST['casa_id'] ?? 0);

        switch ($_POST['acao']) {
            case 'eliminar':
                $stmt = $conn->prepare("DELETE FROM casas WHERE id = ?");
                $stmt->bind_param("i", $casa_id);
                if ($stmt->execute()) {
                    logAdminActivity('Eliminar Casa', 'ID: ' . $casa_id);
                    $mensagem = 'Casa eliminada com sucesso!';
                    $tipo_mensagem = 'success';
                } else {
                    $mensagem = 'Erro ao eliminar casa.';
                    $tipo_mensagem = 'danger';
                }
                break;

            case 'aprovar':
                $stmt = $conn->prepare("UPDATE casas SET aprovado = 1 WHERE id = ?");
                $stmt->bind_param("i", $casa_id);
                if ($stmt->execute()) {
                    logAdminActivity('Aprovar Casa', 'ID: ' . $casa_id);
                    $mensagem = 'Casa aprovada com sucesso!';
                    $tipo_mensagem = 'success';
                }
                break;

            case 'rejeitar':
                $motivo = $_POST['motivo'] ?? '';
                $stmt = $conn->prepare("UPDATE casas SET aprovado = 2, motivo_rejeicao = ? WHERE id = ?");
                $stmt->bind_param("si", $motivo, $casa_id);
                if ($stmt->execute()) {
                    logAdminActivity('Rejeitar Casa', 'ID: ' . $casa_id);
                    $mensagem = 'Casa rejeitada.';
                    $tipo_mensagem = 'warning';
                }
                break;

            case 'destacar':
                $stmt = $conn->prepare("UPDATE casas SET destaque = NOT destaque WHERE id = ?");
                $stmt->bind_param("i", $casa_id);
                if ($stmt->execute()) {
                    logAdminActivity('Alternar Destaque Casa', 'ID: ' . $casa_id);
                    $mensagem = 'Estado de destaque alterado!';
                    $tipo_mensagem = 'success';
                }
                break;

            case 'disponibilidade':
                $stmt = $conn->prepare("UPDATE casas SET disponivel = NOT disponivel WHERE id = ?");
                $stmt->bind_param("i", $casa_id);
                if ($stmt->execute()) {
                    logAdminActivity('Alternar Disponibilidade Casa', 'ID: ' . $casa_id);
                    $mensagem = 'Disponibilidade alterada!';
                    $tipo_mensagem = 'success';
                }
                break;
        }
    }
}

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_aprovacao = $_GET['aprovacao'] ?? '';
$filtro_destaque = $_GET['destaque'] ?? '';
$search = $_GET['search'] ?? '';

// Construir query
$where = [];
$params = [];
$types = '';

if ($filtro_estado === 'disponivel') {
    $where[] = "disponivel = 1";
} elseif ($filtro_estado === 'indisponivel') {
    $where[] = "disponivel = 0";
}

if ($filtro_aprovacao === 'pendente') {
    $where[] = "(aprovado = 0 OR aprovado IS NULL)";
} elseif ($filtro_aprovacao === 'aprovada') {
    $where[] = "aprovado = 1";
} elseif ($filtro_aprovacao === 'rejeitada') {
    $where[] = "aprovado = 2";
}

if ($filtro_destaque === 'sim') {
    $where[] = "destaque = 1";
}

if ($search) {
    $where[] = "(titulo LIKE ? OR morada LIKE ? OR descricao LIKE ?)";
    $search_like = "%$search%";
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $types .= 'sss';
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Paginação
$por_pagina = 15;
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $por_pagina;

// Contar total
$count_sql = "SELECT COUNT(*) as total FROM casas $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result()->fetch_assoc();
$total_casas = $total_result['total'];
$total_paginas = ceil($total_casas / $por_pagina);

// Buscar casas - CORRIGIDO: data_criacao em vez de created_at
$sql = "SELECT c.*, u.utilizador as proprietario_nome, u.email as proprietario_email 
        FROM casas c 
        LEFT JOIN utilizadores u ON c.proprietario_id = u.id 
        $where_clause 
        ORDER BY c.data_criacao DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

// Adicionar parâmetros de paginação
$params[] = $por_pagina;
$params[] = $offset;
$types .= 'ii';

$stmt->bind_param($types, ...$params);
$stmt->execute();
$casas = $stmt->get_result();

// Estatísticas
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total FROM casas");
$stats['total'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM casas WHERE disponivel = 1");
$stats['disponiveis'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM casas WHERE destaque = 1");
$stats['destaque'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM casas WHERE aprovado = 0 OR aprovado IS NULL");
$stats['pendentes'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM casas WHERE aprovado = 2");
$stats['rejeitadas'] = $result->fetch_assoc()['total'];

logAdminActivity('Acesso à Gestão de Casas');
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Gerir Casas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <h2><i class="fas fa-home"></i> Gerir Casas</h2>
            <div class="page-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                <i class="fas fa-<?php echo $tipo_mensagem === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="stats-row">
            <div class="stat-detail-card">
                <h4><i class="fas fa-home"></i> Total Casas</h4>
                <p class="big-number"><?php echo number_format($stats['total']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-check-circle"></i> Disponíveis</h4>
                <p class="big-number"><?php echo number_format($stats['disponiveis']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-star"></i> Em Destaque</h4>
                <p class="big-number"><?php echo number_format($stats['destaque']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-clock"></i> Pendentes</h4>
                <p class="big-number"><?php echo number_format($stats['pendentes']); ?></p>
            </div>
            <div class="stat-detail-card">
                <h4><i class="fas fa-times-circle"></i> Rejeitadas</h4>
                <p class="big-number"><?php echo number_format($stats['rejeitadas']); ?></p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <form class="filters-form" method="GET">
                <div class="form-group search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="form-control" placeholder="Pesquisar casas..." value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <div class="form-group">
                    <select name="estado" class="form-control">
                        <option value="">Todos os estados</option>
                        <option value="disponivel" <?php echo $filtro_estado === 'disponivel' ? 'selected' : ''; ?>>Disponíveis</option>
                        <option value="indisponivel" <?php echo $filtro_estado === 'indisponivel' ? 'selected' : ''; ?>>Indisponíveis</option>
                    </select>
                </div>

                <div class="form-group">
                    <select name="aprovacao" class="form-control">
                        <option value="">Todas as aprovações</option>
                        <option value="pendente" <?php echo $filtro_aprovacao === 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="aprovada" <?php echo $filtro_aprovacao === 'aprovada' ? 'selected' : ''; ?>>Aprovadas</option>
                        <option value="rejeitada" <?php echo $filtro_aprovacao === 'rejeitada' ? 'selected' : ''; ?>>Rejeitadas</option>
                    </select>
                </div>

                <div class="form-group">
                    <select name="destaque" class="form-control">
                        <option value="">Destaque</option>
                        <option value="sim" <?php echo $filtro_destaque === 'sim' ? 'selected' : ''; ?>>Em destaque</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="casas.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpar</a>
            </form>
        </div>

        <!-- Tabela de Casas -->
        <div class="admin-card">
            <div class="export-buttons">
                <button class="export-btn" onclick="exportTableToCSV('casas.csv')">
                    <i class="fas fa-download"></i> Exportar CSV
                </button>
            </div>

            <table class="admin-table" id="casasTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Foto</th>
                        <th>Título</th>
                        <th>Proprietário</th>
                        <th>Localização</th>
                        <th>Preço/Noite</th>
                        <th>Estado</th>
                        <th>Aprovação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($casa = $casas->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $casa['id']; ?></td>
                            <td>
                                <?php
                                $fotos = json_decode($casa['fotos'] ?? '[]', true);
                                $primeira_foto = $fotos[0] ?? '../style/img/casa_default.jpg';
                                ?>
                                <img src="<?php echo htmlspecialchars($primeira_foto); ?>" alt="" style="width: 60px; height: 40px; object-fit: cover; border-radius: 5px;">
                            </td>
                            <td><?php echo htmlspecialchars($casa['titulo']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($casa['proprietario_nome']); ?><br>
                                <small><?php echo htmlspecialchars($casa['proprietario_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($casa['morada']); ?></td>
                            <td>€<?php echo number_format($casa['preco_noite'], 2, ',', '.'); ?></td>
                            <td>
                                <?php if ($casa['disponivel']): ?>
                                    <span class="badge badge-confirmada">Disponível</span>
                                <?php else: ?>
                                    <span class="badge badge-cancelada">Indisponível</span>
                                <?php endif; ?>
                                <?php if ($casa['destaque']): ?>
                                    <span class="badge badge-pendente"><i class="fas fa-star"></i> Destaque</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!isset($casa['aprovado']) || $casa['aprovado'] == 0): ?>
                                    <span class="badge badge-pendente">Pendente</span>
                                <?php elseif ($casa['aprovado'] == 1): ?>
                                    <span class="badge badge-confirmada">Aprovada</span>
                                <?php else: ?>
                                    <span class="badge badge-cancelada">Rejeitada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                    <a href="../proprietario/editar_casa.php?id=<?php echo $casa['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if (!isset($casa['aprovado']) || $casa['aprovado'] == 0): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Aprovar esta casa?');">
                                            <input type="hidden" name="acao" value="aprovar">
                                            <input type="hidden" name="casa_id" value="<?php echo $casa['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>

                                        <button class="btn btn-sm btn-danger" onclick="mostrarRejeicao(<?php echo $casa['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Alternar destaque?');">
                                        <input type="hidden" name="acao" value="destacar">
                                        <input type="hidden" name="casa_id" value="<?php echo $casa['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    </form>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Alternar disponibilidade?');">
                                        <input type="hidden" name="acao" value="disponibilidade">
                                        <input type="hidden" name="casa_id" value="<?php echo $casa['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-toggle-<?php echo $casa['disponivel'] ? 'on' : 'off'; ?>"></i>
                                        </button>
                                    </form>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('ELIMINAR permanentemente?');">
                                        <input type="hidden" name="acao" value="eliminar">
                                        <input type="hidden" name="casa_id" value="<?php echo $casa['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($total_casas === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-home"></i>
                    <h4>Nenhuma casa encontrada</h4>
                    <p>Tente ajustar os filtros de pesquisa</p>
                </div>
            <?php endif; ?>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=<?php echo $pagina - 1; ?>&estado=<?php echo $filtro_estado; ?>&aprovacao=<?php echo $filtro_aprovacao; ?>&destaque=<?php echo $filtro_destaque; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                        <?php if ($i == $pagina): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?pagina=<?php echo $i; ?>&estado=<?php echo $filtro_estado; ?>&aprovacao=<?php echo $filtro_aprovacao; ?>&destaque=<?php echo $filtro_destaque; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina + 1; ?>&estado=<?php echo $filtro_estado; ?>&aprovacao=<?php echo $filtro_aprovacao; ?>&destaque=<?php echo $filtro_destaque; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de Rejeição -->
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
                    <label>Motivo da rejeição:</label>
                    <textarea name="motivo" class="form-control" rows="4" placeholder="Explique o motivo da rejeição..." required></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Rejeitar Casa</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../footer.php'; ?>
    
    <script src="../js/script.js"></script>
    <script>
        function mostrarRejeicao(casaId) {
            document.getElementById('rejeitarCasaId').value = casaId;
            document.getElementById('modalRejeicao').classList.add('active');
        }

        function fecharModal() {
            document.getElementById('modalRejeicao').classList.remove('active');
        }

        // Fechar modal ao clicar fora
        document.getElementById('modalRejeicao').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });

        // Exportar para CSV
        function exportTableToCSV(filename) {
            const csv = [];
            const rows = document.querySelectorAll("#casasTable tr");

            for (let i = 0; i < rows.length; i++) {
                const row = [],
                    cols = rows[i].querySelectorAll("td, th");

                for (let j = 0; j < cols.length - 1; j++) {
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                }

                csv.push(row.join(","));
            }

            downloadCSV(csv.join("\n"), filename);
        }

        function downloadCSV(csv, filename) {
            const csvFile = new Blob([csv], {
                type: "text/csv;charset=utf-8;"
            });
            const downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>

</html>