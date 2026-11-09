<?php require 'config.php'; require 'layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)p('id');
    $d = [p('razao_social'), p('cnpj'), p('responsavel'), p('telefone'), p('email'), isset($_POST['ativo']) || !$id ? 1 : 0];
    if (in_array('', array_slice($d, 0, 5), true) || !filter_var($d[4], FILTER_VALIDATE_EMAIL)) { flash('Preencha todos os campos obrigatórios com um e-mail válido.', 'erro'); redirect('transportadores.php' . ($id ? "?editar=$id" : '')); }
    if ($id) { $d[] = $id; $db->prepare("UPDATE transportadores SET razao_social=?,cnpj=?,responsavel=?,telefone=?,email=?,ativo=? WHERE id=?")->execute($d); }
    else $db->prepare("INSERT INTO transportadores(razao_social,cnpj,responsavel,telefone,email,ativo) VALUES(?,?,?,?,?,?)")->execute($d);
    flash('Transportador salvo com sucesso.'); redirect('transportadores.php');
}
$ed = null;
if (isset($_GET['editar'])) { $s = $db->prepare("SELECT * FROM transportadores WHERE id=?"); $s->execute([(int)$_GET['editar']]); $ed = $s->fetch() ?: null; }
$q = trim($_GET['q'] ?? ''); $st = $_GET['status'] ?? '';
$sql = "SELECT * FROM transportadores WHERE (razao_social LIKE :q OR cnpj LIKE :q)"; $a = [':q' => "%$q%"];
if ($st === 'ativo' || $st === 'inativo') $sql .= " AND ativo=" . ($st === 'ativo' ? 1 : 0);
$lista = $db->prepare($sql . " ORDER BY razao_social"); $lista->execute($a); $lista = $lista->fetchAll();
topo('Transportadores', 'transportadores.php', 'Todos os seus parceiros de transporte, organizados.', ['Cadastrar transportador', 'transportadores.php']); ?>
<div class="grid2">
  <section class="panel"><h2>Seus parceiros</h2>
    <form class="filtros" method="get"><input name="q" placeholder="⌕ Buscar por nome ou CNPJ" value="<?= h($q) ?>">
      <select name="status" onchange="this.form.submit()"><option value="">Todos os status</option>
        <option value="ativo" <?= $st === 'ativo' ? 'selected' : '' ?>>Ativos</option><option value="inativo" <?= $st === 'inativo' ? 'selected' : '' ?>>Inativos</option></select></form>
    <table><thead><tr><th>TRANSPORTADOR</th><th>CONTATO</th><th>STATUS</th></tr></thead><tbody>
    <?php foreach ($lista as $r): ?>
      <tr class="link" onclick="location='transportadores.php?editar=<?= $r['id'] ?>'"><td><?= h($r['razao_social']) ?><small><?= h($r['cnpj']) ?></small></td>
        <td><?= h($r['responsavel']) ?><small><?= h($r['telefone']) ?> · <?= h($r['email']) ?></small></td><td><span class="pill <?= $r['ativo'] ? 'ok' : 'off' ?>"><?= $r['ativo'] ? 'Ativo' : 'Inativo' ?></span></td></tr>
    <?php endforeach; if (!$lista) echo '<tr><td colspan="3" class="vazio">Nenhum transportador encontrado.</td></tr>'; ?></tbody></table></section>
  <section class="panel"><h2><?= $ed ? 'Editar transportador' : 'Novo transportador' ?></h2><p class="sub">Cadastre um novo parceiro logístico.</p>
    <form method="post" class="form"><input type="hidden" name="id" value="<?= $ed['id'] ?? 0 ?>">
      <div class="info">Dados da empresa e do responsável</div>
      <label>Razão social *<input name="razao_social" required placeholder="Nome da empresa" value="<?= h($ed['razao_social'] ?? '') ?>"></label>
      <label>CNPJ *<input name="cnpj" data-mask="cnpj" required placeholder="00.000.000/0000-00" value="<?= h($ed['cnpj'] ?? '') ?>"></label>
      <label>Nome do responsável *<input name="responsavel" required placeholder="Nome completo" value="<?= h($ed['responsavel'] ?? '') ?>"></label>
      <label>Telefone *<input name="telefone" data-mask="tel" required placeholder="(00) 00000-0000" value="<?= h($ed['telefone'] ?? '') ?>"></label>
      <label>E-mail *<input name="email" type="email" required placeholder="contato@empresa.com.br" value="<?= h($ed['email'] ?? '') ?>"></label>
      <?php if ($ed): ?><label class="sw"><input type="checkbox" name="ativo" <?= $ed['ativo'] ? 'checked' : '' ?>><span></span> Transportador ativo</label><?php endif; ?>
      <small class="req">* Campos obrigatórios</small>
      <div class="row btns"><a class="btn ghost" href="transportadores.php">Cancelar</a><button class="btn"><?= $ed ? 'Salvar alterações' : 'Cadastrar transportador' ?></button></div></form></section>
</div>
<?php rodape('Seus transportadores ficam disponíveis na criação de um novo frete.');
