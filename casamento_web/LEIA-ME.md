# Gestão de Convidados — Isabel & Abednego

Sistema em PHP + MySQL para gerir os convidados do casamento: criação de convites (digitais e físicos), geração dos nomes a exibir, confirmação de presença (RSVP), código QR de entrada e página do porteiro para validar convites à porta do evento.

O sistema foi desenhado para **coexistir** com a sua lista atual: cria tabelas novas com o prefixo `cw_` e não altera as tabelas antigas (`guests`, `invite_groups`, `mesas`). Pode, num clique, importar a lista existente para o novo formato.

---

## Ficheiros

| Ficheiro | Função |
|---|---|
| `config.php` | Configuração central: dados do evento, palavras-passe e ligação à base de dados. **É o único ficheiro que precisa de editar.** |
| `conta.php` | **Contas de casais e eventos** (Fase 0, multi-inquilino): registo/login por email, evento ativo e isolamento de dados. |
| `entrar.php` / `registar.php` / `painel-casal.php` / `sair-conta.php` | Entrada, criação de conta, painel do casal e saída. |
| `db.php` | Ligação, criação automática das tabelas e funções partilhadas. |
| `auth.php` | Autenticação por sessão (administrador e porteiro). |
| `api.php` | Todos os pedidos JSON (gestão, RSVP público e porteiro) e exportação CSV. |
| `login.php` / `logout.php` | Entrada e saída. |
| `index.php` | Painel de administração (convites, convidados, mesas, importação, QR). |
| `convite.php` | Página pública de confirmação de presença + passe de entrada com QR. |
| `porteiro.php` | Página do porteiro: leitura de QR por câmara e busca manual. |
| `impressos.php` | Etiquetas dos convites físicos com QR, prontas a imprimir. |
| `editor-modelos.php` | **Editor de modelos** (Fase 1): galeria, cores, tipografia, secções e textos, com pré-visualização ao vivo. |
| `convite-impresso.php` | **Convite impresso** (Fase 2): cartão pronto a imprimir (A5/A6/quadrado), com sangria de 3 mm, marcas de corte, molduras e verso opcional. |
| `convidados.php` | **Convidados & RSVP por conta** (Fase 0): lista, criação/edição/remoção de convites do evento, links de convite/RSVP e estado de confirmação. Isolado por evento. |
| `mesas-plano.php` | **Plano de mesas visual** (Fase 3): criar mesas, adicionar convidados e arrastá-los para as mesas, com ocupação ao vivo. Isolado por evento. |
| `envios.php` | **Convites por WhatsApp** (Fase 3): modelos (convite/lembrete/agradecimento), personalização por convidado e envio via `wa.me` num clique. Isolado por evento. |
| `editor-tela.php` | **Editor visual de tela** (Fase 2): desenho livre com Fabric.js (texto, imagens, formas, camadas, desfazer/refazer), **edição de imagem** (brilho/contraste/saturação, P&B/sépia) e exportação PNG/PDF. |
| `impresso.php` | Persistência da tela do editor (`cw_impressos`) e ponte do design para o editor. |
| `assets/vendor/` | Bibliotecas locais: `fabric.min.js` (editor de tela) e `jspdf.umd.min.js` (exportação PDF). |
| `modelos.php` | Galeria de modelos (predefinições de paleta e tipografia) e registo de tipos de letra. |
| `design.php` | Camada de personalização: esquema `cw_designs`, design padrão e o construtor de tema (funções puras). |
| `upload-imagem.php` | Upload das **fotos por evento** (hero/história/interlúdio/acesso): validação, redimensionamento (GD) e gravação em `uploads/eventos/{id}/`. |
| `assets/estilo.css` | Estilo visual, alinhado com o convite (verde-floresta, dourado e marfim). |
| `assets/convite-base.html` | Modelo do convite digital, com marcadores (`{{...}}`) para cores, tipografia e textos. |

---

## Estrutura da base de dados (tabelas novas)

- **`cw_convites`** — o convite é a unidade central: código único, nome a exibir, sufixo opcional, tipo (`digital`/`fisico`/`ambos`), lado, número de lugares, mesa, telefone, estados de RSVP e de entrada, mensagens e observações.
- **`cw_convidados`** — as pessoas nominais de cada convite (com RSVP e presença individuais).
- **`cw_mesas`** — mesas com capacidade e ocupação.
- **`cw_designs`** — o design do convite (por evento), em JSON (paleta, tipografia, secções e textos). Criada automaticamente.
- **`cw_contas`** / **`cw_eventos`** — contas de casais e os seus eventos (Fase 0). As tabelas existentes ganham `evento_id`; os dados atuais ficam no **evento 1**.

### Dois modos de acesso (Fase 0)

- **Admin legado** (palavra-passe em `config.php`) — gere o **evento 1** (o casamento atual) com todas as funcionalidades de convidados/RSVP/porteiro. Inalterado.
- **Contas de casais** (`registar.php` / `entrar.php`) — cada casal cria a sua conta e um ou mais eventos, com **design isolado** (modelo, cores, textos, convite impresso e editor de tela) por evento. Partilha pública do desenho em `convite-digital.php?evento=SLUG` e `convite-impresso.php?evento=SLUG`.

---

## Instalação no InfinityFree (ou outro alojamento)

1. Carregue todos os ficheiros (incluindo a pasta `assets/`) para a pasta pública do site (`htdocs`).
2. Em `config.php`, confirme os dados de ligação em `DB_CONFIGS['online']`. Já vêm preenchidos com a sua base atual (`if0_40371922_wed`), por isso o sistema liga-se e cria as tabelas `cw_` automaticamente na primeira visita.
3. Abra o site no navegador. As tabelas são criadas sozinhas.

