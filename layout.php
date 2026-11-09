<?php
function topo($titulo, $ativo, $sub, $acao = null) {
    $menu = ['index.php' => ['Dashboard', '▦'], 'produtos.php' => ['Produtos', '▣'], 'enderecos.php' => ['Endereços', '⌖'],
             'transportadores.php' => ['Transportadores', '⛟'], 'novo_frete.php' => ['Novo frete', '+'], 'relatorios.php' => ['Relatórios', '▤']];
    ?><!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>leva · <?= h($titulo) ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body><aside class="side">
  <div class="logo"><span class="logo-i">⛟</span> leva</div><small class="tag">GESTÃO DE FRETES</small>
  <div class="grp">PRINCIPAL</div>
  <nav><?php foreach ($menu as $url => [$nome, $ic]): ?>
    <a href="<?= $url ?>" class="<?= $url === $ativo ? 'on' : '' ?>"><i><?= $ic ?></i><?= $nome ?></a>
  <?php endforeach; ?></nav></aside>
<main><div class="crumb">Workspace / <b><?= h($titulo) ?></b></div>
<div class="content">
  <div class="head"><div><h1><?= h($titulo) ?></h1><p class="sub"><?= h($sub) ?></p></div>
  <?php if ($acao): ?><a class="btn" href="<?= h($acao[1]) ?>">+ <?= h($acao[0]) ?></a><?php endif; ?></div>
  <?php if (!empty($_SESSION['flash'])): [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="flash <?= $t ?>"><?= h($m) ?></div><?php endif;
}
function rodape($nota = '') {
    if ($nota) echo '<p class="nota">' . h($nota) . '</p>';
    echo '</div></main><script src="assets/app.js"></script></body></html>';
}
