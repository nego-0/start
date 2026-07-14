# Análise do estado — Plataforma de Convites

*Documento de análise técnica · gerado a partir da revisão do código em `casamento_web/`.*

Resumo em três partes: **(1) estado atual**, **(2) o que pode ser corrigido** e
**(3) o que implementar a seguir**, por ordem de prioridade.

---

## 1. Estado atual

A aplicação evoluiu de um convite de **um** casamento (Isabel & Abednego) para
uma **plataforma multi-casal**. O que está construído e verificado:

| Fase | Entregue | Ficheiros principais |
|---|---|---|
| **0 — Multi-inquilino** | Contas de casais, eventos, isolamento por `evento_id`; convidados/RSVP por conta; RSVP público consciente do evento | `conta.php`, `registar.php`, `entrar.php`, `painel-casal.php`, `convidados.php`, `convite.php` |
| **1 — Modelos** | Galeria de 4 modelos, editor de cores/tipografia/secções/textos com pré-visualização ao vivo | `modelos.php`, `design.php`, `editor-modelos.php`, `assets/convite-base.html` |
| **2 — Editor** | Convite impresso (A5/A6/quadrado, molduras, verso, marcas de corte); editor de tela Fabric.js; edição de imagem (brilho/contraste/saturação, P&B/sépia); export PNG/PDF | `convite-impresso.php`, `editor-tela.php`, `impresso.php`, `assets/vendor/` |
| **3 — Ecossistema** | Plano de mesas visual (arrastar-para-sentar); convites/lembretes por WhatsApp | `mesas-plano.php`, `envios.php` |

**Base técnica:** PHP 8 + MySQL (mysqli, prepared statements), sem framework nem
*build*; bibliotecas de front-end (Fabric.js, jsPDF) servidas localmente. Cerca
de 5 200 linhas de PHP. Testes da camada de tema (`teste-design.php`, 22
verificações) e verificação end-to-end por navegador em cada funcionalidade.

**Duas realidades de acesso convivem:**
- **Admin legado** (palavra-passe em `config.php`) → gere o **evento 1** com
  todas as funcionalidades antigas (convidados via `api.php`/`index.php`,
  porteiro, etiquetas).
- **Contas de casais** (email + palavra-passe) → gerem os seus próprios eventos
  (design, impresso, convidados, mesas, WhatsApp), **isolados** por evento.

---

## 2. O que pode ser corrigido

### 2.1 Segurança — **prioritário antes de abrir ao público**

| # | Problema | Onde | Risco | Correção sugerida |
|---|---|---|---|---|
| S1 | **Sem proteção CSRF** em qualquer formulário/endpoint de escrita (registo, login, editores, APIs `?api=1`) | todos os `*.php` com POST | Ações forjadas em nome do utilizador autenticado | Token CSRF por sessão, validado em todos os POST |
| S2 | **XSS armazenado** — os textos do convite são inseridos **sem sanitização** (para permitir `<br>`); um casal pode injetar `<script>` no seu convite, servido aos convidados | `design.php` (`mapaTextos`), `convite-base.html` | Execução de script no navegador dos convidados | Permitir apenas um subconjunto seguro (`<br>`, `<b>`, `<i>`) via *allow-list*; escapar o resto |
| S3 | **Conta semeada com login** — o evento 1 é acessível pelo fluxo de casais com o email `principal@local` + a palavra-passe de admin | `db.php` (seed), `conta.php` | Acesso ao casamento real por quem saiba a senha de admin | Semear sem `senha_hash` utilizável (ou email não-loginável) e manter o admin só pelo `login.php` |
| S4 | **Cookies de sessão sem hardening** (`HttpOnly`, `Secure`, `SameSite`) | falta em `auth.php`/`conta.php` | Roubo de sessão | `session_set_cookie_params(['httponly'=>true,'secure'=>true,'samesite'=>'Lax'])` |
| S5 | **Sem limite de tentativas** de login (força bruta) | `entrar.php`, `login.php` | Adivinhação de senha | *Rate limiting* por IP/conta + atraso progressivo |
| S6 | **Senha de admin em texto** no `config.php` (comparada com `hash_equals`) | `config.php`, `auth.php` | Exposição se o ficheiro vazar | Guardar apenas `password_hash`; mover segredos para variáveis de ambiente |

### 2.2 Correção / consistência

