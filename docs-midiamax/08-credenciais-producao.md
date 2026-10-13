# 08 — Credenciais do `.env` para produção

Toda variável de ambiente que o sistema lê, o que cada uma **liga no produto**,
onde conseguir e o que já está pronto.

Tudo vai para o `.env` do repo **`midiamax-infra`** (privado), carregado pelo
`compose.prod.yaml` via `env_file`. Em produção não existe `.env` dentro do
container — as variáveis chegam como ambiente de verdade.

> Repo público: nenhum valor real aqui. Os gerados ficam em
> `midiamax-infra/.env.gerado` e o detalhe por conta em `CREDENCIAIS.md`,
> ambos no repo privado.

Legenda: 🔴 sem isso não sobe · 🟡 sem isso falta função · ⚪ opcional

---

## Status em 13/10/2026

| | Bloco | Situação |
| --- | --- | --- |
| ✅ | 1 Aplicação | `APP_KEY` gerada |
| ✅ | 2 Banco | senha gerada |
| ✅ | 3 Redis/filas | valores fixos |
| ✅ | 4 Reverb | chaves geradas e replicadas no GitHub |
| ✅ | 5 Passport | par RSA 4096 gerado |
| ❌ | 6 Mídia (R2) | bucket não criado |
| ⚠️ | 7 E-mail | envia, mas remetente de teste só entrega para o dono da conta |
| ✅ | 8 Stripe | chaves, preços mensais e anuais, webhook validado ponta a ponta |
| ❌ | 9 Login social | desligado |
| ⚠️ | 10 Redes sociais | Telegram ok · Discord sem bot token · Meta sem redirects · YouTube em projeto de terceiro · LinkedIn e Pinterest não criados |
| ✅ | 11 IA | Gemini gerando em pt-BR |
| ❌ | 12 Mídia no compositor | não configurado |
| ⚠️ | 13 Observabilidade | falta allowlist do Horizon em produção |
| ✅ | 14 URLs internas | páginas no ar |
| ✅ | 15 Build args | as três configuradas |

Fora do `.env`: **domínio** e **VPS**, que destravam quase todo o resto.

---

## 1. Aplicação 🔴

```env
APP_NAME=MidiaMax
APP_ENV=production
APP_DEBUG=false
APP_LOCALE=pt-BR
APP_FALLBACK_LOCALE=en
TRYPOST_TARGET=production
APP_URL=https://app.midiamax.com.br
APP_DOMAIN=app.midiamax.com.br
WEBHOOK_URL=
SELF_HOSTED=false
TELESCOPE_ENABLED=false
LOG_LEVEL=warning
```

| Variável | O que faz |
| --- | --- |
| `APP_URL` | base de **todas** as URLs geradas, inclusive os redirects de OAuth. Sem barra no fim |
| `APP_DOMAIN` | domínio que o Caddy usa para emitir o certificado |
| `SELF_HOSTED=false` | **liga o modo SaaS**: cadastro aberto e assinatura obrigatória. Com `true`, `/register` responde 404 e só entra quem tem convite |
| `WEBHOOK_URL` | base dos webhooks de entrada (Telegram). Vazio usa `APP_URL` |
| `APP_DEBUG` | `true` em produção expõe stack trace com credenciais |

**`APP_KEY`** — gerar uma vez:

```bash
docker compose -f compose.prod.yaml run --rm app php artisan key:generate --show
```

Perder essa chave = perder todo dado criptografado, incluindo os tokens das
redes sociais dos clientes. Não há recuperação.

---

## 2. Banco de dados 🔴

```env
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=midiamax
DB_USERNAME=midiamax
DB_PASSWORD=
```

Gerar: `openssl rand -base64 24`. O serviço `pgsql` do compose lê a mesma
variável — define uma vez só.

---

## 3. Redis, filas, cache e sessão 🔴

```env
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=database
SESSION_LIFETIME=1440
BROADCAST_CONNECTION=reverb
```

Valores fixos. A fila é o que publica os posts agendados — sem Redis, nada sai
no horário.

