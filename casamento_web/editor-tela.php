<?php
// ============================================================
// editor-tela.php — Editor visual de tela do convite impresso (Fase 2)
//   Desenho livre com Fabric.js: texto, imagens e formas, camadas,
//   arrastar/redimensionar/rodar, desfazer/refazer, guias de sangria
//   e margem, exportação PNG (300 dpi) e PDF (A5), guardar/carregar.
//   Bibliotecas servidas localmente (assets/vendor). Só administrador.
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/conta.php';
require_once __DIR__ . '/impresso.php';
exigirEdicao($conn);   // admin legado (evento 1) ou casal (o seu evento)

$flash = '';
if (($_POST['acao'] ?? '') === 'guardar') {
    $ok = guardarImpressoTela($conn, (string)($_POST['tela'] ?? ''));
    header('Location: editor-tela.php?' . ($ok ? 'ok=1' : 'erro=1'));
    exit;
}
if (isset($_GET['ok']))  $flash = 'Tela guardada.';
if (isset($_GET['erro'])) $flash = 'Não foi possível guardar (dados inválidos).';

$design   = carregarDesignAtivo($conn);
$telaJson = carregarImpressoTela($conn);          // string JSON ou null
$dados    = dadosTelaDoDesign($design);
$estilos  = cssImportacaoFontes($design) . cssFontesLocais();
$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Editor de tela · Convite impresso</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="assets/vendor/fabric.min.js"></script>
<script src="assets/vendor/jspdf.umd.min.js"></script>
<style>
<?= $estilos ?>
  :root{ --e-line:#e6dfce; }
  *{ box-sizing:border-box; }
  body{ margin:0; font-family:'Jost',sans-serif; background:#f3efe6; color:#26332b; }
  .topo{ display:flex; align-items:center; gap:.6rem; flex-wrap:wrap; padding:.7rem 1rem; background:#16261E; color:#EFE3CB; }
  .topo h1{ font-family:'Cormorant Garamond',serif; font-size:1.35rem; margin:0; font-weight:600; }
  .topo .sp{ flex:1; }
  .topo a{ color:#EFE3CB; text-decoration:none; border:1px solid rgba(217,188,140,.4); padding:.4rem .8rem; border-radius:50px; font-size:.84rem; }
  .topo a:hover{ background:rgba(217,188,140,.14); }
  .topo button{ font:inherit; font-size:.84rem; border:1px solid rgba(217,188,140,.4); background:rgba(217,188,140,.1); color:#EFE3CB; padding:.4rem .8rem; border-radius:50px; cursor:pointer; }
  .topo .b-guardar{ background:linear-gradient(135deg,#B4864A,#8A6031); border:none; color:#fff; font-weight:500; }
  .flash{ background:#dff0e0; color:#1f5130; border:1px solid #b7dcbd; padding:.5rem 1rem; margin:.6rem 1rem 0; border-radius:10px; font-size:.88rem; }

  .area{ display:grid; grid-template-columns:190px 1fr 240px; gap:.8rem; padding:.8rem 1rem; align-items:start; }
  @media (max-width:1000px){ .area{ grid-template-columns:1fr; } .palco{ order:-1; } }
  .painel{ background:#fff; border:1px solid var(--e-line); border-radius:14px; padding:.8rem; }
  .painel h2{ font-family:'Cormorant Garamond',serif; font-size:1rem; margin:0 0 .5rem; color:#20342A; }
  .grp{ display:flex; flex-direction:column; gap:.4rem; margin-bottom:.8rem; }
  .btn{ display:flex; align-items:center; gap:.5rem; width:100%; text-align:left; font:inherit; font-size:.85rem;
    border:1px solid var(--e-line); background:#fbf8f1; border-radius:9px; padding:.5rem .65rem; cursor:pointer; }
  .btn:hover{ border-color:#D9BC8C; background:#f6efdd; }
  .btn.wide{ justify-content:center; }
  .lin{ display:flex; gap:.4rem; }
  .lin .btn{ flex:1; justify-content:center; }
  .palco{ display:flex; flex-direction:column; align-items:center; gap:.5rem; }
  .telawrap{ background:#cfc9bb; border-radius:12px; padding:16px; box-shadow:inset 0 2px 10px rgba(0,0,0,.08); overflow:auto; max-width:100%; }
  canvas{ box-shadow:0 12px 40px rgba(0,0,0,.25); }
  .ajuda{ color:#7a8074; font-size:.78rem; text-align:center; }
  .prop label{ font-size:.72rem; color:#5c6b5f; display:block; margin:.5rem 0 .2rem; }
  .prop input[type=range]{ width:100%; }
  .prop select, .prop input[type=number], .prop input[type=text]{ width:100%; font:inherit; font-size:.84rem; border:1px solid var(--e-line); border-radius:8px; padding:.35rem .45rem; }
  .prop .row{ display:flex; gap:.4rem; align-items:center; }
  .prop .row .btn{ padding:.35rem; }
  .cor{ width:100%; height:32px; border:1px solid var(--e-line); border-radius:8px; padding:2px; background:#fff; }
  .vazio{ color:#9aa093; font-size:.82rem; }
  .sep{ height:1px; background:var(--e-line); margin:.7rem 0; }
  b.badge{ font-weight:500; font-size:.72rem; color:#8a7a52; }
</style>
</head>
<body>
<div class="topo">
  <h1>Editor de tela</h1>
  <b class="badge">convite impresso · A5</b>
  <span class="sp"></span>
  <a href="convite-impresso.php" target="_blank">Ver versão “config”</a>
  <a href="<?= $H(urlPainel()) ?>">← Painel</a>
  <button type="button" onclick="exportarPNG()">PNG</button>
  <button type="button" onclick="exportarPDF()">PDF</button>
  <button type="button" class="b-guardar" onclick="guardar()">Guardar</button>
</div>
<?php if ($flash): ?><div class="flash"><?= $H($flash) ?></div><?php endif; ?>

<div class="area">
  <!-- Ferramentas -->
  <div class="painel">
    <h2>Adicionar</h2>
    <div class="grp">
      <button class="btn" onclick="addTitulo()">✎ Título</button>
      <button class="btn" onclick="addCorpo()">¶ Texto</button>
      <button class="btn" onclick="document.getElementById('fimg').click()">🖼 Imagem</button>
      <input type="file" id="fimg" accept="image/*" hidden onchange="addImagem(this)">
      <div class="lin">
        <button class="btn" title="Linha" onclick="addLinha()">— Linha</button>
        <button class="btn" title="Retângulo" onclick="addRet()">▭</button>
      </div>
      <div class="lin">
        <button class="btn" title="Círculo" onclick="addCirc()">◯</button>
        <button class="btn" title="Coração" onclick="addCoracao()">♥</button>
      </div>
    </div>
    <div class="sep"></div>
    <div class="grp">
      <button class="btn wide" onclick="reporModelo()">↺ Repor modelo</button>
      <button class="btn wide" onclick="limpar()">🗑 Esvaziar tela</button>
    </div>
  </div>

  <!-- Tela -->
  <div class="palco">
    <div class="telawrap"><canvas id="c"></canvas></div>
    <p class="ajuda">Corte a tracejado · margem de segurança a ponteado. Arraste, redimensione e rode livremente.</p>
  </div>

  <!-- Propriedades -->
  <div class="painel prop">
    <h2>Objeto</h2>
    <div class="grp">
      <div class="lin">
        <button class="btn" title="Trazer para a frente" onclick="camada(1)">⬆ Frente</button>
        <button class="btn" title="Enviar para trás" onclick="camada(-1)">⬇ Trás</button>
      </div>
      <div class="lin">
        <button class="btn" onclick="duplicar()">⧉ Duplicar</button>
        <button class="btn" onclick="apagar()">🗑 Apagar</button>
      </div>
      <div class="lin">
        <button class="btn" onclick="desfazer()">↶ Desfazer</button>
        <button class="btn" onclick="refazer()">↷ Refazer</button>
      </div>
    </div>
    <div class="sep"></div>
    <div id="semSel" class="vazio">Selecione um objeto para editar.</div>
    <div id="propTexto" style="display:none">
      <label>Tipo de letra</label>
      <select id="pFonte" onchange="aplicar('fontFamily', this.value)"></select>
      <label>Tamanho <span id="pTamV"></span></label>
      <input type="range" id="pTam" min="6" max="120" oninput="aplicar('fontSize', +this.value)">
      <label>Cor do texto</label>
      <input type="color" class="cor" id="pCor" oninput="aplicar('fill', this.value)">
      <label>Espaçamento entre letras <span id="pEspV"></span></label>
      <input type="range" id="pEsp" min="0" max="900" oninput="aplicar('charSpacing', +this.value)">
      <div class="row" style="margin-top:.5rem">
        <button class="btn" onclick="alternar('fontWeight','bold','normal')"><b>N</b></button>
        <button class="btn" onclick="alternar('fontStyle','italic','normal')"><i>I</i></button>
        <button class="btn" onclick="aplicar('textAlign','left')">⯇</button>
        <button class="btn" onclick="aplicar('textAlign','center')">≡</button>
        <button class="btn" onclick="aplicar('textAlign','right')">⯈</button>
      </div>
    </div>
    <div id="propForma" style="display:none">
      <label>Cor de preenchimento</label>
      <input type="color" class="cor" id="fCor" oninput="aplicar('fill', this.value)">
      <label>Cor do traço</label>
      <input type="color" class="cor" id="fStroke" oninput="aplicar('stroke', this.value)">
    </div>
    <div id="propComum" style="display:none">
      <label>Opacidade <span id="pOpV"></span></label>
      <input type="range" id="pOp" min="10" max="100" oninput="aplicar('opacity', (+this.value)/100)">
    </div>
  </div>
</div>

<!-- Formulário oculto: guardar -->
<form id="fSave" method="post" style="display:none">
  <input type="hidden" name="acao" value="guardar">
  <input type="hidden" name="tela" id="telaJson">
</form>

<script>
  var DESIGN = <?= json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  var TELA_SALVA = <?= $telaJson !== null ? $telaJson : 'null' ?>;

  // --- Medidas de impressão (px @ 96dpi; export a 300dpi) ---
  var MM = 3.7795275591;                 // px por mm
  var MEDIA_W = Math.round(154*MM), MEDIA_H = Math.round(216*MM); // com sangria 3mm
  var BLEED = Math.round(3*MM), SAFE = Math.round(15*MM);
  var CX = MEDIA_W/2;

  var canvas = new fabric.Canvas('c', {
    width: MEDIA_W, height: MEDIA_H,
    backgroundColor: DESIGN.paleta.ivory,
    preserveObjectStacking: true
  });

  // ---------- Guias (corte + margem de segurança) ----------
  var guias = [];
  function desenharGuias(){
    guias.forEach(function(g){ canvas.remove(g); });
    guias = [];
    var corte = new fabric.Rect({ left:BLEED, top:BLEED, width:MEDIA_W-2*BLEED, height:MEDIA_H-2*BLEED,
      fill:'transparent', stroke:'#c0392b', strokeDashArray:[6,4], strokeWidth:1,
      selectable:false, evented:false, excludeFromExport:true, hoverCursor:'default' });
    var seg = new fabric.Rect({ left:SAFE, top:SAFE, width:MEDIA_W-2*SAFE, height:MEDIA_H-2*SAFE,
      fill:'transparent', stroke:'rgba(22,38,30,.35)', strokeDashArray:[2,4], strokeWidth:1,
      selectable:false, evented:false, excludeFromExport:true, hoverCursor:'default' });
    guias = [corte, seg];
    guias.forEach(function(g){ canvas.add(g); g.moveTo(9999); });
  }

  // ---------- Fábrica de objetos ----------
  function base(o){ return Object.assign({ originX:'center', left:CX, textAlign:'center' }, o); }
  function novoTexto(txt, o){
    var t = new fabric.IText(txt, base(o));
    canvas.add(t); canvas.setActiveObject(t); registar(); return t;
  }
  function addTitulo(){ novoTexto('Título', { top:120, fontFamily:DESIGN.fontes.serif, fontSize:40, fill:DESIGN.paleta.forest }); }
  function addCorpo(){ novoTexto('Escreva aqui…', { top:200, fontFamily:DESIGN.fontes.sans, fontSize:16, fill:DESIGN.paleta.text }); }
  function addLinha(){
    var l = new fabric.Line([CX-70, 300, CX+70, 300], { stroke:DESIGN.paleta.gold, strokeWidth:1.5, originX:'center' });
    canvas.add(l); canvas.setActiveObject(l); registar();
  }
  function addRet(){
    var r = new fabric.Rect({ left:CX, top:340, width:120, height:70, originX:'center', fill:'transparent',
      stroke:DESIGN.paleta.gold, strokeWidth:1.5 });
    canvas.add(r); canvas.setActiveObject(r); registar();
  }
  function addCirc(){
    var c = new fabric.Circle({ left:CX, top:340, radius:44, originX:'center', fill:'transparent',
      stroke:DESIGN.paleta.gold, strokeWidth:1.5 });
    canvas.add(c); canvas.setActiveObject(c); registar();
  }
  function addCoracao(){
    var p = 'M 0 -12 C -12 -30 -40 -12 0 20 C 40 -12 12 -30 0 -12 z';
    var h = new fabric.Path(p, { left:CX, top:340, originX:'center', originY:'center',
      fill:DESIGN.paleta.gold, scaleX:1.1, scaleY:1.1 });
    canvas.add(h); canvas.setActiveObject(h); registar();
  }
  function addImagem(inp){
    var f = inp.files && inp.files[0]; if(!f) return;
    var rd = new FileReader();
    rd.onload = function(e){
      fabric.Image.fromURL(e.target.result, function(img){
        var max = MEDIA_W*0.6; if(img.width>max) img.scale(max/img.width);
        img.set({ left:CX, top:MEDIA_H/2, originX:'center', originY:'center' });
        canvas.add(img); canvas.setActiveObject(img); registar();
      });
    };
    rd.readAsDataURL(f); inp.value='';
  }

  // ---------- Modelo inicial (a partir do design) ----------
  function montarModelo(){
    canvas.getObjects().slice().forEach(function(o){ if(guias.indexOf(o)<0) canvas.remove(o); });
    var P=DESIGN.paleta, F=DESIGN.fontes, T=DESIGN.textos;
    var circ = new fabric.Circle({ left:CX, top:135, radius:34, originX:'center', originY:'center',
      fill:'transparent', stroke:P.gold, strokeWidth:1 });
    var mono = new fabric.IText(T.iniciais||'I&A', base({ top:135, originY:'center', fontFamily:F.script, fontSize:30, fill:P.gold }));
    var abre = new fabric.IText((T.abertura||'').toUpperCase(), base({ top:200, fontFamily:F.serif, fontSize:13, charSpacing:320, fill:P.gold }));
    var nomes= new fabric.IText((T.noiva||'')+'\n&\n'+(T.noivo||''), base({ top:240, fontFamily:F.serif, fontSize:38, fill:P.forest, lineHeight:1.05 }));
    var reg  = new fabric.Line([CX-55, 470, CX+55, 470], { stroke:P.gold, strokeWidth:1.2, originX:'center' });
    var data = new fabric.IText(T.data||'', base({ top:500, fontFamily:F.serif, fontSize:20, fill:P.forest }));
    var hora = new fabric.IText(T.hora||'', base({ top:530, fontFamily:F.serif, fontSize:14, fontStyle:'italic', fill:P.gold }));
    var loc  = new fabric.IText(T.local||'', base({ top:575, fontFamily:F.sans, fontSize:13, fill:P.text, lineHeight:1.3 }));
    var praz = new fabric.IText(T.prazo||'', base({ top:645, fontFamily:F.serif, fontSize:12, fontStyle:'italic', fill:P.gold }));
    [circ,mono,abre,nomes,reg,data,hora,loc,praz].forEach(function(o){ canvas.add(o); });
    desenharGuias(); canvas.requestRenderAll();
  }

  function reporModelo(){ if(confirm('Repor o modelo a partir do design atual? Perde as alterações da tela.')){ montarModelo(); registar(); } }
  function limpar(){ if(confirm('Esvaziar a tela?')){ canvas.getObjects().slice().forEach(function(o){ if(guias.indexOf(o)<0) canvas.remove(o); }); registar(); } }

  // ---------- Ações de objeto ----------
  function ativo(){ return canvas.getActiveObject(); }
  function aplicar(prop, val){ var o=ativo(); if(!o) return; o.set(prop, val); canvas.requestRenderAll(); sincronizar(); registar(); }
  function alternar(prop, on, off){ var o=ativo(); if(!o) return; o.set(prop, o[prop]===on?off:on); canvas.requestRenderAll(); registar(); }
  function camada(d){ var o=ativo(); if(!o) return; d>0?canvas.bringForward(o):canvas.sendBackwards(o); guias.forEach(function(g){g.moveTo(9999);}); canvas.requestRenderAll(); registar(); }
  function apagar(){ var o=ativo(); if(!o) return; canvas.remove(o); canvas.discardActiveObject(); canvas.requestRenderAll(); registar(); atualizarPainel(); }
  function duplicar(){ var o=ativo(); if(!o) return; o.clone(function(cl){ cl.set({ left:o.left+16, top:o.top+16 }); canvas.add(cl); canvas.setActiveObject(cl); registar(); }); }

  // ---------- Painel de propriedades ----------
  function ehTexto(o){ return o && (o.type==='i-text'||o.type==='text'||o.type==='textbox'); }
  function atualizarPainel(){
    var o=ativo();
    document.getElementById('semSel').style.display = o?'none':'block';
    document.getElementById('propComum').style.display = o?'block':'none';
    document.getElementById('propTexto').style.display = ehTexto(o)?'block':'none';
    document.getElementById('propForma').style.display = (o&&!ehTexto(o)&&o.type!=='image')?'block':'none';
    if(o) sincronizar();
  }
  function sincronizar(){
    var o=ativo(); if(!o) return;
    if(ehTexto(o)){
      document.getElementById('pTam').value=Math.round(o.fontSize); document.getElementById('pTamV').textContent=Math.round(o.fontSize);
      if(o.fill) document.getElementById('pCor').value=corHex(o.fill);
      document.getElementById('pEsp').value=o.charSpacing||0; document.getElementById('pEspV').textContent=o.charSpacing||0;
      var sel=document.getElementById('pFonte'); if(![].some.call(sel.options,function(op){return op.value===o.fontFamily;})){ var op=new Option(o.fontFamily,o.fontFamily); sel.add(op);} sel.value=o.fontFamily;
    } else {
      if(o.fill&&o.fill!=='transparent') document.getElementById('fCor').value=corHex(o.fill);
      if(o.stroke) document.getElementById('fStroke').value=corHex(o.stroke);
    }
    document.getElementById('pOp').value=Math.round((o.opacity==null?1:o.opacity)*100); document.getElementById('pOpV').textContent=Math.round((o.opacity==null?1:o.opacity)*100)+'%';
  }
  function corHex(c){ if(typeof c!=='string') return '#000000'; if(c[0]==='#'&&c.length===7) return c;
    var m=c.match(/\d+/g); if(m&&m.length>=3){ return '#'+m.slice(0,3).map(function(n){return ('0'+(+n).toString(16)).slice(-2);}).join(''); } return '#000000'; }

  // ---------- Histórico (desfazer/refazer) ----------
  var pilha=[], futuro=[], aCarregar=false;
  function instantaneo(){ return JSON.stringify(canvas.toJSON()); }
  function registar(){ if(aCarregar) return; pilha.push(instantaneo()); if(pilha.length>60) pilha.shift(); futuro=[]; }
  function carregar(json, cb){ aCarregar=true; canvas.loadFromJSON(json, function(){ desenharGuias(); canvas.requestRenderAll(); aCarregar=false; if(cb)cb(); }); }
  function desfazer(){ if(pilha.length<2) return; futuro.push(pilha.pop()); carregar(pilha[pilha.length-1], atualizarPainel); }
  function refazer(){ if(!futuro.length) return; var j=futuro.pop(); pilha.push(j); carregar(j, atualizarPainel); }

  // ---------- Exportar ----------
  function semGuias(fn){ guias.forEach(function(g){g.visible=false;}); canvas.requestRenderAll(); var r=fn(); guias.forEach(function(g){g.visible=true;}); canvas.requestRenderAll(); return r; }
  function pngDataURL(){ return semGuias(function(){ return canvas.toDataURL({ format:'png', multiplier:300/96 }); }); }
  function baixar(url, nome){ var a=document.createElement('a'); a.href=url; a.download=nome; document.body.appendChild(a); a.click(); document.body.removeChild(a); }
  function exportarPNG(){ baixar(pngDataURL(), 'convite-impresso.png'); }
  function exportarPDF(){
    var jsPDF=window.jspdf.jsPDF; var pdf=new jsPDF({ orientation:'portrait', unit:'mm', format:[154,216] });
    pdf.addImage(pngDataURL(), 'PNG', 0, 0, 154, 216); pdf.save('convite-impresso.pdf');
  }

  // ---------- Guardar ----------
  function guardar(){ document.getElementById('telaJson').value = instantaneo(); document.getElementById('fSave').submit(); }

  // ---------- Eventos ----------
  canvas.on('selection:created', atualizarPainel);
  canvas.on('selection:updated', atualizarPainel);
  canvas.on('selection:cleared', atualizarPainel);
  canvas.on('object:modified', registar);
  window.addEventListener('keydown', function(e){
    if((e.key==='Delete'||e.key==='Backspace') && ativo() && !ativo().isEditing){ e.preventDefault(); apagar(); }
    if((e.ctrlKey||e.metaKey)&&e.key==='z'){ e.preventDefault(); desfazer(); }
    if((e.ctrlKey||e.metaKey)&&(e.key==='y'||(e.shiftKey&&e.key==='Z'))){ e.preventDefault(); refazer(); }
  });

  // ---------- Arranque ----------
  function preencherFontes(){
    var sel=document.getElementById('pFonte'); var vistos={};
    [DESIGN.fontes.serif,DESIGN.fontes.sans,DESIGN.fontes.script,'Georgia','Arial','Times New Roman'].forEach(function(f){
      if(f&&!vistos[f]){ vistos[f]=1; sel.add(new Option(f,f)); }
    });
  }
  function iniciar(){
    preencherFontes(); desenharGuias();
    if(TELA_SALVA){ carregar(TELA_SALVA, function(){ pilha=[instantaneo()]; }); }
    else { montarModelo(); pilha=[instantaneo()]; }
  }
  if(document.fonts && document.fonts.ready){ document.fonts.ready.then(function(){ iniciar(); canvas.requestRenderAll(); }); }
  else { iniciar(); }
</script>
</body>
</html>
