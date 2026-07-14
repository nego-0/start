<?php
// ============================================================
// convite-impresso.php — Convite físico pronto para impressão.
//   A partir do MESMO design ativo (paleta, tipografia, textos,
//   casal e data) gera um cartão A5 com sangria de 3 mm e marcas
//   de corte, pronto a imprimir ou a guardar em PDF.
//   • ?moldura=dupla|simples|cantos — estilo da moldura.
//   • ?preview=1 (admin) — pré-visualiza um design do editor.
//   Requisitos de impressão (plano, Fase 2): dimensões corretas,
//   margem de corte e alta resolução. A conversão para CMYK é um
//   passo de pré-impressão feito pela gráfica.
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/design.php';

$preview = isset($_GET['preview']) && $_GET['preview'] === '1';
if ($preview) {
    if (!ehAdmin()) { http_response_code(403); exit('Pré-visualização reservada ao administrador.'); }
    $design = null;
    if (isset($_POST['design'])) {
        $d = json_decode($_POST['design'], true);
        if (is_array($d)) $design = normalizarDesign($d);
    }
    if (!$design) $design = carregarDesignAtivo($conn);
} else {
    $design = carregarDesignAtivo($conn);
}

$molduras = ['dupla', 'simples', 'cantos'];
$moldura  = in_array($_GET['moldura'] ?? '', $molduras, true) ? $_GET['moldura'] : 'dupla';

$tok = mapaTextos($design);
$G = fn($k) => $tok[$k] ?? '';

$estilos = cssImportacaoFontes($design) . cssFontesLocais() . cssVariaveis($design);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Convite impresso · <?= $G('NOIVA') ?> &amp; <?= $G('NOIVO') ?></title>
<style>
<?= $estilos ?>

/* Medidas de impressão: A5 (corte 148×210) + sangria 3 mm => 154×216 */
:root{ --bleed:3mm; --trim-w:148mm; --trim-h:210mm; --media-w:154mm; --media-h:216mm; --safe:12mm; }
@page{ size:154mm 216mm; margin:0; }
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:var(--ff-sans); background:#3b3b3b; color:var(--text); }

