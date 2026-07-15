<?php
// ============================================================
// db.php — Ligação, esquema e funções partilhadas
// ============================================================
require_once __DIR__ . '/config.php';

// ---- Ligação (tenta local, depois online) ------------------
// No PHP 8.1+ o mysqli lança exceções por defeito. Desligamos esse modo
// para que uma tentativa de ligação falhada devolva erro em vez de abortar
// o script (o que causaria um HTTP 500 antes de tentar a config seguinte).
mysqli_report(MYSQLI_REPORT_OFF);

$conn = null; $CONFIG_ATIVA = null; $ULTIMO_ERRO = '';
foreach (DB_CONFIGS as $nome => $cfg) {
    try {
        $t = @new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db']);
        if ($t && !$t->connect_error) { $conn = $t; $CONFIG_ATIVA = $nome; break; }
        if ($t && $t->connect_error) { $ULTIMO_ERRO = $t->connect_error; }
    } catch (\Throwable $e) {
        $ULTIMO_ERRO = $e->getMessage();
    }
}
if (!$conn) {
    http_response_code(503);
    $msg = 'Erro de ligação à base de dados. Verifique o config.php.';
    // Diagnóstico opcional: aceda com ?diag=1 para ver o motivo técnico.
    if (isset($_GET['diag']) && $_GET['diag'] === '1' && $ULTIMO_ERRO !== '') {
        $msg .= ' [Detalhe: ' . htmlspecialchars($ULTIMO_ERRO) . ']';
    }
    die($msg);
}
$conn->set_charset('utf8mb4');

// Alinha o fuso da base de dados com o fuso local (config.php), para que
// CURRENT_TIMESTAMP/NOW() fiquem na hora local e não na do servidor.
try {
    $offset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
    @$conn->query("SET time_zone = '$offset'");
} catch (\Throwable $e) { /* mantém o fuso do servidor se falhar */ }

$P = PREFIXO; // atalho para o prefixo

// Evento ativo (multi-inquilino). Por defeito o evento 1 (o casamento
// atual). A camada de contas (conta.php) e as páginas públicas ajustam-no.
$GLOBALS['EVENTO_ID'] = 1;

