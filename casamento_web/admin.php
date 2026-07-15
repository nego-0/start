<?php
// ============================================================
// admin.php — Painel do super-administrador (plataforma).
//   Gere TODAS as contas e eventos: listar, entrar num evento para
//   o gerir, apagar evento ou conta. Só o administrador geral
//   (palavra-passe em config.php) acede.
// ============================================================
require_once __DIR__ . '/conta.php';
exigirAdmin();
$P = PREFIXO;

// Entrar num evento para o gerir (define o contexto do super-admin).
if (isset($_GET['gerir'])) {
    $_SESSION['admin_evento'] = (int)$_GET['gerir'];
    header('Location: editor-modelos.php');
    exit;
}
// Ações destrutivas.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf(); // [S1]
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'apagar_evento') {
        apagarEventos($conn, [(int)($_POST['evento_id'] ?? 0)]);
    } elseif ($acao === 'apagar_conta') {
        $cid = (int)($_POST['conta_id'] ?? 0);
        $r = $conn->query("SELECT id FROM {$P}eventos WHERE conta_id=" . $cid);
        $ids = array_map(fn($x) => (int)$x['id'], $r ? $r->fetch_all(MYSQLI_ASSOC) : []);
        apagarEventos($conn, $ids);
        $st = $conn->prepare("DELETE FROM {$P}contas WHERE id=?"); $st->bind_param('i', $cid); $st->execute();
    }
    header('Location: admin.php'); exit;
}

/** Apaga eventos e todos os seus dados (convites, mesas, design, tela). */
function apagarEventos(mysqli $conn, array $ids): void {
    $P = PREFIXO;
    foreach (array_filter($ids) as $eid) {
        $eid = (int)$eid;
        foreach (['convites', 'mesas', 'designs', 'impressos'] as $t) {
            $conn->query("DELETE FROM {$P}{$t} WHERE evento_id=" . $eid);
        }
        $conn->query("DELETE FROM {$P}eventos WHERE id=" . $eid);
        $dir = __DIR__ . '/uploads/eventos/' . $eid;
        if (is_dir($dir)) { array_map('unlink', glob("$dir/*") ?: []); @rmdir($dir); }
    }
}

// Dados: contas com os seus eventos e contadores.
$sql = "SELECT ct.id AS conta_id, ct.nome AS conta_nome, ct.email, ct.criado_em,
               e.id AS evento_id, e.slug, e.noiva, e.noivo, e.data_iso,
               (SELECT COUNT(*) FROM {$P}convites c WHERE c.evento_id=e.id) AS n_conv,
               (SELECT COALESCE(SUM(rsvp_confirmados),0) FROM {$P}convites c WHERE c.evento_id=e.id) AS n_conf
        FROM {$P}contas ct
        LEFT JOIN {$P}eventos e ON e.conta_id=ct.id
        ORDER BY ct.id, e.id";
