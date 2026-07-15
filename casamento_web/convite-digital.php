<?php
// ============================================================
// convite-digital.php — Serve o convite personalizado.
//   • Visualização: recursos externos (leve, cacheável).
//   • ?download=1: monta na hora uma versão autossuficiente
//     (imagens, áudio, tipos de letra e QR embutidos) para ver
//     completamente offline.
//   • ?preview=1 (admin): pré-visualiza um design (do editor) com
//     um convite de exemplo, sem depender de um código válido.
//   O aspeto (paleta, tipografia, secções e textos) vem do design
//   ativo — ver design.php e editor-modelos.php (Fase 1).
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/conta.php';
require_once __DIR__ . '/design.php';

$preview  = isset($_GET['preview']) && $_GET['preview'] === '1';
$download = isset($_GET['download']) && $_GET['download'] === '1';
$amostra  = false;   // convite de exemplo (pré-visualização ou partilha por evento)

// ---- Resolver o evento e o convite ---------------------------
if ($preview) {
    if (!ehAdmin() && contaLogada() === null) { http_response_code(403); exit('Pré-visualização reservada.'); }
    $amostra = true;
    $design = null;
    if (isset($_POST['design'])) {
        $d = json_decode($_POST['design'], true);
        if (is_array($d)) $design = normalizarDesign($d);
    }
    if (!$design) $design = carregarDesignAtivo($conn);
} elseif (isset($_GET['evento'])) {
    // Partilha pública do desenho de um evento (sem convite específico).
    $ev = eventoPorSlug($conn, (string)$_GET['evento']);
    if ($ev) $GLOBALS['EVENTO_ID'] = (int)$ev['id'];
    $amostra = true;
    $design = carregarDesignAtivo($conn);
} else {
    // Convite real: o evento vem do próprio convite.
    $codigo = strtoupper(trim($_GET['c'] ?? ''));
    $c = $codigo !== '' ? carregarConvite($conn, $codigo, 'codigo') : null;
    if ($c && isset($c['evento_id'])) $GLOBALS['EVENTO_ID'] = (int)$c['evento_id'];
    $design = carregarDesignAtivo($conn);
}

// Convite de exemplo (pré-visualização ou partilha por evento)
if ($amostra) {
    $c = [
        'codigo' => 'EXEMPLO', 'nome_exibicao' => 'Família Exemplo',
        'sufixo' => null, 'mostrar_numero' => 1, 'lugares' => 2, 'mesa_nome' => 'Mesa 1',
    ];
}

// ---- Convite inválido: página breve e autossuficiente --------
if (!$c) {
    $noiva = htmlspecialchars($design['evento']['noiva'], ENT_QUOTES, 'UTF-8');
    $noivo = htmlspecialchars($design['evento']['noivo'], ENT_QUOTES, 'UTF-8');
    $bg    = $design['paleta']['forest-deep'];
    $fg    = $design['paleta']['ivory'];
    $ac    = $design['paleta']['gold-soft'];
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . "<title>Convite · {$noiva} &amp; {$noivo}</title>"
       . "<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;"
       . "font-family:Georgia,serif;background:{$bg};color:{$fg};text-align:center;padding:2rem}a{color:{$ac}}</style>"
       . "</head><body><div><p style=\"font-size:1.6rem;color:{$ac}\">{$noiva} &amp; {$noivo}</p>"
       . '<p>Este convite não foi encontrado. Confirme o endereço, por favor, ou fale com os noivos.</p>'
       . '<p><a href="https://wa.me/' . htmlspecialchars(EVENTO['whatsapp']) . '">Falar pelo WhatsApp</a></p></div></body></html>';
    exit;
}

// ---- Carregar o modelo (leve) --------------------------------
$tplPath = __DIR__ . '/assets/convite-base.html';
$tpl = is_readable($tplPath) ? file_get_contents($tplPath) : false;
if ($tpl === false || $tpl === '') {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    $msg = 'O modelo do convite (assets/convite-base.html) não está disponível no servidor.';
    if (isset($_GET['diag']) && $_GET['diag'] === '1') {
        $dir  = __DIR__ . '/assets';
        $cvd  = __DIR__ . '/assets/convite';
        $l1   = is_dir($dir) ? implode(', ', array_diff(scandir($dir), ['.','..'])) : '(sem pasta assets)';
        $l2   = is_dir($cvd) ? implode(', ', array_diff(scandir($cvd), ['.','..'])) : '(sem pasta assets/convite)';
        $msg .= '<pre style="white-space:pre-wrap;font:13px monospace">Caminho: ' . htmlspecialchars($tplPath)
             .  "\nExiste: " . (file_exists($tplPath)?'sim':'não')
             .  "\n/assets: " . htmlspecialchars($l1)
             .  "\n/assets/convite: " . htmlspecialchars($l2) . '</pre>';
    } else {
        $msg .= ' Acrescente &diag=1 ao endereço para ver detalhes.';
    }
    echo '<div style="max-width:640px;margin:3rem auto;font-family:system-ui,sans-serif;line-height:1.6;padding:0 1rem">' . $msg . '</div>';
    exit;
}

