<?php
session_start();
require_once '../backend/db.php';

// Verificar se é proprietário
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] !== 'proprietario') {
    header("Location: ../backend/login.php");
    exit;
}

// Verificar se o ID da casa foi fornecido
if (!isset($_GET['id'])) {
    header("Location: minhas_casas.php");
    exit;
}

$casa_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Verificar se há mensagem de sucesso na URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = 'Casa criada com sucesso! Agora você pode editar os detalhes.';
}

// Buscar dados da casa
$stmt = $conn->prepare("SELECT * FROM casas WHERE id = ? AND proprietario_id = ?");
$stmt->bind_param("ii", $casa_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: minhas_casas.php");
    exit;
}

$casa = $result->fetch_assoc();

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar e sanitizar dados
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $morada = trim($_POST['morada']);
        $codigo_postal = trim($_POST['codigo_postal']);
        $cidade = trim($_POST['cidade']);
        $freguesia = trim($_POST['freguesia']);
        $tipo_propriedade = $_POST['tipo_propriedade'];
        $quartos = (int)$_POST['quartos'];
        $camas = (int)$_POST['camas'];
        $banheiros = (int)$_POST['banheiros'];
        $area = !empty($_POST['area']) ? (int)$_POST['area'] : null;
        $capacidade = (int)$_POST['capacidade'];
        $preco_noite = (float)$_POST['preco_noite'];
        $preco_limpeza = !empty($_POST['preco_limpeza']) ? (float)$_POST['preco_limpeza'] : 0;
        $taxa_seguranca = !empty($_POST['taxa_seguranca']) ? (float)$_POST['taxa_seguranca'] : 0;
        $minimo_noites = (int)$_POST['minimo_noites'];
        $maximo_noites = (int)$_POST['maximo_noites'];
        $hora_checkin = $_POST['hora_checkin'] ?? '15:00';
        $hora_checkout = $_POST['hora_checkout'] ?? '11:00';

        // Comodidades (array para JSON)
        $comodidades = isset($_POST['comodidades']) ? $_POST['comodidades'] : [];
        $comodidades_json = json_encode($comodidades);

        $regras = trim($_POST['regras']);

        // Validações básicas
        if (empty($titulo) || empty($morada) || empty($preco_noite)) {
            throw new Exception('Preencha todos os campos obrigatórios');
        }

        if ($preco_noite <= 0) {
            throw new Exception('Preço por noite deve ser maior que zero');
        }

        // Atualizar no banco
        $stmt = $conn->prepare("
            UPDATE casas SET
                titulo = ?, descricao = ?, morada = ?, codigo_postal = ?, cidade = ?, freguesia = ?,
                tipo_propriedade = ?, quartos = ?, camas = ?, banheiros = ?, area = ?, capacidade = ?,
                preco_noite = ?, preco_limpeza = ?, taxa_seguranca = ?, minimo_noites = ?, maximo_noites = ?,
                hora_checkin = ?, hora_checkout = ?, comodidades = ?, regras = ?, data_atualizacao = NOW()
            WHERE id = ? AND proprietario_id = ?
        ");

        $stmt->bind_param(
            "sssssssiiiiddddiissssii",
            $titulo,
            $descricao,
            $morada,
            $codigo_postal,
            $cidade,
            $freguesia,
            $tipo_propriedade,
            $quartos,
            $camas,
            $banheiros,
            $area,
            $capacidade,
            $preco_noite,
            $preco_limpeza,
            $taxa_seguranca,
            $minimo_noites,
            $maximo_noites,
            $hora_checkin,
            $hora_checkout,
            $comodidades_json,
            $regras,
            $casa_id,
            $user_id
        );

        if ($stmt->execute()) {
            $success = 'Casa atualizada com sucesso!';
            // Recarregar dados da casa
            $stmt = $conn->prepare("SELECT * FROM casas WHERE id = ? AND proprietario_id = ?");
            $stmt->bind_param("ii", $casa_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $casa = $result->fetch_assoc();
        } else {
            throw new Exception('Erro ao atualizar no banco de dados: ' . $conn->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Decodificar comodidades para o formulário
$comodidades_selecionadas = [];
if (!empty($casa['comodidades'])) {
    $comodidades_selecionadas = json_decode($casa['comodidades'], true);
    if (!is_array($comodidades_selecionadas)) {
        $comodidades_selecionadas = [];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Casa - AlugaTorres</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="form-container">
                <h2>Editar Casa</h2>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="property-form">
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Informações Básicas</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="titulo">Título da Casa *</label>
                                <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($casa['titulo']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" rows="4"><?php echo htmlspecialchars($casa['descricao']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Localização</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="morada">Morada *</label>
                                <input type="text" id="morada" name="morada" value="<?php echo htmlspecialchars($casa['morada']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="codigo_postal">Código Postal</label>
                                <input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo htmlspecialchars($casa['codigo_postal']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($casa['cidade']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="freguesia">Freguesia</label>
                                <input type="text" id="freguesia" name="freguesia" value="<?php echo htmlspecialchars($casa['freguesia']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-home"></i> Detalhes da Propriedade</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="tipo_propriedade">Tipo de Propriedade</label>
                                <select id="tipo_propriedade" name="tipo_propriedade">
                                    <option value="apartamento" <?php echo $casa['tipo_propriedade'] === 'apartamento' ? 'selected' : ''; ?>>Apartamento</option>
                                    <option value="casa" <?php echo $casa['tipo_propriedade'] === 'casa' ? 'selected' : ''; ?>>Casa</option>
                                    <option value="moradia" <?php echo $casa['tipo_propriedade'] === 'moradia' ? 'selected' : ''; ?>>Moradia</option>
                                    <option value="quinta" <?php echo $casa['tipo_propriedade'] === 'quinta' ? 'selected' : ''; ?>>Quinta</option>
                                    <option value="outro" <?php echo $casa['tipo_propriedade'] === 'outro' ? 'selected' : ''; ?>>Outro</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="quartos">Quartos</label>
                                <input type="number" id="quartos" name="quartos" min="1" value="<?php echo $casa['quartos']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="camas">Camas</label>
                                <input type="number" id="camas" name="camas" min="1" value="<?php echo $casa['camas']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="banheiros">Banheiros</label>
                                <input type="number" id="banheiros" name="banheiros" min="1" value="<?php echo $casa['banheiros']; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="area">Área (m²)</label>
                                <input type="number" id="area" name="area" min="1" value="<?php echo $casa['area']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="capacidade">Capacidade Máxima</label>
                                <input type="number" id="capacidade" name="capacidade" min="1" value="<?php echo $casa['capacidade']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-euro-sign"></i> Preços e Condições</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="preco_noite">Preço por Noite (€) *</label>
                                <input type="number" id="preco_noite" name="preco_noite" min="0" step="0.01" value="<?php echo $casa['preco_noite']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="preco_limpeza">Taxa de Limpeza (€)</label>
                                <input type="number" id="preco_limpeza" name="preco_limpeza" min="0" step="0.01" value="<?php echo $casa['preco_limpeza']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="taxa_seguranca">Taxa de Segurança (€)</label>
                                <input type="number" id="taxa_seguranca" name="taxa_seguranca" min="0" step="0.01" value="<?php echo $casa['taxa_seguranca']; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="minimo_noites">Mínimo de Noites</label>
                                <input type="number" id="minimo_noites" name="minimo_noites" min="1" value="<?php echo $casa['minimo_noites']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="maximo_noites">Máximo de Noites</label>
                                <input type="number" id="maximo_noites" name="maximo_noites" min="1" value="<?php echo $casa['maximo_noites']; ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="hora_checkin">Hora de Check-in</label>
                                <input type="time" id="hora_checkin" name="hora_checkin" value="<?php echo $casa['hora_checkin']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="hora_checkout">Hora de Check-out</label>
                                <input type="time" id="hora_checkout" name="hora_checkout" value="<?php echo $casa['hora_checkout']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-concierge-bell"></i> Comodidades</h3>
                        <div class="amenities-grid">
                            <?php
                            $amenities = [
                                'wifi' => 'Wi-Fi',
                                'estacionamento' => 'Estacionamento',
                                'piscina' => 'Piscina',
                                'jacuzzi' => 'Jacuzzi',
                                'ar_condicionado' => 'Ar Condicionado',
                                'aquecimento' => 'Aquecimento',
                                'cozinha' => 'Cozinha Equipada',
                                'maquina_lavar' => 'Máquina de Lavar',
                                'secadora' => 'Secadora',
                                'ferro_tacos' => 'Ferro de Engomar',
                                'televisao' => 'Televisão',
                                'netflix' => 'Netflix',
                                'animais' => 'Aceita Animais',
                                'fumadores' => 'Permitido Fumar',
                                'elevador' => 'Elevador',
                                'acesso_deficientes' => 'Acesso para Deficientes',
                                'vista_mar' => 'Vista para o Mar',
                                'varanda' => 'Varanda',
                                'churrasqueira' => 'Churrasqueira',
                                'ginasio' => 'Ginásio',
                                'spa' => 'SPA'
                            ];

                            foreach ($amenities as $key => $label) {
                                $checked = in_array($key, $comodidades_selecionadas) ? 'checked' : '';
                                echo "<label class='amenity-item'>
                                        <input type='checkbox' name='comodidades[]' value='$key' $checked>
                                        <span>$label</span>
                                      </label>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-rules"></i> Regras da Casa</h3>
                        <div class="form-group">
                            <label for="regras">Regras e Condições</label>
                            <textarea id="regras" name="regras" rows="4" placeholder="Ex: Não são permitidos animais, festas, etc."><?php echo htmlspecialchars($casa['regras']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Atualizar Casa
                        </button>
                        <a href="minhas_casas.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include '../footer.php'; ?>

    <script src="../backend/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileToggle = document.getElementById('profile-toggle');
            const profileDropdown = document.getElementById('profile-dropdown');
            if (profileToggle && profileDropdown) {
                profileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!profileToggle.contains(e.target) && !profileDropdown.contains(e.target)) {
                        profileDropdown.classList.remove('active');
                    }
                });
            }
        });
    </script>

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
</body>

</html>