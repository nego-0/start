# I & A — Plataforma de Convites e Gestão de Casamentos

Da aplicação de **um** casamento a um serviço para **muitos** casais.

Este repositório reúne o produto em desenvolvimento: a aplicação atual (que já
serve, em produção, um casamento real — *Isabel & Abednego*) e o plano para a
transformar numa plataforma multi-inquilino onde qualquer casal cria,
personaliza e gere os seus convites e todo o seu evento.

---

## O que já existe (fundação — Fase 0)

### `casamento_web/` — aplicação PHP + MySQL (a funcionar)
Sistema de inquilino único que faz a gestão completa de um casamento:

- **Convites** digitais e físicos, com nome a exibir gerado automaticamente.
- **Convidados** nominais por convite, com RSVP e presença individuais.
- **Confirmação de presença (RSVP)** através de um link único por convite.
- **Passe de entrada com código QR** gerado após a confirmação.
- **Página do porteiro** com leitura de QR por câmara e busca manual.
- **Etiquetas de convites físicos** prontas a imprimir.
- **Mesas** com capacidade e ocupação.
- **Painel de administração** com importação da lista antiga.

Detalhes de instalação e utilização em [`casamento_web/LEIA-ME.md`](casamento_web/LEIA-ME.md).

> **Nota de segurança:** o `casamento_web/config.php` versionado tem as
> credenciais e palavras-passe substituídas por marcadores. Preencha os valores
> reais apenas no seu servidor (ou num `config.local.php`, ignorado pelo git).

### Fase 1 — Modelos (em curso) ✅

Personalização do convite **por configuração**, construída sobre a app atual, sem
reescrever o convite:

- **Galeria de modelos** — quatro predefinições de paleta e tipografia
  (Esmeralda & Ouro, Borgonha & Rosé, Azul-Noite & Champanhe, Terracota & Sálvia).
- **Editor visual** (`casamento_web/editor-modelos.php`) com **pré-visualização ao
  vivo**: cores (11 papéis), tipografia (3 papéis), secções on/off, textos e dados
  do casal/data.
- O aspeto é guardado em JSON (`cw_designs`) e aplicado ao servir o convite —
  o `convite-base.html` mantém-se intacto, agora com marcadores (`{{...}}`) e a
  paleta/tipografia em variáveis CSS.
- Camada de tema em funções puras (`design.php`, `modelos.php`), com testes de
  renderização independentes da base de dados.

### Fase 2 — Convite impresso (início) ✅

O **mesmo desenho gera o convite físico**: `casamento_web/convite-impresso.php`
produz um cartão **A5 (corte 148×210 mm)** com **sangria de 3 mm**, **marcas de
corte** e alta resolução, pronto a imprimir ou guardar em PDF (com molduras à
escolha). É o primeiro dos requisitos de impressão do plano; a conversão CMYK
fica para o passo de pré-impressão da gráfica.

### `prototipos/` — protótipos de interface
- `editor-convites.html` — **protótipo do Atelier de Convites** (editor visual),
  para explorar a experiência da Fase 2.
- `exemplos/` — exemplos de convite impresso e do editor pré-impressão.

### `docs/` — documentação de produto
- `Plano_de_Produto_Plataforma_Convites.docx` — documento de visão, arquitetura
  e execução por fases (Versão 1.0, Julho de 2026).

---

## Para onde vamos — roteiro por fases

Retirado do plano de produto. Cada fase entrega algo utilizável e prepara a
seguinte.

| Fase | Objetivo | Entregáveis principais |
|---|---|---|
| **0 — Fundação** | Infraestrutura própria e multi-inquilinência | Alojamento, base de dados gerida, armazenamento de imagens, contas e eventos |
| **1 — Modelos** *(em curso)* | Personalizar o convite por configuração | Galeria de modelos, edição de cores, textos e secções ✅ · falta: gestão de fotos e música por evento |
| **2 — Editor** *(iniciada)* | Liberdade total de desenho e impressão | Convite impresso A5 com sangria e marcas de corte ✅ · falta: editor visual de tela (Fabric.js) e CMYK |
| **3 — Ecossistema** | Completar a experiência do casamento | Edição de imagem, plano de mesas visual, WhatsApp e restantes módulos |

As duas decisões que condicionam tudo o resto são a **Fase 0** (infraestrutura e
multi-inquilinência) e a **escolha do editor** (avaliar Polotno / Fabric.js).

### Próximos passos sugeridos
1. Decidir a infraestrutura de alojamento, base de dados e armazenamento de imagens.
2. Desenhar e implementar o modelo de dados multi-inquilino (contas e eventos).
3. Selecionar a abordagem do editor com um protótipo (Polotno vs. Fabric.js).
4. Construir a galeria de modelos e a personalização por configuração (Fase 1).
5. Validar com um pequeno grupo de casais reais antes de abrir ao público.

---

## Estrutura do repositório

```
.
├── casamento_web/     Aplicação PHP + MySQL atual (inquilino único)
│   ├── assets/        Estilos, convite-base, fontes e imagens
│   └── LEIA-ME.md     Instalação e utilização detalhadas
├── prototipos/        Protótipos de interface (editor visual, exemplos)
├── docs/              Plano de produto e documentação de visão
└── README.md          Este ficheiro
```

---

## Stack

- **Atual:** PHP + MySQL, HTML/CSS/JS sem dependências pesadas (corre em
  alojamento partilhado simples). QR via `qrious`.
- **Direção (plano):** front-end moderno (React ou Vue) sobre uma API dedicada,
  base de dados gerida (PostgreSQL/MySQL), armazenamento de objetos
  (Cloudflare R2 / Backblaze B2) e CDN, preservando a lógica de negócio já testada.
