<?php
// ============================================================
// convidados.php — Gestão de convidados por conta (Fase 0).
//   Lista, cria, edita e apaga convites do evento ativo, com o
//   link de RSVP e o estado de confirmação. Isolado por evento e
//   com CRUD próprio (não usa o api.php). Admin -> evento 1;
//   casal -> o seu evento.
// ============================================================
require_once __DIR__ . '/conta.php';
exigirEdicao($conn);
$eid = eventoId();
$P = PREFIXO;

if (($_GET['api'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');
    $acao = $_POST['acao'] ?? '';
    $resp = ['ok' => false];

    $doEvento = function (int $id) use ($conn, $P, $eid): bool {
        $st = $conn->prepare("SELECT id FROM {$P}convites WHERE id=? AND evento_id=? LIMIT 1");
        $st->bind_param('ii', $id, $eid); $st->execute();
        return (bool)$st->get_result()->fetch_assoc();
    };

    switch ($acao) {
        case 'listar':
            $sql = "SELECT id, codigo, nome_exibicao, sufixo, mostrar_numero, tipo, lado, lugares, telefone,
                           rsvp_estado, rsvp_confirmados
                    FROM {$P}convites WHERE evento_id=$eid ORDER BY nome_exibicao";
            $resp = ['ok' => true, 'convites' => $conn->query($sql)->fetch_all(MYSQLI_ASSOC)];
            break;

        case 'save':
            $id    = (int)($_POST['id'] ?? 0);
            $nome  = trim((string)($_POST['nome'] ?? ''));
            $lug   = max(1, (int)($_POST['lugares'] ?? 1));
            $tipo  = in_array($_POST['tipo'] ?? '', ['digital','fisico','ambos'], true) ? $_POST['tipo'] : 'ambos';
            $lado  = in_array($_POST['lado'] ?? '', ['noivo','noiva','ambos'], true) ? $_POST['lado'] : 'ambos';
            $tel   = trim((string)($_POST['telefone'] ?? '')) ?: null;
            if ($nome === '') { echo json_encode($resp); exit; }
            if ($id && $doEvento($id)) {
                $st = $conn->prepare("UPDATE {$P}convites SET nome_exibicao=?, lugares=?, tipo=?, lado=?, telefone=? WHERE id=? AND evento_id=?");
                $st->bind_param('sisssii', $nome, $lug, $tipo, $lado, $tel, $id, $eid); $st->execute();
                $resp = ['ok' => true, 'id' => $id];
            } else {
                $cod = gerarCodigo($conn);
                $st = $conn->prepare("INSERT INTO {$P}convites (codigo, nome_exibicao, lugares, tipo, lado, telefone, evento_id) VALUES (?,?,?,?,?,?,?)");
                $st->bind_param('ssisssi', $cod, $nome, $lug, $tipo, $lado, $tel, $eid); $st->execute();
                $resp = ['ok' => true, 'id' => $conn->insert_id];
            }
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($doEvento($id)) {
                $st = $conn->prepare("DELETE FROM {$P}convites WHERE id=? AND evento_id=?");
                $st->bind_param('ii', $id, $eid); $st->execute();
                $resp = ['ok' => true];
            }
            break;

        case 'importar':
            $linhas = parseImportacao((string)($_POST['csv'] ?? ''));
            $n = 0;
            $st = $conn->prepare("INSERT INTO {$P}convites (codigo, nome_exibicao, lugares, tipo, telefone, evento_id) VALUES (?,?,?, 'ambos', ?, ?)");
            foreach ($linhas as $l) {
                $cod = gerarCodigo($conn);
                $st->bind_param('ssisi', $cod, $l['nome'], $l['lugares'], $l['telefone'], $eid);
                if ($st->execute()) $n++;
            }
            $resp = ['ok' => true, 'n' => $n];
            break;
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Analisa texto CSV/colado em linhas de convite (tolerante a formatos). */
function parseImportacao(string $texto): array {
    $out = [];
    $linhas = preg_split('/\r\n|\r|\n/', trim($texto));
    foreach ($linhas as $i => $l) {
        if (trim($l) === '') continue;
        $delim = (substr_count($l, ';') > substr_count($l, ',')) ? ';' : ((strpos($l, "\t") !== false && strpos($l, ',') === false) ? "\t" : ',');
        $cols = array_map('trim', str_getcsv($l, $delim));
        $nome = $cols[0] ?? '';
        if ($nome === '') continue;
        // Ignora linha de cabeçalho.
        if ($i === 0 && preg_match('/^(nome|convidado|name|guest)$/i', $nome)) continue;
        $c1 = $cols[1] ?? ''; $c2 = $cols[2] ?? '';
        $lug = 1; $tel = '';
        $d1 = preg_replace('/\D/', '', $c1);
        if ($c1 !== '' && strlen($d1) >= 6) {         // muitos dígitos -> telefone
            $tel = $c1;
        } elseif ($c1 !== '' && ctype_digit($d1) && $d1 !== '') {
            $lug = max(1, min(99, (int)$d1)); $tel = $c2;
        } else {
            $tel = $c2;
        }
        $out[] = ['nome' => mb_substr($nome, 0, 255), 'lugares' => $lug, 'telefone' => ($tel !== '' ? mb_substr($tel, 0, 50) : null)];
    }
    return $out;
}

$nomeEvento = nomeEventoAtivo($conn);
$base = base_url();
$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$s = estatisticas($conn);
?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Convidados · <?= $H($nomeEvento) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box} body{margin:0;font-family:'Jost',sans-serif;background:#f3efe6;color:#26332b}
  .topo{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;padding:.8rem 1.1rem;background:#16261E;color:#EFE3CB}
  .topo h1{font-family:'Cormorant Garamond',serif;font-size:1.35rem;margin:0;font-weight:600}
  .topo .sp{flex:1} .topo a{color:#EFE3CB;text-decoration:none;border:1px solid rgba(217,188,140,.4);padding:.4rem .8rem;border-radius:50px;font-size:.84rem}
  .badge{font-size:.72rem;color:#C79A5A}
  .wrap{max-width:1000px;margin:0 auto;padding:1rem 1.1rem;display:flex;flex-direction:column;gap:1rem}
  .painel{background:#fff;border:1px solid #e6dfce;border-radius:14px;padding:1rem 1.1rem}
  .painel h2{font-family:'Cormorant Garamond',serif;font-size:1.15rem;margin:0 0 .7rem;color:#20342A}
  .resumo{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:.2rem}
  .kpi{background:#faf7ef;border:1px solid #eee3cc;border-radius:12px;padding:.5rem .8rem;text-align:center;min-width:90px}
  .kpi .n{font-family:'Cormorant Garamond',serif;font-size:1.4rem;font-weight:700;color:#20342A;line-height:1}
  .kpi .l{font-size:.7rem;color:#8a8f88;text-transform:uppercase;letter-spacing:.5px}
  form.linha{display:grid;grid-template-columns:1fr 70px 130px 130px 130px auto;gap:.5rem;align-items:end}
  @media(max-width:760px){form.linha{grid-template-columns:1fr 1fr}}
  label{font-size:.72rem;color:#5c6b5f;display:block;margin-bottom:.2rem}
  input,select{border:1px solid #e6dfce;border-radius:8px;padding:.45rem .55rem;font:inherit;font-size:.86rem;width:100%}
  .btn{border:none;border-radius:50px;padding:.5rem 1rem;font:inherit;font-weight:500;color:#fff;background:linear-gradient(135deg,#B4864A,#8A6031);cursor:pointer}
  .btn.sec{background:#efe7d6;color:#5c4a2c}
  table{width:100%;border-collapse:collapse;font-size:.88rem}
  th,td{text-align:left;padding:.5rem .5rem;border-bottom:1px solid #eee3cc;vertical-align:middle}
  th{font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#8a8f88;font-weight:500}
  .est{font-size:.72rem;padding:.1rem .5rem;border-radius:50px;white-space:nowrap}
  .est.pendente{background:#f0e9d8;color:#8a7a52} .est.confirmado{background:#dff0e0;color:#1f7a3d}
  .est.parcial{background:#e6eefc;color:#2b5bb8} .est.recusado{background:#fbe6e6;color:#a5473f}
  .acoes{display:flex;gap:.35rem;flex-wrap:wrap}
  .mini{border:1px solid #e6dfce;background:#fbf8f1;border-radius:7px;padding:.28rem .5rem;font-size:.78rem;cursor:pointer;text-decoration:none;color:#5c4a2c}
  .mini:hover{border-color:#D9BC8C}
  .vazio{color:#9aa093;font-style:italic;padding:1rem 0}
  textarea{width:100%;border:1px solid #e6dfce;border-radius:8px;padding:.5rem .6rem;font:inherit;font-size:.86rem;resize:vertical}
  #importEstado.ok{color:#1f7a3d} #importEstado.erro{color:#a5473f}
</style></head><body>
<div class="topo">
  <h1>Convidados</h1><span class="badge"><?= $H($nomeEvento) ?></span>
  <span class="sp"></span>
  <?= navCasalLinks('convidados') ?>
</div>
<div class="wrap">
  <div class="painel">
    <div class="resumo">
      <div class="kpi"><div class="n" id="k-convites"><?= $s['convites'] ?></div><div class="l">Convites</div></div>
      <div class="kpi"><div class="n" id="k-lugares"><?= $s['lugares'] ?></div><div class="l">Lugares</div></div>
      <div class="kpi"><div class="n" id="k-confirmados"><?= $s['confirmados'] ?></div><div class="l">Confirmados</div></div>
      <div class="kpi"><div class="n" id="k-pendentes"><?= $s['pendentes'] ?></div><div class="l">Pendentes</div></div>
    </div>
  </div>

  <div class="painel">
    <h2 id="tituloForm">Novo convite</h2>
    <form class="linha" onsubmit="return guardar(event)">
      <input type="hidden" id="fId" value="">
      <div><label>Nome a exibir</label><input type="text" id="fNome" required placeholder="Ex.: Família Silva"></div>
      <div><label>Lugares</label><input type="number" id="fLug" min="1" value="1"></div>
      <div><label>Tipo</label><select id="fTipo"><option value="ambos">Ambos</option><option value="digital">Digital</option><option value="fisico">Físico</option></select></div>
      <div><label>Lado</label><select id="fLado"><option value="ambos">Ambos</option><option value="noiva">Noiva</option><option value="noivo">Noivo</option></select></div>
      <div><label>Telefone</label><input type="text" id="fTel" placeholder="opcional"></div>
      <div class="acoes"><button class="btn" type="submit" id="btnGuardar">Adicionar</button>
        <button class="btn sec" type="button" onclick="limparForm()" id="btnCancel" style="display:none">Cancelar</button></div>
    </form>
  </div>

  <div class="painel">
    <h2>Importar lista <button type="button" class="mini" onclick="toggleImport()" style="font-size:.76rem">abrir / fechar</button></h2>
    <div id="importBox" style="display:none">
      <p class="hint" style="font-size:.82rem;color:#8a8f88;margin:.2rem 0 .5rem">
        Uma linha por convite: <b>Nome, Lugares, Telefone</b> (lugares e telefone opcionais). Cole de uma folha de cálculo ou escolha um ficheiro CSV.</p>
      <textarea id="csvTexto" rows="6" placeholder="Família Silva, 4, 244923000000&#10;Ana e Bruno, 2&#10;João Costa"></textarea>
      <div class="acoes" style="margin-top:.5rem">
        <label class="btn sec" style="cursor:pointer">Escolher CSV…
          <input type="file" accept=".csv,text/csv,text/plain" hidden onchange="lerCsv(this)"></label>
        <button class="btn" type="button" onclick="importar()">Importar</button>
        <span id="importEstado" class="hint" style="font-size:.82rem"></span>
      </div>
    </div>
  </div>

  <div class="painel">
    <h2>Lista de convidados</h2>
    <table>
      <thead><tr><th>Nome</th><th>Lug.</th><th>Tipo</th><th>RSVP</th><th>Telefone</th><th>Ações</th></tr></thead>
      <tbody id="lista"></tbody>
    </table>
    <div id="vazio" class="vazio" style="display:none">Ainda não há convidados. Adicione o primeiro acima.</div>
  </div>
</div>
<script>
  var API='convidados.php?api=1', BASE=<?= json_encode($base) ?>;
  function post(acao,d){ var fd=new FormData(); fd.append('acao',acao); for(var k in d) fd.append(k,d[k]); return fetch(API,{method:'POST',body:fd}).then(function(r){return r.json();}); }
  function esc(s){ var d=document.createElement('div'); d.textContent=s==null?'':s; return d.innerHTML; }

  function carregar(){ return post('listar',{}).then(function(r){ desenhar(r.convites||[]); }); }
  function kpis(cs){
    var lug=0, conf=0, pend=0;
    cs.forEach(function(c){ lug+=parseInt(c.lugares)||0;
      if(c.rsvp_estado==='confirmado'||c.rsvp_estado==='parcial') conf++;
      if(c.rsvp_estado==='pendente') pend++; });
    document.getElementById('k-convites').textContent=cs.length;
    document.getElementById('k-lugares').textContent=lug;
    document.getElementById('k-confirmados').textContent=conf;
    document.getElementById('k-pendentes').textContent=pend;
  }
  function desenhar(cs){
    kpis(cs);
    var tb=document.getElementById('lista'); tb.innerHTML='';
    document.getElementById('vazio').style.display = cs.length? 'none':'block';
    cs.forEach(function(c){
      var rsvpLink = BASE+'/convite.php?c='+c.codigo;
      var digLink  = BASE+'/convite-digital.php?c='+c.codigo;
      var tr=document.createElement('tr');
      tr.innerHTML =
        '<td><strong>'+esc(c.nome_exibicao)+'</strong></td>'+
        '<td>'+c.lugares+'</td>'+
        '<td>'+esc(c.tipo)+'</td>'+
        '<td><span class="est '+c.rsvp_estado+'">'+c.rsvp_estado+(c.rsvp_confirmados?(' ('+c.rsvp_confirmados+')'):'')+'</span></td>'+
        '<td>'+esc(c.telefone||'—')+'</td>'+
        '<td class="acoes">'+
          '<a class="mini" href="'+digLink+'" target="_blank">Convite</a>'+
          '<button class="mini" onclick="copiar(\''+rsvpLink+'\')">Copiar RSVP</button>'+
          '<button class="mini" onclick=\'editar('+JSON.stringify(c).replace(/'/g,"&#39;")+')\'>Editar</button>'+
          '<button class="mini" onclick="apagar('+c.id+',\''+esc(c.nome_exibicao).replace(/'/g,"")+'\')">Apagar</button>'+
        '</td>';
      tb.appendChild(tr);
    });
  }
  function guardar(e){ e.preventDefault();
    var d={ id:document.getElementById('fId').value, nome:document.getElementById('fNome').value.trim(),
      lugares:document.getElementById('fLug').value, tipo:document.getElementById('fTipo').value,
      lado:document.getElementById('fLado').value, telefone:document.getElementById('fTel').value };
    if(!d.nome) return false;
    post('save',d).then(function(){ limparForm(); carregar(); atualizarKpis(); });
    return false;
  }
  function editar(c){
    document.getElementById('fId').value=c.id; document.getElementById('fNome').value=c.nome_exibicao;
    document.getElementById('fLug').value=c.lugares; document.getElementById('fTipo').value=c.tipo;
    document.getElementById('fLado').value=c.lado; document.getElementById('fTel').value=c.telefone||'';
    document.getElementById('tituloForm').textContent='Editar convite';
    document.getElementById('btnGuardar').textContent='Guardar';
    document.getElementById('btnCancel').style.display='inline-block';
    window.scrollTo({top:0,behavior:'smooth'});
  }
  function limparForm(){
    ['fId','fNome','fTel'].forEach(function(i){document.getElementById(i).value='';});
    document.getElementById('fLug').value=1; document.getElementById('fTipo').value='ambos'; document.getElementById('fLado').value='ambos';
    document.getElementById('tituloForm').textContent='Novo convite';
    document.getElementById('btnGuardar').textContent='Adicionar';
    document.getElementById('btnCancel').style.display='none';
  }
  function apagar(id,nome){ if(confirm('Apagar o convite "'+nome+'"?')) post('delete',{id:id}).then(function(){ carregar(); atualizarKpis(); }); }
  function toggleImport(){ var b=document.getElementById('importBox'); b.style.display = b.style.display==='none'?'block':'none'; }
  function lerCsv(inp){ var f=inp.files&&inp.files[0]; if(!f) return; var r=new FileReader(); r.onload=function(e){ document.getElementById('csvTexto').value=e.target.result; }; r.readAsText(f); inp.value=''; }
  function importar(){
    var txt=document.getElementById('csvTexto').value.trim(); var est=document.getElementById('importEstado');
    if(!txt){ est.className='hint erro'; est.textContent='Cole a lista ou escolha um ficheiro.'; return; }
    est.className='hint'; est.textContent='A importar…';
    post('importar',{csv:txt}).then(function(d){
      est.className='hint ok'; est.textContent=(d.n||0)+' convite(s) importado(s).';
      document.getElementById('csvTexto').value=''; carregar();
    }).catch(function(){ est.className='hint erro'; est.textContent='Falhou.'; });
  }
  function copiar(url){ navigator.clipboard.writeText(url).then(function(){ /* copiado */ }); alert('Link de RSVP copiado:\n'+url); }
  function atualizarKpis(){ /* recarrega a página para números exatos, de forma simples */ }
  carregar();
</script>
</body></html>
