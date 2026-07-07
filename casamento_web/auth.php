<?php
// ============================================================
// auth.php — Autenticação por sessão (admin / porteiro)
// ============================================================
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function papel(): ?string { return $_SESSION['papel'] ?? null; }
function ehAdmin(): bool   { return papel() === 'admin'; }
function podeEntrar(): bool { return in_array(papel(), ['admin', 'porteiro'], true); }

/** Tenta autenticar por senha; devolve o papel ou null. */
function autenticar(string $senha): ?string {
    if (hash_equals(SENHA_ADMIN, $senha))    { $_SESSION['papel'] = 'admin';    return 'admin'; }
    if (hash_equals(SENHA_PORTEIRO, $senha)) { $_SESSION['papel'] = 'porteiro'; return 'porteiro'; }
    return null;
}

function terminarSessao(): void {
    $_SESSION = [];
    session_destroy();
}

/** Exige admin; caso contrário redireciona para o login. */
function exigirAdmin(): void {
    if (!ehAdmin()) { header('Location: login.php?r=' . urlencode($_SERVER['REQUEST_URI'] ?? 'index.php')); exit; }
}

/** Exige admin ou porteiro. */
function exigirPorta(): void {
    if (!podeEntrar()) { header('Location: login.php?r=' . urlencode($_SERVER['REQUEST_URI'] ?? 'porteiro.php')); exit; }
}