O sistema tenta primeiro a ligação `local` (útil para testes em XAMPP/Wamp) e, se falhar, usa a `online`.

---

## Antes de publicar — ajustes em `config.php`

- **Palavras-passe:** altere `SENHA_ADMIN` e `SENHA_PORTEIRO`. São distintas: o administrador acede a tudo; o porteiro só acede à página de entrada.
- **Hora da cerimónia:** o campo `EVENTO['hora']` está como `16:00` — ajuste para a hora real.
- **WhatsApp de contacto:** `EVENTO['whatsapp']` está com um número de exemplo — coloque o número real (formato internacional, só dígitos, ex.: `244923000000`).

---

## Primeira utilização

1. Entre com a palavra-passe de administrador.
2. Se a lista antiga for detetada, aparece um aviso **“Importar a sua lista atual”**. Ao importar:
   - cada grupo de convite (ex.: *Família Agostinho*) torna-se um convite físico com os seus membros;
   - cada convidado sem grupo torna-se um convite digital individual;
   - confirmações, telefones e mesas são preservados.

---

## Como funciona

**Criar um convite.** Indique os nomes reais dos convidados; o sistema sugere automaticamente o nome a exibir (ex.: *Família Agostinho*, *Ana e Bruno*). Esse nome pode ser diferente dos nomes reais.

**Regra do número entre parênteses.** Se o convite for para **uma só pessoa**, mostra apenas o nome. Para mais do que uma, acrescenta *(N pessoas)* — ou o texto que escrever no campo **Sufixo** (ex.: *e acompanhante*).

**Tipo do convite.** *Digital*, *Físico* ou *Ambos*. Todos os convites têm sempre um link e um QR; o tipo serve para organizar a produção (marcar como *impresso* / *enviado*).

**Mesas.** Crie mesas com capacidade e atribua convites; o painel mostra a ocupação.

**Confirmação de presença (RSVP).** Cada convite tem um link único (`convite.php?c=CÓDIGO`). O convidado confirma se comparece, quantas pessoas e quem, e pode deixar uma mensagem. Ao confirmar, recebe o **passe de entrada com QR**.

**Porteiro.** Na página de entrada, o porteiro lê o QR com a câmara ou procura pelo nome/código. Vê o estado do convite e regista a entrada (de todos ou de cada pessoa). O contador de presenças atualiza em tempo real.

**Convites físicos.** A página *Convites físicos* gera as etiquetas com o nome e o QR de cada convite, prontas a imprimir para os envelopes.

**Modelo do convite (Fase 1).** Em *Modelo do convite* personaliza o aspeto sem tocar no código:

- **Galeria de modelos** — pontos de partida (Esmeralda & Ouro, Borgonha & Rosé, Azul-Noite & Champanhe, Terracota & Sálvia) que definem paleta e tipografia.
- **Paleta** — 11 cores que se propagam por todo o convite (incluindo o código QR).
- **Tipografia** — três papéis (títulos, corpo e manuscrita). As fontes marcadas *(web)* precisam de internet; o modelo Esmeralda usa fontes locais e mantém o convite totalmente offline.
- **Fotos** — carregue as quatro fotografias do convite (capa, história, interlúdio, passe). São redimensionadas e guardadas por evento em `uploads/eventos/{id}/`; ficam também embutidas na descarga offline.
- **Secções** — ligue/desligue a história, o interlúdio, a contagem decrescente, o cronograma, o manual, as pétalas e a música.
- **Textos** — edite as palavras do convite (aceita `<br>` para quebrar linhas).
- **Data e casal** — os nomes e a data alimentam automaticamente o título, a contagem decrescente, o dia da semana e o botão de calendário.

Tudo é mostrado numa **pré-visualização ao vivo**; ao **Guardar**, o convite público passa a refletir o novo modelo. O design fica guardado em `cw_designs` — o convite `assets/convite-base.html` permanece intacto, apenas recebe as personalizações na altura de servir.

**Convite impresso (Fase 2).** A partir do **mesmo design**, a página *Convite impresso* gera um cartão pronto a imprimir com **sangria de 3 mm** e **marcas de corte**, em três **tamanhos** (A5 148×210, A6 105×148 ou quadrado 140×140), **molduras** à escolha (dupla, simples, cantos, vinha) e **verso** opcional (com monograma e citação). Botão *Imprimir / Guardar PDF*. Assim, um único desenho serve o convite digital **e** o convite físico. A conversão para CMYK é o passo de pré-impressão feito pela gráfica.

**Editor de tela (Fase 2).** Para liberdade total, a página *Editor de tela* abre uma **tela de desenho** (Fabric.js) já preenchida com o modelo do design. Pode **adicionar e mover** texto, imagens e formas, gerir **camadas**, **desfazer/refazer**, e **exportar PNG (300 dpi) ou PDF** no tamanho exato. O desenho é guardado em `cw_impressos`. As bibliotecas (Fabric.js e jsPDF) são servidas localmente em `assets/vendor/`.

---

## Notas importantes

- A leitura de QR por câmara exige **HTTPS**. No InfinityFree, ative o certificado SSL grátis e aceda por `https://`.
- Editar um convite preserva as confirmações e presenças já registadas.
- O check-in à entrada só altera a presença — nunca apaga a confirmação feita pelo convidado.
- O código QR aponta para o link do convite, pelo que serve tanto ao convidado (abre o seu convite) como ao porteiro (identifica-o à entrada).
- O sistema não depende de extensões opcionais (mbstring, gd, intl), pelo que funciona em alojamento partilhado simples.
