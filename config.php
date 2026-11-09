<?php
// Configuração, banco (SQLite via PDO) e funções auxiliares
date_default_timezone_set('America/Sao_Paulo');
session_start();
if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0775, true);
$db = new PDO('sqlite:' . __DIR__ . '/data/leva.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->exec("PRAGMA foreign_keys = ON");
$db->exec("
CREATE TABLE IF NOT EXISTS produtos(
  id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, categoria TEXT NOT NULL,
  peso REAL NOT NULL, unidade TEXT NOT NULL, descricao TEXT, ativo INTEGER NOT NULL DEFAULT 1);
CREATE TABLE IF NOT EXISTS enderecos(
  id INTEGER PRIMARY KEY AUTOINCREMENT, tipo TEXT NOT NULL CHECK(tipo IN ('origem','destino')),
  nome TEXT NOT NULL, cep TEXT NOT NULL, estado TEXT NOT NULL, logradouro TEXT NOT NULL,
  numero TEXT NOT NULL, bairro TEXT NOT NULL, cidade TEXT NOT NULL, complemento TEXT);
CREATE TABLE IF NOT EXISTS transportadores(
  id INTEGER PRIMARY KEY AUTOINCREMENT, razao_social TEXT NOT NULL, cnpj TEXT NOT NULL,
  responsavel TEXT NOT NULL, telefone TEXT NOT NULL, email TEXT NOT NULL, ativo INTEGER NOT NULL DEFAULT 1);
CREATE TABLE IF NOT EXISTS fretes(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  transportador_id INTEGER NOT NULL REFERENCES transportadores(id),
  origem_id INTEGER NOT NULL REFERENCES enderecos(id),
  destino_id INTEGER NOT NULL REFERENCES enderecos(id),
  data_frete TEXT NOT NULL, observacoes TEXT, status TEXT NOT NULL DEFAULT 'agendado',
  criado_em TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS frete_itens(
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  frete_id INTEGER NOT NULL REFERENCES fretes(id) ON DELETE CASCADE,
  produto_id INTEGER NOT NULL REFERENCES produtos(id),
  quantidade INTEGER NOT NULL, peso_total REAL NOT NULL);
");

const STATUS = ['agendado' => 'Agendado', 'em_transito' => 'Em trânsito', 'entregue' => 'Entregue'];
const UFS = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function p($k) { return trim($_POST[$k] ?? ''); }
function redirect($url) { header("Location: $url"); exit; }
function flash($msg, $tipo = 'ok') { $_SESSION['flash'] = [$msg, $tipo]; }
function kg($v) { return number_format((float)$v, 2, ',', '.') . ' kg'; }
function cod($prefixo, $id) { return $prefixo . '-' . str_pad($id, 4, '0', STR_PAD_LEFT); }
function dataBr($iso) { return $iso ? date('d/m/Y', strtotime($iso)) : ''; }

// Fretes com dados relacionados (usado no relatório, CSV e PDF)
function buscarFretes(PDO $db, $data, $transp = '', $status = '') {
    $sql = "SELECT f.*, t.razao_social AS transportador, o.nome AS origem, d.nome AS destino,
      (SELECT COALESCE(SUM(peso_total),0) FROM frete_itens WHERE frete_id=f.id) AS peso,
      (SELECT COUNT(*) FROM frete_itens WHERE frete_id=f.id) AS qtd_produtos,
      (SELECT group_concat(p.nome || ' x' || i.quantidade, ', ') FROM frete_itens i
         JOIN produtos p ON p.id=i.produto_id WHERE i.frete_id=f.id) AS produtos
      FROM fretes f JOIN transportadores t ON t.id=f.transportador_id
      JOIN enderecos o ON o.id=f.origem_id JOIN enderecos d ON d.id=f.destino_id
      WHERE f.data_frete = ?";
    $args = [$data];
    if ($transp !== '') { $sql .= " AND f.transportador_id = ?"; $args[] = (int)$transp; }
    if ($status !== '') { $sql .= " AND f.status = ?"; $args[] = $status; }
    $s = $db->prepare($sql . " ORDER BY f.id");
    $s->execute($args);
    return $s->fetchAll();
}

// Gerador de PDF simples (sem dependências): tabela em A4 paisagem
function gerarPdf($titulo, $subtitulo, array $cabecalho, array $linhas) {
    $x = [40, 100, 245, 445, 690, 755];
    $max = [8, 30, 40, 45, 12, 14];
    $t = function ($s, $n = 0) {
        if ($n) $s = mb_strimwidth($s, 0, $n, '...', 'UTF-8');
        $s = @iconv('UTF-8', 'windows-1252//TRANSLIT', $s);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    };
    $txt = fn($f, $sz, $px, $py, $s) => sprintf("BT /%s %d Tf %d %d Td (%s) Tj ET\n", $f, $sz, $px, $py, $s);
    $paginas = []; $i = 0; $total = count($linhas);
    do {
        $c = $txt('F2', 16, 40, 550, $t($titulo)) . $txt('F1', 9, 40, 534, $t($subtitulo));
        foreach ($cabecalho as $k => $col) $c .= $txt('F2', 8, $x[$k], 508, $t($col));
        $c .= "40 502 m 802 502 l S\n";
        for ($y = 488; $y > 40 && $i < $total; $y -= 14, $i++)
            foreach ($linhas[$i] as $k => $v) $c .= $txt('F1', 8, $x[$k], $y, $t((string)$v, $max[$k]));
        $paginas[] = $c;
    } while ($i < $total);
    $o = [1 => '<< /Type /Catalog /Pages 2 0 R >>',
          3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
          4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
    $kids = []; $n = 5;
    foreach ($paginas as $c) {
        $kids[] = "$n 0 R";
        $o[$n] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents " . ($n + 1) . " 0 R >>";
        $o[$n + 1] = "<< /Length " . strlen($c) . " >>\nstream\n$c\nendstream";
        $n += 2;
    }
    $o[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
    ksort($o);
    $out = "%PDF-1.4\n"; $off = [];
    foreach ($o as $id => $body) { $off[] = strlen($out); $out .= "$id 0 obj\n$body\nendobj\n"; }
    $xr = strlen($out);
    $out .= "xref\n0 " . (count($o) + 1) . "\n0000000000 65535 f \n";
    foreach ($off as $f) $out .= sprintf("%010d 00000 n \n", $f);
    return $out . "trailer\n<< /Size " . (count($o) + 1) . " /Root 1 0 R >>\nstartxref\n$xr\n%%EOF";
}
