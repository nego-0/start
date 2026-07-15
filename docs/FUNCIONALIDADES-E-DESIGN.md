# Funcionalidades a adicionar & melhorias de design

*Relatório de produto — complementa a análise técnica (`ANALISE-ESTADO.md`).
Foco: o que acrescentar e como elevar a experiência, ecrã a ecrã.*

---

## Parte I — Funcionalidades a adicionar

Organizadas por área e por impacto. **★** = maior valor percebido pelo casal;
**⚡** = esforço baixo (vitória rápida).

### 1. Convite digital (o produto que os convidados veem)

| Funcionalidade | Porquê | Impacto |
|---|---|---|
| **Fotos do casal por evento** (hero, história, interlúdio, acesso) | Hoje todos os eventos usam as fotos da Isabel & Abednego — é a personalização mais pedida a seguir às cores | ★★★ |
| **Música por evento** (upload ou escolha de faixa) | Idem — a música é identidade do casal | ★★ |
| **Cronograma editável** (horas e momentos do dia) | O cronograma está fixo no template; cada festa tem o seu | ★★ ⚡ |
| **Capítulos da história editáveis** (título + texto de cada capítulo) | A "nossa história" é única de cada casal | ★★ ⚡ |
| **Versões multilíngues** (PT/EN, futuro FR/UMB) | Convidados internacionais; previsto no plano | ★ |
| **Microsite do casamento** (`/slug` bonito, ex.: `/ana-e-bruno`) | Partilha mais elegante do que `convite-digital.php?evento=...` | ★ ⚡ |

### 2. RSVP e convidados

| Funcionalidade | Porquê | Impacto |
|---|---|---|
| **Perguntas personalizadas no RSVP** (menu, alergias, música pedida) | Previsto no plano; poupa dezenas de mensagens ao casal | ★★★ |
| **Membros nominais por convite no fluxo do casal** | O admin legado já tem; as contas novas ainda criam convites sem pessoas nominais | ★★ |
| **Importação CSV/Excel da lista de convidados** | Quase todos os casais já têm a lista numa folha de cálculo | ★★★ ⚡ |
| **Exportação CSV por evento** (lista, confirmações, mesas) | Partilhar com o catering/decoração | ★★ ⚡ |
| **Check-in à porta por evento (porteiro por conta)** | Fecha o ciclo: convidar → confirmar → entrar; hoje só o evento 1 tem porteiro | ★★★ |
| **Prazo de RSVP configurável com fecho automático** | O texto do prazo existe; falta o comportamento | ★ ⚡ |

### 3. Comunicação

| Funcionalidade | Porquê | Impacto |
|---|---|---|
| **Lembretes automáticos agendados** (X dias antes; a quem não confirmou) | Transforma o envio manual num assistente; o motor wa.me já existe | ★★★ |
| **Envio por email** (convite + confirmação de RSVP) | Nem todos usam WhatsApp; exige serviço transacional | ★★ |
| **Mensagens guardadas por evento** | Hoje os modelos de mensagem voltam ao padrão em cada visita | ★ ⚡ |
| **Página "obrigado" pós-RSVP partilhável** | Momento natural para o convidado guardar o passe | ★ ⚡ |

### 4. Dia do evento

| Funcionalidade | Porquê | Impacto |
|---|---|---|
| **Painel de chegadas ao vivo** para o casal (quem já entrou, em tempo real) | Já existe para o admin; falta por conta/evento | ★★ |
| **Passe na carteira do telemóvel** (Apple/Google Wallet) | Previsto no plano; experiência premium à entrada | ★ |
| **Mural de fotos ao vivo** (convidados enviam, projeta-se na festa) | Momento social forte; diferenciador | ★★ |

### 5. Planeamento e social (Fase 3 restante)

| Funcionalidade | Porquê | Impacto |
|---|---|---|
| **Lista de presentes** com dinheiro/Multicaixa e opção internacional | Previsto no plano; contexto local é diferenciador | ★★★ |
| **Álbum partilhado** pós-festa (upload dos convidados, moderação do casal) | Prolonga a vida do produto depois do casamento | ★★ |
| **Tarefas & orçamento** (checklist com prazos, fornecedores) | Torna a plataforma companheira de todo o planeamento | ★ |

### 6. Plataforma / negócio

| Funcionalidade | Porquê | Impacto |
|---|---|---|
| **Upload de imagens para armazenamento próprio** (R2/B2) | Pré-requisito das fotos por evento; evita data-URIs na BD | ★★★ |
| **Verificação de email + recuperação de senha** | Necessário para contas reais | ★★★ |
| **Planos freemium + pagamentos** (Multicaixa Express + internacional) | Monetização prevista no plano | ★★ |
| **Super-administração** (gerir casais, planos, métricas do serviço) | Operar o serviço com muitos casais | ★★ |
| **Galeria de modelos alargada** (mais 4–6 paletas/estilos; modelos sazonais) | Mais escolha à entrada, sem custo estrutural | ★ ⚡ |

