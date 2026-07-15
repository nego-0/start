<?php
// ============================================================
// teste-design.php — Testes da camada de tema (Fase 1)
//   Verifica a renderização SEM base de dados. Correr com:
//     php teste-design.php
//   Sai com código 0 se tudo passar, 1 caso contrário.
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/modelos.php';
require_once __DIR__ . '/design.php';

$tpl = file_get_contents(__DIR__ . '/assets/convite-base.html');
$extra = [
    'GUEST_NAME' => 'Família Teste', 'MESA_BLOCK' => '', 'GUEST_NOTE' => '',
    'CONFIRM_URL' => '#', 'DOWNLOAD_URL' => '#', 'QR_VALUE' => 'X',
];

$falhas = 0;
function ok($cond, $msg) { global $falhas; echo ($cond ? '  OK   ' : '  FALHA') . "  $msg\n"; if (!$cond) $falhas++; }

// --- 1) Design padrão: render fiel ao convite atual ---
$d = designPadrao();
$html = aplicarDesign($tpl, $d, $extra);
ok(!preg_match('/\{\{[A-Z_]+\}\}/', $html), 'Sem tokens {{...}} por resolver (padrão)');
ok(strpos($html, 'Isabel') !== false && strpos($html, 'Abednego') !== false, 'Nomes presentes');
ok(strpos($html, '<style id="tema-convite">') !== false, 'CSS de tema injetado');
ok(strpos($html, '--forest-deep:#16261E;') !== false, 'Paleta padrão (forest-deep)');
ok(strpos($html, "--ff-serif:'Cormorant Garamond',serif;") !== false, 'Tipografia serif padrão');
ok(strpos($html, "new Date('2026-12-19T20:30:00+01:00')") !== false, 'Data da contagem correta');
ok(strpos($html, 'DTSTART:20261219T193000Z') !== false, 'ICS DTSTART em UTC correto');
ok(strpos($html, 'foreground:"#16261E"') !== false, 'QR foreground = forest-deep');
ok(strpos($html, 'background:"#FBF8F1"') !== false, 'QR background = ivory');
ok(strpos($html, 'Família Teste') !== false, 'Token do convidado aplicado');
ok(strpos($html, '<title>Isabel &amp; Abednego — 19 de Dezembro de 2026</title>') !== false, 'Título derivado');
ok(strpos($html, 'Sábado') !== false, 'Dia da semana derivado (Sábado)');
ok(strpos($html, '@import') === false, 'Padrão não importa fontes web (offline)');
ok(strpos($html, 'enquanto dure.&rdquo;</blockquote>') !== false, 'Texto rico preservado (<br>/entidades)');

// --- 2) Modelo Borgonha + secção oculta + fonte web ---
$b = normalizarDesign([
    'modelo' => 'borgonha',
    'paleta' => modelo('borgonha')['paleta'],
    'tipografia' => modelo('borgonha')['tipografia'],
    'seccoes' => ['historia' => false],
]);
$html2 = aplicarDesign($tpl, $b, $extra);
ok(strpos($html2, '--forest-deep:#2A0E16;') !== false, 'Borgonha: paleta aplicada');
ok(strpos($html2, "--ff-serif:'Playfair Display',serif;") !== false, 'Borgonha: serif Playfair');
ok(strpos($html2, '@import') !== false && strpos($html2, 'Playfair+Display') !== false, 'Borgonha: importa Google Fonts');
ok(strpos($html2, '#historia{display:none!important}') !== false, 'Secção história ocultada');
ok(strpos($html2, 'foreground:"#2A0E16"') !== false, 'Borgonha: QR segue a paleta');

// --- 3) Normalização / validação ---
$n = normalizarDesign(['paleta' => ['gold' => 'nao-hex', 'forest' => '#123ABC']]);
ok($n['paleta']['gold'] === '#B4864A', 'Hex inválido revertido ao padrão');
ok($n['paleta']['forest'] === '#123ABC', 'Hex válido aceite (maiúsculas)');
$n2 = normalizarDesign(['tipografia' => ['serif' => 'montserrat']]); // montserrat é 'sans'
ok($n2['tipografia']['serif'] === 'cormorant', 'Fonte de papel errado rejeitada');

// --- 4) Secções/páginas extra componíveis ---
$sx = normalizarDesign(['seccoes_extra' => [
    ['tipo' => 'lista', 'titulo' => 'Padrinhos', 'itens' => "Ana Sousa\nBruno Lima"],
    ['tipo' => 'citacao', 'verso' => 'Onde tu fores, irei eu.', 'autor' => 'Rute 1:16'],
    ['tipo' => 'invalido', 'titulo' => 'Ignorar'],
]]);
ok(count($sx['seccoes_extra']) === 2, 'Secção de tipo inválido descartada');
ok($sx['seccoes_extra'][0]['tipo'] === 'lista', 'Ordem das secções extra preservada');
$htmlSx = aplicarDesign($tpl, $sx, $extra);
ok(strpos($htmlSx, 'Padrinhos') !== false, 'Secção extra "lista" renderizada');
ok(strpos($htmlSx, 'Ana Sousa') !== false && strpos($htmlSx, 'Bruno Lima') !== false, 'Itens da lista renderizados');
ok(strpos($htmlSx, 'Rute 1:16') !== false, 'Secção extra "citação" renderizada');
$posSx = strpos($htmlSx, 'Padrinhos');
$posAcesso = strpos($htmlSx, 'id="acesso"');
ok($posSx !== false && $posAcesso !== false && $posSx < $posAcesso, 'Secções extra antes do passe de entrada');
ok(strpos($htmlSx, '{{SECCOES_EXTRA}}') === false, 'Token SECCOES_EXTRA resolvido');
// Sem secções extra, o token desaparece sem deixar marca
$semSx = aplicarDesign($tpl, normalizarDesign([]), $extra);
ok(strpos($semSx, '{{SECCOES_EXTRA}}') === false, 'Token SECCOES_EXTRA removido quando vazio');

echo "\n" . ($falhas === 0 ? "TODOS OS TESTES PASSARAM \xE2\x9C\x85" : "FALHAS: $falhas") . "\n";
exit($falhas === 0 ? 0 : 1);