---

## 4. Reverb — WebSocket 🔴

Liga a atualização em tempo real do calendário.

```env
REVERB_APP_ID=1001
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=app.midiamax.com.br
REVERB_PORT=443
REVERB_SCHEME=https
```

Gerar as duas chaves com `openssl rand -hex 16`.

**Pegadinha:** `REVERB_APP_KEY` é **compilada no bundle JS** no build da imagem.
Tem que ser idêntica ao `secrets.REVERB_APP_KEY` do GitHub (bloco 15). Se
divergir, o websocket não conecta e **o servidor não loga erro nenhum** — só
aparece no console do browser.

---

## 5. Passport — tokens de API e MCP 🔴

Liga o acesso via API e via MCP (integração com agentes de IA).

```bash
openssl genrsa -out oauth-private.key 4096
openssl rsa -in oauth-private.key -pubout -out oauth-public.key
```

```env
PASSPORT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
```

`\n` literal nas quebras. Sem essas variáveis o Passport cai para arquivos em
`storage/`, que **não são persistidos pelos volumes**: cada recriação de
container gera chaves novas e derruba todo token de API/MCP dos clientes.

---

## 6. Armazenamento de mídia 🔴

Onde ficam as imagens e vídeos que o cliente envia.

**Cloudflare R2** — https://dash.cloudflare.com → R2 → criar bucket →
*Manage R2 API Tokens*

```env
FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
R2_REGION=auto
R2_BUCKET=midiamax
R2_URL=
```

`R2_URL` é a URL **pública** do bucket. Sem ela a mídia não renderiza — ative o
acesso público ou um domínio customizado.

Alternativas: `s3` (AWS), `spaces` (DigitalOcean), `local` (não escala e
complica backup).

---

## 7. E-mail 🔴

Liga verificação de e-mail, reset de senha e convite de equipe. O default da
imagem é `log` — sem SMTP real, nada disso chega.

**Resend** — https://resend.com → API Keys

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=nao-responda@midiamax.com.br
MAIL_FROM_NAME=MidiaMax
```

`MAIL_PASSWORD` é a API key (`re_...`).

Três coisas descobertas testando:

1. **`MAIL_SCHEME=tls` não existe no Laravel** e derruba todo envio com
   *"The tls scheme is not supported"*. Use `smtp` (STARTTLS/587) ou `smtps`
   (TLS/465). O `compose.prod.yaml` do upstream traz `tls`, que está errado.
2. **`MAIL_MAILER=resend` não funciona** — o mailer existe no `config/mail.php`
   mas o pacote `resend/resend-laravel` não está instalado. Vá por SMTP.
3. Com o remetente de teste `onboarding@resend.dev`, o Resend **só entrega para
   o e-mail dono da conta**. Verifique um domínio em
   https://resend.com/domains e configure SPF e DKIM, ou todo e-mail para
   cliente é recusado.

---

## 8. Stripe — assinaturas 🔴

Obrigatório porque `SELF_HOSTED=false`.

### 8.1 Chaves — https://dashboard.stripe.com/apikeys

```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
```

**Os nomes importam:** o Cashier lê `STRIPE_KEY` e `STRIPE_SECRET`. Com
`STRIPE_PUBLISHABLE_KEY`/`STRIPE_SECRET_KEY` (convenção de outros stacks) elas
são ignoradas em silêncio — parece configurado e não está.

Use `pk_test_`/`sk_test_` fora de produção. Chave live em ambiente de teste
gera cobrança real.

### 8.2 Preços

Dashboard → Produtos → um produto por plano, com preço mensal e anual em BRL:

```env
STRIPE_SOCIALS_MONTHLY=price_...
STRIPE_SOCIALS_YEARLY=price_...
STRIPE_WORKSPACES_MONTHLY=price_...
STRIPE_WORKSPACES_YEARLY=price_...
STRIPE_WORKSPACE_MONTHLY=
STRIPE_WORKSPACE_YEARLY=
```

(`workspace` é plano legado, arquivado no seeder — pode ficar vazio.)

Depois de preencher, rodar o seeder, que é quem grava os IDs nos planos:

```bash
docker compose -f compose.prod.yaml exec app php artisan db:seed --class=PlanSeeder --force
```

### 8.3 O preço EXIBIDO é outra variável 🔴

O valor na tela **não vem do Stripe**: o upstream cravou em
`lang/<locale>/billing.php` e nós sobrescrevemos por config. Estes têm que bater
com os preços reais, senão o cliente vê um valor e é cobrado outro:

```env
MIDIAMAX_PRICE_SOCIALS_MONTHLY="R$ 129,99"
MIDIAMAX_PRICE_SOCIALS_YEARLY="R$ 1.299,90"
MIDIAMAX_PRICE_SOCIALS_YEARLY_PER_MONTH="R$ 108,32"
MIDIAMAX_PRICE_WORKSPACES_MONTHLY="R$ 369,99"
MIDIAMAX_PRICE_WORKSPACES_YEARLY="R$ 3.699,90"
MIDIAMAX_PRICE_WORKSPACES_YEARLY_PER_MONTH="R$ 308,32"
```

### 8.4 Webhook 🔴

É o que mantém o app em dia com o Stripe. Sem ele, cancelamento e falha de
pagamento não chegam e o cliente continua usando.

Dashboard → Developers → Webhooks → **Add endpoint**:

```
https://app.midiamax.com.br/api/v1/webhooks/cobranca/stripe
```

(ou `.../stripe/webhook` — as duas valem, mesmo controller)

Eventos — os 3 primeiros são os que o app processa, os 4 seguintes o Cashier
também trata:

```
customer.subscription.created      ← essencial
customer.subscription.updated      ← essencial
customer.subscription.deleted      ← essencial
customer.updated
customer.deleted
invoice.payment_action_required    ← 3DS, comum em cartão brasileiro
payment_method.automatically_updated
```

```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

