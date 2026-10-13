# 07 — Passo a passo: subir local 100% funcionando

Receita testada em 13/10/2026 nesta máquina, com a aplicação no ar. Para o que
cada variável significa e onde pegar cada credencial, ver
`06-variaveis-ambiente.md`.

---

## Três coisas que descobrimos e que explicam o que parecia quebrado

### 1. Não existe landing page neste repositório

`resources/js/pages/` tem só telas do app (auth, calendário, billing, settings…).
A rota `/` está dentro de `Route::middleware(['auth'])` e só redireciona para o
calendário. O site de marketing do TryPost (trypost.it) é **outro projeto**, que
não vem no código aberto.

Ou seja: a landing page da MidiaMax é trabalho nosso, e é uma decisão separada
(site estático próprio × rota dentro do app). O produto funciona sem ela — a
porta de entrada é `/login` e `/register`.

### 2. O cadastro dava 404 porque `SELF_HOSTED=true`

`app/Http/Middleware/App/EnsureRegistrationEnabled.php` devolve
`NotFoundHttpException` quando `trypost.self_hosted` é verdadeiro e não há
convite válido na requisição. É intencional: em modo self-hosted não existe
cadastro aberto, só convite.

Modo SaaS (`SELF_HOSTED=false`) libera `/register`.

### 3. Os links de Termos e Privacidade apontavam para o trypost.it

`config/trypost.php` tem `legal.terms_url` e `legal.privacy_url` com default
`https://trypost.it/terms` e `/privacy`. São `LEGAL_TERMS_URL` e
`LEGAL_PRIVACY_URL`.

Como não temos domínio ainda — e como app review da Meta e da TikTok exige essas
URLs públicas e visíveis — as páginas agora são servidas pela própria aplicação,
em `routes/midiamax.php`:

| Rota | Para quê |
| --- | --- |
| `/termos` | Termos de Uso |
| `/privacidade` | Política de Privacidade (LGPD) |
| `/exclusao-de-dados` | Instruções de exclusão — a Meta exige esta URL |
| `/codigo-fonte` | Oferta de fonte da AGPL §13 |

> **O conteúdo é um documento de trabalho, não parecer jurídico.** Cobre as
> seções que Meta, TikTok e LGPD verificam, mas **tem que passar por advogado
> antes de submeter app review e antes de cobrar do primeiro cliente.**

---

## Passo 0 — Pré-requisitos

Docker e ngrok. **PHP e Composer não são necessários** — tudo roda dentro do
container.

```bash
docker --version
ngrok version
```

## Passo 1 — Subir a stack

```bash
cd ~/Documents/ferramentas/pessoal/socialcrm
docker compose up -d
```

O primeiro boot faz `composer install`, `npm ci`, migrations e build do Vite:
leva alguns minutos. Esperar o health endpoint:

```bash
until curl -fsS http://127.0.0.1:8000/up >/dev/null; do sleep 3; done; echo "no ar"
```

A porta 8080 já está ocupada pelo OrbStack nesta máquina, então
`compose.override.yaml` (gitignored) joga o Reverb para a 8081. Não precisa
passar variável na mão.

## Passo 2 — Ligar o modo SaaS sem precisar de Stripe

Este é o ponto que destrava o cadastro. No `.env`:

```env
SELF_HOSTED=false
REQUIRE_CARD_FOR_TRIAL=false
APP_NAME="MidiaMax"
APP_LOCALE=pt-BR
```

Por que funciona sem Stripe: `Account::hasAppAccess()` retorna
`subscribed() || (! requiresCardForTrial && isOnTrial())`. Com
`REQUIRE_CARD_FOR_TRIAL=false`, `CreateUser` já atribui o plano `socials` e
`trial_ends_at` no cadastro, e o trial sozinho libera o app.

**Não defina `CASHIER_TRIAL_DAYS` achando que precisa** — e se definir, saiba o
que acontece: `config/cashier.php` não faz cast para int e o Carbon exige
`int|float`, então a string `'8'` quebra o cadastro com TypeError. Nossa
`MidiaMaxServiceProvider::normalizeUpstreamConfig()` corrige isso (bug do
upstream, ver `CUSTOMIZATIONS.md` seção 4), mas o caso vale conhecer.

