<?php
// ============================================================
// envios.php — Convites e lembretes por WhatsApp (Fase 3), por evento.
//   Gera links wa.me personalizados por convidado (convite, lembrete,
//   agradecimento), com a mensagem pré-preenchida e o link do convite.
//   Não usa a API do WhatsApp Business — abre o WhatsApp do casal com
//   um clique. Marca convites como enviados. Isolado por evento.
// ============================================================
require_once __DIR__ . '/conta.php';
exigirEdicao($conn);
$eid = eventoId();
$P = PREFIXO;

if (($_GET['api'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');
    $acao = $_POST['acao'] ?? '';
    $resp = ['ok' => false];
    if ($acao === 'flag_enviado') {
        $id = (int)($_POST['id'] ?? 0);
        $st = $conn->prepare("UPDATE {$P}convites SET enviado=1 WHERE id=? AND evento_id=?");
        $st->bind_param('ii', $id, $eid); $st->execute();
        $resp = ['ok' => (bool)$st->affected_rows || true];
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

$design = carregarDesignAtivo($conn);
$tok = mapaTextos($design);
$casal = $design['evento']['noiva'] . ' & ' . $design['evento']['noivo'];
$dataExt = html_entity_decode(strip_tags($tok['DATA_EXTENSA']), ENT_QUOTES, 'UTF-8');
$base = base_url();

// Convites do evento com telefone/estado.
$sql = "SELECT id, codigo, nome_exibicao, lugares, telefone, rsvp_estado, enviado
        FROM {$P}convites WHERE evento_id=$eid ORDER BY nome_exibicao";
$convites = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

$nomeEvento = nomeEventoAtivo($conn);
$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Envios · <?= $H($nomeEvento) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box} body{margin:0;font-family:'Jost',sans-serif;background:#f3efe6;color:#26332b}
  .topo{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;padding:.8rem 1.1rem;background:#16261E;color:#EFE3CB}
  .topo h1{font-family:'Cormorant Garamond',serif;font-size:1.35rem;margin:0;font-weight:600}
  .topo .sp{flex:1} .topo a{color:#EFE3CB;text-decoration:none;border:1px solid rgba(217,188,140,.4);padding:.4rem .8rem;border-radius:50px;font-size:.84rem}
  .badge{font-size:.72rem;color:#C79A5A}
  .wrap{max-width:1000px;margin:0 auto;padding:1rem 1.1rem;display:flex;flex-direction:column;gap:1rem}
  .painel{background:#fff;border:1px solid #e6dfce;border-radius:14px;padding:1rem 1.1rem}
  .painel h2{font-family:'Cormorant Garamond',serif;font-size:1.15rem;margin:0 0 .6rem;color:#20342A}
  .tabs{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.7rem}
  .tab{border:1px solid #e6dfce;background:#fbf8f1;border-radius:50px;padding:.4rem .9rem;cursor:pointer;font:inherit;font-size:.85rem}
  .tab.sel{background:#16261E;color:#EFE3CB;border-color:#16261E}
  textarea{width:100%;border:1px solid #e6dfce;border-radius:10px;padding:.6rem .7rem;font:inherit;font-size:.9rem;min-height:90px;resize:vertical}
  .marc{font-size:.76rem;color:#8a8f88;margin-top:.35rem}
  .filtros{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.5rem}
  .filtro{border:1px solid #e6dfce;background:#fbf8f1;border-radius:50px;padding:.3rem .8rem;cursor:pointer;font-size:.8rem}
  .filtro.sel{background:#efe0c8;border-color:#D9BC8C}
  table{width:100%;border-collapse:collapse;font-size:.88rem}
  th,td{text-align:left;padding:.5rem;border-bottom:1px solid #eee3cc;vertical-align:middle}
  th{font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#8a8f88;font-weight:500}
  .est{font-size:.72rem;padding:.1rem .5rem;border-radius:50px}
  .est.pendente{background:#f0e9d8;color:#8a7a52} .est.confirmado{background:#dff0e0;color:#1f7a3d}
  .est.parcial{background:#e6eefc;color:#2b5bb8} .est.recusado{background:#fbe6e6;color:#a5473f}
  .wa{display:inline-flex;align-items:center;gap:.35rem;background:#25D366;color:#053d1c;border:none;border-radius:50px;padding:.35rem .8rem;font:inherit;font-size:.8rem;font-weight:600;text-decoration:none;cursor:pointer}
  .wa[disabled],.wa.off{background:#e6e6e6;color:#999;pointer-events:none}
  .env{font-size:.72rem;color:#1f7a3d;margin-left:.4rem}
  .vazio{color:#9aa093;font-style:italic;padding:1rem 0}
</style></head><body>
<div class="topo">
  <h1>Convites por WhatsApp</h1><span class="badge"><?= $H($nomeEvento) ?></span>
  <span class="sp"></span>
  <a href="convidados.php">Convidados</a>
  <a href="<?= $H(urlPainel()) ?>">← Painel</a>
</div>
<div class="wrap">
  <div class="painel">
    <h2>Mensagem</h2>
    <div class="tabs">
      <button class="tab sel" data-m="convite" onclick="escolherModelo('convite')">Convite</button>
      <button class="tab" data-m="lembrete" onclick="escolherModelo('lembrete')">Lembrete</button>
      <button class="tab" data-m="agradecimento" onclick="escolherModelo('agradecimento')">Agradecimento</button>
    </div>
    <textarea id="msg" oninput="render()"></textarea>
    <div class="marc">Marcadores: <b>{nome}</b> · <b>{casal}</b> · <b>{data}</b> · <b>{link}</b> — substituídos por convidado.</div>
  </div>

  <div class="painel">
    <h2>Convidados</h2>
    <div class="filtros">
      <button class="filtro sel" data-f="todos" onclick="filtrar('todos')">Todos</button>
      <button class="filtro" data-f="pendente" onclick="filtrar('pendente')">Por confirmar</button>
      <button class="filtro" data-f="confirmado" onclick="filtrar('confirmado')">Confirmados</button>
      <button class="filtro" data-f="comtel" onclick="filtrar('comtel')">Com telefone</button>
    </div>
    <table>
      <thead><tr><th>Nome</th><th>RSVP</th><th>Telefone</th><th>Enviar</th></tr></thead>
      <tbody id="lista"></tbody>
    </table>
    <div id="vazio" class="vazio" style="display:none">Nenhum convidado neste filtro.</div>
  </div>
</div>
<script>
  var CONVITES = <?= json_encode($convites, JSON_UNESCAPED_UNICODE) ?>;
  var CASAL = <?= json_encode($casal) ?>, DATA = <?= json_encode($dataExt) ?>, BASE = <?= json_encode($base) ?>;
  var MODELOS = {
    convite: 'Olá {nome}! 💍 É com muita alegria que {casal} vos convidam para o seu casamento, {data}. Aqui está o vosso convite: {link}\nContamos convosco! 🤍',
    lembrete: 'Olá {nome}! Um carinhoso lembrete para confirmarem a presença no casamento de {casal} ({data}). É só abrir: {link}\nObrigado! 🤍',
    agradecimento: 'Olá {nome}! Muito obrigado por confirmarem a presença no nosso casamento. Até {data}! Com carinho, {casal} 🤍'
  };
  var modeloAtual='convite', filtro='todos';

  function escolherModelo(m){ modeloAtual=m;
    document.querySelectorAll('.tab').forEach(function(t){ t.classList.toggle('sel', t.dataset.m===m); });
    document.getElementById('msg').value = MODELOS[m]; render();
  }
  function filtrar(f){ filtro=f;
    document.querySelectorAll('.filtro').forEach(function(t){ t.classList.toggle('sel', t.dataset.f===f); });
    render();
  }
  function mensagemPara(c){
    var link = BASE+'/convite.php?c='+c.codigo;
    return (document.getElementById('msg').value||'')
      .replaceAll('{nome}', c.nome_exibicao).replaceAll('{casal}', CASAL)
      .replaceAll('{data}', DATA).replaceAll('{link}', link);
  }
  function esc(s){ var d=document.createElement('div'); d.textContent=s==null?'':s; return d.innerHTML; }
  function tel(c){ return (c.telefone||'').replace(/\D/g,''); }

  function passaFiltro(c){
    if(filtro==='comtel') return !!tel(c);
    if(filtro==='pendente') return c.rsvp_estado==='pendente';
    if(filtro==='confirmado') return c.rsvp_estado==='confirmado'||c.rsvp_estado==='parcial';
    return true;
  }
  function render(){
    var tb=document.getElementById('lista'); tb.innerHTML='';
    var vis=CONVITES.filter(passaFiltro);
    document.getElementById('vazio').style.display = vis.length?'none':'block';
    vis.forEach(function(c){
      var t=tel(c);
      var wa = t ? 'https://wa.me/'+t+'?text='+encodeURIComponent(mensagemPara(c)) : '';
      var tr=document.createElement('tr');
      tr.innerHTML =
        '<td><strong>'+esc(c.nome_exibicao)+'</strong></td>'+
        '<td><span class="est '+c.rsvp_estado+'">'+c.rsvp_estado+'</span></td>'+
        '<td>'+(t?esc(c.telefone):'<span style="color:#b7bdb0">sem telefone</span>')+'</td>'+
        '<td>'+ (t
          ? '<a class="wa" href="'+wa+'" target="_blank" onclick="marcar('+c.id+',this)">WhatsApp</a>'
          : '<span class="wa off">WhatsApp</span>')
          + (c.enviado==1?'<span class="env">✓ enviado</span>':'') +'</td>';
      tb.appendChild(tr);
    });
  }
  function marcar(id, el){
    var fd=new FormData(); fd.append('acao','flag_enviado'); fd.append('id',id);
    fetch('envios.php?api=1',{method:'POST',body:fd});
    var c=CONVITES.find(function(x){return x.id==id;}); if(c) c.enviado=1;
    if(el && !el.parentNode.querySelector('.env')){ var s=document.createElement('span'); s.className='env'; s.textContent='✓ enviado'; el.parentNode.appendChild(s); }
  }
  escolherModelo('convite');
</script>
</body></html>
