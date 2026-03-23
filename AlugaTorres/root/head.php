<?php

// garantir que init já foi chamado caso alguém inclua head.php directamente
if (!defined('BASE_URL')) {
  require_once __DIR__ . '/init.php';
}

?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'AlugaTorres - Sua agência de viagens para destinos incríveis') ?>">
  <title><?= htmlspecialchars($pageTitle ?? 'AlugaTorres') ?></title>

  <!-- Font Awesome para ícones -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Estilos personalizados do site -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/style/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/style/header-links.css">
  <!-- Ícone do site -->
  <link rel="website icon" type="png" href="<?= BASE_URL ?>assets/style/img/Logo_AlugaTorres_branco.png">

  <!-- Espaço para incluir estilos/scripts adicionais por página -->
  <?= $extraHead ?? '' ?>
</head>