**O `whsec_` é por endpoint.** Usar o de outro faz todo evento ser rejeitado com
403. Ao migrar para o domínio definitivo, **edite a URL do endpoint existente**
em vez de criar outro — assim o secret é preservado.

A tolerância de timestamp é de **300 segundos**: se o relógio da VPS estiver
fora de sincronia, todo webhook é rejeitado. Garanta NTP ativo.

### 8.5 Trial

```env
REQUIRE_CARD_FOR_TRIAL=true
CASHIER_TRIAL_DAYS=8
CASHIER_ALLOW_PROMOTION_CODES=false
STRIPE_SOCIALS_FIRST_MONTH_COUPON_ID=
STRIPE_WORKSPACES_FIRST_MONTH_COUPON_ID=
```

`REQUIRE_CARD_FOR_TRIAL=false` libera o app pelo trial **sem cartão** — é o que
permite rodar sem Stripe nenhum em desenvolvimento.

---

## 9. Login social 🟡

Liga os botões "Entrar com Google" e "Entrar com GitHub" na tela de login.

**Os dois vêm desligados por padrão.** E a flag **não checa credencial**: ligar
sem configurar o OAuth faz o botão aparecer e quebrar no clique.

### Google

Usa **o mesmo cliente OAuth do YouTube** (`GOOGLE_CLIENT_ID`/`SECRET` do bloco
10.7), com um redirect diferente:

```env
GOOGLE_AUTH_ENABLED=true
GOOGLE_AUTH_CALLBACK=https://app.midiamax.com.br/auth/google/callback
```

No Google Cloud Console, o cliente OAuth precisa ter **os dois** redirects
cadastrados:

```
https://app.midiamax.com.br/auth/google/callback       ← login
https://app.midiamax.com.br/accounts/youtube/callback  ← conectar canal
```

Para só login (sem YouTube), basta um projeto com a tela de consentimento
preenchida — não precisa habilitar as APIs do YouTube nem pedir quota.

### GitHub

https://github.com/settings/developers → New OAuth App

```env
GITHUB_AUTH_ENABLED=false
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_AUTH_CALLBACK=https://app.midiamax.com.br/auth/github/callback
```