// ---- Esquema (tabelas novas, prefixadas) -------------------
$conn->query("
    CREATE TABLE IF NOT EXISTS {$P}mesas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(191) NOT NULL UNIQUE,
        capacidade INT DEFAULT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("
    CREATE TABLE IF NOT EXISTS {$P}convites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(12) NOT NULL UNIQUE,
        nome_exibicao VARCHAR(255) NOT NULL,          -- nome no convite (ex: Família Agostinho)
        sufixo VARCHAR(120) DEFAULT NULL,             -- override do texto entre parênteses
        mostrar_numero TINYINT(1) DEFAULT 1,          -- mostrar o número entre parênteses no convite
        tipo ENUM('digital','fisico','ambos') DEFAULT 'digital',
        lado ENUM('noivo','noiva','ambos') DEFAULT 'noivo',
        lugares INT NOT NULL DEFAULT 1,
        mesa_id INT DEFAULT NULL,
        telefone VARCHAR(50) DEFAULT NULL,
        impresso TINYINT(1) DEFAULT 0,                -- convite físico já impresso
        enviado TINYINT(1) DEFAULT 0,                 -- convite digital já enviado
        rsvp_estado ENUM('pendente','confirmado','recusado','parcial') DEFAULT 'pendente',
        rsvp_confirmados INT DEFAULT NULL,            -- nº que confirmou presença
        rsvp_mensagem TEXT DEFAULT NULL,
        rsvp_em TIMESTAMP NULL DEFAULT NULL,
        checkin_estado ENUM('aguardando','presente','parcial') DEFAULT 'aguardando',
        checkin_presentes INT DEFAULT 0,
        checkin_em TIMESTAMP NULL DEFAULT NULL,
        observacoes TEXT DEFAULT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("
    CREATE TABLE IF NOT EXISTS {$P}convidados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        convite_id INT NOT NULL,
        nome VARCHAR(255) NOT NULL,
        principal TINYINT(1) DEFAULT 0,
        rsvp ENUM('pendente','confirmado','recusado') DEFAULT 'pendente',
        presente TINYINT(1) DEFAULT 0,
        presente_em TIMESTAMP NULL DEFAULT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (convite_id) REFERENCES {$P}convites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Migração suave: garantir a coluna mostrar_numero em instalações anteriores
$col = $conn->query("SHOW COLUMNS FROM {$P}convites LIKE 'mostrar_numero'");
if ($col && $col->num_rows === 0) {
    $conn->query("ALTER TABLE {$P}convites ADD COLUMN mostrar_numero TINYINT(1) DEFAULT 1 AFTER sufixo");
}
// Respostas às perguntas personalizadas de RSVP (JSON).
$col = $conn->query("SHOW COLUMNS FROM {$P}convites LIKE 'rsvp_extra'");
if ($col && $col->num_rows === 0) {
    $conn->query("ALTER TABLE {$P}convites ADD COLUMN rsvp_extra TEXT DEFAULT NULL AFTER rsvp_mensagem");
}

// ============================================================
// Multi-inquilino (Fase 0): contas, eventos e isolamento por evento.
// Aditivo e retrocompatível — os dados atuais passam a pertencer ao
// evento 1 (semeado a partir de config.php). Nada é apagado.
// ============================================================
$conn->query("
    CREATE TABLE IF NOT EXISTS {$P}contas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(160) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        senha_hash VARCHAR(255) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("
    CREATE TABLE IF NOT EXISTS {$P}eventos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conta_id INT NOT NULL,
        slug VARCHAR(80) NOT NULL UNIQUE,
        noiva VARCHAR(120) NOT NULL,
        noivo VARCHAR(120) NOT NULL,
        data_iso DATE DEFAULT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conta_id) REFERENCES {$P}contas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Coluna evento_id nas tabelas existentes (migração suave, default 1).
foreach (['convites', 'mesas'] as $tab) {
    $c = $conn->query("SHOW COLUMNS FROM {$P}{$tab} LIKE 'evento_id'");
    if ($c && $c->num_rows === 0) {
        $conn->query("ALTER TABLE {$P}{$tab} ADD COLUMN evento_id INT NOT NULL DEFAULT 1");
        $conn->query("ALTER TABLE {$P}{$tab} ADD INDEX (evento_id)");
    }
}
// A unicidade das mesas passa a ser por evento (nomes iguais em eventos diferentes).
$idx = $conn->query("SHOW INDEX FROM {$P}mesas WHERE Key_name='nome'");
if ($idx && $idx->num_rows > 0) {
    @$conn->query("ALTER TABLE {$P}mesas DROP INDEX nome");
    @$conn->query("ALTER TABLE {$P}mesas ADD UNIQUE KEY uniq_mesa_evento (evento_id, nome)");
}

// Semear a conta e o evento 1 com os dados atuais, se ainda não existir.
$temConta = (int)($conn->query("SELECT COUNT(*) FROM {$P}contas")->fetch_row()[0] ?? 0);
if ($temConta === 0) {
    $nome  = (EVENTO['noiva'] ?? 'Isabel') . ' & ' . (EVENTO['noivo'] ?? 'Abednego');
    // [S3] Conta-semente sem palavra-passe utilizável: só é gerida pelo
    // super-administrador (login.php). Um hash de valor aleatório garante
    // que password_verify() nunca confere via entrar.php.
    $hash  = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $st = $conn->prepare("INSERT INTO {$P}contas (id, nome, email, senha_hash) VALUES (1, ?, 'principal@local', ?)");
    $st->bind_param('ss', $nome, $hash); $st->execute();
    $noiva = EVENTO['noiva'] ?? 'Isabel'; $noivo = EVENTO['noivo'] ?? 'Abednego'; $di = EVENTO['data_iso'] ?? '2026-12-19';
    $st = $conn->prepare("INSERT INTO {$P}eventos (id, conta_id, slug, noiva, noivo, data_iso) VALUES (1, 1, 'principal', ?, ?, ?)");
    $st->bind_param('sss', $noiva, $noivo, $di); $st->execute();
    // Garantir que os dados existentes ficam no evento 1.
    $conn->query("UPDATE {$P}convites SET evento_id=1 WHERE evento_id IS NULL OR evento_id=0");
    $conn->query("UPDATE {$P}mesas SET evento_id=1 WHERE evento_id IS NULL OR evento_id=0");
}

// ============================================================
// Funções partilhadas
// ============================================================

/** Id do evento ativo (multi-inquilino). Por defeito, o evento 1. */
function eventoId(): int { return (int)($GLOBALS['EVENTO_ID'] ?? 1); }

/** URL base do site (funciona em local e online, para links e QR). */
function base_url(): string {
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return "$scheme://$host$dir";
}

/** Código único e legível (sem caracteres ambíguos). */
function gerarCodigo(mysqli $conn): string {
    global $P;
    $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sem O,0,I,1
    do {
        $c = '';
        for ($i = 0; $i < 6; $i++) $c .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        $st = $conn->prepare("SELECT id FROM {$P}convites WHERE codigo=? LIMIT 1");
        $st->bind_param('s', $c); $st->execute();
        $existe = $st->get_result()->fetch_assoc();
    } while ($existe);
    return $c;
}

/**
 * Nome final do convite, aplicando a regra do número entre parênteses.
 * - 1 lugar  -> apenas o nome (sem parênteses)
 * - >1 lugar -> "Nome (N)"  ou o sufixo personalizado, se existir.
 */
function nomeConvite(array $c): string {
    $nome = trim($c['nome_exibicao']);
    $lug  = (int)($c['lugares'] ?? 1);
    $suf  = trim((string)($c['sufixo'] ?? ''));
    if ($suf !== '')  return "$nome ($suf)";
    if ($lug <= 1)    return $nome;
    return "$nome ($lug)";
}

/**
 * Nome tal como aparece NO CONVITE do convidado, respeitando a opção
 * "mostrar_numero". Um sufixo textual mantém-se sempre; o número entre
 * parênteses (o nº de lugares) só surge quando a opção está ativa.
 */
function nomeConviteVisivel(array $c): string {
    $nome = trim($c['nome_exibicao']);
    $lug  = (int)($c['lugares'] ?? 1);
    $suf  = trim((string)($c['sufixo'] ?? ''));
    $mostrar = !isset($c['mostrar_numero']) || (int)$c['mostrar_numero'] === 1;
    if ($suf !== '')          return "$nome ($suf)";
    if ($mostrar && $lug > 1) return "$nome ($lug)";
    return $nome;
}

/** Indica se o número entre parênteses (nº de lugares) é mostrado no convite. */
function mostraNumeroConvite(array $c): bool {
    $lug = (int)($c['lugares'] ?? 1);
    $suf = trim((string)($c['sufixo'] ?? ''));
    $mostrar = !isset($c['mostrar_numero']) || (int)$c['mostrar_numero'] === 1;
    return $mostrar && $suf === '' && $lug > 1;
}

/** Resolve/insere uma mesa (no evento ativo) pelo nome e devolve o id. */
function resolverMesa(mysqli $conn, string $nome): ?int {
    global $P;
    $eid = eventoId();
    $nome = trim($nome);
    if ($nome === '') return null;
    $st = $conn->prepare("SELECT id FROM {$P}mesas WHERE nome=? AND evento_id=? LIMIT 1");
    $st->bind_param('si', $nome, $eid); $st->execute();
    if ($r = $st->get_result()->fetch_assoc()) return (int)$r['id'];
    $st = $conn->prepare("INSERT INTO {$P}mesas (nome, evento_id) VALUES (?, ?)");
    $st->bind_param('si', $nome, $eid); $st->execute();
    return $conn->insert_id;
}

/** Recalcula APENAS o estado de entrada (check-in) a partir dos membros. Não toca no RSVP. */
function recalcularCheckin(mysqli $conn, int $conviteId, string $tsSql = 'NOW()'): void {
    global $P;
    $st = $conn->prepare("SELECT COUNT(*) tot,
                                 SUM(CASE WHEN rsvp='confirmado' THEN 1 ELSE 0 END) conf,
                                 COALESCE(SUM(presente),0) pres
                          FROM {$P}convidados WHERE convite_id=?");
    $st->bind_param('i', $conviteId); $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $tot = (int)$r['tot']; $conf = (int)$r['conf']; $pres = (int)$r['pres'];
    if ($tot === 0) return; // convites sem membros nominais são geridos diretamente
    $base = $conf > 0 ? $conf : $tot; // "presente" quando todos os confirmados entraram
    $estado = $pres === 0 ? 'aguardando' : ($pres >= $base ? 'presente' : 'parcial');
    $em = $pres > 0 ? "COALESCE(checkin_em, $tsSql)" : 'NULL';
    $st = $conn->prepare("UPDATE {$P}convites SET checkin_estado=?, checkin_presentes=?, checkin_em=$em WHERE id=?");
    $st->bind_param('sii', $estado, $pres, $conviteId); $st->execute();
}

/** Estatísticas do evento ativo (multi-inquilino). */
function estatisticas(mysqli $conn): array {
    global $P;
    $eid = eventoId();
    // Base de filtro do evento (sempre presente) + condição extra opcional.
    $one = function ($col, $extra = '') use ($conn, $P, $eid) {
        $w = "evento_id=$eid" . ($extra ? " AND $extra" : '');
        return (int)($conn->query("SELECT $col FROM {$P}convites WHERE $w")->fetch_row()[0] ?? 0);
    };
    $s = [];
    $s['convites']     = $one('COUNT(*)');
    $s['lugares']      = $one('COALESCE(SUM(lugares),0)');
    $s['convidados']   = (int)($conn->query("SELECT COUNT(*) FROM {$P}convidados cv JOIN {$P}convites c ON cv.convite_id=c.id WHERE c.evento_id=$eid")->fetch_row()[0] ?? 0);
    $s['digitais']     = $one('COUNT(*)', "tipo IN ('digital','ambos')");
    $s['fisicos']      = $one('COUNT(*)', "tipo IN ('fisico','ambos')");
    $s['noivos']       = $one('COUNT(*)', "lado IN ('noivo','ambos')");
    $s['noivas']       = $one('COUNT(*)', "lado IN ('noiva','ambos')");
    $s['impressos']    = $one('COUNT(*)', 'impresso=1');
    $s['enviados']     = $one('COUNT(*)', 'enviado=1');
    $s['confirmados']  = $one('COUNT(*)', "rsvp_estado='confirmado'");
    $s['parciais']     = $one('COUNT(*)', "rsvp_estado='parcial'");
    $s['recusados']    = $one('COUNT(*)', "rsvp_estado='recusado'");
    $s['pendentes']    = $one('COUNT(*)', "rsvp_estado='pendente'");
    $s['lug_confirm']  = $one('COALESCE(SUM(rsvp_confirmados),0)');
    $s['pes_confirmados'] = $s['lug_confirm'];
    $s['pes_pendentes']   = $one('COALESCE(SUM(lugares),0)', "rsvp_estado='pendente'");
    $s['pes_recusados']   = $one('COALESCE(SUM(lugares),0)', "rsvp_estado='recusado'");
    $s['pes_digitais']    = $one('COALESCE(SUM(lugares),0)', "tipo IN ('digital','ambos')");
    $s['pes_fisicos']     = $one('COALESCE(SUM(lugares),0)', "tipo IN ('fisico','ambos')");
    $s['pes_impressos']   = $one('COALESCE(SUM(lugares),0)', 'impresso=1');
    $s['pes_noivos']      = $one('COALESCE(SUM(lugares),0)', "lado IN ('noivo','ambos')");
    $s['pes_noivas']      = $one('COALESCE(SUM(lugares),0)', "lado IN ('noiva','ambos')");
    $s['capacidade']      = MAX_LUGARES_TOTAL;
    $s['presentes']    = $one('COALESCE(SUM(checkin_presentes),0)');
    $s['no_local']     = $one('COUNT(*)', "checkin_estado IN ('presente','parcial')");
    $s['mesas']        = (int)($conn->query("SELECT COUNT(*) FROM {$P}mesas WHERE evento_id=$eid")->fetch_row()[0] ?? 0);
    return $s;
}

/** Lista simples de mesas com ocupação. */
function listarMesas(mysqli $conn): array {
    global $P;
    $eid = eventoId();
    $sql = "SELECT m.id, m.nome, m.capacidade,
                   COALESCE(SUM(c.lugares),0) AS ocupacao,
                   COUNT(c.id) AS convites
            FROM {$P}mesas m
            LEFT JOIN {$P}convites c ON c.mesa_id = m.id
            WHERE m.evento_id = $eid
            GROUP BY m.id, m.nome, m.capacidade
            ORDER BY m.nome";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/** Carrega um convite (por id ou código) já com os membros e o nome final. */
function carregarConvite(mysqli $conn, $chave, string $por = 'id'): ?array {
    global $P;
    $col = $por === 'codigo' ? 'codigo' : 'id';
    $st = $conn->prepare("SELECT c.*, m.nome AS mesa_nome
                          FROM {$P}convites c LEFT JOIN {$P}mesas m ON c.mesa_id=m.id
                          WHERE c.$col=? LIMIT 1");
    $por === 'codigo' ? $st->bind_param('s', $chave) : $st->bind_param('i', $chave);
    $st->execute();
    $c = $st->get_result()->fetch_assoc();
    if (!$c) return null;
    $st = $conn->prepare("SELECT * FROM {$P}convidados WHERE convite_id=? ORDER BY principal DESC, nome");
    $st->bind_param('i', $c['id']); $st->execute();
    $c['membros'] = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $c['nome_final'] = nomeConvite($c);
    return $c;
}

/** Detecta se a lista antiga existe (para oferecer importação). */
function listaAntigaExiste(mysqli $conn): bool {
    $r = $conn->query("SHOW TABLES LIKE 'guests'");
    return $r && $r->num_rows > 0;
}
