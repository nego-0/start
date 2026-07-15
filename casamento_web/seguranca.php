<?php
// ============================================================
// seguranca.php — Camada de segurança partilhada
//   • sessão endurecida (HttpOnly, SameSite, Secure)     [S4]
//   • cabeçalhos de segurança                            [S4]
//   • proteção CSRF (token de sessão + verificação)      [S1]
//   • limitação de tentativas de início de sessão        [S5]
// Sem dependências: pode ser incluída cedo (antes da sessão).
// ============================================================

/** Deteta HTTPS, incluindo por trás de proxy/CDN. */
function pedidoHttps(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') return true;
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;
    return false;
}

/** Inicia a sessão com cookies endurecidos e cabeçalhos de segurança. [S4] */
function iniciarSessaoSegura(): void {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => pedidoHttps(),
        ]);
        session_name('cwsess');
        session_start();
    }
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

/** Regenera o id de sessão (chamar após autenticar, contra fixação). */
function regenerarSessao(): void {
    if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
}

// ---- CSRF ---------------------------------------------------  [S1]

/** Token CSRF da sessão (criado à primeira utilização). */
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

/** Campo oculto para formulários. */
function csrfCampo(): string {
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES) . '">';
}

/** Meta + script que injeta o token nos pedidos fetch de mutação (mesma origem). */
function csrfScript(): string {
    $t = htmlspecialchars(csrfToken(), ENT_QUOTES);
    return '<meta name="csrf-token" content="' . $t . '">'
        . '<script>(function(){var T="' . $t . '";'
        . 'var f=window.fetch;window.fetch=function(u,o){o=o||{};'
        . 'var m=(o.method||"GET").toUpperCase();'
        . 'var same=true;try{same=new URL(u,location.href).origin===location.origin;}catch(e){}'
        . 'if(same&&m!=="GET"&&m!=="HEAD"){o.headers=new Headers(o.headers||{});'
        . 'if(!o.headers.has("X-CSRF-Token"))o.headers.set("X-CSRF-Token",T);}'
        . 'return f(u,o);};'
        . 'document.addEventListener("submit",function(e){var fm=e.target;'
        . 'if(fm&&fm.method&&fm.method.toLowerCase()==="post"&&!fm.querySelector("input[name=csrf]")){'
        . 'var i=document.createElement("input");i.type="hidden";i.name="csrf";i.value=T;fm.appendChild(i);}},true);'
        . '})();</script>';
}

/** Verifica o token CSRF (do campo ou do cabeçalho). */
function csrfValido(): bool {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($t) && $t !== '' && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

/** Bloqueia pedidos POST sem token válido. $json=true responde em JSON. */
function exigirCsrf(bool $json = false): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
    if (csrfValido()) return;
    http_response_code(403);
    if ($json) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success' => false, 'message' => 'Sessão expirada. Recarregue a página e tente de novo.']); }
    else       { header('Content-Type: text/plain; charset=utf-8'); echo 'Pedido inválido (proteção CSRF). Recarregue a página.'; }
    exit;
}

// ---- Limitação de tentativas (rate limiting) ----------------  [S5]

function _segDir(): string {
    $d = __DIR__ . '/uploads/.seg';
    if (!is_dir($d)) @mkdir($d, 0775, true);
    return $d;
}
function _segChave(string $ctx): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
    return preg_replace('/[^a-z0-9]/i', '', $ctx) . '_' . sha1($ip);
}
function _segLer(string $chave, int $janela): array {
    $f = _segDir() . '/' . $chave . '.json';
    if (!is_file($f)) return [];
    $ts = json_decode(@file_get_contents($f), true) ?: [];
    $lim = time() - $janela;
    return array_values(array_filter($ts, fn($t) => is_int($t) && $t >= $lim));
}

/** Nº de tentativas recentes para um contexto (por IP). */
function tentativasRecentes(string $ctx, int $janela = 900): int {
    return count(_segLer(_segChave($ctx), $janela));
}
/** Regista uma tentativa falhada. */
function registarTentativa(string $ctx, int $janela = 900): void {
    $chave = _segChave($ctx);
    $ts = _segLer($chave, $janela);
    $ts[] = time();
    @file_put_contents(_segDir() . '/' . $chave . '.json', json_encode(array_slice($ts, -50)), LOCK_EX);
}
/** Limpa as tentativas (após sucesso). */
function limparTentativas(string $ctx): void {
    @unlink(_segDir() . '/' . _segChave($ctx) . '.json');
}
/** Está bloqueado por exceder o máximo de tentativas na janela? */
function bloqueadoPorTentativas(string $ctx, int $max = 8, int $janela = 900): bool {
    return tentativasRecentes($ctx, $janela) >= $max;
}
