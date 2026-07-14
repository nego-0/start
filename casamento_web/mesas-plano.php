<?php
// ============================================================
// mesas-plano.php — Plano de mesas visual (Fase 3), por evento.
//   Criar mesas, adicionar convidados e arrastá-los para as mesas,
//   com ocupação ao vivo. Isolado por evento (multi-inquilino) e
//   com CRUD próprio (não usa o api.php). Admin legado -> evento 1;
//   casal -> o seu evento.
// ============================================================
require_once __DIR__ . '/conta.php';   // puxa db.php + auth.php
exigirEdicao($conn);
$eid = eventoId();
$P = PREFIXO;

// ---- API JSON (mesmo ficheiro) -------------------------------
if (($_GET['api'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');
    $acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
    $resp = ['ok' => false];

    // Verifica que um convite pertence ao evento ativo.
    $conviteDoEvento = function (int $id) use ($conn, $P, $eid): bool {
        $st = $conn->prepare("SELECT id FROM {$P}convites WHERE id=? AND evento_id=? LIMIT 1");
        $st->bind_param('ii', $id, $eid); $st->execute();
        return (bool)$st->get_result()->fetch_assoc();
    };

    switch ($acao) {
        case 'listar':
            $resp = ['ok' => true, 'mesas' => planoMesas($conn, $eid), 'convites' => planoConvites($conn, $eid)];
            break;

        case 'add_convite':
            $nome = trim((string)($_POST['nome'] ?? ''));
            $lug  = max(1, (int)($_POST['lugares'] ?? 1));
            if ($nome !== '') {
                $cod = gerarCodigo($conn);
                $st = $conn->prepare("INSERT INTO {$P}convites (codigo, nome_exibicao, lugares, tipo, evento_id) VALUES (?,?,?, 'ambos', ?)");
                $st->bind_param('ssii', $cod, $nome, $lug, $eid); $st->execute();
                $resp = ['ok' => true, 'id' => $conn->insert_id];
            }
            break;

        case 'add_mesa':
            $nome = trim((string)($_POST['nome'] ?? ''));
            $cap  = ($_POST['capacidade'] ?? '') === '' ? null : max(1, (int)$_POST['capacidade']);
            if ($nome !== '') {
                $id = resolverMesa($conn, $nome);   // já isolado por evento
                if ($id) { $st = $conn->prepare("UPDATE {$P}mesas SET capacidade=? WHERE id=? AND evento_id=?"); $st->bind_param('iii', $cap, $id, $eid); $st->execute(); }
                $resp = ['ok' => (bool)$id, 'id' => $id];
            }
            break;

        case 'assign':
            $cid = (int)($_POST['convite_id'] ?? 0);
            $mid = (int)($_POST['mesa_id'] ?? 0);   // 0 = por sentar
            if ($conviteDoEvento($cid)) {
                if ($mid === 0) {
                    $st = $conn->prepare("UPDATE {$P}convites SET mesa_id=NULL WHERE id=? AND evento_id=?");
                    $st->bind_param('ii', $cid, $eid);
                } else {
                    // A mesa tem de ser do mesmo evento.
                    $st = $conn->prepare("UPDATE {$P}convites c JOIN {$P}mesas m ON m.id=? AND m.evento_id=? SET c.mesa_id=m.id WHERE c.id=? AND c.evento_id=?");
                    $st->bind_param('iiii', $mid, $eid, $cid, $eid);
                }
                $st->execute();
                $resp = ['ok' => true];
            }
            break;

        case 'del_mesa':
            $mid = (int)($_POST['mesa_id'] ?? 0);
            $st = $conn->prepare("UPDATE {$P}convites SET mesa_id=NULL WHERE mesa_id=? AND evento_id=?");
            $st->bind_param('ii', $mid, $eid); $st->execute();
            $st = $conn->prepare("DELETE FROM {$P}mesas WHERE id=? AND evento_id=?");
            $st->bind_param('ii', $mid, $eid); $st->execute();
            $resp = ['ok' => true];
            break;

        case 'del_convite':
            $cid = (int)($_POST['convite_id'] ?? 0);
            if ($conviteDoEvento($cid)) {
                $st = $conn->prepare("DELETE FROM {$P}convites WHERE id=? AND evento_id=?");
                $st->bind_param('ii', $cid, $eid); $st->execute();
                $resp = ['ok' => true];
            }
            break;
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Mesas do evento com ocupação. */
function planoMesas(mysqli $conn, int $eid): array {
    $P = PREFIXO;
    $sql = "SELECT m.id, m.nome, m.capacidade,
                   COALESCE(SUM(c.lugares),0) AS ocupacao, COUNT(c.id) AS convites
            FROM {$P}mesas m LEFT JOIN {$P}convites c ON c.mesa_id=m.id
            WHERE m.evento_id=$eid GROUP BY m.id, m.nome, m.capacidade ORDER BY m.nome";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}
/** Convites do evento (para sentar / já sentados). */
function planoConvites(mysqli $conn, int $eid): array {
    $P = PREFIXO;
    $sql = "SELECT id, nome_exibicao, lugares, mesa_id FROM {$P}convites WHERE evento_id=$eid ORDER BY nome_exibicao";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$nomeEvento = nomeEventoAtivo($conn);
$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Plano de mesas · <?= $H($nomeEvento) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box} body{margin:0;font-family:'Jost',sans-serif;background:#f3efe6;color:#26332b}
  .topo{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;padding:.8rem 1.1rem;background:#16261E;color:#EFE3CB}
  .topo h1{font-family:'Cormorant Garamond',serif;font-size:1.35rem;margin:0;font-weight:600}
  .topo .sp{flex:1} .topo a{color:#EFE3CB;text-decoration:none;border:1px solid rgba(217,188,140,.4);padding:.4rem .8rem;border-radius:50px;font-size:.84rem}
  .badge{font-size:.72rem;color:#C79A5A}
  .area{display:grid;grid-template-columns:290px 1fr;gap:1rem;padding:1rem 1.1rem;align-items:start}
  @media(max-width:820px){.area{grid-template-columns:1fr}}
  .painel{background:#fff;border:1px solid #e6dfce;border-radius:14px;padding:.9rem 1rem}
  .painel h2{font-family:'Cormorant Garamond',serif;font-size:1.1rem;margin:0 0 .5rem;color:#20342A}
  .pool{display:flex;flex-direction:column;gap:.4rem;min-height:60px;border:2px dashed #e0d8c4;border-radius:10px;padding:.5rem}
  .pool.hover{border-color:#B4864A;background:#faf5ea}
  .chip{display:flex;align-items:center;gap:.5rem;background:#fbf8f1;border:1px solid #e6dfce;border-radius:9px;padding:.4rem .55rem;cursor:grab;font-size:.86rem}
  .chip .lug{margin-left:auto;font-size:.72rem;color:#8a8f88;background:#f0e9d8;border-radius:50px;padding:.05rem .45rem}
  .chip.arrasta{opacity:.4}
  form.mini{display:flex;gap:.4rem;margin-top:.6rem;flex-wrap:wrap}
  form.mini input{border:1px solid #e6dfce;border-radius:8px;padding:.4rem .5rem;font:inherit;font-size:.84rem}
  form.mini input[type=text]{flex:1;min-width:110px} form.mini input[type=number]{width:70px}
  .btn{border:none;border-radius:50px;padding:.45rem .9rem;font:inherit;font-size:.84rem;font-weight:500;color:#fff;background:linear-gradient(135deg,#B4864A,#8A6031);cursor:pointer}
  .mesas{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:.8rem}
  .mesa{background:#fff;border:1px solid #e6dfce;border-radius:14px;padding:.7rem .8rem;min-height:130px;transition:.12s}
  .mesa.hover{border-color:#B4864A;box-shadow:0 6px 16px rgba(180,134,74,.15)}
  .mesa .cab{display:flex;align-items:center;gap:.4rem;margin-bottom:.4rem}
  .mesa .cab .nome{font-family:'Cormorant Garamond',serif;font-weight:600;font-size:1.05rem;color:#20342A}
  .mesa .cab .x{margin-left:auto;color:#b06;cursor:pointer;border:none;background:none;font-size:1rem;opacity:.5}
  .mesa .cab .x:hover{opacity:1}
  .barra{height:6px;background:#f0e9d8;border-radius:50px;overflow:hidden;margin-bottom:.5rem}
  .barra span{display:block;height:100%;background:#B4864A}
  .barra span.cheio{background:#c0392b}
  .ocup{font-size:.74rem;color:#8a8f88;margin-bottom:.4rem}
  .assentos{display:flex;flex-direction:column;gap:.3rem;min-height:24px}
  .vazio{color:#b7bdb0;font-size:.8rem;font-style:italic}
</style></head><body>
<div class="topo">
  <h1>Plano de mesas</h1><span class="badge"><?= $H($nomeEvento) ?></span>
  <span class="sp"></span>
  <a href="<?= $H(urlPainel()) ?>">← Painel</a>
</div>
<div class="area">
  <div class="painel">
    <h2>Por sentar</h2>
    <div id="pool" class="pool" data-mesa="0"></div>
    <form class="mini" onsubmit="return addConvite(event)">
      <input type="text" id="cNome" placeholder="Nome do convite" required>
      <input type="number" id="cLug" min="1" value="1" title="Lugares">
      <button class="btn" type="submit">+ Convidado</button>
    </form>
    <h2 style="margin-top:1rem">Nova mesa</h2>
    <form class="mini" onsubmit="return addMesa(event)">
      <input type="text" id="mNome" placeholder="Ex.: Mesa 1" required>
      <input type="number" id="mCap" min="1" placeholder="Cap.">
      <button class="btn" type="submit">+ Mesa</button>
    </form>
  </div>
  <div class="painel">
    <h2>Mesas</h2>
    <div id="mesas" class="mesas"></div>
  </div>
</div>
<script>
  var API = 'mesas-plano.php?api=1';
  var estado = { mesas: [], convites: [] };

  function post(acao, dados){
    var fd = new FormData(); fd.append('acao', acao);
    for (var k in dados) fd.append(k, dados[k]);
    return fetch(API, { method:'POST', body:fd }).then(function(r){ return r.json(); });
  }
  function carregar(){ return post('listar', {}).then(function(d){ estado.mesas=d.mesas||[]; estado.convites=d.convites||[]; desenhar(); }); }

  function chip(c){
    var el = document.createElement('div');
    el.className = 'chip'; el.draggable = true; el.dataset.id = c.id;
    el.innerHTML = '<span>'+escapar(c.nome_exibicao)+'</span><span class="lug">'+c.lugares+'</span>';
    el.addEventListener('dragstart', function(e){ e.dataTransfer.setData('text/plain', c.id); el.classList.add('arrasta'); });
    el.addEventListener('dragend', function(){ el.classList.remove('arrasta'); });
    return el;
  }
  function desenhar(){
    var pool = document.getElementById('pool'); pool.innerHTML='';
    estado.convites.filter(function(c){ return !c.mesa_id; }).forEach(function(c){ pool.appendChild(chip(c)); });
    if(!pool.children.length) pool.innerHTML = '<span class="vazio">Todos sentados 🎉</span>';

    var wrap = document.getElementById('mesas'); wrap.innerHTML='';
    if(!estado.mesas.length){ wrap.innerHTML = '<span class="vazio">Ainda não há mesas. Crie a primeira à esquerda.</span>'; }
    estado.mesas.forEach(function(m){
      var cap = m.capacidade ? parseInt(m.capacidade) : null;
      var ocup = parseInt(m.ocupacao)||0;
      var pct = cap ? Math.min(100, Math.round(ocup/cap*100)) : (ocup?60:0);
      var cheio = cap && ocup>cap;
      var d = document.createElement('div'); d.className='mesa'; d.dataset.mesa=m.id;
      d.innerHTML = '<div class="cab"><span class="nome">'+escapar(m.nome)+'</span>'
        + '<button class="x" title="Apagar mesa" onclick="delMesa('+m.id+')">✕</button></div>'
        + '<div class="barra"><span class="'+(cheio?'cheio':'')+'" style="width:'+pct+'%"></span></div>'
        + '<div class="ocup">'+ocup+(cap?(' / '+cap):'')+' lugares'+(cheio?' · excede!':'')+'</div>'
        + '<div class="assentos"></div>';
      var as = d.querySelector('.assentos');
      var sentados = estado.convites.filter(function(c){ return String(c.mesa_id)===String(m.id); });
      if(!sentados.length) as.innerHTML='<span class="vazio">arraste para aqui</span>';
      sentados.forEach(function(c){ as.appendChild(chip(c)); });
      dropzone(d, m.id);
      wrap.appendChild(d);
    });
    dropzone(document.getElementById('pool'), 0);
  }
  function dropzone(el, mesaId){
    el.addEventListener('dragover', function(e){ e.preventDefault(); el.classList.add('hover'); });
    el.addEventListener('dragleave', function(){ el.classList.remove('hover'); });
    el.addEventListener('drop', function(e){
      e.preventDefault(); el.classList.remove('hover');
      var id = e.dataTransfer.getData('text/plain');
      if(id) post('assign', { convite_id:id, mesa_id:mesaId }).then(carregar);
    });
  }
  function addConvite(e){ e.preventDefault();
    var nome=document.getElementById('cNome').value.trim(), lug=document.getElementById('cLug').value||1;
    if(nome) post('add_convite',{nome:nome,lugares:lug}).then(function(){ document.getElementById('cNome').value=''; document.getElementById('cLug').value=1; carregar(); });
    return false;
  }
  function addMesa(e){ e.preventDefault();
    var nome=document.getElementById('mNome').value.trim(), cap=document.getElementById('mCap').value;
    if(nome) post('add_mesa',{nome:nome,capacidade:cap}).then(function(){ document.getElementById('mNome').value=''; document.getElementById('mCap').value=''; carregar(); });
    return false;
  }
  function delMesa(id){ if(confirm('Apagar a mesa? Os convites voltam a “por sentar”.')) post('del_mesa',{mesa_id:id}).then(carregar); }
  function escapar(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
  carregar();
</script>
</body></html>
