<?php
// ============================================================
// porta.php — Check-in à porta + chegadas ao vivo (Fase 3), por evento.
//   Procurar o convidado (nome/código) ou ler o QR, registar/anular a
//   entrada, com contador ao vivo e lista de chegadas. Isolado por
//   evento e com CRUD próprio (não usa o api.php legado).
// ============================================================
require_once __DIR__ . '/conta.php';
exigirEdicao($conn);
$eid = eventoId();
$P = PREFIXO;

if (($_GET['api'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');
    exigirCsrf(true); // [S1] só bloqueia POST (check-in); leituras GET passam
    $acao = $_POST['acao'] ?? '';
    $resp = ['ok' => false];

    $carregar = function (int $id) use ($conn, $P, $eid) {
        $st = $conn->prepare("SELECT id, codigo, nome_exibicao, lugares, rsvp_estado, rsvp_confirmados, checkin_estado, checkin_presentes
                              FROM {$P}convites WHERE id=? AND evento_id=? LIMIT 1");
        $st->bind_param('ii', $id, $eid); $st->execute();
        return $st->get_result()->fetch_assoc();
    };

    switch ($acao) {
        case 'stats':
            $resp = ['ok' => true] + portaStats($conn, $eid);
            break;

        case 'buscar':
            $q = trim((string)($_POST['q'] ?? ''));
            $like = '%' . $q . '%'; $cod = strtoupper($q);
            $st = $conn->prepare("SELECT id, codigo, nome_exibicao, lugares, rsvp_estado, rsvp_confirmados, checkin_estado, checkin_presentes
                                  FROM {$P}convites WHERE evento_id=? AND (nome_exibicao LIKE ? OR codigo=?)
                                  ORDER BY nome_exibicao LIMIT 25");
            $st->bind_param('iss', $eid, $like, $cod); $st->execute();
            $resp = ['ok' => true, 'convites' => $st->get_result()->fetch_all(MYSQLI_ASSOC)];
            break;

        case 'resolver':   // a partir do QR (URL com c=CODE) ou código
            $code = strtoupper(trim((string)($_POST['codigo'] ?? '')));
            if (preg_match('/[?&]c=([A-Z0-9]+)/i', $code, $m)) $code = strtoupper($m[1]);
            $st = $conn->prepare("SELECT id FROM {$P}convites WHERE evento_id=? AND codigo=? LIMIT 1");
            $st->bind_param('is', $eid, $code); $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $resp = $row ? ['ok' => true, 'convite' => $carregar((int)$row['id'])] : ['ok' => false, 'erro' => 'Convite não encontrado neste evento.'];
            break;

        case 'checkin':
            $id = (int)($_POST['id'] ?? 0); $entrar = ($_POST['entrar'] ?? '1') === '1';
            $c = $carregar($id);
            if ($c) {
                if ($entrar) {
                    $pres = (int)$c['rsvp_confirmados'] > 0 ? (int)$c['rsvp_confirmados'] : (int)$c['lugares'];
                    $st = $conn->prepare("UPDATE {$P}convites SET checkin_estado='presente', checkin_presentes=?, checkin_em=COALESCE(checkin_em,NOW()) WHERE id=? AND evento_id=?");
                    $st->bind_param('iii', $pres, $id, $eid);
                } else {
                    $st = $conn->prepare("UPDATE {$P}convites SET checkin_estado='aguardando', checkin_presentes=0, checkin_em=NULL WHERE id=? AND evento_id=?");
                    $st->bind_param('ii', $id, $eid);
                }
                $st->execute();
                $resp = ['ok' => true, 'convite' => $carregar($id)] + portaStats($conn, $eid);
            }
            break;

        case 'chegadas':
            $st = $conn->prepare("SELECT nome_exibicao, checkin_presentes, checkin_em FROM {$P}convites
                                  WHERE evento_id=? AND checkin_estado IN ('presente','parcial') AND checkin_em IS NOT NULL
                                  ORDER BY checkin_em DESC LIMIT 30");
            $st->bind_param('i', $eid); $st->execute();
            $resp = ['ok' => true, 'chegadas' => $st->get_result()->fetch_all(MYSQLI_ASSOC)];
            break;
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

function portaStats(mysqli $conn, int $eid): array {
    $one = fn($sql) => (int)($conn->query($sql)->fetch_row()[0] ?? 0);
    return [
        'presentes'   => $one("SELECT COALESCE(SUM(checkin_presentes),0) FROM {$GLOBALS['P']}convites WHERE evento_id=$eid"),
        'confirmados' => $one("SELECT COALESCE(SUM(rsvp_confirmados),0) FROM {$GLOBALS['P']}convites WHERE evento_id=$eid"),
        'no_local'    => $one("SELECT COUNT(*) FROM {$GLOBALS['P']}convites WHERE evento_id=$eid AND checkin_estado IN ('presente','parcial')"),
    ];
}

$nomeEvento = nomeEventoAtivo($conn);
$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$st0 = portaStats($conn, $eid);
?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<?= csrfScript() ?>
<title>Porta · <?= $H($nomeEvento) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box} body{margin:0;font-family:'Jost',sans-serif;background:#f3efe6;color:#26332b}
  .topo{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;padding:.8rem 1.1rem;background:#16261E;color:#EFE3CB}
  .topo h1{font-family:'Cormorant Garamond',serif;font-size:1.35rem;margin:0;font-weight:600}
  .topo .sp{flex:1} .topo a{color:#EFE3CB;text-decoration:none;border:1px solid rgba(217,188,140,.4);padding:.4rem .8rem;border-radius:50px;font-size:.84rem}
  .badge{font-size:.72rem;color:#C79A5A}
  .wrap{max-width:820px;margin:0 auto;padding:1rem 1.1rem;display:flex;flex-direction:column;gap:1rem}
  .painel{background:#fff;border:1px solid #e6dfce;border-radius:14px;padding:1rem 1.1rem}
  .kpis{display:flex;gap:.6rem;flex-wrap:wrap}
  .kpi{flex:1;min-width:120px;background:#16261E;color:#EFE3CB;border-radius:14px;padding:.8rem 1rem;text-align:center}
  .kpi .n{font-family:'Cormorant Garamond',serif;font-size:2rem;font-weight:700;line-height:1;color:#fff}
  .kpi .l{font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:#C79A5A}
  .busca{display:flex;gap:.5rem;flex-wrap:wrap}
  .busca input{flex:1;min-width:180px;border:1px solid #e6dfce;border-radius:10px;padding:.6rem .7rem;font:inherit;font-size:1rem}
  .btn{border:none;border-radius:50px;padding:.6rem 1.1rem;font:inherit;font-weight:500;color:#fff;background:linear-gradient(135deg,#B4864A,#8A6031);cursor:pointer}
  .btn.sec{background:#efe7d6;color:#5c4a2c}
  #video{width:100%;max-height:230px;border-radius:12px;background:#000;display:none;margin-top:.6rem}
  .res{display:flex;align-items:center;gap:.6rem;border-bottom:1px solid #eee3cc;padding:.6rem .2rem}
  .res .info{flex:1;min-width:0}
  .res .nome{font-weight:600}
  .res .sub{font-size:.76rem;color:#8a8f88}
  .est{font-size:.7rem;padding:.1rem .5rem;border-radius:50px;white-space:nowrap}
  .est.presente{background:#dff0e0;color:#1f7a3d} .est.aguardando{background:#f0e9d8;color:#8a7a52}
  .est.confirmado{background:#dff0e0;color:#1f7a3d} .est.pendente{background:#f0e9d8;color:#8a7a52}
  .est.recusado{background:#fbe6e6;color:#a5473f} .est.parcial{background:#e6eefc;color:#2b5bb8}
  .chegada{display:flex;justify-content:space-between;font-size:.86rem;padding:.35rem .2rem;border-bottom:1px solid #f0e9d8}
  .chegada .h{color:#8a8f88;font-size:.78rem}
  .vazio{color:#9aa093;font-style:italic;padding:.6rem 0}
  h2{font-family:'Cormorant Garamond',serif;font-size:1.15rem;margin:0 0 .6rem;color:#20342A}
</style></head><body>
<div class="topo">
  <h1>Porta</h1><span class="badge"><?= $H($nomeEvento) ?></span>
  <span class="sp"></span>
  <?= navCasalLinks('') ?>
</div>
<div class="wrap">
  <div class="painel">
    <div class="kpis">
      <div class="kpi"><div class="n" id="k-presentes"><?= $st0['presentes'] ?></div><div class="l">Presentes</div></div>
      <div class="kpi"><div class="n" id="k-confirmados"><?= $st0['confirmados'] ?></div><div class="l">Confirmados</div></div>
      <div class="kpi"><div class="n" id="k-local"><?= $st0['no_local'] ?></div><div class="l">Convites no local</div></div>
    </div>
  </div>

  <div class="painel">
    <h2>Entrada</h2>
    <div class="busca">
      <input id="q" placeholder="Nome ou código do convite" oninput="buscar()" autofocus>
      <button class="btn sec" type="button" id="btnQr" onclick="alternarCamera()">📷 Ler QR</button>
    </div>
    <video id="video" playsinline></video>
    <div id="resultados" style="margin-top:.6rem"></div>
  </div>

  <div class="painel">
    <h2>Chegadas</h2>
    <div id="chegadas"><div class="vazio">Ainda sem chegadas.</div></div>
  </div>
</div>
<script>
  var API='porta.php?api=1';
  function post(acao,d){ var fd=new FormData(); fd.append('acao',acao); for(var k in d) fd.append(k,d[k]); return fetch(API,{method:'POST',body:fd}).then(function(r){return r.json();}); }
  function esc(s){ var d=document.createElement('div'); d.textContent=s==null?'':s; return d.innerHTML; }
  function setStats(d){ if(d.presentes==null) return; document.getElementById('k-presentes').textContent=d.presentes;
    document.getElementById('k-confirmados').textContent=d.confirmados; document.getElementById('k-local').textContent=d.no_local; }

  var t=null;
  function buscar(){ clearTimeout(t); t=setTimeout(function(){
    var q=document.getElementById('q').value.trim();
    if(q.length<1){ document.getElementById('resultados').innerHTML=''; return; }
    post('buscar',{q:q}).then(function(d){ desenhar(d.convites||[]); });
  },250); }

  function linha(c){
    var badge='<span class="est '+c.checkin_estado+'">'+(c.checkin_estado==='presente'?'no local':'por entrar')+'</span>';
    var rsvp='<span class="est '+c.rsvp_estado+'">'+c.rsvp_estado+'</span>';
    var presente = c.checkin_estado==='presente';
    var botao = presente
      ? '<button class="btn sec" onclick="checkin('+c.id+',0)">Anular</button>'
      : '<button class="btn" onclick="checkin('+c.id+',1)">Registar entrada</button>';
    return '<div class="res" id="res-'+c.id+'"><div class="info"><div class="nome">'+esc(c.nome_exibicao)+
      '</div><div class="sub">'+c.lugares+' lugar(es) · '+rsvp+' '+badge+'</div></div>'+botao+'</div>';
  }
  function desenhar(cs){
    var box=document.getElementById('resultados');
    box.innerHTML = cs.length ? cs.map(linha).join('') : '<div class="vazio">Nenhum convite encontrado.</div>';
  }
  function checkin(id,entrar){
    post('checkin',{id:id,entrar:entrar}).then(function(d){
      if(d.ok){ setStats(d); var el=document.getElementById('res-'+id); if(el) el.outerHTML=linha(d.convite); carregarChegadas(); }
    });
  }
  function carregarChegadas(){
    post('chegadas',{}).then(function(d){
      var box=document.getElementById('chegadas'); var cs=d.chegadas||[];
      box.innerHTML = cs.length ? cs.map(function(c){
        var h=(c.checkin_em||'').substr(11,5);
        return '<div class="chegada"><span>'+esc(c.nome_exibicao)+' <span class="h">('+c.checkin_presentes+')</span></span><span class="h">'+h+'</span></div>';
      }).join('') : '<div class="vazio">Ainda sem chegadas.</div>';
    });
  }

  // ---- Leitura de QR (BarcodeDetector, sem biblioteca externa) ----
  var stream=null, lendo=false;
  function alternarCamera(){ stream ? pararCamera() : iniciarCamera(); }
  function iniciarCamera(){
    if(!('BarcodeDetector' in window)){ alert('A leitura de QR não é suportada neste navegador. Use a procura por nome ou código.'); return; }
    var v=document.getElementById('video');
    navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}}).then(function(s){
      stream=s; v.srcObject=s; v.style.display='block'; v.play(); lendo=true;
      var det=new BarcodeDetector({formats:['qr_code']});
      (function loop(){ if(!lendo) return;
        det.detect(v).then(function(codes){
          if(codes && codes.length){ pararCamera(); resolverQr(codes[0].rawValue); }
          else requestAnimationFrame(loop);
        }).catch(function(){ requestAnimationFrame(loop); });
      })();
    }).catch(function(){ alert('Não foi possível aceder à câmara.'); });
  }
  function pararCamera(){ lendo=false; if(stream){ stream.getTracks().forEach(function(t){t.stop();}); stream=null; } document.getElementById('video').style.display='none'; }
  function resolverQr(valor){
    post('resolver',{codigo:valor}).then(function(d){
      if(d.ok){ document.getElementById('q').value=d.convite.nome_exibicao; desenhar([d.convite]); }
      else alert(d.erro||'Convite não encontrado.');
    });
  }

  carregarChegadas();
</script>
</body></html>
