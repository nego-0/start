<?php
// painel-casal.php — Painel do casal (Fase 0): eventos e desenho do convite.
require_once __DIR__ . '/conta.php';
exigirConta();
$contaId = contaLogada();

// Novo evento
if (($_POST['acao'] ?? '') === 'novo_evento') {
    $id = criarEvento($conn, $contaId, $_POST['noiva'] ?? '', $_POST['noivo'] ?? '', $_POST['data'] ?? null);
    definirEventoAtivo($conn, $id);
    header('Location: painel-casal.php'); exit;
}
// Trocar de evento ativo
if (isset($_GET['evento'])) {
    definirEventoAtivo($conn, (int)$_GET['evento']);
    header('Location: painel-casal.php'); exit;
}

$eventos = eventosDaConta($conn, $contaId);
$ativoId = eventoDaSessao();
$ativo   = $ativoId ? carregarEvento($conn, $ativoId, $contaId) : null;
$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>O meu painel · Plataforma de Convites</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box} body{margin:0;font-family:'Jost',sans-serif;background:#f3efe6;color:#26332b}
  .topo{display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;padding:.9rem 1.2rem;background:#16261E;color:#EFE3CB}
  .topo h1{font-family:'Cormorant Garamond',serif;font-size:1.4rem;margin:0;font-weight:600}
  .topo .sp{flex:1} .topo a{color:#EFE3CB;text-decoration:none;border:1px solid rgba(217,188,140,.4);padding:.4rem .8rem;border-radius:50px;font-size:.84rem}
  .wrap{max-width:960px;margin:0 auto;padding:1.3rem 1.2rem;display:grid;grid-template-columns:1fr;gap:1.1rem}
  .cartao{background:#fff;border:1px solid #e6dfce;border-radius:16px;padding:1.1rem 1.2rem}
  .cartao h2{font-family:'Cormorant Garamond',serif;font-size:1.2rem;margin:0 0 .6rem;color:#20342A}
  .eventos{display:flex;flex-wrap:wrap;gap:.6rem}
  .ev{border:2px solid #e6dfce;border-radius:12px;padding:.6rem .9rem;text-decoration:none;color:#26332b;min-width:170px}
  .ev.sel{border-color:#16261E;background:#faf7ef}
  .ev .n{font-family:'Cormorant Garamond',serif;font-weight:600;font-size:1.05rem;color:#20342A}
  .ev .d{font-size:.78rem;color:#8a8f88}
  .acoes{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.7rem}
  .acao{display:block;text-decoration:none;color:#26332b;border:1px solid #e6dfce;border-radius:12px;padding:.9rem 1rem;background:#fbf8f1}
  .acao:hover{border-color:#D9BC8C}
  .acao .t{font-weight:600;color:#20342A} .acao .s{font-size:.8rem;color:#8a8f88;margin-top:.15rem}
  label{display:block;font-size:.78rem;color:#5c6b5f;margin:.5rem 0 .2rem}
  input{border:1px solid #e6dfce;border-radius:9px;padding:.5rem .6rem;font:inherit;font-size:.9rem;width:100%}
  .dois{display:grid;grid-template-columns:1fr 1fr 1fr;gap:.6rem;align-items:end}
  .btn{border:none;border-radius:50px;padding:.6rem 1.1rem;font:inherit;font-weight:500;color:#fff;background:linear-gradient(135deg,#B4864A,#8A6031);cursor:pointer}
  @media(max-width:560px){.dois{grid-template-columns:1fr}}
</style></head><body>
<div class="topo">
  <h1>O meu painel</h1>
  <span class="sp"></span>
  <a href="sair-conta.php">Terminar sessão</a>
</div>
<div class="wrap">

  <div class="cartao">
    <h2>Os meus casamentos</h2>
    <div class="eventos">
      <?php foreach ($eventos as $e): ?>
        <a class="ev<?= (int)$e['id'] === (int)$ativoId ? ' sel' : '' ?>" href="painel-casal.php?evento=<?= (int)$e['id'] ?>">
          <div class="n"><?= $H($e['noiva']) ?> &amp; <?= $H($e['noivo']) ?></div>
          <div class="d"><?= $e['data_iso'] ? $H($e['data_iso']) : 'sem data' ?> · /<?= $H($e['slug']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($ativo): ?>
  <div class="cartao">
    <h2>Convite de <?= $H($ativo['noiva']) ?> &amp; <?= $H($ativo['noivo']) ?></h2>
    <div class="acoes">
      <a class="acao" href="editor-modelos.php"><div class="t">Modelo &amp; cores</div><div class="s">Galeria, paleta, tipografia, secções e textos</div></a>
      <a class="acao" href="editor-tela.php"><div class="t">Editor de tela</div><div class="s">Desenho livre do convite impresso</div></a>
      <a class="acao" href="convite-impresso.php"><div class="t">Convite impresso</div><div class="s">Cartão pronto a imprimir (PDF)</div></a>
      <a class="acao" href="convite-digital.php?evento=<?= $H($ativo['slug']) ?>" target="_blank"><div class="t">Ver convite digital ↗</div><div class="s">Pré-visualização pública do desenho</div></a>
    </div>
  </div>
  <?php endif; ?>

  <div class="cartao">
    <h2>Novo casamento</h2>
    <form method="post" class="dois">
      <input type="hidden" name="acao" value="novo_evento">
      <div><label>Noiva</label><input type="text" name="noiva" required></div>
      <div><label>Noivo</label><input type="text" name="noivo" required></div>
      <div><label>Data (opcional)</label><input type="date" name="data"></div>
      <div style="grid-column:1/-1;margin-top:.6rem"><button class="btn" type="submit">Criar evento</button></div>
    </form>
  </div>

</div>
</body></html>
