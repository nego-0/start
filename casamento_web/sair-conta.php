<?php
// sair-conta.php — Termina a sessão de casal.
require_once __DIR__ . '/conta.php';
terminarConta();
header('Location: entrar.php');
exit;
