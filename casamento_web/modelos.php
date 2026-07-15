<?php
// ============================================================
// modelos.php — Galeria de modelos e registo de tipografias
//   Fase 1: personalização por configuração.
//   Um "modelo" é uma predefinição (paleta + tipografia) sobre
//   o mesmo layout do convite. Não contém segredos nem toca na BD.
// ============================================================

/**
 * Registo de tipos de letra disponíveis.
 *   'família' — valor CSS de font-family (com fallback).
 *   'google'  — parâmetro para a API Google Fonts, ou null quando
 *               o tipo de letra já é servido localmente (assets/convite/fonts).
 *   'papel'   — serif | sans | script (onde pode ser usado).
 *   'local'   — true se não precisa de rede (já embutível offline).
 */
function fontesDisponiveis(): array {
    return [
        // Serifadas (títulos e nomes)
        'cormorant'   => ['nome' => 'Cormorant Garamond', 'familia' => "'Cormorant Garamond',serif", 'google' => null,
                          'papel' => 'serif', 'local' => true],
        'eb-garamond' => ['nome' => 'EB Garamond',        'familia' => "'EB Garamond',serif",        'google' => 'EB+Garamond:ital,wght@0,500;0,600;1,500',
                          'papel' => 'serif', 'local' => false],
        'playfair'    => ['nome' => 'Playfair Display',    'familia' => "'Playfair Display',serif",    'google' => 'Playfair+Display:ital,wght@0,500;0,600;0,700;1,600',
                          'papel' => 'serif', 'local' => false],

        // Sem serifa (corpo e interface)
        'jost'        => ['nome' => 'Jost',                'familia' => "'Jost',sans-serif",           'google' => null,
                          'papel' => 'sans', 'local' => true],
        'montserrat'  => ['nome' => 'Montserrat',          'familia' => "'Montserrat',sans-serif",     'google' => 'Montserrat:wght@300;400;500;600',
                          'papel' => 'sans', 'local' => false],
        'josefin'     => ['nome' => 'Josefin Sans',        'familia' => "'Josefin Sans',sans-serif",   'google' => 'Josefin+Sans:wght@300;400;500',
                          'papel' => 'sans', 'local' => false],

        // Manuscritas (assinatura, âmpersand, selo)
        'pinyon'      => ['nome' => 'Pinyon Script',       'familia' => "'Pinyon Script',cursive",     'google' => null,
                          'papel' => 'script', 'local' => true],
        'great-vibes' => ['nome' => 'Great Vibes',         'familia' => "'Great Vibes',cursive",       'google' => 'Great+Vibes',
                          'papel' => 'script', 'local' => false],
        'tangerine'   => ['nome' => 'Tangerine',           'familia' => "'Tangerine',cursive",         'google' => 'Tangerine:wght@700',
                          'papel' => 'script', 'local' => false],
    ];
}

/** Tipos de letra de um determinado papel (serif|sans|script). */
function fontesDoPapel(string $papel): array {
    return array_filter(fontesDisponiveis(), fn($f) => $f['papel'] === $papel);
}

/** Chaves de cor da paleta, pela ordem em que aparecem no editor. */
function chavesPaleta(): array {
    return ['ink','forest','forest-deep','ivory','cream','sand','gold','gold-soft','gold-pale','blush','text'];
}

/** Rótulos amigáveis de cada cor (para o editor). */
function rotulosPaleta(): array {
    return [
        'ink'         => 'Tinta (títulos)',
        'forest'      => 'Cor principal',
        'forest-deep' => 'Fundo escuro',
        'ivory'       => 'Fundo da página',
        'cream'       => 'Creme (áreas suaves)',
        'sand'        => 'Areia (contornos)',
        'gold'        => 'Destaque',
        'gold-soft'   => 'Destaque suave',
        'gold-pale'   => 'Destaque pálido',
        'blush'       => 'Tom de acento',
        'text'        => 'Texto corrente',
    ];
}

/**
 * Modelos disponíveis na galeria. Cada um define a paleta completa
 * e a tipografia; os textos ficam a cargo do design (o casal edita).
 */
function modelosDisponiveis(): array {
    return [
        'esmeralda' => [
            'nome'      => 'Esmeralda & Ouro',
            'descricao' => 'Verde-floresta, dourado e marfim. Clássico e quente.',
            'paleta'    => [
                'ink' => '#20342A', 'forest' => '#2C4536', 'forest-deep' => '#16261E',
                'ivory' => '#FBF8F1', 'cream' => '#F5EEDF', 'sand' => '#E9DFC9',
                'gold' => '#B4864A', 'gold-soft' => '#D9BC8C', 'gold-pale' => '#EFE3CB',
                'blush' => '#E4CDBB', 'text' => '#3B4A40',
            ],
            'tipografia' => ['serif' => 'cormorant', 'sans' => 'jost', 'script' => 'pinyon'],
        ],
        'borgonha' => [
            'nome'      => 'Borgonha & Rosé',
            'descricao' => 'Vinho profundo, rosé e cobre. Romântico e intenso.',
            'paleta'    => [
                'ink' => '#3A1622', 'forest' => '#5E2233', 'forest-deep' => '#2A0E16',
                'ivory' => '#FCF7F5', 'cream' => '#F6EAE7', 'sand' => '#E9D4D1',
                'gold' => '#B67C63', 'gold-soft' => '#DBB19C', 'gold-pale' => '#F0DED4',
                'blush' => '#E7CDC7', 'text' => '#4C2A32',
            ],
            'tipografia' => ['serif' => 'playfair', 'sans' => 'montserrat', 'script' => 'great-vibes'],
        ],
        'azul-noite' => [
            'nome'      => 'Azul-Noite & Champanhe',
            'descricao' => 'Azul-marinho, champanhe e prata. Sóbrio e elegante.',
            'paleta'    => [
                'ink' => '#14243B', 'forest' => '#223A5E', 'forest-deep' => '#0F1A2E',
                'ivory' => '#F8FAFC', 'cream' => '#EEF2F7', 'sand' => '#DCE3EC',
                'gold' => '#B9985A', 'gold-soft' => '#D8C393', 'gold-pale' => '#ECE1C7',
                'blush' => '#CBD6E2', 'text' => '#2C3A4C',
            ],
            'tipografia' => ['serif' => 'cormorant', 'sans' => 'montserrat', 'script' => 'pinyon'],
        ],
        'terracota' => [
            'nome'      => 'Terracota & Sálvia',
            'descricao' => 'Terracota, areia e verde-sálvia. Mediterrânico e natural.',
            'paleta'    => [
                'ink' => '#4A2A1E', 'forest' => '#7C4A32', 'forest-deep' => '#3A2019',
                'ivory' => '#FBF6EF', 'cream' => '#F4E9DC', 'sand' => '#E7D4C0',
                'gold' => '#BE7B4C', 'gold-soft' => '#DDB187', 'gold-pale' => '#EFDCC7',
                'blush' => '#D8C4A8', 'text' => '#4E362A',
            ],
            'tipografia' => ['serif' => 'eb-garamond', 'sans' => 'jost', 'script' => 'tangerine'],
        ],
    ];
}

/** Devolve um modelo pela chave, ou o modelo base ('esmeralda'). */
function modelo(string $id): array {
    $ms = modelosDisponiveis();
    return $ms[$id] ?? $ms['esmeralda'];
}
