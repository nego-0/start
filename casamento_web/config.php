<?php
// ============================================================
// config.php — Configuração central do sistema
// Isabel & Abednego · Gestão de Convidados
// Edite APENAS este ficheiro para adaptar o sistema.
// ============================================================

// ---- Evento -------------------------------------------------
const EVENTO = [
    'noiva'     => 'Isabel',
    'noivo'     => 'Abednego',
    'data_iso'  => '2026-12-19',                 // usado no contador e QR
    'data_ext'  => '19 de Dezembro de 2026',     // texto exibido
    'hora'      => '16:00',                       // AJUSTE: hora real da cerimónia
    'local'     => 'Estufa Municipal de Moçâmedes',
    'cidade'    => 'Namibe, Angola',
    'whatsapp'  => '244000000000',                // AJUSTE: nº WhatsApp p/ contacto (formato internacional, só dígitos)
];

// ---- Acesso -------------------------------------------------
// O administrador acede a tudo; o porteiro acede só à página de entrada.
//
// RECOMENDADO: guarde um *hash* (não a palavra-passe em texto simples).
// Gere-o com:  php gerar-hash.php "a-sua-palavra-passe"
// e cole aqui o resultado (começa por $2y$...). O sistema aceita ambos,
// mas o texto simples é um recurso apenas para testes locais.
const SENHA_ADMIN    = 'ALTERE_ESTA_SENHA_ADMIN';
const SENHA_PORTEIRO = 'ALTERE_ESTA_SENHA_PORTEIRO';

// ---- Base de dados -----------------------------------------
// Tenta 'local' primeiro (XAMPP/Wamp) e depois 'online' (alojamento).
// Reaproveita a mesma base da sua lista antiga, para permitir a importação.
const DB_CONFIGS = [
    'local'  => ['host' => 'localhost', 'user' => 'root',          'pass' => '',                 'db' => 'wedding_guests'],
    'online' => ['host' => 'DB_HOST',   'user' => 'DB_UTILIZADOR', 'pass' => 'DB_PALAVRA_PASSE', 'db' => 'DB_NOME']
];

// ---- Regras -------------------------------------------------
const PREFIXO   = 'cw_';   // prefixo das tabelas novas (não mexe na lista antiga)
const MAX_LUGARES_TOTAL = 300;  // teto de segurança de lugares no evento

// Fuso não é crítico aqui; datas guardadas em UTC pelo MySQL.
date_default_timezone_set('Africa/Luanda');