// ---- Tokens do convidado (têm prioridade sobre o design) -----
$nome = htmlspecialchars(nomeConviteVisivel($c), ENT_QUOTES, 'UTF-8');

$mesaBlock = '';
if (!empty($c['mesa_nome'])) {
    $mesa = htmlspecialchars($c['mesa_nome'], ENT_QUOTES, 'UTF-8');
    $mesaBlock = "<p class=\"guest-mesa\" style=\"margin-top:12px;font-family:var(--ff-serif);"
        . "font-size:17px;letter-spacing:.02em;color:var(--gold)\">Mesa: "
        . "<b style=\"font-weight:600;color:var(--forest)\">{$mesa}</b></p>";
}

if ($amostra) {
    $confirmUrl  = '#';
    $downloadUrl = '#';
    $qrValue     = 'PRE-VISUALIZACAO';
} else {
    $confirmUrl  = htmlspecialchars(base_url() . '/convite.php?c=' . $c['codigo'], ENT_QUOTES, 'UTF-8');
    $downloadUrl = htmlspecialchars('convite-digital.php?c=' . $c['codigo'] . '&download=1', ENT_QUOTES, 'UTF-8');
    $qrValue     = base_url() . '/convite-digital.php?c=' . $c['codigo'];
}

$guestNote = mostraNumeroConvite($c)
    ? '<p class="guest-note">O número entre parênteses corresponde ao número de pessoas para as quais o convite é destinado.</p>'
    : '';

$extra = [
    'GUEST_NAME'   => $nome,
    'MESA_BLOCK'   => $mesaBlock,
    'GUEST_NOTE'   => $guestNote,
    'CONFIRM_URL'  => $confirmUrl,
    'DOWNLOAD_URL' => $downloadUrl,
    'QR_VALUE'     => htmlspecialchars($qrValue, ENT_QUOTES, 'UTF-8'),
];

// ---- Aplicar o design (paleta, tipografia, secções, textos) --
$out = aplicarDesign($tpl, $design, $extra);

// ---- Descarga: embutir tudo e transmitir (offline) -----------
if ($download) {
    $out = embutirRecursos($out, __DIR__);
    // Retira o botão flutuante de descarga do ficheiro guardado
    $out = preg_replace('#<a id="dlBtn".*?</a>\s*#s', '', $out, 1);
    $slug = slugAscii($design['evento']['noiva']) . '-' . slugAscii($design['evento']['noivo']);
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="Convite-' . $slug . '.html"');
    header('Content-Length: ' . strlen($out));
    echo $out;
    exit;
}

// ---- Visualização normal (recursos externos) -----------------
header('Content-Type: text/html; charset=utf-8');
echo $out;


// ============================================================
// Converte as referências a recursos externos em dados embutidos
// (base64), para o ficheiro poder ser visto completamente offline.
// (As fontes web de modelos personalizados não são embutidas — o
//  modelo padrão usa fontes locais e fica totalmente offline.)
// ============================================================
function embutirRecursos(string $html, string $base): string {
    $mime = ['mp3'=>'audio/mpeg','m4a'=>'audio/mp4','mp4'=>'audio/mp4','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','woff2'=>'font/woff2'];

    $paraDataUri = function (string $rel) use ($base, $mime): ?string {
        $rel = ltrim($rel, '/');
        $abs = $base . '/' . $rel;
        if (!is_readable($abs)) return null;
        $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $tp  = $mime[$ext] ?? 'application/octet-stream';
        return 'data:' . $tp . ';base64,' . base64_encode(file_get_contents($abs));
    };

    // 1) Imagens e áudio:  src="assets/convite/...."  ou  src="uploads/eventos/..."
    $html = preg_replace_callback(
        '#src="((?:assets/convite/|uploads/)[^"]+\.(?:jpg|jpeg|png|webp|mp3|m4a|mp4))"#i',
        function ($m) use ($paraDataUri) {
            $d = $paraDataUri($m[1]);
            return $d ? 'src="' . $d . '"' : $m[0];
        }, $html);

    // 2) Tipos de letra:  url(assets/convite/fonts/....woff2)
    $html = preg_replace_callback(
        '#url\((assets/convite/fonts/[^)]+\.woff2)\)#i',
        function ($m) use ($paraDataUri) {
            $d = $paraDataUri($m[1]);
            return $d ? 'url(' . $d . ')' : $m[0];
        }, $html);

    // 3) QRious:  <script src="assets/qrious.min.js"></script> -> inline
    $qr = $base . '/assets/qrious.min.js';
    if (is_readable($qr)) {
        $js = file_get_contents($qr);
        $html = preg_replace(
            '#<script src="assets/qrious\.min\.js"></script>#',
            '<script>' . $js . '</script>',
            $html, 1);
    }
    return $html;
}
