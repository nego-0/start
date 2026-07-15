<?php
// ============================================================
// auth.php — Autenticação por sessão (admin / porteiro)
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/seguranca.php';
iniciarSessaoSegura();

function papel(): ?string { return $_SESSION['papel'] ?? null; }
function ehAdmin(): bool   { return papel() === 'admin'; }
function podeEntrar(): bool { return in_array(papel(), ['admin', 'porteiro'], true); }

/**
 * Verifica uma palavra-passe contra uma configuração que pode ser um hash
 * (password_hash, começa por $2y$/$argon) ou, em recurso, texto simples.
 */
function senhaConfere(string $senha, string $config): bool {
    if ($config === '') return false;
    if (preg_match('/^\$(2y|2a|2b|argon2)/', $config)) return password_verify($senha, $config);
    return hash_equals($config, $senha); // recurso legado (texto simples)
}

/** Tenta autenticar por senha; devolve o papel ou null. [S6] */
function autenticar(string $senha): ?string {
    if ($senha !== '' && senhaConfere($senha, SENHA_ADMIN))    { $_SESSION['papel'] = 'admin';    regenerarSessao(); return 'admin'; }
    if ($senha !== '' && senhaConfere($senha, SENHA_PORTEIRO)) { $_SESSION['papel'] = 'porteiro'; regenerarSessao(); return 'porteiro'; }
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