| # | Problema | Onde | Correção |
|---|---|---|---|
| C1 | **Gestão de convidados duplicada** — `convidados.php` (novo, por evento) coexiste com `api.php`/`index.php` (legado, só evento 1); regras podem divergir | `api.php`, `index.php`, `convidados.php` | Tornar `api.php`/`index.php` conscientes do evento **ou** aposentá-los a favor das páginas novas |
| C2 | **Porteiro/check-in não isolado por evento** — serve apenas o evento 1 | `porteiro.php`, `api.php` (`porta_*`) | Escopo por evento; permitir check-in às contas de casais |
| C3 | **Contacto WhatsApp errado no RSVP** — `convite.php` usa o número do `config.php` (evento 1) para todos os casais | `convite.php` (linhas do `EVENTO['whatsapp']`) | Campo de contacto por evento (em `cw_eventos`), editável no painel |
| C4 | **Fontes web não embutidas na descarga offline** — modelos com fontes Google não ficam 100% offline | `convite-digital.php` (`embutirRecursos`) | Alojar as woff2 localmente e embuti-las, ou avisar o casal |
| C5 | **Imagens do editor de tela guardadas como *data URI*** no `cw_impressos` (LONGTEXT) — incha a BD, sem limite de tamanho | `editor-tela.php`, `impresso.php` | Armazenamento de objetos (ver 3.1) + limite/《compressão》no upload |
| C6 | **Teto de lugares global** (`MAX_LUGARES_TOTAL=300`) em vez de por evento | `config.php`, `db.php` | Passar o limite para `cw_eventos` |

### 2.3 Dívida técnica / operacional

- **Sem migrações versionadas** — o esquema é criado/alterado *on-the-fly* em
  `db.php`/`design.php`/`impresso.php`. Funciona, mas dificulta evoluções
  controladas. → Adotar ficheiros de migração numerados.
- **Sem testes automatizados** além de `teste-design.php`; **sem CI**. → Testes
  de integração + GitHub Actions (lint + testes).
- **Sem verificação de email nem recuperação de palavra-passe** (o plano
  prevê-as). → Implementar antes de abrir contas ao público.

---

## 3. O que implementar a seguir (por prioridade)

### 3.1 Infraestrutura de produção — *fecha a Fase 0* (a decisão que condiciona tudo)

O alojamento gratuito atual não serve a um produto. Antes de mais:
- **Alojamento** da aplicação/API (VPS ou Railway/Render/Fly).
- **Base de dados gerida** (MySQL/PostgreSQL) com cópias de segurança.
- **Armazenamento de objetos** (Cloudflare R2 / Backblaze B2) para as imagens
  dos casais — resolve C5 e o limite de tamanho.
- **CDN** (Cloudflare) e **envio de email transacional** (verificação de conta,
  recuperação de senha, avisos).

### 3.2 Endurecer a segurança (S1–S6) — *pré-requisito para abrir ao público*

Implementar CSRF, sanitização dos textos do convite, *hardening* de sessão,
*rate limiting* e verificação de email. É o bloco que separa um protótipo de um
serviço que guarda dados de muitos convidados.

### 3.3 Unificar a gestão do convidado por evento (C1, C2)

Tornar `api.php`, `index.php` e `porteiro.php` conscientes do evento (ou
substituí-los pelas páginas novas), para que cada casal tenha o **ciclo
completo**: convidados → RSVP → **check-in à porta com QR** → estatísticas ao
vivo — tudo isolado.

### 3.4 Completar a Fase 3 — experiência do casamento

- **Álbum partilhado** de fotografias (por evento) + mural na festa.
- **Lista de presentes**, incluindo dinheiro / **Multicaixa** (contexto local).
- **Lembretes automáticos** e agradecimentos (agendados), sobre o motor de
  WhatsApp/email já iniciado.

### 3.5 Completar a Fase 2 — impressão profissional

- **Recorte interativo** de imagem no editor de tela.
- **CMYK** e integração com gráfica (passo de pré-impressão, feito no
  servidor com uma biblioteca dedicada — não no navegador).

### 3.6 Modelo de negócio

- **Planos (freemium)** e **pagamentos** — Multicaixa Express (Angola) a par de
  uma opção internacional; *super-administração* para gerir casais e planos.

---

## Recomendação de sequência

1. **Infra de produção** (3.1) — sem isto, o resto não sai do protótipo.
2. **Segurança** (3.2) — antes de qualquer casal real usar a plataforma.
3. **Ciclo do convidado por evento** (3.3) — completa o valor da Fase 0.
4. **Álbum + presentes + lembretes** (3.4) — diferenciação e retenção.
5. **CMYK/gráfica** (3.5) e **planos/pagamentos** (3.6) — monetização.

> Princípio a manter: passos pequenos, verificados, sem nunca partir o
> casamento em produção (evento 1). Cada mudança estruturante deve preservar a
> compatibilidade e ser testada ponta a ponta.
