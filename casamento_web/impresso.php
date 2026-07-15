<?php
// ============================================================
// impresso.php — Persistência do editor visual de tela (Fase 2)
//   Guarda a "tela" (desenho livre em Fabric.js) do convite
//   impresso, em JSON. Uma única linha nesta fase (multi-evento
//   fica para a Fase 0 da plataforma).
// ============================================================
require_once __DIR__ . '/design.php';

function garantirEsquemaImpresso(mysqli $conn): void {
    global $P;
    $conn->query("
        CREATE TABLE IF NOT EXISTS {$P}impressos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evento_id INT NOT NULL DEFAULT 1,
            nome VARCHAR(120) NOT NULL DEFAULT 'Convite impresso',
            config LONGTEXT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $c = $conn->query("SHOW COLUMNS FROM {$P}impressos LIKE 'evento_id'");
    if ($c && $c->num_rows === 0) {
        $conn->query("ALTER TABLE {$P}impressos ADD COLUMN evento_id INT NOT NULL DEFAULT 1 AFTER id");
        $conn->query("ALTER TABLE {$P}impressos ADD INDEX (evento_id)");
    }
}

/** Devolve o JSON da tela guardada do evento, ou null se ainda não houver. */
function carregarImpressoTela(mysqli $conn, ?int $eventoId = null): ?string {
    global $P;
    garantirEsquemaImpresso($conn);
    $eid = $eventoId ?? eventoId();
    $st = $conn->prepare("SELECT config FROM {$P}impressos WHERE evento_id=? ORDER BY id LIMIT 1");
    $st->bind_param('i', $eid); $st->execute();
    if ($row = $st->get_result()->fetch_assoc()) return $row['config'];
    return null;
}

/** Guarda o JSON da tela do evento (valida que é JSON). Devolve true/false. */
function guardarImpressoTela(mysqli $conn, string $json, ?int $eventoId = null): bool {
    global $P;
    garantirEsquemaImpresso($conn);
    $eid = $eventoId ?? eventoId();
    if (json_decode($json) === null && strtolower(trim($json)) !== 'null') return false;
    $st = $conn->prepare("SELECT id FROM {$P}impressos WHERE evento_id=? ORDER BY id LIMIT 1");
    $st->bind_param('i', $eid); $st->execute();
    if ($row = $st->get_result()->fetch_assoc()) {
        $st = $conn->prepare("UPDATE {$P}impressos SET config=? WHERE id=?");
        $st->bind_param('si', $json, $row['id']); return $st->execute();
    }
    $st = $conn->prepare("INSERT INTO {$P}impressos (evento_id, config) VALUES (?, ?)");
    $st->bind_param('is', $eid, $json); return $st->execute();
}

/**
 * Dados do design entregues ao editor de tela (JS), para montar o
 * modelo inicial: nomes, textos, paleta e famílias tipográficas.
 */
function dadosTelaDoDesign(array $design): array {
    $fs = fontesDisponiveis();
    $tp = $design['tipografia'];
    $tok = mapaTextos($design);
    $familia = fn($k) => $fs[$k]['familia'] ?? '';
    // Extrai o 1.º nome de família (sem fallback) para o Fabric.
    $nomeFamilia = function ($css) {
        if (preg_match("/'([^']+)'/", $css, $m)) return $m[1];
        return trim(explode(',', $css)[0]);
    };
    // Descodifica entidades e converte <br> em quebras de linha (texto plano na tela).
    $texto = fn($s) => trim(html_entity_decode(str_ireplace(['<br>','<br/>','<br />'], "\n", $s), ENT_QUOTES, 'UTF-8'));
    return [
        'paleta' => $design['paleta'],
        'fontes' => [
            'serif'  => $nomeFamilia($familia($tp['serif'])),
            'sans'   => $nomeFamilia($familia($tp['sans'])),
            'script' => $nomeFamilia($familia($tp['script'])),
        ],
        'textos' => [
            'iniciais' => $texto($tok['INICIAIS']),
            'abertura' => $texto($tok['IMPRESSO_ABERTURA']),
            'noiva'    => $texto($tok['NOIVA']),
            'noivo'    => $texto($tok['NOIVO']),
            'data'     => $texto($tok['DATA_EXTENSA']),
            'hora'     => $texto($tok['HORA_EXTENSA']),
            'local'    => $texto($tok['VENUE_LOCAL']),
            'prazo'    => $texto($tok['RSVP_DEADLINE']),
        ],
    ];
}