## Passo 3 — Dados da empresa nas páginas legais

Também no `.env`. As páginas omitem a linha quando o valor está vazio, então
nunca aparece placeholder — mas app review exige identificação do responsável:

```env
MIDIAMAX_LEGAL_NAME="Razão Social LTDA"
MIDIAMAX_CNPJ="00.000.000/0001-00"
MIDIAMAX_ADDRESS="Rua, nº, cidade/UF, CEP"
MIDIAMAX_SUPPORT_EMAIL=suporte@midiamax.com.br
MIDIAMAX_PRIVACY_EMAIL=privacidade@midiamax.com.br
MIDIAMAX_JURISDICTION="Comarca de ..."
MIDIAMAX_LEGAL_UPDATED_AT="13/10/2026"
```

## Passo 4 — Seed dos planos

```bash
docker compose exec app php artisan db:seed --class=PlanSeeder --force
docker compose exec app php artisan config:clear
```

Cria `socials`, `workspaces` e `workspace`. Os campos de price ID do Stripe ficam
nulos, o que é o esperado enquanto não há conta Stripe.

## Passo 5 — ngrok (HTTPS público para o OAuth)

Nenhuma plataforma aceita `localhost` como redirect. Em outro terminal:

```bash
ngrok http 8000 --url=seu-dominio.ngrok-free.app
```

