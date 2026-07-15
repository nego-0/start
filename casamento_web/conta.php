<?php
// ============================================================
// conta.php — Contas de casais e eventos (Fase 0, multi-inquilino)
//   Autenticação por email + palavra-passe (hash), sessão com o
//   evento ativo, criação/seleção de eventos e isolamento de dados.
//   Coexiste com o acesso legado (auth.php: admin/porteiro do evento 1).
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';    // ehAdmin(), sessão
require_once __DIR__ . '/design.php';  // slugAscii()
if (session_status() === PHP_SESSION_NONE) session_start();

// ---- Sessão da conta ----------------------------------------
function contaLogada(): ?int { return isset($_SESSION['conta_id']) ? (int)$_SESSION['conta_id'] : null; }
function eventoDaSessao(): ?int { return isset($_SESSION['evento_id']) ? (int)$_SESSION['evento_id'] : null; }

/** Aplica o evento ativo (da sessão) ao escopo global dos dados. */
function aplicarEventoDaSessao(): void {
    if (($e = eventoDaSessao()) !== null) $GLOBALS['EVENTO_ID'] = $e;
}
aplicarEventoDaSessao();

// ---- Registo / autenticação ---------------------------------

/** Regista uma conta e cria o seu primeiro evento. Devolve [ok, msg]. */
function registarConta(mysqli $conn, string $nome, string $email, string $senha, string $noiva, string $noivo, ?string $dataIso): array {
    global $P;
    $nome = trim($nome); $email = strtolower(trim($email));
    if ($nome === '' || $noiva === '' || $noivo === '') return [false, 'Preencha o seu nome e os nomes do casal.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, 'Email inválido.'];
    if (strlen($senha) < 6) return [false, 'A palavra-passe deve ter pelo menos 6 caracteres.'];

    $st = $conn->prepare("SELECT id FROM {$P}contas WHERE email=? LIMIT 1");
    $st->bind_param('s', $email); $st->execute();
    if ($st->get_result()->fetch_assoc()) return [false, 'Já existe uma conta com esse email.'];

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $st = $conn->prepare("INSERT INTO {$P}contas (nome, email, senha_hash) VALUES (?,?,?)");
    $st->bind_param('sss', $nome, $email, $hash); $st->execute();
    $contaId = $conn->insert_id;

    $eventoId = criarEvento($conn, $contaId, $noiva, $noivo, $dataIso);
    $_SESSION['conta_id'] = $contaId;
    $_SESSION['evento_id'] = $eventoId;
    aplicarEventoDaSessao();
    return [true, 'Conta criada.'];
}

/** Autentica por email + palavra-passe. Devolve [ok, msg]. */
function autenticarConta(mysqli $conn, string $email, string $senha): array {
    global $P;
    $email = strtolower(trim($email));
    $st = $conn->prepare("SELECT id, senha_hash FROM {$P}contas WHERE email=? LIMIT 1");
    $st->bind_param('s', $email); $st->execute();
    $c = $st->get_result()->fetch_assoc();
    if (!$c || !password_verify($senha, $c['senha_hash'])) return [false, 'Email ou palavra-passe incorretos.'];
    $_SESSION['conta_id'] = (int)$c['id'];
    $ev = eventosDaConta($conn, (int)$c['id']);
    $_SESSION['evento_id'] = $ev ? (int)$ev[0]['id'] : null;
    aplicarEventoDaSessao();
    return [true, 'Sessão iniciada.'];
}

function terminarConta(): void {
    unset($_SESSION['conta_id'], $_SESSION['evento_id']);
}

/** Exige uma conta de casal iniciada; caso contrário redireciona. */
function exigirConta(): void {
    if (contaLogada() === null) { header('Location: entrar.php'); exit; }
}

// ---- Eventos -------------------------------------------------

function slugDisponivel(mysqli $conn, string $base): string {
    global $P;
    $base = strtolower(slugAscii($base)) ?: 'evento';
    $slug = $base; $n = 1;
    while (true) {
        $st = $conn->prepare("SELECT id FROM {$P}eventos WHERE slug=? LIMIT 1");
        $st->bind_param('s', $slug); $st->execute();
        if (!$st->get_result()->fetch_assoc()) return $slug;
        $slug = $base . '-' . (++$n);
    }
}

/** Cria um evento para a conta e devolve o id. */
function criarEvento(mysqli $conn, int $contaId, string $noiva, string $noivo, ?string $dataIso): int {
    global $P;
    $noiva = trim($noiva); $noivo = trim($noivo);
    $slug = slugDisponivel($conn, $noiva . '-' . $noivo);
    $data = ($dataIso && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataIso)) ? $dataIso : null;
    $st = $conn->prepare("INSERT INTO {$P}eventos (conta_id, slug, noiva, noivo, data_iso) VALUES (?,?,?,?,?)");
    $st->bind_param('issss', $contaId, $slug, $noiva, $noivo, $data);
    $st->execute();
    return $conn->insert_id;
}