Para um produto voltado a pequenos negócios e agências, login com GitHub
raramente faz sentido — deixe `false`.

---

## 10. Redes sociais 🟡

Apps **seus**, nunca os do upstream. Redirect padrão:
`https://app.midiamax.com.br/accounts/<plataforma>/callback`, idêntico ao
cadastrado no portal (protocolo, subdomínio, sem barra final).

### 10.1 Telegram — sai no mesmo dia

https://t.me/BotFather → `/newbot`

```env
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=
TELEGRAM_BOT_USERNAME=
TELEGRAM_WEBHOOK_SECRET=
```

`TELEGRAM_WEBHOOK_SECRET`: `openssl rand -hex 32`. Depois:

```bash
docker compose -f compose.prod.yaml exec app php artisan telegram:set-webhook
```

Um bot único compartilhado — cada cliente adiciona o seu bot ao canal dele.

### 10.2 Discord — sai no mesmo dia

https://discord.com/developers/applications → New Application

- **OAuth2** → Client ID, Client Secret e cadastrar o redirect
- **Bot** → *Reset Token* e ligar **Server Members Intent**

```env
DISCORD_ENABLED=true
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_BOT_TOKEN=
DISCORD_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/discord/callback
```

**Quem publica é o `DISCORD_BOT_TOKEN`**, não o token OAuth do usuário — o OAuth
só autoriza a entrada do bot no servidor do cliente. Escopos: `bot`, `identify`,
`guilds`. Permissões: bitfield `248832`.

### 10.3 LinkedIn

https://developer.linkedin.com → Create app (exige uma Página de empresa sua).
Em *Products*: **Sign In with LinkedIn using OpenID Connect** e
**Share on LinkedIn**.

```env
LINKEDIN_ENABLED=true
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
LINKEDIN_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/linkedin/callback
LINKEDIN_PAGE_ENABLED=false
```

Publicar em **Página de empresa** exige a Community Management API, que é
programa de parceiro — deixe `LINKEDIN_PAGE_ENABLED=false` até ter aprovação.

### 10.4 Pinterest

https://developers.pinterest.com → My apps (conta business)

```env
PINTEREST_ENABLED=true
PINTEREST_CLIENT_ID=
PINTEREST_CLIENT_SECRET=
PINTEREST_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/pinterest/callback
```

Escopos: `boards:read`, `boards:write`, `pins:read`, `pins:write`,
`user_accounts:read`. App começa em trial; produção exige review.

### 10.5 Meta — Facebook e Instagram

https://developers.facebook.com → criar app tipo **Business**

**Configurações → Básico**: App ID e Chave Secreta. Preencher também as URLs de
privacidade, termos e exclusão de dados (bloco 14) — o portal exige para
publicar o app.

**Facebook Login** → dois redirects:

```
https://app.midiamax.com.br/accounts/facebook/callback
https://app.midiamax.com.br/accounts/instagram-facebook/callback
```

**Produto Instagram** → *API setup with Instagram login* → credenciais próprias e
o redirect `https://app.midiamax.com.br/accounts/instagram/callback`

```env
FACEBOOK_ENABLED=true
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/facebook/callback
INSTAGRAM_ENABLED=true
INSTAGRAM_FACEBOOK_ENABLED=true
INSTAGRAM_CLIENT_ID=
INSTAGRAM_CLIENT_SECRET=
INSTAGRAM_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/instagram/callback
META_PAGE_WALK_SECONDS=20
```

São **dois caminhos de conexão** que aparecem como "Instagram": o direto
(credenciais do Instagram) e o via Página (credenciais do Facebook). Vale ter os
dois — o segundo é o que funciona para conta administrada por agência.

Em modo desenvolvimento você conecta **as suas próprias contas** e testa tudo.
Para atender clientes: App Review dos escopos e Verificação do Negócio com CNPJ.

Pré-requisito do cliente: conta Instagram **Business ou Creator** vinculada a
uma Página.

### 10.6 TikTok

https://developers.tiktok.com → Manage apps → produto **Content Posting API**