---

## Parte II — Melhorias de design (UX/UI)

### 1. Identidade e coerência

- **Sistema de design unificado.** As páginas de administração partilham o
  estilo (verde-floresta/dourado/marfim) mas cada uma redeclara o seu CSS.
  → Extrair um `admin.css` comum (variáveis, botões, cartões, tabelas, topo)
  para coerência e manutenção.
- **A administração é sempre "Esmeralda".** O painel do casal podia refletir a
  paleta escolhida pelo próprio casal — reforça a sensação de "meu".
- **Marca da plataforma.** Nome, logótipo e favicon próprios (hoje herda o
  monograma I&A); rodapé "feito com ♥ em Angola".

### 2. Navegação e fluxo

- **Menu lateral persistente no fluxo do casal** (Design · Convidados · Mesas ·
  Envios · Impresso) em vez de voltar sempre ao painel — reduz cliques.
- **Onboarding do casal** (assistente em 3 passos: nomes/data → modelo → 1.º
  convidado) com barra de progresso; hoje o registo despeja no painel.
- **Estado vazio orientado.** Nos ecrãs sem dados, guiar: "Ainda não tem
  convidados — importe a sua lista ou adicione o primeiro" com botão.
- **Pré-visualização do convite dentro do painel** (miniatura viva no cartão
  "Ver convite digital") — feedback imediato do estado do design.

### 3. Editor de modelos

- **Pré-visualização em modo telemóvel/desktop** (alternar largura do iframe) —
  os convidados abrem quase sempre no telemóvel.
- **Atualização ao vivo sem recarregar o iframe** (postMessage a injetar o CSS
  do tema) — hoje cada alteração re-submete o iframe (mais lento).
- **Paleta "inteligente":** escolher 2–3 cores base e derivar as 11
  automaticamente (com pré-visualização), mantendo o modo avançado atual.
- **Contraste garantido:** avisar quando texto/fundo escolhidos violam
  legibilidade (WCAG) — proteção contra convites ilegíveis.

### 4. Editor de tela (impresso)

- **Zoom e pan** (a tela A5 em ecrãs pequenos corta); atalhos +/−.
- **Snapping a guias** (centro, margens de segurança) ao arrastar — alinhamento
  profissional sem esforço.
- **Painel de camadas** (lista reordenável) além dos botões frente/trás.
- **Recorte de imagem** (crop) e máscaras simples (círculo/oval) para retratos.
- **Duplo clique para editar texto** já funciona; falta *hint* visual (tooltip
  inicial "toque duas vezes para editar").

### 5. Plano de mesas

- **Vista de sala** opcional (mesas posicionáveis num "chão" 2D, redondas/
  retangulares) além da grelha atual — é o "plano visual" do plano de produto.
- **Sentar pessoas, não só convites** (quando houver membros nominais).
- **Avisos inteligentes:** mesa sobrelotada destacada (já existe), sugerir mesa
  com espaço ao arrastar; contador global "sentados / por sentar" no topo.
- **Impressão do plano** (PDF por mesa para o dia — lista por mesa na entrada).

### 6. Páginas públicas (convidado)

- **Ecrã de RSVP herdando o design do evento** — hoje herda os nomes/data mas
  mantém a paleta Esmeralda fixa; devia usar a paleta do casal (como o convite).
- **Página 404/convite inválido mais afável** com contacto do casal certo (C3
  da análise técnica).
- **Acessibilidade:** já há `prefers-reduced-motion` e aria-labels no convite;
  estender ao RSVP e páginas novas; alvo de toque ≥ 44px nos botões móveis.
- **Desempenho móvel:** *lazy-load* já aplicado às imagens; falta comprimir/
  redimensionar uploads dos casais (imagens de 5 MB matam o 3G).

### 7. Microcopy e tom

- Rever textos de interface para tom caloroso e local (PT europeu/angolano
  consistente — hoje mistura "Ás 20h30" com "às"; normalizar para "às").
- Mensagens de erro humanas ("Não conseguimos guardar — tente novamente"),
  nunca técnicas.

---

## Sugestão de pacote seguinte (2–3 sessões de trabalho)

**Pacote "Convite verdadeiramente do casal"** — junta as maiores vitórias:
1. **Fotos por evento** (upload + armazenamento) — a personalização nº 1.
2. **Cronograma e história editáveis** (⚡ sobre a camada de textos existente).
3. **Menu lateral + onboarding** — o fluxo do casal fica redondo.
4. **Pré-visualização móvel no editor** (⚡).

**Pacote "Ciclo completo do convidado"** (a seguir):
importação CSV → perguntas de RSVP → check-in por evento → painel de chegadas.

> Critério mantido: cada pacote entrega valor visível ao casal, é verificável
> ponta a ponta, e não toca no casamento em produção (evento 1).
