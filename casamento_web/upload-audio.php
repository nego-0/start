<?php
// ============================================================
// upload-audio.php — Música de fundo do convite, por evento.
//   Recebe um ficheiro de áudio (mp3/m4a/aac/ogg/wav), valida,
//   guarda em uploads/eventos/{evento}/ e devolve JSON { ok, url }.
//   Também remove a música atual (acao=remover).
//   Só admin ou o casal do evento ativo. O URL é guardado no
//   design pelo editor (design.audio).
// ============================================================
require_once __DIR__ . '/conta.php';
require_once __DIR__ . '/design.php';
exigirEdicao($conn);
exigirCsrf(true); // [S1]
$eid = eventoId();

header('Content-Type: application/json; charset=utf-8');

$relDir = 'uploads/eventos/' . $eid;
$absDir = __DIR__ . '/' . $relDir;

// ---- Remover a música atual --------------------------------
if (($_POST['acao'] ?? '') === 'remover') {
    foreach (glob($absDir . '/musica-*') ?: [] as $velho) @unlink($velho);
    echo json_encode(['ok' => true, 'url' => '']); exit;
}

// ---- Receber e validar o ficheiro --------------------------
if (empty($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'erro' => 'Nenhum ficheiro recebido.']); exit;
}
$f = $_FILES['audio'];
if ($f['size'] > 12 * 1024 * 1024) { echo json_encode(['ok' => false, 'erro' => 'Áudio demasiado grande (máx. 12 MB).']); exit; }

// Extensão pela do nome original, confirmada por uma lista segura.
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
$aceites = ['mp3', 'm4a', 'aac', 'ogg', 'wav'];
if (!in_array($ext, $aceites, true)) {
    echo json_encode(['ok' => false, 'erro' => 'Formato não suportado (use MP3, M4A, AAC, OGG ou WAV).']); exit;
}
// Confirmação por conteúdo (quando o finfo está disponível).
if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($fi, $f['tmp_name']);
    finfo_close($fi);
    if ($mime && stripos($mime, 'audio') === false && stripos($mime, 'ogg') === false
        && stripos($mime, 'octet-stream') === false && stripos($mime, 'video/mp4') === false) {
        echo json_encode(['ok' => false, 'erro' => 'O ficheiro não parece ser áudio.']); exit;
    }
}

// Pasta do evento
if (!is_dir($absDir) && !@mkdir($absDir, 0775, true)) {
    echo json_encode(['ok' => false, 'erro' => 'Não foi possível criar a pasta do evento.']); exit;
}
// Impede execução de scripts na pasta de uploads (Apache).
$ht = __DIR__ . '/uploads/.htaccess';
if (!file_exists($ht)) @file_put_contents($ht, "<FilesMatch \"\\.(php|phtml|phar|cgi)$\">\n  Require all denied\n</FilesMatch>\nOptions -ExecCGI\n");

// Substitui qualquer música anterior.
foreach (glob($absDir . '/musica-*') ?: [] as $velho) @unlink($velho);

$nome   = 'musica-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
$relOut = $relDir . '/' . $nome;
$absOut = $absDir . '/' . $nome;

if (!@move_uploaded_file($f['tmp_name'], $absOut)) {
    echo json_encode(['ok' => false, 'erro' => 'Falha ao guardar o ficheiro.']); exit;
}

echo json_encode(['ok' => true, 'url' => $relOut], JSON_UNESCAPED_SLASHES);
