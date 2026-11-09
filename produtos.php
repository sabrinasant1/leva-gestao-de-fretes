<?php require 'config.php'; require 'layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)p('id');
    $d = [p('nome'), p('categoria'), (float)str_replace(',', '.', p('peso')), p('unidade'), p('descricao'), isset($_POST['ativo']) ? 1 : 0];
    if ($d[0] === '' || $d[1] === '' || $d[2] <= 0 || $d[3] === '') { flash('Preencha os campos obrigatórios (peso maior que zero).', 'erro'); redirect('produtos.php' . ($id ? "?editar=$id" : '')); }
    if ($id) { $d[] = $id; $db->prepare("UPDATE produtos SET nome=?,categoria=?,peso=?,unidade=?,descricao=?,ativo=? WHERE id=?")->execute($d); }
    else $db->prepare("INSERT INTO produtos(nome,categoria,peso,unidade,descricao,ativo) VALUES(?,?,?,?,?,?)")->execute($d);
    flash('Produto salvo com sucesso.'); redirect('produtos.php');
}
$ed = null;
if (isset($_GET['editar'])) { $s = $db->prepare("SELECT * FROM produtos WHERE id=?"); $s->execute([(int)$_GET['editar']]); $ed = $s->fetch() ?: null; }
$q = trim($_GET['q'] ?? ''); $st = $_GET['status'] ?? '';
$sql = "SELECT * FROM produtos WHERE (nome LIKE :q OR printf('PR-%04d', id) LIKE :q)"; $a = [':q' => "%$q%"];
if ($st === 'ativo' || $st === 'inativo') $sql .= " AND ativo=" . ($st === 'ativo' ? 1 : 0);
$lista = $db->prepare($sql . " ORDER BY nome"); $lista->execute($a); $lista = $lista->fetchAll();
topo('Produtos', 'produtos.php', 'Organize os produtos que fazem parte da sua operação.', ['Cadastrar produto', 'produtos.php']); ?>
<div class="grid2">
  <section class="panel"><h2>Catálogo de produtos</h2>
    <form class="filtros" method="get"><input name="q" placeholder="⌕ Buscar por nome ou código" value="<?= h($q) ?>">
      <select name="status" onchange="this.form.submit()"><option value="">Todos os status</option>
        <option value="ativo" <?= $st === 'ativo' ? 'selected' : '' ?>>Ativos</option><option value="inativo" <?= $st === 'inativo' ? 'selected' : '' ?>>Inativos</option></select></form>
    <table><thead><tr><th>CÓDIGO</th><th>PRODUTO</th><th>PESO</th><th>STATUS</th></tr></thead><tbody>
    <?php foreach ($lista as $r): ?>
      <tr onclick="location='produtos.php?editar=<?= $r['id'] ?>'" class="link"><td><?= cod('PR', $r['id']) ?></td><td><?= h($r['nome']) ?><small><?= h($r['categoria']) ?></small></td>
        <td><?= kg($r['peso']) ?> / <?= h($r['unidade']) ?></td><td><span class="pill <?= $r['ativo'] ? 'ok' : 'off' ?>"><?= $r['ativo'] ? 'Ativo' : 'Inativo' ?></span></td></tr>
    <?php endforeach; if (!$lista) echo '<tr><td colspan="4" class="vazio">Nenhum produto encontrado.</td></tr>'; ?></tbody></table></section>
  <section class="panel"><div class="ph"><h2><?= $ed ? 'Editar produto' : 'Cadastrar produto' ?></h2><?php if ($ed): ?><a href="produtos.php" class="x">×</a><?php endif; ?></div>
    <p class="sub"><?= $ed ? 'Atualize as informações do cadastro.' : 'Informe os dados do novo produto.' ?></p>
    <form method="post" class="form"><input type="hidden" name="id" value="<?= $ed['id'] ?? 0 ?>">
      <?php if ($ed): ?><div class="info"><?= cod('PR', $ed['id']) ?></div><?php endif; ?>
      <label>Nome do produto *<input name="nome" required value="<?= h($ed['nome'] ?? '') ?>"></label>
      <label>Categoria *<input name="categoria" required value="<?= h($ed['categoria'] ?? '') ?>"></label>
      <div class="row"><label>Peso unitário (kg) *<input name="peso" type="number" step="0.01" min="0.01" required value="<?= h($ed['peso'] ?? '') ?>"></label>
        <label>Unidade *<select name="unidade" required><?php foreach (['un', 'cx', 'kg', 'pç', 'pal', 'sc'] as $u) echo "<option" . (($ed['unidade'] ?? '') === $u ? ' selected' : '') . ">$u</option>"; ?></select></label></div>
      <label>Descrição<textarea name="descricao" rows="3"><?= h($ed['descricao'] ?? '') ?></textarea></label>
      <div class="lbl">Status</div><label class="sw"><input type="checkbox" name="ativo" <?= ($ed['ativo'] ?? 1) ? 'checked' : '' ?>><span></span> Produto ativo</label>
      <div class="row btns"><a class="btn ghost" href="produtos.php">Cancelar</a><button class="btn"><?= $ed ? 'Salvar alterações' : 'Cadastrar produto' ?></button></div></form></section>
</div>
<?php rodape('Mantenha o catálogo atualizado para agilizar o cadastro dos seus fretes.');
