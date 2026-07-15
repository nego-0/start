<?php
// ============================================================
// verificar.php — Verificação de requisitos e configuração
//   Uso:  php verificar.php            (linha de comandos)
//    ou:  aceda a verificar.php como super-administrador (web).
//   Não imprime segredos — só o estado (OK / AVISO / FALHA).
// ============================================================
require_once __DIR__ . '/config.php';

$cli = (PHP_SAPI === 'cli');
if (!$cli) {
    require_once __DIR__ . '/auth.php';
    if (!ehAdmin()) { http_response_code(403); header('Content-Type: text/plain; charset=utf-8');
        exit("Disponível apenas por linha de comandos (php verificar.php) ou autenticado como administrador.\n"); }
    header('Content-Type: text/plain; charset=utf-8');
}

$linhas = []; $falhas = 0; $avisos = 0;
function chk(string $estado, string $rot, string $obs = ''): void {
    global $linhas, $falhas, $avisos;
    if ($estado === 'FALHA') $falhas++; elseif ($estado === 'AVISO') $avisos++;
    $marca = ['OK' => '✓', 'AVISO' => '!', 'FALHA' => '✗'][$estado] ?? '·';
    $linhas[] = sprintf('[%s] %-7s %s%s', $marca, $estado, $rot, $obs !== '' ? ' — ' . $obs : '');
}

// PHP
chk(version_compare(PHP_VERSION, '8.0', '>=') ? 'OK' : 'AVISO', 'PHP ' . PHP_VERSION, version_compare(PHP_VERSION, '8.0', '>=') ? '' : 'recomendado 8.0+');

// Extensões
foreach (['mysqli' => 'FALHA', 'gd' => 'AVISO', 'fileinfo' => 'AVISO', 'mbstring' => 'AVISO'] as $ext => $sev) {
    $ok = extension_loaded($ext);
    chk($ok ? 'OK' : $sev, "Extensão $ext", $ok ? '' : ($ext === 'gd' ? 'sem redimensionamento de imagens' : ($ext === 'fileinfo' ? 'validação de áudio por conteúdo limitada' : 'necessária')));
}
chk(function_exists('random_bytes') ? 'OK' : 'FALHA', 'random_bytes (CSRF/tokens)');

// Pasta de uploads gravável
$up = __DIR__ . '/uploads';
if (!is_dir($up)) @mkdir($up, 0775, true);
chk(is_writable($up) ? 'OK' : 'FALHA', 'uploads/ gravável', is_writable($up) ? '' : 'defina permissões de escrita');

// Senhas configuradas
$adminDef = SENHA_ADMIN === 'ALTERE_ESTA_SENHA_ADMIN' || SENHA_ADMIN === '';
$portDef  = SENHA_PORTEIRO === 'ALTERE_ESTA_SENHA_PORTEIRO' || SENHA_PORTEIRO === '';
chk($adminDef ? 'FALHA' : 'OK', 'Senha de admin definida', $adminDef ? 'ainda no valor por defeito' : (preg_match('/^\$(2y|2a|2b|argon2)/', SENHA_ADMIN) ? 'em hash' : 'em texto simples (prefira hash)'));
chk($portDef ? 'AVISO' : 'OK', 'Senha de porteiro definida', $portDef ? 'ainda no valor por defeito' : '');

// Base de dados
mysqli_report(MYSQLI_REPORT_OFF);
$ligou = false; $modo = '';
foreach (DB_CONFIGS as $nome => $c) {
    $t = @new mysqli($c['host'], $c['user'], $c['pass'], $c['db']);
    if ($t && !$t->connect_error) { $ligou = true; $modo = $nome; $t->close(); break; }
}
chk($ligou ? 'OK' : 'FALHA', 'Ligação à base de dados', $ligou ? "via '$modo'" : 'verifique CW_DB_*');

// URL base / HTTPS
chk(BASE_URL_OVERRIDE !== '' ? 'OK' : 'AVISO', 'CW_BASE_URL', BASE_URL_OVERRIDE !== '' ? BASE_URL_OVERRIDE : 'não definido (derivado do pedido; defina atrás de proxy)');

echo "Verificação da plataforma de convites\n" . str_repeat('=', 40) . "\n"
   . implode("\n", $linhas) . "\n" . str_repeat('-', 40) . "\n"
   . ($falhas ? "RESULTADO: $falhas falha(s), $avisos aviso(s) — corrija as falhas antes de abrir ao público.\n"
              : ($avisos ? "RESULTADO: sem falhas, $avisos aviso(s).\n" : "RESULTADO: tudo OK.\n"))
   . "Lembrete: a leitura de QR por câmara exige HTTPS.\n";
if ($cli) exit($falhas ? 1 : 0);