/* Barra de ferramentas (só no ecrã) */
.barra{ position:sticky; top:0; z-index:10; display:flex; gap:.6rem; align-items:center; flex-wrap:wrap;
  padding:.7rem 1rem; background:#16261E; color:#EFE3CB; font-size:.85rem; }
.barra a, .barra button, .barra select{ font:inherit; font-size:.85rem; }
.barra a{ color:#EFE3CB; text-decoration:none; border:1px solid rgba(217,188,140,.4); padding:.4rem .8rem; border-radius:50px; }
.barra a:hover{ background:rgba(217,188,140,.14); }
.barra select{ background:#0f1a15; color:#EFE3CB; border:1px solid rgba(217,188,140,.4); border-radius:50px; padding:.4rem .7rem; }
.barra .sp{ flex:1; }
.btn-print{ background:linear-gradient(135deg,#B4864A,#8A6031); color:#fff; border:none; padding:.5rem 1.1rem; border-radius:50px; cursor:pointer; font-weight:500; }
.dica{ text-align:center; color:#d9d3c4; font-size:.8rem; padding:.6rem; }

.palco{ display:flex; justify-content:center; padding:26px 16px 50px; }

/* Folha (área de media, com sangria) */
.folha{ position:relative; width:var(--media-w); height:var(--media-h); background:var(--ivory);
  box-shadow:0 18px 60px rgba(0,0,0,.45); }

/* Marcas de corte (nos cantos do corte, dentro da sangria) */
.corte{ position:absolute; background:#111; }
.corte.h{ width:var(--bleed); height:.2mm; }
.corte.v{ width:.2mm; height:var(--bleed); }
.c-tl-h{ top:var(--bleed); left:0 }         .c-tl-v{ top:0; left:var(--bleed) }
.c-tr-h{ top:var(--bleed); right:0 }        .c-tr-v{ top:0; right:var(--bleed) }
.c-bl-h{ bottom:var(--bleed); left:0 }      .c-bl-v{ bottom:0; left:var(--bleed) }
.c-br-h{ bottom:var(--bleed); right:0 }     .c-br-v{ bottom:0; right:var(--bleed) }

/* Conteúdo dentro da margem de segurança */
.seguro{ position:absolute; inset:var(--safe); display:flex; }
.cartao{ flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center;
  text-align:center; color:var(--text); padding:10mm 8mm; }

/* Molduras */
.moldura-dupla .cartao{ border:1.4pt solid var(--gold); outline:.6pt solid var(--gold); outline-offset:2.2mm; }
.moldura-simples .cartao{ border:1pt solid var(--gold-soft); }
.moldura-cantos .cartao{ border:none; position:relative; }
.cantos-svg{ position:absolute; inset:0; pointer-events:none; }
.moldura-dupla .cantos-svg, .moldura-simples .cantos-svg{ display:none; }

.mono{ width:20mm; height:20mm; border-radius:50%; border:.8pt solid var(--gold); color:var(--gold);
  display:flex; align-items:center; justify-content:center; font-family:var(--ff-script); font-size:20pt; margin-bottom:6mm; }
.abertura{ font-size:8.5pt; letter-spacing:.32em; text-transform:uppercase; color:var(--gold); margin-bottom:5mm; }
.nomes{ font-family:var(--ff-serif); font-weight:600; color:var(--forest); font-size:34pt; line-height:1.05; }
.nomes .e{ font-family:var(--ff-script); font-weight:400; color:var(--gold); font-size:26pt; display:block; margin:1mm 0; }
.regua{ width:34mm; height:0; border-top:.7pt solid var(--gold); position:relative; margin:6mm 0; }
.regua::after{ content:""; position:absolute; left:50%; top:50%; width:1.6mm; height:1.6mm; background:var(--gold);
  transform:translate(-50%,-50%) rotate(45deg); }
.data{ font-family:var(--ff-serif); font-size:14pt; color:var(--forest); }
.hora{ font-family:var(--ff-serif); font-style:italic; font-size:11pt; color:var(--gold); margin-top:1mm; }
.local{ font-family:var(--ff-sans); font-size:9.5pt; line-height:1.5; color:var(--text); margin-top:6mm; }
.prazo{ font-family:var(--ff-serif); font-style:italic; font-size:9pt; color:var(--gold); margin-top:6mm; }

@media print{
  .no-print{ display:none !important; }
  body{ background:#fff; }
  .palco{ padding:0; }
  .folha{ box-shadow:none; }
}
</style>
</head>
<body>
<div class="barra no-print">
  <a href="index.php">← Painel</a>
  <a href="editor-modelos.php">Editar modelo</a>
  <span class="sp"></span>
  <label>Moldura:
    <select onchange="location.search='?moldura='+this.value">
      <?php foreach ($molduras as $m): ?>
        <option value="<?= $m ?>"<?= $m === $moldura ? ' selected' : '' ?>><?= ucfirst($m) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn-print" onclick="window.print()">Imprimir / Guardar PDF</button>
</div>

<div class="palco">
  <div class="folha moldura-<?= $moldura ?>">
    <!-- marcas de corte -->
    <span class="corte h c-tl-h"></span><span class="corte v c-tl-v"></span>
    <span class="corte h c-tr-h"></span><span class="corte v c-tr-v"></span>
    <span class="corte h c-bl-h"></span><span class="corte v c-bl-v"></span>
    <span class="corte h c-br-h"></span><span class="corte v c-br-v"></span>

    <div class="seguro">
      <div class="cartao">
        <svg class="cantos-svg" viewBox="0 0 100 140" preserveAspectRatio="none" aria-hidden="true">
          <g fill="none" stroke="var(--gold)" stroke-width=".5">
            <path d="M2 14 V2 H14"/><path d="M86 2 H98 V14"/>
            <path d="M98 126 V138 H86"/><path d="M14 138 H2 V126"/>
          </g>
        </svg>
        <div class="mono"><?= $G('INICIAIS') ?></div>
        <div class="abertura"><?= $G('IMPRESSO_ABERTURA') ?></div>
        <div class="nomes"><?= $G('NOIVA') ?><span class="e">&amp;</span><?= $G('NOIVO') ?></div>
        <div class="regua"></div>
        <div class="data"><?= $G('DATA_EXTENSA') ?></div>
        <div class="hora"><?= $G('HORA_EXTENSA') ?></div>
        <div class="local"><?= $G('VENUE_LOCAL') ?></div>
        <div class="prazo"><?= $G('RSVP_DEADLINE') ?></div>
      </div>
    </div>
  </div>
</div>
<p class="dica no-print">Convite de impressão A5 (corte 148×210 mm) com sangria de 3 mm e marcas de corte. Ao guardar em PDF, escolha “Tamanho real / 100%”.</p>
</body>
</html>
