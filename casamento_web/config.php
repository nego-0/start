<?php
// ============================================================
// config.php — Configuração central do sistema
//
// Em PRODUÇÃO, defina os valores por VARIÁVEIS DE AMBIENTE (ver .env.example
// e docs/IMPLANTACAO.md). Cada opção abaixo lê primeiro a variável de
// ambiente e só usa o valor por defeito se ela não existir — assim os
// segredos (senhas, credenciais da BD) nunca ficam no repositório.
//
// Para desenvolvimento local, pode simplesmente editar os valores por defeito.
// ============================================================

/** Valor de uma variável de ambiente, com recurso a um padrão. */
function cfg_env(string $chave, string $padrao = ''): string {
    $v = getenv($chave);
    return ($v === false || $v === '') ? $padrao : $v;
}

// ---- Evento -------------------------------------------------
// Dados-semente do primeiro evento (o casamento inicial). Cada casal na
// plataforma gere os seus próprios dados; estes são apenas o arranque.
define('EVENTO', [
    'noiva'     => cfg_env('CW_NOIVA', 'Isabel'),
    'noivo'     => cfg_env('CW_NOIVO', 'Abednego'),
    'data_iso'  => cfg_env('CW_DATA_ISO', '2026-12-19'),          // usado no contador e QR
    'data_ext'  => cfg_env('CW_DATA_EXT', '19 de Dezembro de 2026'), // texto exibido
    'hora'      => cfg_env('CW_HORA', '16:00'),                    // hora da cerimónia
    'local'     => cfg_env('CW_LOCAL', 'Estufa Municipal de Moçâmedes'),
    'cidade'    => cfg_env('CW_CIDADE', 'Namibe, Angola'),
    'whatsapp'  => cfg_env('CW_WHATSAPP', '244000000000'),         // nº WhatsApp (só dígitos)
]);

// ---- Acesso -------------------------------------------------
// O administrador acede a tudo; o porteiro acede só à página de entrada.
// RECOMENDADO: guardar um *hash* (php gerar-hash.php "senha") e passá-lo em
// CW_SENHA_ADMIN / CW_SENHA_PORTEIRO. O sistema aceita hash ou texto simples.
define('SENHA_ADMIN',    cfg_env('CW_SENHA_ADMIN', 'ALTERE_ESTA_SENHA_ADMIN'));
define('SENHA_PORTEIRO', cfg_env('CW_SENHA_PORTEIRO', 'ALTERE_ESTA_SENHA_PORTEIRO'));

// ---- Base de dados -----------------------------------------
// CW_DB_MODE: 'auto' (padrão) tenta 'local' e depois 'online';
//             'online' força só a ligação de produção; 'local' só a de testes.
$__dbMode = strtolower(cfg_env('CW_DB_MODE', 'auto'));
$__local  = ['host' => cfg_env('CW_DB_LOCAL_HOST', 'localhost'),
             'user' => cfg_env('CW_DB_LOCAL_USER', 'root'),
             'pass' => cfg_env('CW_DB_LOCAL_PASS', ''),
             'db'   => cfg_env('CW_DB_LOCAL_NAME', 'wedding_guests')];
$__online = ['host' => cfg_env('CW_DB_HOST', 'DB_HOST'),
             'user' => cfg_env('CW_DB_USER', 'DB_UTILIZADOR'),
             'pass' => cfg_env('CW_DB_PASS', 'DB_PALAVRA_PASSE'),
             'db'   => cfg_env('CW_DB_NAME', 'DB_NOME')];
if ($__dbMode === 'online')      define('DB_CONFIGS', ['online' => $__online]);
elseif ($__dbMode === 'local')   define('DB_CONFIGS', ['local'  => $__local]);
else                             define('DB_CONFIGS', ['local' => $__local, 'online' => $__online]);

// URL base explícito (útil atrás de proxy/CDN, para links e QR corretos).
define('BASE_URL_OVERRIDE', rtrim(cfg_env('CW_BASE_URL', ''), '/'));

// ---- Regras -------------------------------------------------
define('PREFIXO', 'cw_');   // prefixo das tabelas novas (não mexe na lista antiga)
define('MAX_LUGARES_TOTAL', (int)cfg_env('CW_MAX_LUGARES', '300'));  // teto de lugares

// Fuso não é crítico aqui; datas guardadas em UTC pelo MySQL.
date_default_timezone_set(cfg_env('CW_TZ', 'Africa/Luanda'));
