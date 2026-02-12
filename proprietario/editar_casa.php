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
$casa_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Verificar se a casa pertence ao proprietário
$stmt = $conn->prepare("SELECT * FROM casas WHERE id = ? AND proprietario_id = ?");
$stmt->bind_param("ii", $casa_id, $user_id);
$stmt->execute();
$casa = $stmt->get_result()->fetch_assoc();

if (!$casa) {
    header("Location: minhas_casas.php");
    exit;
}

// Processar formulário
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
        $disponivel = isset($_POST['disponivel']) ? 1 : 0;
        $destaque = isset($_POST['destaque']) ? 1 : 0;

        // Comodidades (array para JSON)
        $comodidades = isset($_POST['comodidades']) ? $_POST['comodidades'] : [];
        $comodidades_json = json_encode($comodidades);

        $regras = trim($_POST['regras']);

        // Validações básicas
        if (empty($titulo) || empty($morada) || empty($preco_noite)) {
            throw new \Exception('Preencha todos os campos obrigatórios');
        }

        if ($preco_noite <= 0) {
            throw new \Exception('Preço por noite deve ser maior que zero');
        }

        // Atualizar no banco
        $stmt = $conn->prepare("
            UPDATE casas SET
                titulo = ?, descricao = ?, morada = ?, codigo_postal = ?, cidade = ?, freguesia = ?,
                tipo_propriedade = ?, quartos = ?, camas = ?, banheiros = ?, area = ?, capacidade = ?,
                preco_noite = ?, preco_limpeza = ?, taxa_seguranca = ?, minimo_noites = ?, maximo_noites = ?,
                hora_checkin = ?, hora_checkout = ?, comodidades = ?, regras = ?, disponivel = ?, destaque = ?
            WHERE id = ? AND proprietario_id = ?
        ");

        $stmt->bind_param(
            "sssssssiiiiddddiissssiii",
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
            $disponivel,
            $destaque,
            $casa_id,
            $user_id
        );

        if ($stmt->execute()) {
            $success = 'Casa atualizada com sucesso!';
            // Recarregar dados da casa
            $stmt = $conn->prepare("SELECT * FROM casas WHERE id = ? AND proprietario_id = ?");
            $stmt->bind_param("ii", $casa_id, $user_id);
            $stmt->execute();
            $casa = $stmt->get_result()->fetch_assoc();
        } else {
            throw new \Exception('Erro ao atualizar no banco de dados: ' . $conn->error);
        }
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

// Decodificar comodidades
$comodidades_atuais = json_decode($casa['comodidades'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Editar Casa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
</head>

<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <div class="form-container">
        <div class="form-header">
            <h1 class="form-title">Editar Propriedade</h1>
            <p class="form-subtitle">Atualize os detalhes da sua propriedade</p>
        </div>

        <?php if ($error): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="casa-form">
            <!-- Seção 1: Informações Básicas -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-info-circle"></i> Informações Básicas</h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Título da Propriedade <span class="required">*</span></label>
                        <input type="text" name="titulo" class="form-control" required
                            value="<?php echo htmlspecialchars($casa['titulo']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Tipo de Propriedade <span class="required">*</span></label>
                        <select name="tipo_propriedade" class="form-control" required>
                            <option value="casa" <?php echo $casa['tipo_propriedade'] == 'casa' ? 'selected' : ''; ?>>Casa</option>
                            <option value="apartamento" <?php echo $casa['tipo_propriedade'] == 'apartamento' ? 'selected' : ''; ?>>Apartamento</option>
                            <option value="vivenda" <?php echo $casa['tipo_propriedade'] == 'vivenda' ? 'selected' : ''; ?>>Vivenda</option>
                            <option value="quinta" <?php echo $casa['tipo_propriedade'] == 'quinta' ? 'selected' : ''; ?>>Quinta</option>
                            <option value="outro" <?php echo $casa['tipo_propriedade'] == 'outro' ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição <span class="required">*</span></label>
                    <textarea name="descricao" class="form-control" required rows="4"><?php echo htmlspecialchars($casa['descricao']); ?></textarea>
                </div>
            </div>

            <!-- Seção 2: Localização -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> Localização</h2>

                <div class="form-group">
                    <label>Morada Completa <span class="required">*</span></label>
                    <input type="text" name="morada" class="form-control" required
                        value="<?php echo htmlspecialchars($casa['morada']); ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Código Postal <span class="required">*</span></label>
                        <input type="text" name="codigo_postal" class="form-control"
                            value="<?php echo htmlspecialchars($casa['codigo_postal']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Cidade <span class="required">*</span></label>
                        <input type="text" name="cidade" class="form-control" required
                            value="<?php echo htmlspecialchars($casa['cidade']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Freguesia</label>
                        <input type="text" name="freguesia" class="form-control"
                            value="<?php echo htmlspecialchars($casa['freguesia']); ?>">
                    </div>
                </div>
            </div>

            <!-- Seção 3: Detalhes da Propriedade -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-home"></i> Detalhes da Propriedade</h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Quartos <span class="required">*</span></label>
                        <input type="number" name="quartos" class="form-control" required min="1" max="20"
                            value="<?php echo $casa['quartos']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Camas <span class="required">*</span></label>
                        <input type="number" name="camas" class="form-control" required min="1" max="50"
                            value="<?php echo $casa['camas']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Banheiros <span class="required">*</span></label>
                        <input type="number" name="banheiros" class="form-control" required min="1" max="20"
                            value="<?php echo $casa['banheiros']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Área (m²)</label>
                        <input type="number" name="area" class="form-control" min="1"
                            value="<?php echo $casa['area']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Capacidade (hóspedes) <span class="required">*</span></label>
                        <input type="number" name="capacidade" class="form-control" required min="1" max="100"
                            value="<?php echo $casa['capacidade']; ?>">
                    </div>
                </div>
            </div>

            <!-- Seção 4: Preços e Regras -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-euro-sign"></i> Preços e Regras</h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Preço por Noite (€) <span class="required">*</span></label>
                        <input type="number" name="preco_noite" class="form-control" required min="1" step="0.01"
                            value="<?php echo $casa['preco_noite']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Taxa de Limpeza (€)</label>
                        <input type="number" name="preco_limpeza" class="form-control" min="0" step="0.01"
                            value="<?php echo $casa['preco_limpeza']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Taxa de Segurança (€)</label>
                        <input type="number" name="taxa_seguranca" class="form-control" min="0" step="0.01"
                            value="<?php echo $casa['taxa_seguranca']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Mínimo de Noites <span class="required">*</span></label>
                        <input type="number" name="minimo_noites" class="form-control" required min="1"
                            value="<?php echo $casa['minimo_noites']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Máximo de Noites</label>
                        <input type="number" name="maximo_noites" class="form-control" min="1"
                            value="<?php echo $casa['maximo_noites']; ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Hora de Check-in <span class="required">*</span></label>
                        <input type="time" name="hora_checkin" class="form-control" required
                            value="<?php echo $casa['hora_checkin']; ?>">
                    </div>

                    <div class="form-group">
                        <label>Hora de Check-out <span class="required">*</span></label>
                        <input type="time" name="hora_checkout" class="form-control" required
                            value="<?php echo $casa['hora_checkout']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Regras da Casa</label>
                    <textarea name="regras" class="form-control" rows="3"><?php echo htmlspecialchars($casa['regras']); ?></textarea>
                </div>
            </div>

            <!-- Seção 5: Comodidades -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-star"></i> Comodidades</h2>

                <p>Selecione as comodidades disponíveis na sua propriedade:</p>

                <div class="checkbox-grid">
                    <?php
                    $comodidades = [
                        'wifi' => 'Wi-Fi',
                        'tv' => 'TV',
                        'ar_condicionado' => 'Ar Condicionado',
                        'aquecimento' => 'Aquecimento',
                        'cozinha' => 'Cozinha Equipada',
                        'frigorifico' => 'Frigorífico',
                        'microondas' => 'Microondas',
                        'maquina_lavar' => 'Máquina de Lavar',
                        'secador' => 'Secador de Cabelo',
                        'ferro' => 'Ferro de Engomar',
                        'estacionamento' => 'Estacionamento Gratuito',
                        'piscina' => 'Piscina',
                        'jardim' => 'Jardim',
                        'varanda' => 'Varanda',
                        'churrasqueira' => 'Churrasqueira',
                        'acesso_cadeira_rodas' => 'Acesso para Cadeira de Rodas',
                        'elevador' => 'Elevador',
                        'aquecedor' => 'Aquecedor',
                        'ventilador' => 'Ventilador',
                        'cacifos' => 'Cacifos/Bagageira'
                    ];

                    foreach ($comodidades as $value => $label):
                        $checked = in_array($value, $comodidades_atuais) ? 'checked' : '';
                    ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="comodidades[]" value="<?php echo $value; ?>" <?php echo $checked; ?>>
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Seção 6: Configurações -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-cog"></i> Configurações</h2>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="disponivel" <?php echo $casa['disponivel'] ? 'checked' : ''; ?>>
                        <span>Disponível para reservas</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="destaque" <?php echo $casa['destaque'] ? 'checked' : ''; ?>>
                        <span>Destacar na página inicial</span>
                    </label>
                </div>
            </div>

            <!-- Ações do Formulário -->
            <div class="form-actions">
                <a href="minhas_casas.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    <?php include '../footer.php'; ?>

    <script src="../backend/script.js"></script>
</body>

</html>