O `--url` usa o **domínio estático** que a conta gratuita do ngrok oferece
(https://dashboard.ngrok.com/domains). Sem ele a URL muda a cada reinício e você
tem que recadastrar o redirect em 8 portais. Com ele, cadastra uma vez.

Depois, com o túnel no ar:

```bash
scripts/midiamax/dev-ngrok.sh
```

O script lê a URL do túnel pela API local do ngrok e reescreve no `.env`:
`APP_URL`, `WEBHOOK_URL`, `LEGAL_TERMS_URL`, `LEGAL_PRIVACY_URL` e os 8
redirects. No fim ele imprime a lista exata de URIs para colar em cada portal.

Duas coisas esperadas pelo túnel:

- o **websocket (Reverb) não passa pelo ngrok** — fica em localhost. Pelo túnel o
  calendário não atualiza em tempo real; não é defeito do setup;
- na primeira visita o ngrok gratuito mostra uma página de aviso. Abra o domínio
  no browser **uma vez** antes de testar OAuth, senão o callback cai nela.

`trustProxies(at: '*')` já está configurado em `bootstrap/app.php`, então o
Laravel reconhece o HTTPS do túnel e gera as URLs corretamente. Nada a fazer.

## Passo 6 — Credenciais das plataformas

Ordem recomendada: as que funcionam **hoje** primeiro, para validar o produto
ponta a ponta enquanto o resto está em análise.

### 6.1 Telegram e Discord — sem app review, saem no mesmo dia

**Telegram** — https://t.me/BotFather → `/newbot`

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_BOT_USERNAME=
TELEGRAM_WEBHOOK_SECRET=     # openssl rand -hex 32
```

```bash
docker compose exec app php artisan telegram:set-webhook
```

**Discord** — https://discord.com/developers/applications → New Application

- OAuth2 → Redirects: a URL que o script imprimiu
- Bot → Reset Token (é o `DISCORD_BOT_TOKEN`) e ligar **Server Members Intent**

```env
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_BOT_TOKEN=
```

A postagem usa o **bot token**, não o token OAuth do usuário — o OAuth só
autoriza a entrada do bot no servidor do cliente.

### 6.2 LinkedIn e Pinterest — aprovação rápida

**LinkedIn** — https://developer.linkedin.com (precisa de uma Página de empresa
sua). Em *Products*, pedir **Sign In with LinkedIn using OpenID Connect** e
**Share on LinkedIn**.

```env
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
LINKEDIN_PAGE_ENABLED=false    # Página exige Community Management API (parceiro)
```

**Pinterest** — https://developers.pinterest.com (conta business).

```env
PINTEREST_CLIENT_ID=
PINTEREST_CLIENT_SECRET=
```

### 6.3 Meta, TikTok e YouTube — abrir os pedidos agora

Credenciais saem na hora (modo desenvolvimento, só com as suas próprias contas);
o que leva semanas é o App Review para atender clientes.

**Meta** (Facebook + Instagram) — https://developers.facebook.com, app tipo
Business. Cadastrar os **três** redirects que o script imprimiu. Em *Configurações
Básicas*, preencher as URLs de Termos, Privacidade e Exclusão de dados — são as
nossas rotas no domínio do ngrok.

```env
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
INSTAGRAM_CLIENT_ID=
INSTAGRAM_CLIENT_SECRET=
```

**TikTok** — https://developers.tiktok.com, produto **Content Posting API**.

```env
TIKTOK_CLIENT_ID=
TIKTOK_CLIENT_SECRET=
```

Se a autorização falhar em app novo, enxugar:
`TIKTOK_SCOPES="user.info.basic,video.publish,video.upload"`.

**YouTube** — https://console.cloud.google.com → habilitar **YouTube Data API
v3** e **YouTube Analytics API** → Credenciais → ID do cliente OAuth tipo
*Aplicativo da Web*.

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

Pedir aumento de quota no mesmo dia: o default dá ~6 uploads/dia para a
plataforma inteira.

### 6.4 Desligar o que não vamos usar

Obrigatório: `Platform::isEnabled()` olha só a flag, nunca a credencial, e todas
default para `true`. Sem isto o cliente vê botão "Conectar" que estoura no OAuth.

```env
X_ENABLED=false
THREADS_ENABLED=false
BLUESKY_ENABLED=false
MASTODON_ENABLED=false
GOOGLE_BUSINESS_ENABLED=false
```

### 6.5 Opcionais

```env
MAIL_MAILER=smtp     # sem SMTP real, convite e reset de senha não chegam
                     # (o compose local já sobe o Mailpit em http://localhost:8025)
OPENAI_API_KEY=      # vazio desliga as features de IA
```

## Passo 7 — Aplicar e conferir

```bash
docker compose exec app php artisan config:clear
docker compose restart app
```

```bash
# todas têm que responder 200
for p in /login /register /termos /privacidade /exclusao-de-dados /codigo-fonte; do
  printf "%-22s %s\n" "$p" "$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8000$p)"
done
```

Checklist manual:

- [ ] `/register` abre e cria conta
- [ ] depois do cadastro cai no app (não na tela de assinatura) — é o trial sem cartão
- [ ] rodapé das páginas legais aponta para as **nossas** rotas, não para trypost.it
- [ ] só as 8 plataformas aparecem em *Contas*
- [ ] conectar Telegram ou Discord e publicar um post de teste
- [ ] e-mail de verificação chega no Mailpit (http://localhost:8025)

## Troubleshooting

| Sintoma | Causa |
| --- | --- |
| `/register` dá 404 | `SELF_HOSTED` ainda é `true`, ou falta `config:clear` |
| Cadastro dá erro 500 com TypeError do Carbon | `CASHIER_TRIAL_DAYS` como string e provider não carregado — conferir `CUSTOMIZATIONS.md` seção 4 |
| Depois do cadastro cai em "assinatura necessária" | `REQUIRE_CARD_FOR_TRIAL` não é `false`, ou faltou o `PlanSeeder` |
| OAuth dá `redirect_uri_mismatch` | URL do portal diferente do `.env` — rodar `dev-ngrok.sh` e recolar |
| Callback do OAuth cai na página de aviso do ngrok | abrir o domínio no browser uma vez antes |
| Calendário não atualiza em tempo real pelo ngrok | esperado: o Reverb não passa pelo túnel |
| Variável nova não surte efeito | `config:clear` e reiniciar o container |
| Links legais ainda vão para trypost.it | `LEGAL_TERMS_URL`/`LEGAL_PRIVACY_URL` vazias |