/** Lista os eventos de uma conta. */
function eventosDaConta(mysqli $conn, int $contaId): array {
    global $P;
    $st = $conn->prepare("SELECT id, slug, noiva, noivo, data_iso FROM {$P}eventos WHERE conta_id=? ORDER BY id");
    $st->bind_param('i', $contaId); $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

/** Carrega um evento pelo id (com verificação opcional de dono). */
function carregarEvento(mysqli $conn, int $eventoId, ?int $contaId = null): ?array {
    global $P;
    $st = $conn->prepare("SELECT * FROM {$P}eventos WHERE id=? LIMIT 1");
    $st->bind_param('i', $eventoId); $st->execute();
    $e = $st->get_result()->fetch_assoc();
    if (!$e) return null;
    if ($contaId !== null && (int)$e['conta_id'] !== $contaId) return null;
    return $e;
}

/** Carrega um evento pelo slug (público). */
function eventoPorSlug(mysqli $conn, string $slug): ?array {
    global $P;
    $st = $conn->prepare("SELECT * FROM {$P}eventos WHERE slug=? LIMIT 1");
    $st->bind_param('s', $slug); $st->execute();
    return $st->get_result()->fetch_assoc() ?: null;
}

/** Define o evento ativo na sessão (verificando que pertence à conta). */
function definirEventoAtivo(mysqli $conn, int $eventoId): bool {
    $conta = contaLogada(); if ($conta === null) return false;
    if (!carregarEvento($conn, $eventoId, $conta)) return false;
    $_SESSION['evento_id'] = $eventoId;
    aplicarEventoDaSessao();
    return true;
}

/**
 * Autoriza a edição do convite e fixa o evento a editar:
 *   • conta de casal  -> o seu evento ativo;
 *   • admin legado    -> o evento 1 (o casamento atual);
 *   • ninguém         -> redireciona para entrar.php.
 */
function exigirEdicao(mysqli $conn): void {
    if (contaLogada() !== null && eventoDaSessao() !== null) {
        $GLOBALS['EVENTO_ID'] = eventoDaSessao();
        return;
    }
    if (ehAdmin()) { $GLOBALS['EVENTO_ID'] = 1; return; }
    header('Location: entrar.php'); exit;
}

/** Nome do evento ativo (para cabeçalhos). */
function nomeEventoAtivo(mysqli $conn): string {
    $e = carregarEvento($conn, eventoId());
    return $e ? ($e['noiva'] . ' & ' . $e['noivo']) : 'Convite';
}

/** Painel a que voltar consoante o tipo de sessão. */
function urlPainel(): string {
    return contaLogada() !== null ? 'painel-casal.php' : 'index.php';
}

/** Links de navegação entre secções do casal (menu consistente). */
function navCasalLinks(string $ativa = ''): string {
    $links = [
        'painel'     => [urlPainel(),          'Painel'],
        'design'     => ['editor-modelos.php', 'Design'],
        'convidados' => ['convidados.php',     'Convidados'],
        'mesas'      => ['mesas-plano.php',     'Mesas'],
        'envios'     => ['envios.php',          'Envios'],
        'porta'      => ['porta.php',           'Porta'],
        'impresso'   => ['convite-impresso.php','Impresso'],
    ];
    $out = '';
    foreach ($links as $k => [$href, $lbl]) {
        $on = $k === $ativa ? ' style="background:rgba(217,188,140,.28)" aria-current="page"' : '';
        $out .= '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '"' . $on . '>' . $lbl . '</a>';
    }
    return $out;
}
