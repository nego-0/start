<?php
// entrar.php — Início de sessão de casais (Fase 0)
require_once __DIR__ . '/conta.php';
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf(); // [S1]
    if (bloqueadoPorTentativas('entrar')) {          // [S5]
        $flash = 'Demasiadas tentativas. Aguarde uns minutos e tente de novo.';
    } else {
        [$ok, $msg] = autenticarConta($conn, $_POST['email'] ?? '', $_POST['senha'] ?? '');
        if ($ok) { limparTentativas('entrar'); header('Location: painel-casal.php'); exit; }
        registarTentativa('entrar');
        $flash = $msg;
    }
}
$H = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar · Plataforma de Convites</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box} body{margin:0;font-family:'Jost',sans-serif;background:#16261E;color:#26332b;
    min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem}
  .cartao{background:#fbf8f1;border-radius:18px;max-width:420px;width:100%;padding:2rem 1.8rem;box-shadow:0 24px 70px rgba(0,0,0,.3)}
  h1{font-family:'Cormorant Garamond',serif;font-size:1.7rem;color:#20342A;margin:0 0 .3rem}
  .sub{color:#7a8074;font-size:.9rem;margin:0 0 1.4rem}
  label{display:block;font-size:.8rem;color:#5c6b5f;margin:.7rem 0 .25rem}
  input{width:100%;border:1px solid #e6dfce;border-radius:9px;padding:.6rem .7rem;font:inherit;font-size:.95rem}
  .btn{width:100%;margin-top:1.3rem;border:none;border-radius:50px;padding:.75rem;font:inherit;font-weight:500;
    color:#fff;background:linear-gradient(135deg,#B4864A,#8A6031);cursor:pointer}
  .flash{background:#fbe6e6;color:#8a2f2f;border:1px solid #e6bcbc;padding:.6rem .8rem;border-radius:9px;font-size:.86rem;margin-bottom:1rem}
  .alt{text-align:center;margin-top:1.1rem;font-size:.88rem;color:#5c6b5f}
  a{color:#8A6031}
</style></head><body>
<form class="cartao" method="post">
  <?= csrfCampo() ?>
  <h1>Entrar</h1>
  <p class="sub">A sua plataforma de convites de casamento.</p>
  <?php if ($flash): ?><div class="flash"><?= $H($flash) ?></div><?php endif; ?>
  <label>Email</label>
  <input type="email" name="email" required autofocus value="<?= $H($_POST['email'] ?? '') ?>">
  <label>Palavra-passe</label>
  <input type="password" name="senha" required>
  <button class="btn" type="submit">Entrar</button>
  <p class="alt">Ainda não tem conta? <a href="registar.php">Criar conta</a></p>
</form>
</body></html>
