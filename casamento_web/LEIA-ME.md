# Gestão de Convidados — Isabel & Abednego

Sistema em PHP + MySQL para gerir os convidados do casamento: criação de convites (digitais e físicos), geração dos nomes a exibir, confirmação de presença (RSVP), código QR de entrada e página do porteiro para validar convites à porta do evento.

O sistema foi desenhado para **coexistir** com a sua lista atual: cria tabelas novas com o prefixo `cw_` e não altera as tabelas antigas (`guests`, `invite_groups`, `mesas`). Pode, num clique, importar a lista existente para o novo formato.

---

## Ficheiros

| Ficheiro | Função |
|---|---|
| `config.php` | Configuração central: dados do evento, palavras-passe e ligação à base de dados. **É o único ficheiro que precisa de editar.** |
| `db.php` | Ligação, criação automática das tabelas e funções partilhadas. |
| `auth.php` | Autenticação por sessão (administrador e porteiro). |
| `api.php` | Todos os pedidos JSON (gestão, RSVP público e porteiro) e exportação CSV. |
| `login.php` / `logout.php` | Entrada e saída. |
| `index.php` | Painel de administração (convites, convidados, mesas, importação, QR). |
| `convite.php` | Página pública de confirmação de presença + passe de entrada com QR. |
| `porteiro.php` | Página do porteiro: leitura de QR por câmara e busca manual. |
| `impressos.php` | Etiquetas dos convites físicos com QR, prontas a imprimir. |
| `assets/estilo.css` | Estilo visual, alinhado com o convite (verde-floresta, dourado e marfim). |

---

## Estrutura da base de dados (tabelas novas)

- **`cw_convites`** — o convite é a unidade central: código único, nome a exibir, sufixo opcional, tipo (`digital`/`fisico`/`ambos`), lado, número de lugares, mesa, telefone, estados de RSVP e de entrada, mensagens e observações.
- **`cw_convidados`** — as pessoas nominais de cada convite (com RSVP e presença individuais).
- **`cw_mesas`** — mesas com capacidade e ocupação.

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

---

## Notas importantes

- A leitura de QR por câmara exige **HTTPS**. No InfinityFree, ative o certificado SSL grátis e aceda por `https://`.
- Editar um convite preserva as confirmações e presenças já registadas.
- O check-in à entrada só altera a presença — nunca apaga a confirmação feita pelo convidado.
- O código QR aponta para o link do convite, pelo que serve tanto ao convidado (abre o seu convite) como ao porteiro (identifica-o à entrada).
- O sistema não depende de extensões opcionais (mbstring, gd, intl), pelo que funciona em alojamento partilhado simples.