$linhas = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$contas = [];
foreach ($linhas as $l) {
    $cid = $l['conta_id'];
    if (!isset($contas[$cid])) $contas[$cid] = ['nome' => $l['conta_nome'], 'email' => $l['email'], 'criado' => $l['criado_em'], 'eventos' => []];
    if ($l['evento_id']) $contas[$cid]['eventos'][] = $l;
}
$totContas = count($contas);
$totEventos = (int)($conn->query("SELECT COUNT(*) FROM {$P}eventos")->fetch_row()[0] ?? 0);
$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administração · Plataforma de Convites</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box} body{margin:0;font-family:'Jost',sans-serif;background:#f3efe6;color:#26332b}
  .topo{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;padding:.8rem 1.1rem;background:#16261E;color:#EFE3CB}
  .topo h1{font-family:'Cormorant Garamond',serif;font-size:1.4rem;margin:0;font-weight:600}
  .topo .badge{font-size:.72rem;color:#C79A5A} .topo .sp{flex:1}
  .topo a{color:#EFE3CB;text-decoration:none;border:1px solid rgba(217,188,140,.4);padding:.4rem .8rem;border-radius:50px;font-size:.84rem}
  .wrap{max-width:1000px;margin:0 auto;padding:1.2rem 1.1rem;display:flex;flex-direction:column;gap:1rem}
  .kpis{display:flex;gap:.6rem;flex-wrap:wrap}
  .kpi{background:#fff;border:1px solid #e6dfce;border-radius:14px;padding:.7rem 1.2rem;text-align:center}
  .kpi .n{font-family:'Cormorant Garamond',serif;font-size:1.8rem;font-weight:700;color:#20342A;line-height:1}
  .kpi .l{font-size:.72rem;color:#8a8f88;text-transform:uppercase;letter-spacing:.5px}
  .conta{background:#fff;border:1px solid #e6dfce;border-radius:14px;padding:1rem 1.1rem}
  .conta .cab{display:flex;align-items:baseline;gap:.6rem;flex-wrap:wrap;margin-bottom:.6rem}
  .conta .cab .nome{font-family:'Cormorant Garamond',serif;font-size:1.2rem;font-weight:600;color:#20342A}
  .conta .cab .email{font-size:.82rem;color:#8a8f88}
  .conta .cab .sp{flex:1}
  .eventos{display:flex;flex-direction:column;gap:.5rem}
  .ev{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;border:1px solid #eee3cc;border-radius:10px;padding:.55rem .8rem;background:#faf7ef}
  .ev .n{font-weight:600} .ev .d{font-size:.78rem;color:#8a8f88}
  .ev .sp{flex:1}
  .ev .cnt{font-size:.78rem;color:#5c6b5f;background:#efe7d6;border-radius:50px;padding:.15rem .6rem}
  .btn{border:none;border-radius:50px;padding:.4rem .9rem;font:inherit;font-size:.82rem;font-weight:500;color:#fff;background:linear-gradient(135deg,#B4864A,#8A6031);cursor:pointer;text-decoration:none}
  .btn.x{background:#efe7d6;color:#a5473f}
  .vazio{color:#9aa093;font-style:italic}
  form.inl{display:inline}
</style></head><body>
<div class="topo">
  <h1>Administração</h1><span class="badge">super-administrador</span>
  <span class="sp"></span>
  <a href="logout.php">Terminar sessão</a>
</div>
<div class="wrap">
  <div class="kpis">
    <div class="kpi"><div class="n"><?= $totContas ?></div><div class="l">Contas</div></div>
    <div class="kpi"><div class="n"><?= $totEventos ?></div><div class="l">Casamentos</div></div>
  </div>

  <?php if (!$contas): ?>
    <div class="conta"><span class="vazio">Ainda não há contas. Os casais registam-se em <a href="registar.php">registar.php</a>.</span></div>
  <?php endif; ?>

  <?php foreach ($contas as $cid => $ct): ?>
  <div class="conta">
    <div class="cab">
      <span class="nome"><?= $H($ct['nome']) ?></span>
      <span class="email"><?= $H($ct['email']) ?></span>
      <span class="sp"></span>
      <form class="inl" method="post" onsubmit="return confirm('Apagar a conta e TODOS os seus casamentos? Esta ação é irreversível.')">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="apagar_conta"><input type="hidden" name="conta_id" value="<?= (int)$cid ?>">
        <button class="btn x" type="submit">Apagar conta</button>
      </form>
    </div>
    <div class="eventos">
      <?php if (!$ct['eventos']): ?><span class="vazio">Sem casamentos.</span><?php endif; ?>
      <?php foreach ($ct['eventos'] as $e): ?>
      <div class="ev">
        <div>
          <div class="n"><?= $H($e['noiva']) ?> &amp; <?= $H($e['noivo']) ?></div>
          <div class="d"><?= $e['data_iso'] ? $H($e['data_iso']) : 'sem data' ?> · /<?= $H($e['slug']) ?></div>
        </div>
        <span class="sp"></span>
        <span class="cnt"><?= (int)$e['n_conv'] ?> convites · <?= (int)$e['n_conf'] ?> confirmados</span>
        <a class="btn" href="admin.php?gerir=<?= (int)$e['evento_id'] ?>">Gerir</a>
        <a class="btn" href="convite-digital.php?evento=<?= $H($e['slug']) ?>" target="_blank" style="background:#efe7d6;color:#5c4a2c">Ver</a>
        <form class="inl" method="post" onsubmit="return confirm('Apagar este casamento e todos os seus dados?')">
          <?= csrfCampo() ?><input type="hidden" name="acao" value="apagar_evento"><input type="hidden" name="evento_id" value="<?= (int)$e['evento_id'] ?>">
          <button class="btn x" type="submit">Apagar</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
</body></html>