```env
TIKTOK_ENABLED=true
TIKTOK_CLIENT_ID=
TIKTOK_CLIENT_SECRET=
TIKTOK_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/tiktok/callback
```

Publicar direto no perfil exige o **audit de Direct Post**; sem ele o conteúdo
só vira rascunho no app do usuário. App novo não recebe mais Display API — se a
autorização falhar:

```env
TIKTOK_SCOPES="user.info.basic,video.publish,video.upload"
```

### 10.7 YouTube e Shorts

https://console.cloud.google.com → projeto **dedicado**

1. Habilitar **YouTube Data API v3** e **YouTube Analytics API**
2. Tela de permissão OAuth preenchida
3. Credenciais → ID do cliente OAuth → **Aplicativo da Web** → cadastrar
   `/accounts/youtube/callback` e `/auth/google/callback`

```env
YOUTUBE_ENABLED=true
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/youtube/callback
```

Escopos `youtube.upload` e `youtube.readonly` são sensíveis e exigem verificação
do app.

**Peça aumento de quota no dia 1.** O default do Data API é 10.000 unidades/dia
e um upload custa ~1.600 → **~6 vídeos/dia para a plataforma inteira**, somando
todos os clientes.

Shorts não tem API separada: é a Data API, e o YouTube classifica pelo formato
(vertical, até 3 min).

Use um projeto Google **dedicado** — reaproveitar projeto de outro produto faz a
tela de consentimento mostrar o nome errado ao seu cliente.

### 10.8 Desligar o que não usamos 🔴

`Platform::isEnabled()` olha **só a flag, nunca a credencial**, e todas default
para `true`. Sem isto o cliente vê botão "Conectar" que estoura no OAuth:

```env
X_ENABLED=false
THREADS_ENABLED=false
BLUESKY_ENABLED=false
MASTODON_ENABLED=false
GOOGLE_BUSINESS_ENABLED=false
```

---

## 11. Inteligência artificial 🟡

Liga geração e ajuste de legenda no compositor. Sem chave, o app esconde os
recursos de IA sozinho.

**Gemini** — https://aistudio.google.com/apikey

```env
AI_TEXT_PROVIDER=gemini
GEMINI_API_KEY=
GEMINI_TEXT_MODEL=gemini-flash-lite-latest
AI_AUDIO_PROVIDER=
ELEVENLABS_API_KEY=
```

**Fixe o modelo com um alias `-latest`.** Modelos são aposentados pelo provedor e
o driver reporta *"AI provider is overloaded"* — mensagem que despista, porque a
causa real é um 404 *"no longer available to new users"*. Diagnostique chamando
a API direto:

```bash
curl -s "https://generativelanguage.googleapis.com/v1beta/models?key=$GEMINI_API_KEY"
```

