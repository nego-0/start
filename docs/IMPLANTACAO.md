# Implantação — Plataforma de Convites

Guia para colocar a plataforma em produção com segurança. Assume os ficheiros
de `casamento_web/`.

---

## 1. Requisitos

- **PHP 8.0+** (testado em 8.4) com as extensões `mysqli` (obrigatória), e de
  preferência `gd` (redimensiona imagens), `fileinfo` (valida áudio por
  conteúdo) e `mbstring`.
- **MySQL / MariaDB** (a base pode estar no mesmo alojamento).
- **HTTPS** — essencial: a leitura de QR pela câmara do porteiro **só funciona
  em `https://`**, e os cookies de sessão são marcados `Secure`.
- Espaço em disco para a pasta `uploads/` (fotos e músicas por evento).

Verifique tudo de uma vez:

```bash
php verificar.php
```

O comando reporta `OK / AVISO / FALHA` para PHP, extensões, escrita em
`uploads/`, senhas, ligação à base de dados e URL base. **Não imprime segredos.**
Na Web, só o super-administrador lhe pode aceder.

---

## 2. Configuração por variáveis de ambiente

Os segredos **não** ficam no repositório. O `config.php` lê variáveis de
ambiente e só recorre aos valores por defeito quando elas não existem.
Copie `.env.example` e defina os valores no seu alojamento (painel do host,
`.env` do servidor, variáveis do systemd/Docker, etc.).

Mínimo para produção:

| Variável | Para quê |
|---|---|
| `CW_SENHA_ADMIN` | **Hash** da senha de administrador (ver §3) |
| `CW_SENHA_PORTEIRO` | **Hash** da senha do porteiro |
| `CW_DB_MODE` | `online` para forçar a ligação de produção |
| `CW_DB_HOST` / `CW_DB_USER` / `CW_DB_PASS` / `CW_DB_NAME` | Credenciais da base |
| `CW_BASE_URL` | URL público (ex.: `https://convites.exemplo.pt`) — links e QR corretos |
| `CW_TZ` | Fuso horário IANA (ex.: `Africa/Luanda`, `Europe/Lisbon`) |

As tabelas (`cw_*`) são criadas automaticamente na primeira visita.

---

## 3. Senhas (em hash)

Nunca guarde senhas em texto. Gere um hash e coloque-o na variável de ambiente:

```bash
php gerar-hash.php "a-minha-senha-de-admin"
# saída: $2y$... -> defina em CW_SENHA_ADMIN
```

O sistema aceita hash ou texto simples, mas o texto simples só deve servir para
testes locais. O super-administrador entra por `login.php`; os casais entram por
`entrar.php` (email + senha da sua conta).

---

## 4. Pasta de uploads

- `uploads/` tem de ser **gravável** pelo PHP (`chmod 775` costuma bastar).
- É onde ficam as fotos e músicas (`uploads/eventos/{id}/`) e o registo de
  tentativas de login (`uploads/.seg/`). Um `.htaccess` é criado
  automaticamente para impedir a execução de scripts na pasta.
- **Faça backup** de `uploads/` **e** da base de dados. O repositório ignora
  `uploads/`, por isso o seu conteúdo não é versionado.

---

## 5. Publicar

### Alojamento partilhado (ex.: InfinityFree, cPanel)

1. Carregue o conteúdo de `casamento_web/` para a pasta pública (`htdocs`/`public_html`), **incluindo `assets/`**.
2. Defina as variáveis de ambiente no painel do host (ou, se não for possível, edite os valores por defeito no `config.php` — nesse caso mantenha o ficheiro fora de qualquer pasta pública indexável e restrinja o acesso).
3. Ative o **certificado SSL** e force `https://`.
4. Abra o site: as tabelas são criadas sozinhas. Corra `verificar.php` como admin.

### Servidor próprio (VPS)

1. Sirva `casamento_web/` com Apache/Nginx + PHP-FPM; raiz pública nessa pasta.
2. Exporte as variáveis (systemd `EnvironmentFile=`, `.env` lido pelo gestor, ou `SetEnv`/`fastcgi_param`).
3. Certificado TLS (Let's Encrypt) e redireccionamento para HTTPS.
4. `php verificar.php` na consola antes de abrir ao público.

---

## 6. Lista de verificação final

- [ ] `php verificar.php` sem **FALHA**.
- [ ] `CW_SENHA_ADMIN` / `CW_SENHA_PORTEIRO` em **hash** e diferentes do valor por defeito.
- [ ] `CW_DB_*` corretos e `CW_DB_MODE=online`.
- [ ] HTTPS ativo e `CW_BASE_URL` a apontar para o domínio público.
- [ ] `uploads/` gravável e incluída na rotina de backup, com a base de dados.
- [ ] Teste um fluxo completo: criar conta de casal → editar convite → confirmar presença → check-in à porta.
