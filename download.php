<?php require 'config.php';
$data = $_GET['data'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) $data = date('Y-m-d');
$fretes = buscarFretes($db, $data, $_GET['transportador'] ?? '', $_GET['filtro_status'] ?? '');
$linhas = array_map(fn($f) => [cod('FR', $f['id']), $f['transportador'], $f['origem'] . ' -> ' . $f['destino'],
    $f['produtos'], kg($f['peso']), STATUS[$f['status']] ?? $f['status']], $fretes);
$cab = ['FRETE', 'TRANSPORTADOR', 'ROTA', 'PRODUTOS', 'PESO TOTAL', 'STATUS'];
$nome = "fretes_$data";
if (($_GET['formato'] ?? '') === 'pdf') {
    $linhas[] = ['', '', '', 'TOTAL', kg(array_sum(array_column($fretes, 'peso'))), count($fretes) . ' fretes'];
    header('Content-Type: application/pdf'); header("Content-Disposition: attachment; filename=$nome.pdf");
    echo gerarPdf('leva - Relatório de fretes', 'Data do frete: ' . dataBr($data) . ' | Gerado em ' . date('d/m/Y H:i'), $cab, $linhas); exit;
}
header('Content-Type: text/csv; charset=UTF-8'); header("Content-Disposition: attachment; filename=$nome.csv");
$out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['Frete', 'Data', 'Transportador', 'Origem', 'Destino', 'Produtos', 'Peso total (kg)', 'Status'], ';');
foreach ($fretes as $f)
    fputcsv($out, [cod('FR', $f['id']), dataBr($f['data_frete']), $f['transportador'], $f['origem'], $f['destino'], $f['produtos'], number_format($f['peso'], 2, ',', ''), STATUS[$f['status']]], ';');
