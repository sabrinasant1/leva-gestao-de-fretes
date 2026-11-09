<?php require 'config.php'; require 'layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prods = $_POST['produto_id'] ?? []; $qtds = $_POST['quantidade'] ?? []; $itens = [];
    foreach ($prods as $i => $pid) {
        $pid = (int)$pid; $qt = (int)($qtds[$i] ?? 0);
        if ($pid && $qt > 0) $itens[$pid] = ($itens[$pid] ?? 0) + $qt;
    }
    $tr = (int)p('transportador_id'); $or = (int)p('origem_id'); $de = (int)p('destino_id'); $dt = p('data_frete');
    if (!$tr || !$or || !$de || !$dt || !$itens) { flash('Selecione transportador, ao menos um produto, origem, destino e data.', 'erro'); redirect('novo_frete.php'); }
    $db->beginTransaction();
    try {
        $db->prepare("INSERT INTO fretes(transportador_id,origem_id,destino_id,data_frete,observacoes,status,criado_em) VALUES(?,?,?,?,?,'agendado',?)")
           ->execute([$tr, $or, $de, $dt, p('observacoes'), date('Y-m-d H:i:s')]);
        $fid = $db->lastInsertId();
        $ps = $db->prepare("SELECT peso FROM produtos WHERE id=?");
        $ins = $db->prepare("INSERT INTO frete_itens(frete_id,produto_id,quantidade,peso_total) VALUES(?,?,?,?)");
        foreach ($itens as $pid => $qt) { $ps->execute([$pid]); $peso = $ps->fetchColumn(); $ins->execute([$fid, $pid, $qt, $peso * $qt]); }
        $db->commit();
    } catch (Throwable $e) { $db->rollBack(); flash('Erro ao cadastrar o frete.', 'erro'); redirect('novo_frete.php'); }
    flash('Frete ' . cod('FR', $fid) . ' cadastrado com sucesso.'); redirect('index.php');
}
$trs = $db->query("SELECT id, razao_social FROM transportadores WHERE ativo=1 ORDER BY razao_social")->fetchAll();
$prs = $db->query("SELECT id, nome, peso, unidade FROM produtos WHERE ativo=1 ORDER BY nome")->fetchAll();
$ors = $db->query("SELECT id, nome, cidade, estado FROM enderecos WHERE tipo='origem' ORDER BY nome")->fetchAll();
$des = $db->query("SELECT id, nome, cidade, estado FROM enderecos WHERE tipo='destino' ORDER BY nome")->fetchAll();
topo('Novo frete', 'novo_frete.php', 'Planeje uma nova movimentação de ponta a ponta.'); ?>
<form method="post" id="frete" class="grid2">
<div>
  <section class="panel"><h3><i class="n">1</i> Transportador</h3>
    <label>Selecione o transportador *<select name="transportador_id" id="transp" required><option value="">Selecione</option>
      <?php foreach ($trs as $t) echo "<option value='$t[id]'>" . h($t['razao_social']) . "</option>"; ?></select></label></section>
  <section class="panel"><div class="ph"><h3><i class="n">2</i> Produtos</h3><button type="button" class="lnk" id="addProd">+ Adicionar produto</button></div>
    <table><thead><tr><th>PRODUTO</th><th>QUANTIDADE</th><th>PESO TOTAL</th><th></th></tr></thead><tbody id="itens"></tbody></table></section>
  <section class="panel"><h3><i class="n">3</i> Rota e data</h3>
    <div class="row"><label>Endereço de origem *<select name="origem_id" id="orig" required><option value="">Selecione</option>
      <?php foreach ($ors as $o) echo "<option value='$o[id]'>" . h("$o[nome] — $o[cidade]/$o[estado]") . "</option>"; ?></select></label>
    <label>Endereço de destino *<select name="destino_id" id="dest" required><option value="">Selecione</option>
      <?php foreach ($des as $o) echo "<option value='$o[id]'>" . h("$o[nome] — $o[cidade]/$o[estado]") . "</option>"; ?></select></label></div>
    <label>Data do frete *<input type="date" name="data_frete" id="data" required value="<?= date('Y-m-d') ?>"></label></section>
  <section class="panel"><label>Observações<input name="observacoes"></label></section>
</div>
<aside><section class="panel"><h2>Resumo do frete</h2><p class="sub">Confira os dados antes de cadastrar.</p><hr>
  <div class="lbl">TRANSPORTADOR</div><div class="res" id="rT">—</div>
  <div class="lbl">ORIGEM</div><div class="res" id="rO">—</div><div class="seta">↓</div>
  <div class="lbl">DESTINO</div><div class="res" id="rD">—</div>
  <dl><dt>Data do frete</dt><dd id="rDt">—</dd><dt>Produtos</dt><dd id="rP">0</dd><dt>Peso total</dt><dd id="rW">0,00 kg</dd></dl>
  <button class="btn full">Cadastrar frete</button><a class="cancel" href="index.php">Cancelar e voltar</a></section>
  <div class="ready"><b>Tudo pronto para seguir.</b><br>Após o cadastro, o frete ficará disponível no dashboard e nos relatórios.</div></aside>
</form>
<script>const PRODUTOS = <?= json_encode($prs, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php rodape();
