<?php
// ============================================================
// design.php — Camada de personalização do convite (Fase 1)
//   • Esquema cw_designs (JSON do design ativo).
//   • Design padrão (idêntico ao convite atual).
//   • Construtor de tema (CSS de paleta + tipografia + secções)
//     e mapa de textos — funções PURAS, testáveis sem base de dados.
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/modelos.php';

// ---- Meses e dias em português (sem depender de intl) --------
function mesesExtenso(): array {
    return [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
            7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
}
function mesesCurto(): array {
    return [1=>'Jan',2=>'Fev',3=>'Mar',4=>'Abr',5=>'Mai',6=>'Jun',
            7=>'Jul',8=>'Ago',9=>'Set',10=>'Out',11=>'Nov',12=>'Dez'];
}
function diasSemana(): array {
    // Índice por date('w'): 0=Domingo … 6=Sábado
    return ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
}

// ============================================================
// Predefinições (design padrão idêntico ao convite atual)
// ============================================================

/** Textos padrão — exatamente os do convite Isabel & Abednego. */
function textosPadrao(): array {
    return [
        'capa_dica'     => 'Toque para abrir',
        'hero_kicker'   => 'Vamos nos casar',
        'hero_sub'      => 'O nosso casamento',
        'conv_eyebrow'  => 'Venha partilhar a nossa alegria',
        'conv_lead'     => 'Há amores que, como o amanhecer, chegam devagar — e o nosso chegou para iluminar toda uma vida. É com o coração cheio de júbilo que %NOIVOS% têm a honra de convidar V.&nbsp;Exa. a partilhar a celebração do seu enlace matrimonial, e a alegria de um dia que ficará para sempre guardado na memória.',
        'conv_closing'  => 'A vossa presença será o mais belo dos presentes — a luz e a música que tornarão eterno o mais feliz dos nossos dias.',
        'hist_eyebrow'  => 'A nossa história',
        'hist_titulo'   => 'Dois olhares, um caminho',
        'hist_citacao'  => 'Amamos aquilo que nos completa.',
        'hist_autor'    => 'Goethe',
        'inter_verso'   => '&ldquo;Que não seja imortal, posto que é chama,<br>mas que seja infinito enquanto dure.&rdquo;',
        'inter_autor'   => 'Vinicius de Moraes',
        'inter_fecho'   => 'Duas vidas, um só caminho —<br>e todo o tempo do mundo pela frente.',
        'venue_titulo'  => 'Copo d&rsquo;água',
        'venue_local'   => 'Estufa Municipal de Moçâmedes<br>Namibe · Angola',
        'venue_mapa'    => 'https://maps.app.goo.gl/9o8MAHokTFRpgDBG9',
        'crono_titulo'  => 'Cronograma do dia',
        'rsvp_titulo'   => 'Contamos com a<br>sua presença',
        'rsvp_sub'      => 'Cada história de amor é bela — mas a nossa terá um capítulo escrito também por si.',
        'rsvp_deadline' => 'Confirme a sua presença até 5 de Dezembro',
        'footer_nota'   => '&ldquo;Amor é fogo que arde sem se ver.&rdquo; — Luís de Camões',
        'impresso_abertura' => 'Com alegria, convidam',
    ];
}

/** Imagens padrão do convite (as do casamento Isabel & Abednego). */
function imagensPadrao(): array {
    return [
        'hero'       => 'assets/convite/hero.jpg',
        'historia'   => 'assets/convite/historia.jpg',
        'interludio' => 'assets/convite/interludio.jpg',
        'acesso'     => 'assets/convite/acesso.jpg',
    ];
}
function rotulosImagens(): array {
    return [
        'hero'       => 'Capa (foto principal)',
        'historia'   => 'A nossa história',
        'interludio' => 'Interlúdio (fundo do verso)',
        'acesso'     => 'Passe de entrada',
    ];
}

/** Secções que podem ser mostradas/ocultadas, e o seu rótulo. */
function seccoesPadrao(): array {
    return [
        'historia'   => true,   // A nossa história
        'interludio' => true,   // Interlúdio (verso)
        'countdown'  => true,   // Contagem decrescente
        'cronograma' => true,   // Cronograma do dia
        'manual'     => true,   // Manual do convidado
        'petalas'    => true,   // Pétalas a cair (animação)
        'musica'     => true,   // Botão de música ambiente
    ];
}
function rotulosSeccoes(): array {
    return [
        'historia'   => 'A nossa história',
        'interludio' => 'Interlúdio (verso)',
        'countdown'  => 'Contagem decrescente',
        'cronograma' => 'Cronograma do dia',
        'manual'     => 'Manual do convidado',
        'petalas'    => 'Pétalas a cair',
        'musica'     => 'Música ambiente',
    ];
}

/** Design padrão completo (evento + modelo esmeralda + textos atuais). */
function designPadrao(): array {
    return substituirNomesNosTextos(designPadraoBruto());
}

/** Design padrão "em bruto" — os textos ainda com o marcador %NOIVOS%. */
function designPadraoBruto(): array {
    $noiva = EVENTO['noiva'] ?? 'Isabel';
    $noivo = EVENTO['noivo'] ?? 'Abednego';
    $m = modelo('esmeralda');
    return [
        'modelo' => 'esmeralda',
        'evento' => [
            'noiva'       => $noiva,
            'noivo'       => $noivo,
            'iniciais'    => iniciaisDe($noiva, $noivo),
            'data_iso'    => EVENTO['data_iso'] ?? '2026-12-19',
            'hora'        => '20:30',
            'tz'          => '+01:00',
            'local_curto' => 'Moçâmedes',
        ],
        'paleta'     => $m['paleta'],
        'tipografia' => $m['tipografia'],
        'seccoes'    => seccoesPadrao(),
        'imagens'    => imagensPadrao(),
        'textos'     => textosPadrao(),
    ];
}

/** Caminho de imagem seguro (só assets/convite/ ou uploads/), ou null. */
function validarImagem($v): ?string {
    if (!is_string($v)) return null;
    $v = trim($v);
    if (preg_match('#^(assets/convite/|uploads/)[A-Za-z0-9_./-]+\.(jpe?g|png|webp)$#i', $v)
        && strpos($v, '..') === false) {
        return $v;
    }
    return null;
}

/** Substitui o marcador %NOIVOS% (nos textos) pelos nomes do casal. */
function substituirNomesNosTextos(array $d): array {
    $par = trim(($d['evento']['noiva'] ?? '') . ' e ' . ($d['evento']['noivo'] ?? ''), ' e');
    foreach ($d['textos'] as $k => $v) {
        if (is_string($v)) $d['textos'][$k] = str_replace('%NOIVOS%', $par, $v);
    }
    return $d;
}

/** Iniciais no formato "I&A" a partir dos nomes. */
function iniciaisDe(string $noiva, string $noivo): string {
    $a = mb_strtoupper(mb_substr(trim($noiva), 0, 1) ?: 'I');
    $b = mb_strtoupper(mb_substr(trim($noivo), 0, 1) ?: 'A');
    return "$a&$b";
}

// ============================================================
// Normalização / validação (funde com o padrão e sanea)
// ============================================================

/** Devolve #RRGGBB válido em maiúsculas, ou null. */
function validarHex($v): ?string {
    $v = is_string($v) ? trim($v) : '';
    if (preg_match('/^#?([0-9a-fA-F]{6})$/', $v, $m)) return '#' . strtoupper($m[1]);
    return null;
}

/** Funde o design recebido com o padrão, validando cada campo. */
function normalizarDesign($data): array {
    $base = designPadrao();
    if (!is_array($data)) return $base;

    // Modelo
    $ms = modelosDisponiveis();
    $out = $base;
    if (!empty($data['modelo']) && isset($ms[$data['modelo']])) $out['modelo'] = $data['modelo'];

    // Evento
    $ev = is_array($data['evento'] ?? null) ? $data['evento'] : [];
    foreach (['noiva','noivo','local_curto'] as $k) {
        if (isset($ev[$k]) && is_string($ev[$k]) && trim($ev[$k]) !== '') $out['evento'][$k] = trim($ev[$k]);
    }
    if (isset($ev['iniciais']) && is_string($ev['iniciais']) && trim($ev['iniciais']) !== '') {
        $out['evento']['iniciais'] = trim($ev['iniciais']);
    } else {
        $out['evento']['iniciais'] = iniciaisDe($out['evento']['noiva'], $out['evento']['noivo']);
    }
    if (isset($ev['data_iso']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$ev['data_iso'])) {
        $out['evento']['data_iso'] = $ev['data_iso'];
    }
    if (isset($ev['hora']) && preg_match('/^\d{1,2}:\d{2}$/', (string)$ev['hora'])) {
        $out['evento']['hora'] = $ev['hora'];
    }
    if (isset($ev['tz']) && preg_match('/^[+-]\d{2}:\d{2}$/', (string)$ev['tz'])) {
        $out['evento']['tz'] = $ev['tz'];
    }

    // Paleta (11 cores)
    $pal = is_array($data['paleta'] ?? null) ? $data['paleta'] : [];
    foreach (chavesPaleta() as $k) {
        $hex = validarHex($pal[$k] ?? null);
        if ($hex !== null) $out['paleta'][$k] = $hex;
    }

    // Tipografia (valida o papel)
    $fs = fontesDisponiveis();
    $tp = is_array($data['tipografia'] ?? null) ? $data['tipografia'] : [];
    foreach (['serif','sans','script'] as $papel) {
        $k = $tp[$papel] ?? null;
        if (is_string($k) && isset($fs[$k]) && $fs[$k]['papel'] === $papel) $out['tipografia'][$papel] = $k;
    }

    // Secções (booleanos)
    $sc = is_array($data['seccoes'] ?? null) ? $data['seccoes'] : [];
    foreach (seccoesPadrao() as $k => $def) {
        if (array_key_exists($k, $sc)) $out['seccoes'][$k] = (bool)$sc[$k];
    }

    // Imagens (só caminhos seguros; mantém o padrão quando inválido)
    $im = is_array($data['imagens'] ?? null) ? $data['imagens'] : [];
    foreach (imagensPadrao() as $k => $def) {
        $v = validarImagem($im[$k] ?? null);
        if ($v !== null) $out['imagens'][$k] = $v;
    }

    // Textos (mantém o padrão quando ausente)
    $tx = is_array($data['textos'] ?? null) ? $data['textos'] : [];
    foreach (textosPadrao() as $k => $def) {
        if (isset($tx[$k]) && is_string($tx[$k])) $out['textos'][$k] = $tx[$k];
    }

    // Segurança: substitui qualquer %NOIVOS% remanescente pelos nomes do evento.
    return substituirNomesNosTextos($out);
}

// ============================================================
// Esquema e persistência (única linha de design ativo — Fase 1)
// ============================================================

function garantirEsquemaDesign(mysqli $conn): void {
    global $P;
    $conn->query("
        CREATE TABLE IF NOT EXISTS {$P}designs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evento_id INT NOT NULL DEFAULT 1,
            nome VARCHAR(120) NOT NULL DEFAULT 'Convite',
            ativo TINYINT(1) DEFAULT 0,
            config LONGTEXT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Migração suave para instalações anteriores (sem evento_id).
    $c = $conn->query("SHOW COLUMNS FROM {$P}designs LIKE 'evento_id'");
    if ($c && $c->num_rows === 0) {
        $conn->query("ALTER TABLE {$P}designs ADD COLUMN evento_id INT NOT NULL DEFAULT 1 AFTER id");
        $conn->query("ALTER TABLE {$P}designs ADD INDEX (evento_id)");
    }
}

/** Carrega o design do evento (ou o padrão, semeado com os dados do evento). */
function carregarDesignAtivo(mysqli $conn, ?int $eventoId = null): array {
    global $P;
    garantirEsquemaDesign($conn);
    $eid = $eventoId ?? eventoId();
    $st = $conn->prepare("SELECT config FROM {$P}designs WHERE evento_id=? ORDER BY id LIMIT 1");
    $st->bind_param('i', $eid); $st->execute();
    if ($row = $st->get_result()->fetch_assoc()) {
        $cfg = json_decode($row['config'], true);
        if (is_array($cfg)) return normalizarDesign($cfg);
    }
    return designPadraoDoEvento($conn, $eid);
}

/** Design padrão com os nomes/data do evento (quando ainda não há design guardado). */
function designPadraoDoEvento(mysqli $conn, int $eid): array {
    global $P;
    $d = designPadraoBruto();
    $r = $conn->query("SELECT noiva, noivo, data_iso FROM {$P}eventos WHERE id=" . (int)$eid . " LIMIT 1");
    if ($r && ($e = $r->fetch_assoc())) {
        $d['evento']['noiva']    = $e['noiva'];
        $d['evento']['noivo']    = $e['noivo'];
        $d['evento']['iniciais'] = iniciaisDe($e['noiva'], $e['noivo']);
        if (!empty($e['data_iso'])) $d['evento']['data_iso'] = $e['data_iso'];
    }
    return substituirNomesNosTextos($d);
}

/** Guarda o design do evento (uma linha por evento). */
function guardarDesign(mysqli $conn, array $config, string $nome = 'Convite', ?int $eventoId = null): array {
    global $P;
    garantirEsquemaDesign($conn);
    $eid = $eventoId ?? eventoId();
    $norm = normalizarDesign($config);
    $json = json_encode($norm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $st = $conn->prepare("SELECT id FROM {$P}designs WHERE evento_id=? ORDER BY id LIMIT 1");
    $st->bind_param('i', $eid); $st->execute();
    if ($row = $st->get_result()->fetch_assoc()) {
        $st = $conn->prepare("UPDATE {$P}designs SET nome=?, config=?, ativo=1 WHERE id=?");
        $st->bind_param('ssi', $nome, $json, $row['id']); $st->execute();
    } else {
        $st = $conn->prepare("INSERT INTO {$P}designs (evento_id, nome, ativo, config) VALUES (?,?,1,?)");
        $st->bind_param('iss', $eid, $nome, $json); $st->execute();
    }
    return $norm;
}

// ============================================================
// Tokens derivados (nomes, datas, calendário, QR) — PUROS
// ============================================================

/** Slug ASCII simples (sem intl) para identificadores/ficheiros. */
function slugAscii(string $s): string {
    $de = ['á','à','â','ã','ä','é','ê','è','í','ï','ó','ô','õ','ö','ú','ü','ç','Á','À','Â','Ã','Ä','É','Ê','È','Í','Ï','Ó','Ô','Õ','Ö','Ú','Ü','Ç'];
    $pa = ['a','a','a','a','a','e','e','e','i','i','o','o','o','o','u','u','c','A','A','A','A','A','E','E','E','I','I','O','O','O','O','U','U','C'];
    $s = str_replace($de, $pa, $s);
    $s = preg_replace('/[^A-Za-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/** Escapa uma string para usar dentro de aspas simples em JavaScript. */
function jsEscape(string $s): string {
    return str_replace(["\\", "'", "\r", "\n"], ["\\\\", "\\'", '', ''], $s);
}

/** Calcula os tokens derivados do evento (datas, calendário, QR, nomes). */
function tokensDerivados(array $design): array {
    $ev  = $design['evento'];
    $pal = $design['paleta'];
    $noiva = $ev['noiva']; $noivo = $ev['noivo'];

    // Data/hora
    [$ano, $mes, $dia] = array_map('intval', explode('-', $ev['data_iso']));
    [$h, $mi] = array_pad(array_map('intval', explode(':', $ev['hora'])), 2, 0);
    $mesExt = mesesExtenso()[$mes] ?? '';
    $mesCur = mesesCurto()[$mes] ?? '';
    $tz = $ev['tz'];

    // Dia da semana
    $diaSemana = '';
    try {
        $dt = new DateTime(sprintf('%04d-%02d-%02dT%02d:%02d:00%s', $ano, $mes, $dia, $h, $mi, $tz));
        $diaSemana = diasSemana()[(int)$dt->format('w')];
    } catch (\Throwable $e) { $dt = null; }

    $horaTxt = sprintf('Ás %dh%02d', $h, $mi);
    $isoLocal = sprintf('%04d-%02d-%02dT%02d:%02d:00%s', $ano, $mes, $dia, $h, $mi, $tz);

    // Data/hora por extenso (convite impresso)
    $dataExtensa = trim(sprintf('%s, %d de %s de %d', $diaSemana, $dia, mb_strtolower($mesExt, 'UTF-8'), $ano), ', ');
    $horaExtensa = sprintf('às %dh%02d', $h, $mi);

    // Calendário (.ics) em UTC
    $dtStart = $dtEnd = '';
    if ($dt) {
        $utc = clone $dt; $utc->setTimezone(new DateTimeZone('UTC'));
        $dtStart = $utc->format('Ymd\THis\Z');
        $utc->modify('+5 hours 30 minutes');
        $dtEnd = $utc->format('Ymd\THis\Z');
    }
    $locTexto = html_entity_decode(strip_tags(str_ireplace('<br>', ', ', $design['textos']['venue_local'])), ENT_QUOTES, 'UTF-8');
    $venueTit = html_entity_decode(strip_tags($design['textos']['venue_titulo']), ENT_QUOTES, 'UTF-8');
    $slug = slugAscii($noiva) . '-' . slugAscii($noivo);

    // Título e legendas derivadas
    $titulo   = "$noiva & $noivo — $dia de $mesExt de $ano";
    $cronoSub = trim("$diaSemana, $dia de $mesExt", ', ');
    $footerData = sprintf('%02d · %02d · %04d &nbsp;—&nbsp; %s', $dia, $mes, $ano, $ev['local_curto']);

    $esc = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

    return [
        // Nomes (contexto HTML/atributo)
        'NOIVA'    => $esc($noiva),
        'NOIVO'    => $esc($noivo),
        'INICIAIS' => $esc($ev['iniciais']),
        // Datas (texto)
        'MES_CURTO'  => $esc($mesCur),
        'MES_EXT'    => $esc($mesExt),
        'DIA'        => (string)$dia,
        'ANO'        => (string)$ano,
        'DIA_SEMANA' => $esc($diaSemana),
        'HORA_TXT'   => $esc($horaTxt),
        'DATA_EXTENSA' => $esc($dataExtensa),
        'HORA_EXTENSA' => $esc($horaExtensa),
        // Derivados de texto
        'TITULO_PAGINA' => $esc($titulo),
        'CRONO_SUB'     => $esc($cronoSub),
        'FOOTER_DATA'   => $footerData, // já contém &nbsp; propositado
        // JavaScript (contagem + calendário)
        'DATA_HORA_ISO' => jsEscape($isoLocal),
        'ICS_UID'       => jsEscape(strtolower($slug) . '-' . $ano . '@convite'),
        'ICS_DTSTART'   => $dtStart,
        'ICS_DTEND'     => $dtEnd,
        'ICS_SUMMARY'   => jsEscape("Casamento de $noiva & $noivo"),
        'ICS_LOCATION'  => jsEscape($locTexto),
        'ICS_DESC'      => jsEscape("$venueTit — $horaTxt. Confirme a sua presenca."),
        'ICS_FICHEIRO'  => jsEscape("Casamento-$slug.ics"),
        // QR (contexto JS "..."): usa a paleta
        'QR_FG' => $pal['forest-deep'],
        'QR_BG' => $pal['ivory'],
    ];
}

// ============================================================
// Construtor de tema (CSS injetado) e mapa de textos — PUROS
// ============================================================

/**
 * CSS do tema: importação de fontes web (se necessário),
 * substituição da paleta e da tipografia, e visibilidade das secções.
 */
/** Importação Google Fonts para as fontes web escolhidas (ou '' se todas locais). */
function cssImportacaoFontes(array $design): string {
    $fs = fontesDisponiveis();
    $params = [];
    foreach (['serif','sans','script'] as $papel) {
        $k = $design['tipografia'][$papel] ?? null;
        if ($k && isset($fs[$k]) && !empty($fs[$k]['google'])) $params[$fs[$k]['google']] = true;
    }
    if (!$params) return '';
    $q = implode('', array_map(fn($p) => '&family=' . $p, array_keys($params)));
    return "@import url('https://fonts.googleapis.com/css2?" . ltrim($q, '&') . "&display=swap');\n";
}

/** :root com a paleta e as três famílias tipográficas do design. */
function cssVariaveis(array $design): string {
    $fs = fontesDisponiveis();
    $pal = $design['paleta'];
    $tp  = $design['tipografia'];
    $css = ':root{';
    foreach (chavesPaleta() as $k) $css .= "--$k:{$pal[$k]};";
    $css .= '--ff-serif:'  . ($fs[$tp['serif']]['familia']  ?? "'Cormorant Garamond',serif") . ';';
    $css .= '--ff-sans:'   . ($fs[$tp['sans']]['familia']   ?? "'Jost',sans-serif") . ';';
    $css .= '--ff-script:' . ($fs[$tp['script']]['familia'] ?? "'Pinyon Script',cursive") . ';';
    return $css . '}';
}

/** Declarações @font-face das fontes locais (para páginas fora do convite-base). */
function cssFontesLocais(): string {
    $b = 'assets/convite/fonts';
    $f = fn($fam, $st, $w, $file) => "@font-face{font-family:'$fam';font-style:$st;font-weight:$w;font-display:swap;src:url($b/$file) format('woff2')}";
    return implode('', [
        $f('Pinyon Script','normal',400,'pinyon-script-latin-400-normal.woff2'),
        $f('Cormorant Garamond','normal',400,'cormorant-garamond-latin-400-normal.woff2'),
        $f('Cormorant Garamond','normal',500,'cormorant-garamond-latin-500-normal.woff2'),
        $f('Cormorant Garamond','normal',600,'cormorant-garamond-latin-600-normal.woff2'),
        $f('Cormorant Garamond','italic',400,'cormorant-garamond-latin-400-italic.woff2'),
        $f('Cormorant Garamond','italic',500,'cormorant-garamond-latin-500-italic.woff2'),
        $f('Jost','normal',300,'jost-latin-300-normal.woff2'),
        $f('Jost','normal',400,'jost-latin-400-normal.woff2'),
        $f('Jost','normal',500,'jost-latin-500-normal.woff2'),
    ]);
}

function construirCssTema(array $design): string {
    $css = cssImportacaoFontes($design);

    // Paleta + tipografia (sobrepõem o :root base)
    $css .= cssVariaveis($design);

    // Visibilidade das secções
    $seletor = [
        'historia'   => '#historia',
        'interludio' => '#interludio',
        'countdown'  => '#countdown',
        'cronograma' => '.timeline-wrap',
        'manual'     => '#manual',
        'petalas'    => '#petals',
        'musica'     => '#audioBtn',
    ];
    foreach (seccoesPadrao() as $k => $def) {
        if (empty($design['seccoes'][$k])) $css .= "{$seletor[$k]}{display:none!important}";
    }

    return "<style id=\"tema-convite\">\n$css\n</style>";
}

/** Mapa completo de tokens de texto do design (textos + derivados). */
function mapaTextos(array $design): array {
    $t = $design['textos'];
    $esc = fn($s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $tokens = [
        // Textos ricos do casal (permitem <br> e entidades — conteúdo de administração)
        'CAPA_DICA'     => $t['capa_dica'],
        'HERO_KICKER'   => $t['hero_kicker'],
        'HERO_SUB'      => $t['hero_sub'],
        'CONV_EYEBROW'  => $t['conv_eyebrow'],
        'CONV_LEAD'     => $t['conv_lead'],
        'CONV_CLOSING'  => $t['conv_closing'],
        'HIST_EYEBROW'  => $t['hist_eyebrow'],
        'HIST_TITULO'   => $t['hist_titulo'],
        'HIST_CITACAO'  => $t['hist_citacao'],
        'HIST_AUTOR'    => $t['hist_autor'],
        'INTER_VERSO'   => $t['inter_verso'],
        'INTER_AUTOR'   => $t['inter_autor'],
        'INTER_FECHO'   => $t['inter_fecho'],
        'VENUE_TITULO'  => $t['venue_titulo'],
        'VENUE_LOCAL'   => $t['venue_local'],
        'VENUE_MAPA'    => $esc($t['venue_mapa']), // contexto de atributo href
        'CRONO_TITULO'  => $t['crono_titulo'],
        'RSVP_TITULO'   => $t['rsvp_titulo'],
        'RSVP_SUB'      => $t['rsvp_sub'],
        'RSVP_DEADLINE' => $t['rsvp_deadline'],
        'FOOTER_NOTA'   => $t['footer_nota'],
        'IMPRESSO_ABERTURA' => $t['impresso_abertura'] ?? 'Com alegria, convidam',
    ];
    // Imagens (contexto de atributo src)
    $im = $design['imagens'] ?? imagensPadrao();
    foreach (imagensPadrao() as $k => $def) {
        $tokens['IMG_' . strtoupper($k)] = $esc(validarImagem($im[$k] ?? null) ?? $def);
    }
    return array_merge($tokens, tokensDerivados($design));
}

/**
 * Aplica o design a um template do convite:
 *   • funde tokens de texto do design com os extra (nome do convidado, URLs…);
 *   • injeta o CSS do tema antes de </head>.
 * $extra tem prioridade sobre os tokens do design.
 */
function aplicarDesign(string $tpl, array $design, array $extra = []): string {
    $tokens = array_merge(mapaTextos($design), $extra);
    $subs = [];
    foreach ($tokens as $k => $v) $subs['{{' . $k . '}}'] = (string)$v;
    $html = strtr($tpl, $subs);

    $tema = construirCssTema($design);
    $pos = stripos($html, '</head>');
    if ($pos !== false) {
        $html = substr($html, 0, $pos) . $tema . "\n" . substr($html, $pos);
    } else {
        $html = $tema . $html;
    }
    return $html;
}
