<?php
// registar.php — Criação de conta de casal + primeiro evento (Fase 0)
require_once __DIR__ . '/conta.php';
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf(); // [S1]
    [$ok, $msg] = registarConta(
        $conn,
        $_POST['nome'] ?? '', $_POST['email'] ?? '', $_POST['senha'] ?? '',
        $_POST['noiva'] ?? '', $_POST['noivo'] ?? '', $_POST['data'] ?? null
    );
    if ($ok) { header('Location: painel-casal.php'); exit; }
    $flash = $msg;
}
$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$old = fn($k) => $H($_POST[$k] ?? '');
?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Criar conta · Plataforma de Convites</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box} body{margin:0;font-family:'Jost',sans-serif;background:#16261E;color:#26332b;
    min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem}
  .cartao{background:#fbf8f1;border-radius:18px;max-width:480px;width:100%;padding:2rem 1.8rem;box-shadow:0 24px 70px rgba(0,0,0,.3)}
  h1{font-family:'Cormorant Garamond',serif;font-size:1.7rem;color:#20342A;margin:0 0 .3rem}
  .sub{color:#7a8074;font-size:.9rem;margin:0 0 1.4rem}
  label{display:block;font-size:.8rem;color:#5c6b5f;margin:.7rem 0 .25rem}
  input{width:100%;border:1px solid #e6dfce;border-radius:9px;padding:.6rem .7rem;font:inherit;font-size:.95rem}
  .dois{display:grid;grid-template-columns:1fr 1fr;gap:.6rem}
  .btn{width:100%;margin-top:1.3rem;border:none;border-radius:50px;padding:.75rem;font:inherit;font-weight:500;
    color:#fff;background:linear-gradient(135deg,#B4864A,#8A6031);cursor:pointer}
  .flash{background:#fbe6e6;color:#8a2f2f;border:1px solid #e6bcbc;padding:.6rem .8rem;border-radius:9px;font-size:.86rem;margin-bottom:1rem}
  .alt{text-align:center;margin-top:1.1rem;font-size:.88rem;color:#5c6b5f} a{color:#8A6031}
  .sec{font-size:.72rem;letter-spacing:.14em;text-transform:uppercase;color:#B4864A;margin:1.2rem 0 .2rem}
</style></head><body>
<form class="cartao" method="post">
  <h1>Criar conta</h1>
  <p class="sub">Comece a desenhar o convite do seu casamento.</p>
  <?php if ($flash): ?><div class="flash"><?= $H($flash) ?></div><?php endif; ?>

  <div class="sec">A sua conta</div>
  <label>O seu nome</label>
  <input type="text" name="nome" required value="<?= $old('nome') ?>">
  <label>Email</label>
  <input type="email" name="email" required value="<?= $old('email') ?>">
  <label>Palavra-passe (mín. 6)</label>
  <input type="password" name="senha" required minlength="6">

  <div class="sec">O casamento</div>
  <div class="dois">
    <div><label>Noiva</label><input type="text" name="noiva" required value="<?= $old('noiva') ?>"></div>
    <div><label>Noivo</label><input type="text" name="noivo" required value="<?= $old('noivo') ?>"></div>
  </div>
  <label>Data (opcional)</label>
  <input type="date" name="data" value="<?= $old('data') ?>">

  <button class="btn" type="submit">Criar conta e evento</button>
  <p class="alt">Já tem conta? <a href="entrar.php">Entrar</a></p>
</form>
</body></html>
