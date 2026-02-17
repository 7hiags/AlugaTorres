<?php

/**
 * Gestor de Upload de Imagens
 * Funções para processar upload de fotos de perfil e casas
 */

// Configurações de upload
define('PASTA_UPLOAD_PERFIL', '../uploads/fotos_perfil/');
define('PASTA_UPLOAD_CASAS', '../uploads/casas/');
define('TAMANHO_MAXIMO', 5 * 1024 * 1024); // 5MB em bytes
define('MAX_FOTOS_CASA', 7);

// Tipos de imagem permitidos
$tipos_permitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
$extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

/**
 * Verifica e cria as pastas de upload se não existirem
 */
function verificarPastasUpload()
{
    if (!file_exists(PASTA_UPLOAD_PERFIL)) {
        mkdir(PASTA_UPLOAD_PERFIL, 0755, true);
    }
    if (!file_exists(PASTA_UPLOAD_CASAS)) {
        mkdir(PASTA_UPLOAD_CASAS, 0755, true);
    }
}

/**
 * Gera um nome único para o arquivo
 */
function gerarNomeUnico($extensao)
{
    return uniqid() . '_' . time() . '.' . $extensao;
}

/**
 * Valida um arquivo de imagem
 */
function validarImagem($arquivo)
{
    global $tipos_permitidos, $extensoes_permitidas;

    $erros = [];

    // Verificar se houve erro no upload
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Erro no upload do arquivo.';
        return $erros;
    }

    // Verificar tamanho
    if ($arquivo['size'] > TAMANHO_MAXIMO) {
        $erros[] = 'O arquivo excede o tamanho máximo permitido de 5MB.';
    }

    // Verificar tipo MIME
    $tipo_mime = mime_content_type($arquivo['tmp_name']);
    if (!in_array($tipo_mime, $tipos_permitidos)) {
        $erros[] = 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou WebP.';
    }

    // Verificar extensão
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, $extensoes_permitidas)) {
        $erros[] = 'Extensão de arquivo não permitida.';
    }

    return $erros;
}

/**
 * Processa upload de foto de perfil
 */
function uploadFotoPerfil($arquivo, $utilizador_id)
{
    verificarPastasUpload();

    // Validar imagem
    $erros = validarImagem($arquivo);
    if (!empty($erros)) {
        return ['sucesso' => false, 'erros' => $erros];
    }

    // Gerar nome único
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    $nome_arquivo = 'perfil_' . $utilizador_id . '_' . gerarNomeUnico($extensao);
    $caminho_destino = PASTA_UPLOAD_PERFIL . $nome_arquivo;

    // Mover arquivo
    if (move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
        // Retornar caminho relativo para guardar na base de dados
        $caminho_relativo = 'uploads/fotos_perfil/' . $nome_arquivo;
        return ['sucesso' => true, 'caminho' => $caminho_relativo];
    } else {
        return ['sucesso' => false, 'erros' => ['Erro ao mover o arquivo.']];
    }
}

/**
 * Processa upload de fotos de casas
 */
function uploadFotosCasa($arquivos, $casa_id)
{
    verificarPastasUpload();

    $resultados = [];
    $fotos_salvas = [];
    $erros = [];

    // Verificar se é array de arquivos
    if (!is_array($arquivos['name'])) {
        // Converter para array se for upload único
        $arquivos = [
            'name' => [$arquivos['name']],
            'type' => [$arquivos['type']],
            'tmp_name' => [$arquivos['tmp_name']],
            'error' => [$arquivos['error']],
            'size' => [$arquivos['size']]
        ];
    }

    $total_arquivos = count($arquivos['name']);

    // Verificar limite de fotos
    if ($total_arquivos > MAX_FOTOS_CASA) {
        return [
            'sucesso' => false,
            'erros' => ['Máximo de ' . MAX_FOTOS_CASA . ' fotos permitido.']
        ];
    }

    // Processar cada arquivo
    for ($i = 0; $i < $total_arquivos; $i++) {
        $arquivo = [
            'name' => $arquivos['name'][$i],
            'type' => $arquivos['type'][$i],
            'tmp_name' => $arquivos['tmp_name'][$i],
            'error' => $arquivos['error'][$i],
            'size' => $arquivos['size'][$i]
        ];

        // Pular arquivos vazios
        if ($arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        // Validar imagem
        $erros_validacao = validarImagem($arquivo);
        if (!empty($erros_validacao)) {
            $erros[] = "Arquivo " . ($i + 1) . ": " . implode(", ", $erros_validacao);
            continue;
        }

        // Gerar nome único
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $nome_arquivo = 'casa_' . $casa_id . '_' . gerarNomeUnico($extensao);
        $caminho_destino = PASTA_UPLOAD_CASAS . $nome_arquivo;

        // Mover arquivo
        if (move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
            $caminho_relativo = 'uploads/casas/' . $nome_arquivo;
            $fotos_salvas[] = $caminho_relativo;
        } else {
            $erros[] = "Arquivo " . ($i + 1) . ": Erro ao salvar.";
        }
    }

    if (empty($fotos_salvas) && !empty($erros)) {
        return ['sucesso' => false, 'erros' => $erros];
    }

    return [
        'sucesso' => true,
        'fotos' => $fotos_salvas,
        'erros' => $erros
    ];
}

/**
 * Remove uma foto do sistema de ficheiros
 */
function removerFoto($caminho_relativo)
{
    $caminho_completo = '../' . $caminho_relativo;

    if (file_exists($caminho_completo)) {
        return unlink($caminho_completo);
    }

    return false;
}

/**
 * Atualiza as fotos de uma casa na base de dados
 */
function atualizarFotosCasa($conn, $casa_id, $fotos_array)
{
    $fotos_json = json_encode($fotos_array);

    $stmt = $conn->prepare("UPDATE casas SET fotos = ? WHERE id = ?");
    $stmt->bind_param("si", $fotos_json, $casa_id);

    return $stmt->execute();
}

/**
 * Atualiza a foto de perfil do utilizador na base de dados
 */
function atualizarFotoPerfil($conn, $utilizador_id, $caminho_foto)
{
    $stmt = $conn->prepare("UPDATE utilizadores SET foto_perfil = ? WHERE id = ?");
    $stmt->bind_param("si", $caminho_foto, $utilizador_id);

    return $stmt->execute();
}

/**
 * Obtém a foto de perfil do utilizador
 */
function obterFotoPerfil($conn, $utilizador_id)
{
    $stmt = $conn->prepare("SELECT foto_perfil FROM utilizadores WHERE id = ?");
    $stmt->bind_param("i", $utilizador_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $dados = $resultado->fetch_assoc();
        return $dados['foto_perfil'];
    }

    return null;
}

/**
 * Obtém as fotos de uma casa
 */
function obterFotosCasa($conn, $casa_id)
{
    $stmt = $conn->prepare("SELECT fotos FROM casas WHERE id = ?");
    $stmt->bind_param("i", $casa_id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $dados = $resultado->fetch_assoc();
        $fotos = json_decode($dados['fotos'], true);
        return is_array($fotos) ? $fotos : [];
    }

    return [];
}
