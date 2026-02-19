<?php
session_start();
require_once '../backend/db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['tipo_utilizador'] !== 'proprietario') {
    header("Location: ../backend/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$casa_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = isset($_GET['success']);

// Obter dados da casa
$stmt = $conn->prepare("SELECT * FROM casas WHERE id = ? AND proprietario_id = ?");
$stmt->bind_param("ii", $casa_id, $user_id);
$stmt->execute();
$casa = $stmt->get_result()->fetch_assoc();

if (!$casa) {
    header("Location: ../dashboard.php");
    exit;
}

// Função helper para manter valores antigos ou do post
function old($campo, $casa)
{
    return isset($_POST[$campo])
        ? htmlspecialchars($_POST[$campo])
        : htmlspecialchars($casa[$campo] ?? '');
}

// Comodidades atuais

$comodidades_atuais = isset($_POST['comodidades'])
    ? $_POST['comodidades']
    : json_decode($casa['comodidades'], true);

// Horários
$hora_checkin = isset($_POST['hora_checkin']) ? $_POST['hora_checkin'] : $casa['hora_checkin'];
$hora_checkout = isset($_POST['hora_checkout']) ? $_POST['hora_checkout'] : $casa['hora_checkout'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Campos principais

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
        $hora_checkin = $_POST['hora_checkin'];
        $hora_checkout = $_POST['hora_checkout'];
        $comodidades = isset($_POST['comodidades']) ? $_POST['comodidades'] : [];
        $comodidades_json = json_encode($comodidades);
        $regras = trim($_POST['regras']);

        // Validações simples
        if (empty($titulo) || empty($morada) || $preco_noite <= 0) {
            throw new \Exception('Preencha todos os campos obrigatórios e preço deve ser maior que 0.');
        }

        // Atualizar no banco
        $stmt = $conn->prepare("
            UPDATE casas SET
                titulo=?, descricao=?, morada=?, codigo_postal=?, cidade=?, freguesia=?,
                tipo_propriedade=?, quartos=?, camas=?, banheiros=?, area=?, capacidade=?,
                preco_noite=?, preco_limpeza=?, taxa_seguranca=?, minimo_noites=?, maximo_noites=?,
                hora_checkin=?, hora_checkout=?, comodidades=?, regras=?
            WHERE id=? AND proprietario_id=?
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

        $stmt->execute();

        header("Location: editar_casa.php?id=$casa_id&success=1");

        exit;
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlugaTorres | Editar Casa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style/style.css">
    <link rel="website icon" type="png" href="../style/img/Logo_AlugaTorres_branco.png">
</head>


<body>
    <?php include '../header.php'; ?>
    <?php include '../sidebar.php'; ?>

    <div class="form-container-casa">
        <div class="form-header">
            <h1 class="form-title">Editar Casa</h1>
            <p class="form-subtitle">Atualize as informações da sua propriedade</p>
        </div>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success">
                Casa atualizada com sucesso!
                <div style="margin-top: 10px;">
                    <a href="../perfil.php" class="btn-save" onclick="localStorage.setItem('atualizarStats', 'true');">
                        <i class="fas fa-chart-line"></i> Ver Estatísticas Atualizadas
                    </a>
                </div>
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
                            placeholder="Ex: Encantadora casa no centro de Torres Novas"
                            value="<?php echo old('titulo', $casa); ?>">
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
                        <div id="campo-outro" class="hidden-outro" style="margin-top: 10px; <?php echo $casa['tipo_propriedade'] == 'outro' ? 'display: block;' : 'display: none;'; ?>">
                            <input type="text" name="outro_texto" placeholder="Especifique outro tipo de propriedade" class="form-control"
                                value="<?php echo old('outro_texto', $casa); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição <span class="required">*</span></label>
                    <textarea name="descricao" class="form-control" required rows="4"
                        placeholder="Descreva a sua propriedade, localização, características únicas..."><?php echo old('descricao', $casa); ?></textarea>
                </div>
            </div>


            <!-- Seção 2: Localização -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> Localização</h2>

                <div class="form-group">
                    <label>Morada Completa <span class="required">*</span></label>
                    <input type="text" name="morada" class="form-control" required
                        placeholder="Ex: Rua Principal, 123"
                        value="<?php echo old('morada', $casa); ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Código Postal <span class="required">*</span></label>
                        <input type="text" name="codigo_postal" class="form-control"
                            placeholder="Ex: 2350-000"
                            value="<?php echo old('codigo_postal', $casa); ?>">
                    </div>

                    <div class="form-group">
                        <label>Cidade <span class="required">*</span></label>
                        <input type="text" name="cidade" class="form-control" required
                            value="Torres Novas" readonly>
                    </div>

                    <div class="form-group">
                        <label>Freguesia</label>
                        <select name="freguesia" class="form-control">
                            <option value="assentiz" <?php echo $casa['freguesia'] == 'assentiz' ? 'selected' : ''; ?>>Assentiz</option>
                            <option value="chancelaria" <?php echo $casa['freguesia'] == 'chancelaria' ? 'selected' : ''; ?>>Chancelaria</option>
                            <option value="meia-via" <?php echo $casa['freguesia'] == 'meia-via' ? 'selected' : ''; ?>>Meia Via</option>
                            <option value="pedrogao" <?php echo $casa['freguesia'] == 'pedrogao' ? 'selected' : ''; ?>>Pedrógão</option>
                            <option value="riachos" <?php echo $casa['freguesia'] == 'riachos' ? 'selected' : ''; ?>>Riachos</option>
                            <option value="UF-brogueira-Parceiros-Alcorochel" <?php echo $casa['freguesia'] == 'UF-brogueira-Parceiros-Alcorochel' ? 'selected' : ''; ?>>Brogueira/Parceiros/Alcorochel</option>
                            <option value="UF-olaia-paco" <?php echo $casa['freguesia'] == 'UF-olaia-paco' ? 'selected' : ''; ?>>Olaia/Paço</option>
                            <option value="UFT-santamaria-salvador-santiago" <?php echo $casa['freguesia'] == 'UFT-santamaria-salvador-santiago' ? 'selected' : ''; ?>>Santa Maria/Salvador/Santiago</option>
                            <option value="UFT-saopedro-lapas-ribeirab" <?php echo $casa['freguesia'] == 'UFT-saopedro-lapas-ribeirab' ? 'selected' : ''; ?>>São Pedro/Lapas/Ribeira Branca</option>
                            <option value="UF-zibreira" <?php echo $casa['freguesia'] == 'UF-zibreira' ? 'selected' : ''; ?>>Zibreira</option>
                        </select>
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
                            value="<?php echo old('quartos', $casa); ?>">
                    </div>

                    <div class="form-group">
                        <label>Camas <span class="required">*</span></label>
                        <input type="number" name="camas" class="form-control" required min="1" max="50"
                            value="<?php echo old('camas', $casa); ?>">
                    </div>

                    <div class="form-group">
                        <label>Banheiros <span class="required">*</span></label>
                        <input type="number" name="banheiros" class="form-control" required min="1" max="20"
                            value="<?php echo old('banheiros', $casa); ?>">
                    </div>

                    <div class="form-group">
                        <label>Área (m²)</label>
                        <input type="number" name="area" class="form-control" min="1"
                            value="<?php echo old('area', $casa); ?>">
                    </div>

                    <div class="form-group">
                        <label>Capacidade (hóspedes) <span class="required">*</span></label>
                        <input type="number" name="capacidade" class="form-control" required min="1" max="100"
                            value="<?php echo old('capacidade', $casa); ?>">
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
                            value="<?php echo old('preco_noite', $casa); ?>">
                    </div>

                    <div class="form-group">
                        <label>Taxa de Limpeza (€)</label>
                        <input type="number" name="preco_limpeza" class="form-control" min="0" step="0.01"
                            value="<?php echo old('preco_limpeza', $casa); ?>">
                    </div>

                    <div class="form-group">
                        <label>Taxa de Segurança (€)</label>
                        <input type="number" name="taxa_seguranca" class="form-control" min="0" step="0.01"
                            value="<?php echo old('taxa_seguranca', $casa); ?>">
                    </div>

                    <div class="form-group">
                        <label>Mínimo de Noites <span class="required">*</span></label>
                        <input type="number" name="minimo_noites" class="form-control" required min="1"
                            value="<?php echo old('minimo_noites', $casa); ?>">
                    </div>

                    <div class="form-group">
                        <label>Máximo de Noites</label>
                        <input type="number" name="maximo_noites" class="form-control" min="1"
                            value="<?php echo old('maximo_noites', $casa); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Hora de Check-in <span class="required">*</span></label>
                        <div style="display: flex; gap: 10px;">
                            <select name="hora_checkin_hora" class="form-control" required style="flex: 1;">
                                <?php
                                list($hora_sel, $min_sel) = explode(':', $hora_checkin);
                                for ($hora = 0; $hora < 24; $hora++) {
                                    $selected = ($hora == (int)$hora_sel) ? 'selected' : '';
                                    echo "<option value=\"$hora\" $selected>" . sprintf('%02d', $hora) . "</option>";
                                }
                                ?>
                            </select>
                            <span style="align-self: center;">:</span>
                            <select name="hora_checkin_minuto" class="form-control" required style="flex: 1;">
                                <?php
                                for ($min = 0; $min < 60; $min++) {
                                    $min_formatado = sprintf('%02d', $min);
                                    $selected = ($min_formatado == $min_sel) ? 'selected' : '';
                                    echo "<option value=\"$min_formatado\" $selected>$min_formatado</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <input type="hidden" name="hora_checkin" id="hora_checkin_hidden" value="<?php echo $hora_checkin; ?>">
                    </div>

                    <div class="form-group">
                        <label>Hora de Check-out <span class="required">*</span></label>
                        <div style="display: flex; gap: 10px;">
                            <select name="hora_checkout_hora" class="form-control" required style="flex: 1;">
                                <?php
                                list($hora_sel_out, $min_sel_out) = explode(':', $hora_checkout);
                                for ($hora = 0; $hora < 24; $hora++) {
                                    $selected = ($hora == (int)$hora_sel_out) ? 'selected' : '';
                                    echo "<option value=\"$hora\" $selected>" . sprintf('%02d', $hora) . "</option>";
                                }
                                ?>
                            </select>
                            <span style="align-self: center;">:</span>
                            <select name="hora_checkout_minuto" class="form-control" required style="flex: 1;">
                                <?php
                                for ($min = 0; $min < 60; $min++) {
                                    $min_formatado = sprintf('%02d', $min);
                                    $selected = ($min_formatado == $min_sel_out) ? 'selected' : '';
                                    echo "<option value=\"$min_formatado\" $selected>$min_formatado</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <input type="hidden" name="hora_checkout" id="hora_checkout_hidden" value="<?php echo $hora_checkout; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Regras da Casa</label>
                    <textarea name="regras" class="form-control" rows="3"
                        placeholder="Ex: Não são permitidos animais, Proibido fumar, Silêncio após as 22h..."><?php echo old('regras', $casa); ?></textarea>
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


            <!-- Ações do Formulário -->
            <div class="form-actions">
                <a href="../dashboard.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Guardar Alterações
                </button>
            </div>


        </form>
    </div>

    <?php include '../footer.php'; ?>

    <script src="../js/script.js"></script>

    <script>
        // Mostrar/ocultar campo "outro" tipo de propriedade
        document.querySelector('select[name="tipo_propriedade"]').addEventListener('change', function() {
            const campoOutro = document.getElementById('campo-outro');
            if (this.value === 'outro') {
                campoOutro.style.display = 'block';
            } else {
                campoOutro.style.display = 'none';
            }
        });

        document.addEventListener('DOMContentLoaded', function() {

            // Função para combinar hora e minuto nos campos hidden
            function updateTimeFields() {
                // Check-in
                const checkinHora = document.querySelector('select[name="hora_checkin_hora"]').value;
                const checkinMinuto = document.querySelector('select[name="hora_checkin_minuto"]').value;
                const checkinHidden = document.getElementById('hora_checkin_hidden');
                checkinHidden.value = checkinHora.padStart(2, '0') + ':' + checkinMinuto;

                // Check-out
                const checkoutHora = document.querySelector('select[name="hora_checkout_hora"]').value;
                const checkoutMinuto = document.querySelector('select[name="hora_checkout_minuto"]').value;
                const checkoutHidden = document.getElementById('hora_checkout_hidden');
                checkoutHidden.value = checkoutHora.padStart(2, '0') + ':' + checkoutMinuto;
            }

            // Adicionar event listeners aos selects de hora
            document.querySelectorAll('select[name*="hora_checkin"], select[name*="hora_checkout"]').forEach(select => {
                select.addEventListener('change', updateTimeFields);
            });

            // Inicializar valores
            updateTimeFields();
        });
    </script>
</body>

</html>
