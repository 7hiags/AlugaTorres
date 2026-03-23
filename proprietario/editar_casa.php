<?php
require_once __DIR__ . '/../root/init.php';


if (!isset($_SESSION['user_id']) || $_SESSION['tipo_utilizador'] !== 'proprietario') {
    header("Location: ../backend/autenticacao/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$casa_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = isset($_GET['success']);

// Obter dados da casa
$stmt = $conn->prepare("SELECT *, custom_tipo FROM casas WHERE id = ? AND proprietario_id = ?");
$stmt->bind_param("ii", $casa_id, $user_id);
$stmt->execute();
$casa = $stmt->get_result()->fetch_assoc();

if (!$casa) {
    header("Location: ../root/dashboard.php");
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

// Fotos existentes
$fotos_atuais = json_decode($casa['fotos'] ?? '[]', true);
if (!is_array($fotos_atuais)) {
    $fotos_atuais = [];
}

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
        $hora_checkin = $_POST['hora_checkin'];
        $hora_checkout = $_POST['hora_checkout'];
        $comodidades = isset($_POST['comodidades']) ? $_POST['comodidades'] : [];
        $comodidades_json = json_encode($comodidades);
        $regras = trim($_POST['regras']);

        // Processar novas fotos
        $fotos = $fotos_atuais; // Manter fotos existentes

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
        }

        // Processar fotos para eliminar (viaJavaScript/AJAX seria melhor, mas aqui usando input hidden)
        $fotos_eliminar = isset($_POST['fotos_eliminar']) ? json_decode($_POST['fotos_eliminar'], true) : [];
        if (is_array($fotos_eliminar)) {
            foreach ($fotos_eliminar as $foto_eliminar) {
                // Eliminar arquivo físico
                $caminho_arquivo = __DIR__ . '/../' . $foto_eliminar;
                if (file_exists($caminho_arquivo)) {
                    unlink($caminho_arquivo);
                }
                // Remover do array
                $fotos = array_filter($fotos, function ($f) use ($foto_eliminar) {
                    return $f !== $foto_eliminar;
                });
            }
            $fotos = array_values($fotos); // Reindexar
        }

        // Definir foto de referência
        $foto_referencia = isset($_POST['foto_referencia']) ? $_POST['foto_referencia'] : ($fotos[0] ?? '');

        // Reordenar fotos para que a referência seja a primeira
        if ($foto_referencia && count($fotos) > 1) {
            $fotos = array_filter($fotos, function ($f) use ($foto_referencia) {
                return $f !== $foto_referencia;
            });
            array_unshift($fotos, $foto_referencia);
            $fotos = array_values($fotos);
        }

        $fotos_json = json_encode($fotos);

        // Validações simples
        if (empty($titulo) || empty($morada) || $preco_noite <= 0) {
            throw new \Exception('Preencha todos os campos obrigatórios e preço deve ser maior que 0.');
        }

        // Atualizar na base de dados
        $stmt = $conn->prepare(
            "
            UPDATE casas SET
            titulo = ?,
            descricao = ?,
            morada = ?,
            codigo_postal = ?,
            cidade = ?,
            freguesia = ?,
            tipo_propriedade = ?,
            custom_tipo = ?,
            quartos = ?,
            camas = ?,
            casas_de_banho = ?,
            area = ?,
            capacidade = ?,
            preco_noite = ?,
            preco_limpeza = ?,
            taxa_seguranca = ?,
            minimo_noites = ?,
            maximo_noites = ?,
            hora_checkin = ?,
            hora_checkout = ?,
            comodidades = ?,
            regras = ?,
            fotos = ?
            WHERE id = ? AND proprietario_id = ?"
        );

        $stmt->bind_param(
            "ssssssssiiiiidddiisssssii",
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
<?php
$pageTitle = 'AlugaTorres | Editar Casa';
require_once __DIR__ . '/../root/head.php';
    include '../root/header.php';
    include '../root/sidebar.php';
?>


<body>
    <div class="form-container-casa">
        <div class="form-header">
            <h1 class="form-title">Editar Casa</h1>
            <p class="form-subtitle">Atualize as informações da sua propriedade</p>
        </div>

        <?php if ($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof AlugaTorresNotifications !== 'undefined') {
                        AlugaTorresNotifications.error(<?php echo json_encode(htmlspecialchars($error)); ?>);
                    }
                });
            </script>
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
                Casa atualizada com sucesso!
                <div style="margin-top: 10px;">
                    <a href="../root/perfil.php" class="btn-save" onclick="localStorage.setItem('atualizarStats', 'true');">
                        <i class="fas fa-chart-line"></i> Ver Estatísticas Atualizadas
                    </a>
                </div>
            </div>
        <?php endif; ?>


        <form method="POST" class="casa-form" enctype="multipart/form-data">


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
                                value="<?php echo old('outro_texto', ['custom_tipo' => $casa['custom_tipo'] ?? '']); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição <span class="required">*</span></label>
                    <textarea name="descricao" class="form-control" required rows="4"
                        placeholder="Descreva a sua propriedade, localização, características únicas..."><?php echo old('descricao', $casa); ?></textarea>
                </div>

                <!-- Fotos da Casa -->
                <div class="form-group">
                    <label>Fotos da Propriedade</label>
                    <p style="font-size: 0.9em; color: #666; margin-bottom: 10px;">Adicione várias fotos. A primeira foto será a imagem de capa.</p>

                    <!-- Fotos existentes -->
                    <?php if (!empty($fotos_atuais)): ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                            <?php foreach ($fotos_atuais as $index => $foto): ?>
                                <?php
                                // Corrigir caminho da foto se necessário
                                $foto_exibir = $foto;
                                if (!empty($foto_exibir) && strpos($foto_exibir, 'assets/') !== 0) {
                                    $foto_exibir = 'assets/' . ltrim($foto_exibir, '/');
                                }
                                ?>
                                <div class="foto-item" style="position: relative; width: 120px; height: 90px; border: 2px solid #ddd; border-radius: 5px; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($foto_exibir); ?>" alt="Foto <?php echo $index + 1; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); padding: 3px; display: flex; justify-content: space-between; align-items: center;">
                                        <label style="color: white; font-size: 10px; cursor: pointer; display: flex; align-items: center;">
                                            <input type="radio" name="foto_referencia" value="<?php echo htmlspecialchars($foto); ?>" <?php echo $index === 0 ? 'checked' : ''; ?> style="margin-right: 3px;">
                                            <span>Capa</span>
                                        </label>
                                        <button type="button" class="eliminar-foto" data-foto="<?php echo htmlspecialchars($foto); ?>" style="background: #dc3545; border: none; color: white; padding: 2px 6px; border-radius: 3px; cursor: pointer; font-size: 10px;">✕</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="fotos_eliminar" id="fotos_eliminar" value="[]">
                    <?php endif; ?>

                    <input type="file" name="fotos[]" class="form-control" multiple accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color: #666;">Formatos aceitos: JPEG, PNG, GIF, WebP. Múltiplos arquivos permitidos.</small>
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
                        <label>casas_de_banho <span class="required">*</span></label>
                        <input type="number" name="casas_de_banho" class="form-control" required min="1" max="20"
                            value="<?php echo old('casas_de_banho', $casa); ?>">
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
                        'escadas' => 'Escadas',
                        'ventilador' => 'Ventilador',
                        'animais_permitidos' => 'Animais Permitidos'
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
                <a href="../root/dashboard.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Guardar Alterações
                </button>
            </div>
        </form>
    </div>

    <?php include '../root/footer.php'; ?>

</body>

</html>