Outros provedores: `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `OPENROUTER_API_KEY`
(troca de modelo sem mexer em config).

⚠️ **O custo é seu, não do cliente.** Cada legenda sai da sua chave, e **não
existe limite de IA por plano no código** — a tabela `workspace_ai_usages`
existe mas nenhum código a usa.

---

## 12. Fontes de mídia no compositor ⚪

Liga os botões de importar imagem de dentro do editor de post.

```env
UNSPLASH_ACCESS_KEY=
UNSPLASH_SECRET_KEY=
GOOGLE_DRIVE_ENABLED=false
GOOGLE_PHOTOS_ENABLED=false
GOOGLE_MEDIA_CLIENT_ID=
GOOGLE_MEDIA_CLIENT_SECRET=
GOOGLE_MEDIA_CLIENT_REDIRECT=https://app.midiamax.com.br/integrations/google/callback
GOOGLE_MEDIA_API_KEY=
GOOGLE_MEDIA_APP_ID=
CANVA_ENABLED=false
CANVA_CLIENT_ID=
CANVA_CLIENT_SECRET=
```

- Unsplash: https://unsplash.com/developers
- Drive e Photos: projeto Google **separado** do YouTube. Nada herda de
  `GOOGLE_CLIENT_ID`. Passo a passo em `MEDIA_INTEGRATIONS_SETUP.md`
- Canva: exige integração pública aprovada por eles

Cada fonte só aparece quando a flag está ligada **e** as chaves preenchidas.

---

## 13. Observabilidade ⚪ (com um item 🔴)

```env
HORIZON_ALLOWED_EMAILS=voce@midiamax.com.br
NIGHTWATCH_ENABLED=false
NIGHTWATCH_TOKEN=
POSTHOG_ENABLED=false
POSTHOG_API_KEY=
POSTHOG_HOST=https://us.i.posthog.com
GTM_ID=
```

🔴 **`HORIZON_ALLOWED_EMAILS` é obrigatório em produção.** É a lista de e-mails
que acessa `/horizon`, o painel de filas e jobs falhando — onde você descobre
que um post agendado não publicou. Em `APP_ENV=local` o gate nem é aplicado e
`/horizon` fica **aberto**; em produção, sem a variável, ninguém entra.

Telescope tem gate com lista vazia cravada no código: ninguém acessa fora de
local. Mantenha `TELESCOPE_ENABLED=false`.

---

## 14. URLs das páginas internas 🔴

Sem as duas primeiras, os links de Termos e Privacidade do app apontam para o
**site do projeto original**. As páginas são servidas pela própria aplicação.

```env
LEGAL_TERMS_URL=https://app.midiamax.com.br/termos
LEGAL_PRIVACY_URL=https://app.midiamax.com.br/privacidade
MIDIAMAX_SOURCE_URL=https://github.com/LeonardoMarcelo/midiamax-app
MIDIAMAX_LEGAL_NAME=
MIDIAMAX_CNPJ=
MIDIAMAX_ADDRESS=
MIDIAMAX_SUPPORT_EMAIL=suporte@midiamax.com.br
MIDIAMAX_PRIVACY_EMAIL=privacidade@midiamax.com.br
MIDIAMAX_JURISDICTION=
MIDIAMAX_LEGAL_UPDATED_AT=
```

Os campos `MIDIAMAX_*` preenchem os dados do responsável nas páginas; a página
omite a linha quando o valor está vazio. Os portais da Meta e da TikTok pedem
essas URLs no cadastro do app, e a Meta pede também
`https://app.midiamax.com.br/exclusao-de-dados`.

`MIDIAMAX_VERSION` e `MIDIAMAX_COMMIT` são preenchidos pelo `scripts/deploy.sh`
a partir dos labels da imagem — não editar à mão.

---

## 15. Build args da imagem 🔴

**Não vão no `.env`**: entram no bundle JS no build. Em `midiamax-app` →
Settings → Secrets and variables → **Actions**:

| Tipo | Nome | Valor |
| --- | --- | --- |
| Variable | `APP_NAME` | `MidiaMax` |
| Variable | `APP_DOMAIN` | `app.midiamax.com.br` |
| Secret | `REVERB_APP_KEY` | **idêntico** ao do `.env` |

Mudar qualquer um exige **rebuild da imagem** — reiniciar o container não basta.

Para baixar a imagem na VPS: `docker login ghcr.io -u LeonardoMarcelo` com um PAT
que tenha `read:packages`.

---

## 16. Verificação final

```bash
for p in / /login /register /termos /privacidade /exclusao-de-dados /codigo-fonte; do
  printf "%-22s %s\n" "$p" "$(curl -s -o /dev/null -w '%{http_code}' https://app.midiamax.com.br$p)"
done
```

Todas 200. Depois, no produto:

- [ ] cadastrar uma conta e cair no calendário
- [ ] e-mail de verificação chega
- [ ] só as plataformas configuradas aparecem em *Contas*
- [ ] conectar uma rede e publicar um post agendado
- [ ] websocket conecta (console do browser sem erro)
- [ ] checkout em modo de teste completa e a assinatura aparece no app
- [ ] cancelar no Stripe e ver o acesso cair (prova que o webhook funciona)
- [ ] `/horizon` acessível só para os e-mails da allowlist
