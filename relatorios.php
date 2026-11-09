<?php require 'config.php'; require 'layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset(STATUS[$_POST['status'] ?? '']))
        $db->prepare("UPDATE fretes SET status=? WHERE id=?")->execute([$_POST['status'], (int)$_POST['id']]);
    redirect('relatorios.php?' . http_build_query(['data' => $_POST['data'], 'transportador' => $_POST['transportador'], 'filtro_status' => $_POST['filtro_status']]));
}
$data = $_GET['data'] ?? date('Y-m-d'); $tr = $_GET['transportador'] ?? ''; $st = $_GET['filtro_status'] ?? '';
$fretes = buscarFretes($db, $data, $tr, $st);
$conc = count(array_filter($fretes, fn($f) => $f['status'] === 'entregue')); $peso = array_sum(array_column($fretes, 'peso'));
$trs = $db->query("SELECT id, razao_social FROM transportadores ORDER BY razao_social")->fetchAll();
$qs = http_build_query(['data' => $data, 'transportador' => $tr, 'filtro_status' => $st]);
topo('Relatórios', 'relatorios.php', 'Consulte suas movimentações e exporte os resultados.'); ?>
<form method="get" class="panel filtros3"><label>Data do frete<input type="date" name="data" value="<?= h($data) ?>"></label>
  <label>Transportador<select name="transportador"><option value="">Todos os transportadores</option>
    <?php foreach ($trs as $t) echo "<option value='$t[id]'" . ($tr == $t['id'] ? ' selected' : '') . '>' . h($t['razao_social']) . '</option>'; ?></select></label>
  <label>Status<select name="filtro_status"><option value="">Todos os status</option>
    <?php foreach (STATUS as $k => $v) echo "<option value='$k'" . ($st === $k ? ' selected' : '') . ">$v</option>"; ?></select></label>
  <button class="btn">Pesquisar fretes</button></form>
<div class="cards c3"><div class="card"><span>Fretes no dia</span><b><?= count($fretes) ?></b></div>
  <div class="card"><span>Entregas concluídas</span><b><?= $conc ?></b></div><div class="card"><span>Peso movimentado</span><b><?= kg($peso) ?></b></div></div>
<section class="panel"><div class="ph"><h2>Fretes de <?= dataBr($data) ?></h2>
  <div><a class="btn ghost" href="download.php?formato=csv&<?= $qs ?>">↓ Baixar CSV</a> <a class="btn" href="download.php?formato=pdf&<?= $qs ?>">↓ Baixar PDF</a></div></div>
  <table><thead><tr><th>FRETE</th><th>TRANSPORTADOR</th><th>ROTA</th><th>PRODUTOS</th><th>PESO TOTAL</th><th>STATUS</th></tr></thead><tbody>
  <?php foreach ($fretes as $f): ?><tr><td><?= cod('FR', $f['id']) ?></td><td><?= h($f['transportador']) ?></td><td><?= h($f['origem'] . ' → ' . $f['destino']) ?></td>
    <td><?= $f['qtd_produtos'] ?><small><?= h($f['produtos']) ?></small></td><td><?= kg($f['peso']) ?></td>
    <td><form method="post"><input type="hidden" name="id" value="<?= $f['id'] ?>"><input type="hidden" name="data" value="<?= h($data) ?>">
      <input type="hidden" name="transportador" value="<?= h($tr) ?>"><input type="hidden" name="filtro_status" value="<?= h($st) ?>">
      <select name="status" class="mini" onchange="this.form.submit()"><?php foreach (STATUS as $k => $v) echo "<option value='$k'" . ($f['status'] === $k ? ' selected' : '') . ">$v</option>"; ?></select></form></td></tr>
  <?php endforeach; if (!$fretes) echo '<tr><td colspan="6" class="vazio">Nenhum frete encontrado para os filtros.</td></tr>'; ?></tbody></table></section>
<?php rodape('Os relatórios incluem apenas os fretes encontrados nos filtros selecionados.');
