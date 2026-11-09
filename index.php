<?php require 'config.php'; require 'layout.php';
$c = ['total' => 0, 'agendado' => 0, 'em_transito' => 0, 'entregue' => 0];
foreach ($db->query("SELECT status, COUNT(*) n FROM fretes GROUP BY status") as $r) { $c[$r['status']] = $r['n']; $c['total'] += $r['n']; }
$dias = [];
for ($i = 6; $i >= 0; $i--) $dias[date('Y-m-d', strtotime("-$i day"))] = 0;
$s = $db->prepare("SELECT data_frete, COUNT(*) n FROM fretes WHERE data_frete BETWEEN ? AND ? GROUP BY data_frete");
$s->execute([array_key_first($dias), array_key_last($dias)]);
foreach ($s as $r) $dias[$r['data_frete']] = $r['n'];
$escala = max(10, (int)(ceil(max($dias) / 10) * 10));
$ult = $db->query("SELECT id, criado_em FROM fretes ORDER BY id DESC LIMIT 6")->fetchAll();
topo('Dashboard', 'index.php', 'Sua operação em movimento, em um só lugar.', ['Novo frete', 'novo_frete.php']); ?>
<div class="cards">
  <div class="card dark"><span>Fretes cadastrados</span><b><?= $c['total'] ?></b></div>
  <div class="card"><span>Em trânsito</span><b><?= $c['em_transito'] ?></b></div>
  <div class="card"><span>Entregues</span><b><?= $c['entregue'] ?></b></div>
  <div class="card"><span>Agendados</span><b><?= $c['agendado'] ?></b><small class="amber">Próximas coletas</small></div>
</div>
<div class="grid2 wide">
  <section class="panel"><h2>Movimentações da semana</h2><p class="sub">Fretes cadastrados por dia (<?= dataBr(array_key_first($dias)) ?> — <?= dataBr(array_key_last($dias)) ?>)</p>
    <div class="chart"><?php foreach ($dias as $d => $n): ?>
      <div class="col"><div class="bar" style="height:<?= $n / $escala * 100 ?>%" title="<?= $n ?>"></div><em><?= date('d/m', strtotime($d)) ?></em></div>
    <?php endforeach; ?><span class="ax" style="top:0">30</span></div>
    <div class="scale">Escala máxima: <?= $escala ?></div></section>
  <section class="panel"><h2>Últimas movimentações</h2>
    <?php if (!$ult) echo '<p class="sub">Nenhum frete cadastrado ainda.</p>'; foreach ($ult as $u): ?>
      <div class="mov"><i></i><div><b>Frete #<?= cod('FR', $u['id']) ?> cadastrado</b><small><?= date('d/m/Y, H:i', strtotime($u['criado_em'])) ?></small></div></div>
    <?php endforeach; ?></section>
</div>
<?php rodape();
