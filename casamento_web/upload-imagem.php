<?php
// ============================================================
// upload-imagem.php — Upload de fotos do convite, por evento (Fase 3).
//   Recebe uma imagem para um "slot" (hero/historia/interludio/acesso),
//   valida, redimensiona (GD) e guarda em uploads/eventos/{evento}/.
//   Devolve JSON { ok, url }. Só admin ou o casal do evento ativo.
//   O URL devolvido é guardado no design pelo editor (design.imagens).
// ============================================================
require_once __DIR__ . '/conta.php';
require_once __DIR__ . '/design.php';
exigirEdicao($conn);
exigirCsrf(true); // [S1]
$eid = eventoId();

header('Content-Type: application/json; charset=utf-8');

$slots = array_keys(imagensPadrao());
$slot  = $_POST['slot'] ?? '';
if (!in_array($slot, $slots, true)) { echo json_encode(['ok' => false, 'erro' => 'Slot inválido.']); exit; }

if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'erro' => 'Nenhum ficheiro recebido.']); exit;
}
$f = $_FILES['imagem'];
if ($f['size'] > 8 * 1024 * 1024) { echo json_encode(['ok' => false, 'erro' => 'Imagem demasiado grande (máx. 8 MB).']); exit; }

$info = @getimagesize($f['tmp_name']);
if ($info === false) { echo json_encode(['ok' => false, 'erro' => 'O ficheiro não é uma imagem válida.']); exit; }
$mime = $info['mime'];
$aceites = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($aceites[$mime])) { echo json_encode(['ok' => false, 'erro' => 'Formato não suportado (use JPG, PNG ou WEBP).']); exit; }

// Pasta do evento
$relDir = 'uploads/eventos/' . $eid;
$absDir = __DIR__ . '/' . $relDir;
if (!is_dir($absDir) && !@mkdir($absDir, 0775, true)) {
    echo json_encode(['ok' => false, 'erro' => 'Não foi possível criar a pasta de imagens.']); exit;
}
// Impede execução de scripts na pasta de uploads (Apache).
$ht = __DIR__ . '/uploads/.htaccess';
if (!file_exists($ht)) @file_put_contents($ht, "<FilesMatch \"\\.(php|phtml|phar|cgi)$\">\n  Require all denied\n</FilesMatch>\nOptions -ExecCGI\n");

// Nome final e extensão de saída (png mantém-se png; resto -> jpg)
$saidaPng = ($mime === 'image/png');
$ext = $saidaPng ? 'png' : 'jpg';
$nome = $slot . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
$absOut = $absDir . '/' . $nome;
$relOut = $relDir . '/' . $nome;

if (!redimensionarImagem($f['tmp_name'], $absOut, 1600, $mime, $saidaPng)) {
    echo json_encode(['ok' => false, 'erro' => 'Falha ao processar a imagem.']); exit;
}

echo json_encode(['ok' => true, 'slot' => $slot, 'url' => $relOut], JSON_UNESCAPED_SLASHES);

// ---- Redimensiona com GD (lado maior <= $max) --------------
function redimensionarImagem(string $src, string $dst, int $max, string $mime, bool $png): bool {
    $img = null;
    if ($mime === 'image/jpeg') $img = @imagecreatefromjpeg($src);
    elseif ($mime === 'image/png') $img = @imagecreatefrompng($src);
    elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $img = @imagecreatefromwebp($src);
    if (!$img) return false;

    $w = imagesx($img); $h = imagesy($img);
    $scale = min(1.0, $max / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));

    $out = imagecreatetruecolor($nw, $nh);
    if ($png) {
        imagealphablending($out, false);
        imagesavealpha($out, true);
    } else {
        // fundo branco para JPEG (evita preto em imagens com transparência)
        $branco = imagecolorallocate($out, 255, 255, 255);
        imagefilledrectangle($out, 0, 0, $nw, $nh, $branco);
    }
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = $png ? imagepng($out, $dst, 6) : imagejpeg($out, $dst, 84);
    imagedestroy($img); imagedestroy($out);
    return $ok;
}
