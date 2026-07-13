<?php
// ============================================================
// editor-modelos.php — Editor de modelos do convite (Fase 1)
//   Galeria de modelos + personalização por configuração:
//   paleta, tipografia, secções e textos, com pré-visualização
//   ao vivo. Só o administrador acede.
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/design.php';
exigirAdmin();

$flash = '';

// ---- Guardar (Post/Redirect/Get) -----------------------------
if (($_POST['acao'] ?? '') === 'guardar') {
    $cfg = json_decode($_POST['design'] ?? '', true);
    if (is_array($cfg)) {
        guardarDesign($conn, $cfg, 'Convite');
        header('Location: editor-modelos.php?ok=1');
        exit;
    }
    $flash = 'Não foi possível ler o design enviado.';
}
if (isset($_GET['ok'])) $flash = 'Modelo guardado. O convite já reflete as alterações.';

$design   = carregarDesignAtivo($conn);
$modelos  = modelosDisponiveis();
$fontes   = fontesDisponiveis();
$labelsPal = rotulosPaleta();
$labelsSec = rotulosSeccoes();

// Rótulos amigáveis dos textos (e se são multilinha)
$labelsTex = [
    'capa_dica'     => ['Capa · dica', 0],
    'hero_kicker'   => ['Topo · frase pequena', 0],
    'hero_sub'      => ['Topo · subtítulo', 0],
    'conv_eyebrow'  => ['Convite · sobretítulo', 0],
    'conv_lead'     => ['Convite · texto principal', 1],
    'conv_closing'  => ['Convite · encerramento', 1],
    'hist_eyebrow'  => ['História · sobretítulo', 0],
    'hist_titulo'   => ['História · título', 0],
    'hist_citacao'  => ['História · citação', 0],
    'hist_autor'    => ['História · autor', 0],
    'inter_verso'   => ['Interlúdio · verso (use &lt;br&gt; p/ quebrar)', 1],
    'inter_autor'   => ['Interlúdio · autor', 0],
    'inter_fecho'   => ['Interlúdio · fecho', 1],
    'venue_titulo'  => ['Local · título', 0],
    'venue_local'   => ['Local · morada (use &lt;br&gt;)', 1],
    'venue_mapa'    => ['Local · ligação do mapa', 0],
    'crono_titulo'  => ['Cronograma · título', 0],
    'rsvp_titulo'   => ['RSVP · título (use &lt;br&gt;)', 1],
    'rsvp_sub'      => ['RSVP · subtítulo', 1],
    'rsvp_deadline' => ['RSVP · prazo', 0],
    'footer_nota'   => ['Rodapé · citação', 1],
];

