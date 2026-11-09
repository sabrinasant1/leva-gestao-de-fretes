<?php require 'config.php'; require 'layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)p('id');
    $d = [p('tipo'), p('nome'), p('cep'), p('estado'), p('logradouro'), p('numero'), p('bairro'), p('cidade'), p('complemento')];
    if (!in_array($d[0], ['origem', 'destino']) || in_array('', array_slice($d, 1, 7), true)) { flash('Preencha os campos obrigatórios.', 'erro'); redirect('enderecos.php' . ($id ? "?editar=$id" : '')); }
    if ($id) { $d[] = $id; $db->prepare("UPDATE enderecos SET tipo=?,nome=?,cep=?,estado=?,logradouro=?,numero=?,bairro=?,cidade=?,complemento=? WHERE id=?")->execute($d); }
    else $db->prepare("INSERT INTO enderecos(tipo,nome,cep,estado,logradouro,numero,bairro,cidade,complemento) VALUES(?,?,?,?,?,?,?,?,?)")->execute($d);
    flash('Endereço salvo com sucesso.'); redirect('enderecos.php');
}
$ed = null;
if (isset($_GET['editar'])) { $s = $db->prepare("SELECT * FROM enderecos WHERE id=?"); $s->execute([(int)$_GET['editar']]); $ed = $s->fetch() ?: null; }
$cnt = ['origem' => 0, 'destino' => 0];
foreach ($db->query("SELECT tipo, COUNT(*) n FROM enderecos GROUP BY tipo") as $r) $cnt[$r['tipo']] = $r['n'];
$tipo = $_GET['tipo'] ?? ''; $q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM enderecos WHERE (nome LIKE :q OR cidade LIKE :q OR cep LIKE :q)"; $a = [':q' => "%$q%"];
if (in_array($tipo, ['origem', 'destino'])) { $sql .= " AND tipo=:t"; $a[':t'] = $tipo; }
$lista = $db->prepare($sql . " ORDER BY nome"); $lista->execute($a); $lista = $lista->fetchAll();
$tipoForm = $ed['tipo'] ?? 'origem';
topo('Endereços', 'enderecos.php', 'Gerencie os pontos de coleta e entrega.', ['Novo endereço', 'enderecos.php']); ?>
<div class="grid2">
  <div><div class="tabs"><a class="<?= !$tipo ? 'on' : '' ?>" href="enderecos.php">Todos (<?= array_sum($cnt) ?>)</a>
    <a class="<?= $tipo === 'origem' ? 'on' : '' ?>" href="?tipo=origem">Origens (<?= $cnt['origem'] ?>)</a>
    <a class="<?= $tipo === 'destino' ? 'on' : '' ?>" href="?tipo=destino">Destinos (<?= $cnt['destino'] ?>)</a></div>
    <form method="get" class="busca"><input type="hidden" name="tipo" value="<?= h($tipo) ?>"><input name="q" placeholder="⌕ Buscar por nome, cidade ou CEP" value="<?= h($q) ?>"></form>
    <?php foreach ($lista as $r): ?>
      <a class="addr" href="enderecos.php?editar=<?= $r['id'] ?>"><div><b><?= h($r['nome']) ?></b> <span class="pill <?= $r['tipo'] === 'origem' ? 'ok' : 'blue' ?>"><?= ucfirst($r['tipo']) ?></span>
        <small><?= h("$r[logradouro], $r[numero] — $r[bairro], $r[cidade]/$r[estado] · CEP $r[cep]") ?><?= $r['complemento'] ? ' · ' . h($r['complemento']) : '' ?></small></div><em><?= cod('EN', $r['id']) ?></em></a>
    <?php endforeach; if (!$lista) echo '<p class="vazio">Nenhum endereço encontrado.</p>'; ?></div>
  <section class="panel"><h2><?= $ed ? 'Editar endereço' : 'Novo endereço' ?></h2><p class="sub">Adicione um ponto de coleta ou entrega.</p>
    <form method="post" class="form"><input type="hidden" name="id" value="<?= $ed['id'] ?? 0 ?>">
      <div class="lbl">Tipo de endereço *</div>
      <div class="seg"><label><input type="radio" name="tipo" value="origem" <?= $tipoForm === 'origem' ? 'checked' : '' ?>><span>Origem</span></label>
        <label><input type="radio" name="tipo" value="destino" <?= $tipoForm === 'destino' ? 'checked' : '' ?>><span>Destino</span></label></div>
      <label>Nome de identificação *<input name="nome" required placeholder="Ex.: Centro de distribuição" value="<?= h($ed['nome'] ?? '') ?>"></label>
      <div class="row"><label>CEP *<input name="cep" data-mask="cep" required placeholder="00000-000" value="<?= h($ed['cep'] ?? '') ?>"></label>
        <label>Estado *<select name="estado" required><option value="">Selecione</option><?php foreach (UFS as $u) echo "<option" . (($ed['estado'] ?? '') === $u ? ' selected' : '') . ">$u</option>"; ?></select></label></div>
      <div class="row r73"><label>Logradouro *<input name="logradouro" required placeholder="Rua ou avenida" value="<?= h($ed['logradouro'] ?? '') ?>"></label>
        <label>Número *<input name="numero" required placeholder="Nº" value="<?= h($ed['numero'] ?? '') ?>"></label></div>
      <div class="row"><label>Bairro *<input name="bairro" required placeholder="Nome do bairro" value="<?= h($ed['bairro'] ?? '') ?>"></label>
        <label>Cidade *<input name="cidade" required placeholder="Nome da cidade" value="<?= h($ed['cidade'] ?? '') ?>"></label></div>
      <label>Complemento<input name="complemento" placeholder="Galpão, bloco ou referência (opcional)" value="<?= h($ed['complemento'] ?? '') ?>"></label>
      <div class="row btns"><a class="btn ghost" href="enderecos.php">Cancelar</a><button class="btn">Salvar endereço</button></div></form></section>
</div>
<?php rodape();
