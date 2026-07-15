<?php
// ============================================================
// gerar-hash.php — Gera o hash de uma palavra-passe para config.php
// Uso (linha de comandos):  php gerar-hash.php "a-minha-senha"
// Cole o resultado em SENHA_ADMIN ou SENHA_PORTEIRO.
// ============================================================
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Apenas por linha de comandos.\n"); }
$senha = $argv[1] ?? '';
if ($senha === '') { fwrite(STDERR, "Uso: php gerar-hash.php \"a-minha-senha\"\n"); exit(1); }
echo password_hash($senha, PASSWORD_DEFAULT) . "\n";