$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Editor de modelos · Convite</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="assets/estilo.css" rel="stylesheet">
<style>
  :root{ --e-line:#e6dfce; }
  body{ background:#f3efe6; }
  .em-top{ display:flex; align-items:center; gap:.8rem; flex-wrap:wrap; padding:1rem 1.2rem; background:#16261E; color:#EFE3CB; }
  .em-top h1{ font-family:'Cormorant Garamond',serif; font-size:1.5rem; margin:0; font-weight:600; }
  .em-top .sp{ flex:1; }
  .em-top a, .em-top button{ font-family:'Jost',sans-serif; font-size:.86rem; }
  .em-top a{ color:#EFE3CB; text-decoration:none; border:1px solid rgba(217,188,140,.4); padding:.45rem .8rem; border-radius:50px; }
  .em-top a:hover{ background:rgba(217,188,140,.14); }
  .btn-guardar{ background:linear-gradient(135deg,#B4864A,#8A6031); color:#fff; border:none; padding:.55rem 1.1rem; border-radius:50px; cursor:pointer; font-weight:500; }
  .btn-guardar:active{ transform:translateY(1px); }
  .flash{ background:#dff0e0; color:#1f5130; border:1px solid #b7dcbd; padding:.6rem 1rem; margin:.8rem 1.2rem 0; border-radius:10px; font-size:.9rem; }

  .em-wrap{ display:grid; grid-template-columns:minmax(0,1fr) minmax(0,420px); gap:1.1rem; padding:1.1rem 1.2rem; align-items:start; }
  @media (max-width:900px){ .em-wrap{ grid-template-columns:1fr; } .em-preview{ position:static !important; height:70vh !important; } }

  .card{ background:#fff; border:1px solid var(--e-line); border-radius:14px; padding:1rem 1.1rem; margin-bottom:1rem; }
  .card h2{ font-family:'Cormorant Garamond',serif; font-size:1.15rem; color:#20342A; margin:0 0 .2rem; }
  .card .hint{ font-size:.78rem; color:#8a8f88; margin:0 0 .8rem; }

  .modelos{ display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:.7rem; }
  .modelo{ border:2px solid var(--e-line); border-radius:12px; padding:.6rem; cursor:pointer; background:#fff; text-align:left; transition:.15s; }
  .modelo:hover{ border-color:#D9BC8C; }
  .modelo.sel{ border-color:#16261E; box-shadow:0 6px 16px rgba(22,38,30,.14); }
  .modelo .amostra{ display:flex; height:34px; border-radius:8px; overflow:hidden; margin-bottom:.5rem; }
  .modelo .amostra span{ flex:1; }
  .modelo .mn{ font-family:'Cormorant Garamond',serif; font-weight:600; font-size:1rem; color:#20342A; }
  .modelo .md{ font-size:.72rem; color:#8a8f88; line-height:1.3; margin-top:.15rem; }

  .grade{ display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:.7rem .9rem; }
  .campo{ display:flex; flex-direction:column; gap:.25rem; }
  .campo label{ font-size:.74rem; color:#5c6b5f; font-weight:500; }
  .campo input[type=text], .campo input[type=date], .campo input[type=time], .campo textarea, .campo select{
    border:1px solid var(--e-line); border-radius:8px; padding:.4rem .55rem; font:inherit; font-size:.86rem; background:#fff; width:100%; }
  .campo textarea{ resize:vertical; min-height:2.4rem; }
  .cor{ display:flex; align-items:center; gap:.5rem; }
  .cor input[type=color]{ width:38px; height:32px; border:1px solid var(--e-line); border-radius:8px; padding:2px; background:#fff; cursor:pointer; }
  .cor input[type=text]{ width:100%; text-transform:uppercase; font-family:ui-monospace,monospace; font-size:.8rem; }
  .secs{ display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:.5rem; }
  .sec{ display:flex; align-items:center; gap:.5rem; background:#faf7ef; border:1px solid var(--e-line); border-radius:10px; padding:.5rem .7rem; font-size:.86rem; }
  .sec input{ width:18px; height:18px; }

  .em-preview{ position:sticky; top:1.1rem; height:calc(100vh - 2.2rem); background:#16261E; border-radius:16px; overflow:hidden; border:1px solid var(--e-line); }
  .em-preview .pv-bar{ display:flex; align-items:center; gap:.5rem; padding:.5rem .8rem; color:#EFE3CB; font-size:.78rem; }
  .em-preview .pv-bar button{ background:rgba(217,188,140,.16); color:#EFE3CB; border:1px solid rgba(217,188,140,.35); border-radius:50px; padding:.3rem .7rem; cursor:pointer; font:inherit; font-size:.76rem; }
  .em-preview iframe{ width:100%; height:calc(100% - 34px); border:0; background:#16261E; }
  .textos-grade{ display:grid; grid-template-columns:1fr 1fr; gap:.7rem .9rem; }
  @media (max-width:620px){ .textos-grade{ grid-template-columns:1fr; } }
</style>
</head>
<body>
<div class="em-top">
  <h1>Editor de modelos</h1>
  <span class="sp"></span>
  <a href="index.php">← Voltar ao painel</a>
  <a href="convite-digital.php?preview=1" target="previewFrame" onclick="return false;" style="display:none">pv</a>
  <button class="btn-guardar" type="button" onclick="guardar()">Guardar modelo</button>
</div>
<?php if ($flash): ?><div class="flash"><?= $H($flash) ?></div><?php endif; ?>

<div class="em-wrap">
  <!-- ---------------- Controlos ---------------- -->
  <div class="em-cols">

    <div class="card">
      <h2>Galeria de modelos</h2>
      <p class="hint">Escolha um ponto de partida — pode ajustar tudo a seguir.</p>
      <div class="modelos">
        <?php foreach ($modelos as $id => $m): ?>
          <button type="button" class="modelo<?= $design['modelo'] === $id ? ' sel' : '' ?>" data-modelo="<?= $H($id) ?>" onclick="aplicarModelo('<?= $H($id) ?>')">
            <span class="amostra">
              <?php foreach (['forest-deep','forest','gold','gold-soft','ivory'] as $ck): ?>
                <span style="background:<?= $H($m['paleta'][$ck]) ?>"></span>
              <?php endforeach; ?>
            </span>
            <span class="mn"><?= $H($m['nome']) ?></span>
            <div class="md"><?= $H($m['descricao']) ?></div>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h2>O casal e a data</h2>
      <div class="grade">
        <div class="campo"><label>Noiva</label><input type="text" data-g="ev" data-k="noiva" value="<?= $H($design['evento']['noiva']) ?>"></div>
        <div class="campo"><label>Noivo</label><input type="text" data-g="ev" data-k="noivo" value="<?= $H($design['evento']['noivo']) ?>"></div>
        <div class="campo"><label>Iniciais (selo)</label><input type="text" data-g="ev" data-k="iniciais" value="<?= $H($design['evento']['iniciais']) ?>"></div>
        <div class="campo"><label>Data</label><input type="date" data-g="ev" data-k="data_iso" value="<?= $H($design['evento']['data_iso']) ?>"></div>
        <div class="campo"><label>Hora</label><input type="time" data-g="ev" data-k="hora" value="<?= $H($design['evento']['hora']) ?>"></div>
        <div class="campo"><label>Local (curto, rodapé)</label><input type="text" data-g="ev" data-k="local_curto" value="<?= $H($design['evento']['local_curto']) ?>"></div>
      </div>
    </div>

    <div class="card">
      <h2>Paleta de cores</h2>
      <p class="hint">As cores propagam-se por todo o convite (incluindo o código QR).</p>
      <div class="grade">
        <?php foreach (chavesPaleta() as $ck): $v = $design['paleta'][$ck]; ?>
          <div class="campo">
            <label><?= $H($labelsPal[$ck]) ?></label>
            <div class="cor">
              <input type="color" value="<?= $H($v) ?>" data-cor="<?= $H($ck) ?>" oninput="corSync(this)">
              <input type="text" value="<?= $H($v) ?>" data-g="pal" data-k="<?= $H($ck) ?>" data-cortxt="<?= $H($ck) ?>" oninput="corTxtSync(this)">
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h2>Tipografia</h2>
      <div class="grade">
        <?php
        $papeis = ['serif' => 'Títulos e nomes', 'sans' => 'Corpo e interface', 'script' => 'Manuscrita (selo)'];
        foreach ($papeis as $papel => $rot):
            $atual = $design['tipografia'][$papel]; ?>
          <div class="campo">
            <label><?= $H($rot) ?></label>
            <select data-g="tip" data-k="<?= $H($papel) ?>" onchange="atualizarPreview()">
              <?php foreach (fontesDoPapel($papel) as $fk => $f): ?>
                <option value="<?= $H($fk) ?>"<?= $fk === $atual ? ' selected' : '' ?>>
                  <?= $H($f['nome']) ?><?= $f['local'] ? '' : ' (web)' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="hint" style="margin-top:.6rem">As fontes marcadas “(web)” precisam de internet; o modelo Esmeralda usa fontes locais e funciona offline.</p>
    </div>

    <div class="card">
      <h2>Secções visíveis</h2>
      <p class="hint">Ligue ou desligue partes do convite.</p>
      <div class="secs">
        <?php foreach ($labelsSec as $sk => $rot): ?>
          <label class="sec">
            <input type="checkbox" data-g="sec" data-k="<?= $H($sk) ?>" <?= !empty($design['seccoes'][$sk]) ? 'checked' : '' ?> onchange="atualizarPreview()">
            <?= $H($rot) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <h2>Textos</h2>
      <p class="hint">Personalize as palavras do convite. Pode usar &lt;br&gt; para quebrar linhas.</p>
      <div class="textos-grade">
        <?php foreach ($labelsTex as $tk => [$rot, $multi]): $v = $design['textos'][$tk] ?? ''; ?>
          <div class="campo" style="<?= $multi ? 'grid-column:1/-1' : '' ?>">
            <label><?= $rot /* já seguro */ ?></label>
            <?php if ($multi): ?>
              <textarea data-g="tex" data-k="<?= $H($tk) ?>" rows="2" oninput="agenda()"><?= $H($v) ?></textarea>
            <?php else: ?>
              <input type="text" data-g="tex" data-k="<?= $H($tk) ?>" value="<?= $H($v) ?>" oninput="agenda()">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- ---------------- Pré-visualização ---------------- -->
  <div class="em-preview">
    <div class="pv-bar">
      <span>Pré-visualização ao vivo</span>
      <span class="sp" style="flex:1"></span>
      <button type="button" onclick="atualizarPreview()">Atualizar</button>
    </div>
    <iframe name="previewFrame" id="previewFrame"></iframe>
  </div>
</div>

<!-- Formulário oculto: envia o design para a pré-visualização (iframe) -->
<form id="previewForm" method="post" action="convite-digital.php?preview=1" target="previewFrame" style="display:none">
  <input type="hidden" name="design" id="previewDesign">
</form>

<!-- Formulário oculto: guardar -->
<form id="saveForm" method="post" action="editor-modelos.php" style="display:none">
  <input type="hidden" name="acao" value="guardar">
  <input type="hidden" name="design" id="saveDesign">
</form>

<script>
  var MODELOS = <?= json_encode($modelos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var CHAVES_PAL = <?= json_encode(chavesPaleta()) ?>;
  var modeloAtual = <?= json_encode($design['modelo']) ?>;

  // Recolhe o design completo a partir dos controlos.
  function coletar(){
    var d = { modelo: modeloAtual, evento:{}, paleta:{}, tipografia:{}, seccoes:{}, textos:{} };
    var mapa = { ev:'evento', pal:'paleta', tip:'tipografia', sec:'seccoes', tex:'textos' };
    document.querySelectorAll('[data-g][data-k]').forEach(function(el){
      var grupo = mapa[el.getAttribute('data-g')];
      var chave = el.getAttribute('data-k');
      if(!grupo) return;
      if(el.type === 'checkbox') d[grupo][chave] = el.checked;
      else d[grupo][chave] = el.value;
    });
    return d;
  }

  function atualizarPreview(){
    document.getElementById('previewDesign').value = JSON.stringify(coletar());
    document.getElementById('previewForm').submit();
  }

  // Debounce para digitação nos textos/cores
  var t = null;
  function agenda(){ clearTimeout(t); t = setTimeout(atualizarPreview, 550); }

  // Sincroniza o seletor de cor com o campo de texto e vice-versa
  function corSync(inp){
    var k = inp.getAttribute('data-cor');
    var txt = document.querySelector('[data-cortxt="'+k+'"]');
    if(txt) txt.value = inp.value.toUpperCase();
    agenda();
  }
  function corTxtSync(inp){
    var k = inp.getAttribute('data-cortxt');
    var col = document.querySelector('[data-cor="'+k+'"]');
    if(col && /^#?[0-9a-fA-F]{6}$/.test(inp.value.trim())){
      col.value = '#' + inp.value.trim().replace('#','');
    }
    agenda();
  }

  // Aplica um modelo (paleta + tipografia) aos controlos
  function aplicarModelo(id){
    var m = MODELOS[id]; if(!m) return;
    modeloAtual = id;
    document.querySelectorAll('.modelo').forEach(function(b){
      b.classList.toggle('sel', b.getAttribute('data-modelo') === id);
    });
    CHAVES_PAL.forEach(function(k){
      var col = document.querySelector('[data-cor="'+k+'"]');
      var txt = document.querySelector('[data-cortxt="'+k+'"]');
      if(col) col.value = m.paleta[k];
      if(txt) txt.value = String(m.paleta[k]).toUpperCase();
    });
    ['serif','sans','script'].forEach(function(p){
      var sel = document.querySelector('select[data-g="tip"][data-k="'+p+'"]');
      if(sel && m.tipografia[p]) sel.value = m.tipografia[p];
    });
    atualizarPreview();
  }

  function guardar(){
    document.getElementById('saveDesign').value = JSON.stringify(coletar());
    document.getElementById('saveForm').submit();
  }

  // Primeira pré-visualização
  window.addEventListener('load', atualizarPreview);
</script>
</body>
</html>
