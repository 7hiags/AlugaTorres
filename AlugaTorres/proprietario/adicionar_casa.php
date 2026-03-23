<?php

require_once __DIR__ . '/../root/init.php';

// Verificar se é proprietário (acesso restrito)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo_utilizador']) || $_SESSION['tipo_utilizador'] !== 'proprietario') {
    header("Location: ../backend/autenticacao/login.php");
    exit;
}

// Verificar se o usuário ainda existe na base de dados
$stmt = $conn->prepare("SELECT id FROM utilizadores WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    session_destroy();
    header("Location: ../backend/autenticacao/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $morada = trim($_POST['morada']);
        $codigo_postal = trim($_POST['codigo_postal']);
        $cidade = trim($_POST['cidade']);
        $freguesia = trim($_POST['freguesia']);
        $tipo_propriedade = $_POST['tipo_propriedade'];
        $custom_tipo = ($tipo_propriedade === 'outro') ? trim($_POST['outro_texto'] ?? '') : null;
        $quartos = (int)$_POST['quartos'];
        $camas = (int)$_POST['camas'];
        $casas_de_banho = (int)$_POST['casas_de_banho'];
        $area = !empty($_POST['area']) ? (int)$_POST['area'] : null;
        $capacidade = (int)$_POST['capacidade'];
        $preco_noite = (float)$_POST['preco_noite'];
        $preco_limpeza = !empty($_POST['preco_limpeza']) ? (float)$_POST['preco_limpeza'] : 0;
        $taxa_seguranca = !empty($_POST['taxa_seguranca']) ? (float)$_POST['taxa_seguranca'] : 0;
        $minimo_noites = (int)$_POST['minimo_noites'];
        $maximo_noites = (int)$_POST['maximo_noites'];
        $hora_checkin = $_POST['hora_checkin'] ?? '15:00';
        $hora_checkout = $_POST['hora_checkout'] ?? '11:00';
        $regras = trim($_POST['regras']);
        $aprovado = 0;

        // Comodidades
        $comodidades = isset($_POST['comodidades']) ? $_POST['comodidades'] : [];
        $comodidades_json = json_encode($comodidades);


        // Processar fotos
        $fotos = [];
        $foto_referencia = '';
        if (!empty($_FILES['fotos']['name'][0])) {
            $upload_dir = __DIR__ . '/../assets/uploads/casas/';

            // Criar diretório se não existir
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            foreach ($_FILES['fotos']['name'] as $key => $filename) {
                if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['fotos']['type'][$key];

                    if (in_array($file_type, $allowed_types)) {
                        // Gerar nome único
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $novo_nome = uniqid('casa_') . '_' . time() . '_' . $key . '.' . $ext;
                        $caminho = $upload_dir . $novo_nome;
                        if (move_uploaded_file($_FILES['fotos']['tmp_name'][$key], $caminho)) {
                            $fotos[] = 'assets/uploads/casas/' . $novo_nome;
                        }
                    }
                }
            }
            // Primeira foto é a referência
            if (!empty($fotos)) {
                $foto_referencia = $fotos[0];
            }
        }
        $fotos_json = json_encode($fotos);

        if (empty($titulo) || empty($morada) || empty($preco_noite)) {
            throw new \Exception('Preencha todos os campos obrigatórios');
        }

        if ($preco_noite <= 0) {
            throw new \Exception('Preço por noite deve ser maior que zero');
        }

        $stmt = $conn->prepare("
            INSERT INTO casas (
                proprietario_id, titulo, descricao, morada, codigo_postal, cidade, freguesia,
                tipo_propriedade, custom_tipo, quartos, camas, casas_de_banho, area, capacidade,
                preco_noite, preco_limpeza, taxa_seguranca, minimo_noites, maximo_noites,
                hora_checkin, hora_checkout, comodidades, regras, fotos, aprovado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "isssssssiisiddddiissssssi",
            $user_id,
            $titulo,
            $descricao,
            $morada,
            $codigo_postal,
            $cidade,
            $freguesia,
            $tipo_propriedade,
            $custom_tipo,
            $quartos,
            $camas,
            $casas_de_banho,
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
            $fotos_json,
            $aprovado
        );

        if ($stmt->execute()) {
            $casa_id = $stmt->insert_id;
            $success = 'Casa enviada para aprovação do administrador! Aguarde confirmação.';
            // Redireciona para a página de edição com mensagem de sucesso
            header("Location: editar_casa.php?id=$casa_id&success=1");
            exit;
        } else {
            throw new \Exception('Erro ao salvar no banco de dados: ' . $conn->error);
        }

        // ============================================
        // Tratamento de Exceções
        // ============================================
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php
$pageTitle = 'AlugaTorres | Adicionar Casa';
require_once __DIR__ . '/../root/head.php';
include '../root/header.php';
include '../root/sidebar.php';
?>

<body>
    <div class="form-container-casa">

        <!-- ========================================
             Cabeçalho do Formulário
             ======================================== -->
        <div class="form-header">
            <h1 class="form-title">Adicionar Nova Casa</h1>
            <p class="form-subtitle">Preencha os detalhes da sua propriedade para começar a receber reservas</p>
        </div>

        <!-- Mensagens de Feedback (Toast) -->
        <?php if ($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.error(<?php echo json_encode(htmlspecialchars($error)); ?>);
                    }
                });
            </script>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.success('Casa adicionada com sucesso!', 5000);
                    }
                });
            </script>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
                <div style="margin-top: 10px;">
                    <a href="../root/perfil.php" class="btn-save" onclick="localStorage.setItem('atualizarStats', 'true');">
                        <i class="fas fa-chart-line"></i> Ver Estatísticas Atualizadas
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ========================================
             Formulário de Adicionar Casa
             ======================================== -->
        <form method="POST" class="casa-form" enctype="multipart/form-data">

            <!-- ========================================
                 Seção 1: Informações Básicas
                 ======================================== -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-info-circle"></i> Informações Básicas</h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Título da Propriedade <span class="required">*</span></label>
                        <input type="text" name="titulo" class="form-control" required
                            placeholder="Ex: Encantadora casa no centro de Torres Novas"
                            value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Tipo de Propriedade <span class="required">*</span></label>
                        <select name="tipo_propriedade" class="form-control" required>
                            <option value="casa" <?php echo (isset($_POST['tipo_propriedade']) && $_POST['tipo_propriedade'] == 'casa') ? 'selected' : ''; ?>>Casa</option>
                            <option value="apartamento" <?php echo (isset($_POST['tipo_propriedade']) && $_POST['tipo_propriedade'] == 'apartamento') ? 'selected' : ''; ?>>Apartamento</option>
                            <option value="vivenda" <?php echo (isset($_POST['tipo_propriedade']) && $_POST['tipo_propriedade'] == 'vivenda') ? 'selected' : ''; ?>>Vivenda</option>
                            <option value="quinta" <?php echo (isset($_POST['tipo_propriedade']) && $_POST['tipo_propriedade'] == 'quinta') ? 'selected' : ''; ?>>Quinta</option>
                            <option value="outro" <?php echo (isset($_POST['tipo_propriedade']) && $_POST['tipo_propriedade'] == 'outro') ? 'selected' : ''; ?>>Outro</option>
                        </select>
                        <div id="campo-outro" class="hidden-outro" style="margin-top: 10px;">
                            <input type="text" name="outro_texto" placeholder="Especifique outro tipo de propriedade" class="form-control"
                                value="<?php echo isset($_POST['outro_texto']) ? htmlspecialchars($_POST['outro_texto']) : ''; ?>" required>
                            <span class="required">Campo obrigatório</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição <span class="required">*</span></label>
                    <textarea name="descricao" class="form-control" required rows="4"
                        placeholder="Descreva a sua propriedade, localização, características únicas..."><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                </div>

                <!-- Fotos da Casa -->
                <div class="form-group">
                    <label>Fotos da Propriedade</label>
                    <p style="font-size: 0.9em; color: #666; margin-bottom: 10px;">Adicione várias fotos da sua propriedade. A primeira foto será usada como imagem de capa.</p>
                    <input type="file" name="fotos[]" class="form-control" multiple accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color: #666;">Formatos aceitos: JPEG, PNG, GIF, WebP. Múltiplos arquivos permitidos.</small>
                </div>
            </div>

            <!-- ========================================
                 Seção 2: Localização
                 ======================================== -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> Localização</h2>

                <div class="form-group">
                    <label>Morada Completa <span class="required">*</span></label>
                    <input type="text" name="morada" class="form-control" required
                        placeholder="Ex: Rua Principal, 123"
                        value="<?php echo isset($_POST['morada']) ? htmlspecialchars($_POST['morada']) : ''; ?>">
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Código Postal <span class="required">*</span></label>
                        <input type="text" name="codigo_postal" class="form-control"
                            placeholder="Ex: 2350-000"
                            value="<?php echo isset($_POST['codigo_postal']) ? htmlspecialchars($_POST['codigo_postal']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Cidade <span class="required">*</span></label>
                        <input type="text" name="cidade" class="form-control" required
                            value="Torres Novas" readonly
                            value="<?php echo isset($_POST['cidade']) ? htmlspecialchars($_POST['cidade']) : 'Torres Novas'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Freguesia</label>
                        <select type="text" name="freguesia" class="form-control"
                            placeholder="Ex: Santa Maria"
                            value="<?php echo isset($_POST['freguesia']) ? htmlspecialchars($_POST['freguesia']) : ''; ?>">
                            <option value="assentiz" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'assentiz') ? 'selected' : ''; ?>>Assentiz</option>
                            <option value="chancelaria" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'chancelaria') ? 'selected' : ''; ?>>Chancelaria</option>
                            <option value="meia-via" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'meia-via') ? 'selected' : ''; ?>>Meia Via</option>
                            <option value="pedrogao" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'pedrogao') ? 'selected' : ''; ?>>Pedrógão</option>
                            <option value="riachos" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'riachos') ? 'selected' : ''; ?>>Riachos</option>
                            <option value="UF-brogueira-Parceiros-Alcorochel" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'UF-brogueira-Parceiros-Alcorochel') ? 'selected' : ''; ?>>Brogueira/Parceiros/Alcorochel</option>
                            <option value="UF-olaia-paco" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'UF-olaia-paco') ? 'selected' : ''; ?>>Olaia/Paço</option>
                            <option value="UFT-santamaria-salvador-santiago" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'UFT-santamaria-salvador-santiago') ? 'selected' : ''; ?>>Santa Maria/Salvador/Santiago</option>
                            <option value="UFT-saopedro-lapas-ribeirab" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'UFT-saopedro-lapas-ribeirab') ? 'selected' : ''; ?>>São Pedro/Lapas/Ribeira Branca</option>
                            <option value="UF-zibreira" <?php echo (isset($_POST['freguesia']) && $_POST['freguesia'] == 'UF-zibreira') ? 'selected' : ''; ?>>Zibreira</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ========================================
                 Seção 3: Detalhes da Propriedade
                 ======================================== -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-home"></i> Detalhes da Propriedade</h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Quartos <span class="required">*</span></label>
                        <input type="number" name="quartos" class="form-control" required min="1" max="20"
                            value="<?php echo isset($_POST['quartos']) ? $_POST['quartos'] : '1'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Camas <span class="required">*</span></label>
                        <input type="number" name="camas" class="form-control" required min="1" max="50"
                            value="<?php echo isset($_POST['camas']) ? $_POST['camas'] : '1'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Casas de banho <span class="required">*</span></label>
                        <input type="number" name="casas_de_banho" class="form-control" required min="1" max="20"
                            value="<?php echo isset($_POST['casas_de_banho']) ? $_POST['casas_de_banho'] : '1'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Área (m²)</label>
                        <input type="number" name="area" class="form-control" min="1"
                            value="<?php echo isset($_POST['area']) ? $_POST['area'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Capacidade (hóspedes) <span class="required">*</span></label>
                        <input type="number" name="capacidade" class="form-control" required min="1" max="100"
                            value="<?php echo isset($_POST['capacidade']) ? $_POST['capacidade'] : '2'; ?>">
                    </div>
                </div>
            </div>

            <!-- ========================================
                 Seção 4: Preços e Regras
                 ======================================== -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-euro-sign"></i> Preços e Regras</h2>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Preço por Noite (€) <span class="required">*</span></label>
                        <input type="number" name="preco_noite" class="form-control" required min="1" step="0.01"
                            value="<?php echo isset($_POST['preco_noite']) ? $_POST['preco_noite'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Taxa de Limpeza (€)</label>
                        <input type="number" name="preco_limpeza" class="form-control" min="0" step="0.01"
                            value="<?php echo isset($_POST['preco_limpeza']) ? $_POST['preco_limpeza'] : '0'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Taxa de Segurança (€)</label>
                        <input type="number" name="taxa_seguranca" class="form-control" min="0" step="0.01"
                            value="<?php echo isset($_POST['taxa_seguranca']) ? $_POST['taxa_seguranca'] : '0'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Mínimo de Noites <span class="required">*</span></label>
                        <input type="number" name="minimo_noites" class="form-control" required min="1"
                            value="<?php echo isset($_POST['minimo_noites']) ? $_POST['minimo_noites'] : '1'; ?>">
                    </div>

                    <div class="form-group">
                        <label>Máximo de Noites</label>
                        <input type="number" name="maximo_noites" class="form-control" min="1"
                            value="<?php echo isset($_POST['maximo_noites']) ? $_POST['maximo_noites'] : '30'; ?>">
                    </div>
                </div>

                <!-- Horário de Check-in e Check-out -->
                <div class="form-grid">
                    <div class="form-group">
                        <label>Hora de Check-in <span class="required">*</span></label>
                        <div style="display: flex; gap: 10px;">
                            <select name="hora_checkin_hora" class="form-control" required style="flex: 1;">
                                <?php
                                $hora_checkin = isset($_POST['hora_checkin']) ? $_POST['hora_checkin'] : '15:00';
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
                                $hora_checkout = isset($_POST['hora_checkout']) ? $_POST['hora_checkout'] : '11:00';
                                list($hora_sel, $min_sel) = explode(':', $hora_checkout);
                                for ($hora = 0; $hora < 24; $hora++) {
                                    $selected = ($hora == (int)$hora_sel) ? 'selected' : '';
                                    echo "<option value=\"$hora\" $selected>" . sprintf('%02d', $hora) . "</option>";
                                }
                                ?>
                            </select>
                            <span style="align-self: center;">:</span>
                            <select name="hora_checkout_minuto" class="form-control" required style="flex: 1;">
                                <?php
                                for ($min = 0; $min < 60; $min++) {
                                    $min_formatado = sprintf('%02d', $min);
                                    $selected = ($min_formatado == $min_sel) ? 'selected' : '';
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
                        placeholder="Ex: Não são permitidos animais, Proibido fumar, Silêncio após as 22h..."><?php echo isset($_POST['regras']) ? htmlspecialchars($_POST['regras']) : ''; ?></textarea>
                </div>
            </div>

            <!-- ========================================
                 Seção 5: Comodidades
                 ======================================== -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-star"></i> Comodidades</h2>

                <p>Selecione as comodidades disponíveis na sua propriedade:</p>

                <div class="checkbox-grid">
                    <?php
                    // Lista de comodidades disponíveis
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
                        'escadas' => 'Escadas',
                        'ventilador' => 'Ventilador',
                        'animais_permitidos' => 'Animais Permitidos'
                    ];

                    foreach ($comodidades as $value => $label):
                        $checked = isset($_POST['comodidades']) && in_array($value, $_POST['comodidades']) ? 'checked' : '';
                    ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="comodidades[]" value="<?php echo $value; ?>" <?php echo $checked; ?>>
                            <span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ========================================
                 Ações do Formulário
                 ======================================== -->
            <div class="form-actions">
                <a href="../root/dashboard.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Adicionar Casa
                </button>
            </div>
        </form>
    </div>

    <?php include '../root/footer.php'; ?>

</body>

</html>