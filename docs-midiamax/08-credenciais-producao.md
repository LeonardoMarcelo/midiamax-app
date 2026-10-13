# 08 — Todas as credenciais para ir ao ar em produção

Checklist completa. Cada item traz **onde conseguir**, **o que colar** e se é
obrigatório. Tudo vai para o `.env` do repo **`midiamax-infra`** (privado), que
o `compose.prod.yaml` carrega via `env_file`.

> **Este arquivo é público** (o repo é público por obrigação da AGPL §13).
> Nenhum valor real nosso entra aqui — só o caminho para obter cada um. Os
> valores já descobertos ficam em `CREDENCIAIS.md` no repo privado.

Legenda: 🔴 sem isso não sobe · 🟡 sem isso falta função · ⚪ opcional

---

## 0. Antes das credenciais

| Item | Onde |
| --- | --- |
| 🔴 Domínio | Registro.br, Cloudflare ou similar. Ex.: `midiamax.com.br`, app em `app.midiamax.com.br` |
| 🔴 VPS | Hetzner / DigitalOcean / Contabo. 4 vCPU, 8 GB, 80 GB SSD |
| 🔴 DNS | registro `A` do subdomínio apontando para o IP da VPS, **antes** de subir o Caddy (ele precisa resolver para emitir o certificado) |
| 🟡 CNPJ | já temos. Necessário para Verificação do Negócio na Meta |
| 🟡 Conta de e-mail do domínio | `suporte@` e `privacidade@`. App review da Meta testa esse contato |

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

**`APP_KEY`** — gerar uma vez e **guardar no gerenciador de segredos**:

```bash
docker compose -f compose.prod.yaml run --rm app php artisan key:generate --show
```

Perder essa chave = perder todo dado criptografado, incluindo os tokens das
redes sociais dos clientes. Não há recuperação.

`APP_DEBUG=true` em produção expõe stack trace com credenciais. Nunca.

---

## 2. Banco de dados 🔴

```env
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=midiamax
DB_USERNAME=midiamax
DB_PASSWORD=<senha forte que você inventa>
```

Gerar: `openssl rand -base64 24`. O serviço `pgsql` do compose lê a mesma
variável — define uma vez só.

---

## 3. Redis, filas, cache, sessão 🔴

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

Valores fixos — não precisa conseguir nada em lugar nenhum.

---

## 4. Reverb / WebSocket 🔴

```env
REVERB_APP_ID=1001
REVERB_APP_KEY=<openssl rand -hex 16>
REVERB_APP_SECRET=<openssl rand -hex 16>
REVERB_HOST=app.midiamax.com.br
REVERB_PORT=443
REVERB_SCHEME=https
```

**Pegadinha:** `REVERB_APP_KEY` é **compilada no bundle JS** no build da imagem.
Ela tem que ser idêntica ao `secrets.REVERB_APP_KEY` configurado no GitHub
(bloco 13). Se divergir, o websocket não conecta e **o servidor não loga erro
nenhum** — só aparece no console do browser.

---

## 5. Passport — tokens de API e MCP 🔴

```bash
openssl genrsa -out oauth-private.key 4096
openssl rsa -in oauth-private.key -pubout -out oauth-public.key
```

```env
PASSPORT_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----"
PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
```

Use `\n` literal nas quebras. **Obrigatório:** sem essas variáveis o Passport cai
para arquivos em `storage/`, que não são persistidos pelos volumes — cada
recriação de container gera chaves novas e invalida todo token de API/MCP dos
seus clientes.

---

## 6. Armazenamento de mídia 🔴

**Cloudflare R2** (recomendado — sem custo de egresso):
https://dash.cloudflare.com → R2 → criar bucket → *Manage R2 API Tokens*

```env
FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
R2_REGION=auto
R2_BUCKET=midiamax
R2_URL=https://<url-publica-do-bucket>
```

`R2_URL` é a URL pública do bucket. **Sem ela a mídia não renderiza** — ative o
acesso público ou um domínio customizado no bucket.

Alternativas suportadas: `s3` (AWS), `spaces` (DigitalOcean), `local` (não
escala e complica backup).

---

## 7. E-mail 🔴

Sem SMTP real, convite de equipe, verificação de e-mail e reset de senha **não
chegam** — o default da imagem é `log`.

**Resend** (https://resend.com → API Keys):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=<a API key re_...>
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=nao-responda@midiamax.com.br
MAIL_FROM_NAME=MidiaMax
```

Duas armadilhas já encontradas na prática:

1. **`MAIL_SCHEME=tls` não existe no Laravel** e derruba todo envio com
   *"The tls scheme is not supported"*. Use `smtp` (STARTTLS/587) ou `smtps`
   (TLS/465). O `compose.prod.yaml` do upstream traz `tls`, que está errado.
2. **`MAIL_MAILER=resend` não funciona** — o mailer existe no `config/mail.php`
   mas o pacote `resend/resend-laravel` não está instalado. Vá por SMTP.

**Verifique um domínio** em https://resend.com/domains e configure SPF e DKIM.
Com o remetente de teste `onboarding@resend.dev` o Resend só entrega para o
e-mail dono da conta, e sem SPF/DKIM o resto cai em spam.

---

## 8. Stripe 🔴

Obrigatório porque `SELF_HOSTED=false`.

### 8.1 Chaves — https://dashboard.stripe.com/apikeys

```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
```

**Os nomes importam:** o Cashier lê `STRIPE_KEY` e `STRIPE_SECRET`. Se usar
`STRIPE_PUBLISHABLE_KEY`/`STRIPE_SECRET_KEY` (convenção de outros stacks) elas
são ignoradas em silêncio — parece configurado e não está.

Use `pk_test_`/`sk_test_` em qualquer ambiente que não seja produção. Chave live
em ambiente de teste gera cobrança real.

### 8.2 Produtos e preços

Dashboard → Produtos → criar um produto por plano, com preço **mensal** e
**anual** em BRL. Copiar os `price_...`:

```env
STRIPE_SOCIALS_MONTHLY=price_...
STRIPE_SOCIALS_YEARLY=price_...
STRIPE_WORKSPACES_MONTHLY=price_...
STRIPE_WORKSPACES_YEARLY=price_...
STRIPE_WORKSPACE_MONTHLY=
STRIPE_WORKSPACE_YEARLY=
```

(`workspace` é um plano legado, arquivado no seeder — pode ficar vazio.)

Depois de preencher, **rodar o seeder**, que é quem grava os price IDs nos planos:

```bash
docker compose -f compose.prod.yaml exec app php artisan db:seed --class=PlanSeeder --force
```

### 8.3 O preço EXIBIDO é outra variável 🔴

O valor que aparece na tela **não vem do Stripe** — o upstream cravou em
`lang/<locale>/billing.php`. Nós sobrescrevemos por config. **Mostrar um valor e
cobrar outro é problema de CDC**, então estes têm que bater com os preços reais:

```env
MIDIAMAX_PRICE_SOCIALS_MONTHLY="R$ 129,99"
MIDIAMAX_PRICE_SOCIALS_YEARLY="R$ 1.299,90"
MIDIAMAX_PRICE_SOCIALS_YEARLY_PER_MONTH="R$ 108,32"
MIDIAMAX_PRICE_WORKSPACES_MONTHLY="R$ 369,99"
MIDIAMAX_PRICE_WORKSPACES_YEARLY="R$ 3.699,90"
MIDIAMAX_PRICE_WORKSPACES_YEARLY_PER_MONTH="R$ 308,32"
```

### 8.4 Webhook 🔴

Dashboard → Developers → Webhooks → **Add endpoint**:

```
https://app.midiamax.com.br/api/v1/webhooks/cobranca/stripe
```

(ou `https://app.midiamax.com.br/stripe/webhook` — as duas valem, mesmo controller)

**Eventos a habilitar** — os 3 primeiros são os que o app realmente processa, os
4 seguintes o Cashier também trata:

```
customer.subscription.created      ← essencial
customer.subscription.updated      ← essencial
customer.subscription.deleted      ← essencial
customer.updated
customer.deleted
invoice.payment_action_required    ← 3DS, comum em cartão brasileiro
payment_method.automatically_updated
```

Depois de criar, **Reveal** o signing secret:

```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

**O `whsec_` é por endpoint.** Usar o de outro endpoint faz todo evento ser
rejeitado com 403 — e aí você cancela uma assinatura no Stripe e o cliente
continua usando o sistema. Trocou a URL do endpoint? Confira se o secret mudou.

### 8.5 Trial

```env
REQUIRE_CARD_FOR_TRIAL=true     # false = trial sem cartão (não precisa de Stripe)
CASHIER_TRIAL_DAYS=8
CASHIER_ALLOW_PROMOTION_CODES=false
STRIPE_SOCIALS_FIRST_MONTH_COUPON_ID=       # opcional
STRIPE_WORKSPACES_FIRST_MONTH_COUPON_ID=    # opcional
```

---

## 9. Redes sociais

Apps **seus**, nunca os do upstream. Padrão de redirect:
`https://app.midiamax.com.br/accounts/<plataforma>/callback` — tem que ser
idêntico ao cadastrado no portal (protocolo, subdomínio, sem barra final).

### 9.1 Telegram 🟡 — sem review, sai hoje

https://t.me/BotFather → `/newbot`

```env
TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=<token do BotFather>
TELEGRAM_BOT_USERNAME=<username sem @>
TELEGRAM_WEBHOOK_SECRET=<openssl rand -hex 32>
```

```bash
docker compose -f compose.prod.yaml exec app php artisan telegram:set-webhook
```

Um bot único compartilhado: cada cliente adiciona o seu bot ao canal dele.

### 9.2 Discord 🟡 — sem review até 100 servidores

https://discord.com/developers/applications → New Application

- **OAuth2** → Client ID, Client Secret, e adicionar o redirect
- **Bot** → *Reset Token*, e ligar **Server Members Intent**

```env
DISCORD_ENABLED=true
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_BOT_TOKEN=
DISCORD_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/discord/callback
```

A publicação usa o **bot token**, não o token OAuth do usuário. Escopos: `bot`,
`identify`, `guilds`. Permissões: bitfield `248832`.

### 9.3 LinkedIn 🟡 — self-service, rápido

https://developer.linkedin.com → Create app (precisa de uma Página de empresa sua)

Em *Products*, solicitar **Sign In with LinkedIn using OpenID Connect** e
**Share on LinkedIn**.

```env
LINKEDIN_ENABLED=true
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=
LINKEDIN_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/linkedin/callback
LINKEDIN_PAGE_ENABLED=false
```

Página de empresa exige a **Community Management API** (programa de parceiro) —
deixe desligado até ter a aprovação.

### 9.4 Pinterest 🟡

https://developers.pinterest.com → My apps (precisa de conta business)

```env
PINTEREST_ENABLED=true
PINTEREST_CLIENT_ID=
PINTEREST_CLIENT_SECRET=
PINTEREST_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/pinterest/callback
```

Escopos: `boards:read`, `boards:write`, `pins:read`, `pins:write`,
`user_accounts:read`. App começa em trial; produção exige review.

### 9.5 Meta — Facebook e Instagram 🟡 — o processo mais longo

https://developers.facebook.com → Meus apps → criar app tipo **Business**

**Configurações → Básico**: copiar App ID e Chave Secreta, e preencher:

```
Política de Privacidade:  https://app.midiamax.com.br/privacidade
Termos de Serviço:        https://app.midiamax.com.br/termos
Exclusão de dados:        https://app.midiamax.com.br/exclusao-de-dados
```

**Produto Facebook Login** → cadastrar **dois** redirects:

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

Para atender clientes: **App Review** de `pages_manage_posts`,
`instagram_content_publish` e companhia, **Verificação do Negócio** com CNPJ, e
app em modo Live. Exige vídeo demonstrando o fluxo completo. Semanas.

Pré-requisito do cliente: conta Instagram **Business ou Creator** vinculada a uma
Página do Facebook.

### 9.6 TikTok 🟡

https://developers.tiktok.com → Manage apps → produto **Content Posting API**

```env
TIKTOK_ENABLED=true
TIKTOK_CLIENT_ID=
TIKTOK_CLIENT_SECRET=
TIKTOK_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/tiktok/callback
```

Publicar direto no perfil exige o **audit de Direct Post**; sem ele o conteúdo
só vira rascunho no app do usuário. App novo não recebe mais Display API — se a
autorização falhar, enxugue:

```env
TIKTOK_SCOPES="user.info.basic,video.publish,video.upload"
```

### 9.7 YouTube e Shorts 🟡

https://console.cloud.google.com → projeto **dedicado à MidiaMax**

1. APIs e Serviços → Biblioteca → habilitar **YouTube Data API v3** e
   **YouTube Analytics API**
2. Tela de permissão OAuth → preencher, com links de Termos e Privacidade
3. Credenciais → ID do cliente OAuth → **Aplicativo da Web** → cadastrar:
   `https://app.midiamax.com.br/accounts/youtube/callback`
   `https://app.midiamax.com.br/auth/google/callback`

```env
YOUTUBE_ENABLED=true
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_CLIENT_REDIRECT=https://app.midiamax.com.br/accounts/youtube/callback
GOOGLE_AUTH_ENABLED=false
GOOGLE_AUTH_CALLBACK=https://app.midiamax.com.br/auth/google/callback
```

Escopos `youtube.upload` e `youtube.readonly` são sensíveis e exigem verificação
do app no Google.

**Peça aumento de quota no dia 1.** O default do Data API é 10.000 unidades/dia e
um upload custa ~1.600 → **~6 vídeos/dia para a plataforma inteira**, somando
todos os clientes. Sem aumento, não existe produto.

Shorts não tem API separada: é a Data API, e o YouTube classifica pelo formato.

Use um projeto Google **dedicado**. Reaproveitar um projeto de outro produto faz
a tela de consentimento mostrar o nome errado ao seu cliente e atrapalha a
verificação.

### 9.8 Desligar o que não usamos 🔴

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

## 10. Inteligência artificial 🟡

Sem chave, o app esconde os recursos de IA sozinho (`aiEnabled`).

**Gemini** — https://aistudio.google.com/apikey

```env
AI_TEXT_PROVIDER=gemini
GEMINI_API_KEY=
GEMINI_TEXT_MODEL=gemini-flash-lite-latest
```

**Fixe o modelo com um alias `-latest`.** Modelos são aposentados pelo provedor e
o driver reporta *"AI provider is overloaded"* — mensagem que despista
completamente, porque a causa real é um 404 *"no longer available to new users"*.
Diagnostique chamando a API direto:

```bash
curl -s "https://generativelanguage.googleapis.com/v1beta/models?key=$GEMINI_API_KEY"
```

Outros provedores: `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `OPENROUTER_API_KEY`
(permite trocar de modelo sem mexer em config), `ELEVENLABS_API_KEY` (áudio).

⚠️ **O custo é seu, não do cliente.** Cada legenda sai da sua chave. Com
assinatura de preço fixo, um cliente intensivo come a margem — e **não existe
limite de IA por plano no código** (a tabela `workspace_ai_usages` existe mas
nenhum código a usa).

---

## 11. Observabilidade ⚪

```env
HORIZON_ALLOWED_EMAILS=voce@midiamax.com.br     # 🔴 ver aviso abaixo
NIGHTWATCH_ENABLED=false
NIGHTWATCH_TOKEN=
POSTHOG_ENABLED=false
POSTHOG_API_KEY=
POSTHOG_HOST=https://us.i.posthog.com
GTM_ID=
```

🔴 **`HORIZON_ALLOWED_EMAILS` é obrigatório em produção.** É a lista de e-mails
que acessa `/horizon` (filas e jobs falhando). Em `APP_ENV=local` o gate nem é
aplicado — `/horizon` fica **aberto sem autenticação**. Em produção, sem essa
variável, ninguém entra.

O Telescope tem gate com lista vazia e cravada no código: ninguém acessa fora de
local. Mantenha `TELESCOPE_ENABLED=false`.

---

## 12. Páginas legais e dados do controlador 🔴

Sem as duas primeiras, os links do app apontam para **trypost.it**.

```env
LEGAL_TERMS_URL=https://app.midiamax.com.br/termos
LEGAL_PRIVACY_URL=https://app.midiamax.com.br/privacidade
MIDIAMAX_LEGAL_NAME=
MIDIAMAX_CNPJ=
MIDIAMAX_ADDRESS=
MIDIAMAX_SUPPORT_EMAIL=suporte@midiamax.com.br
MIDIAMAX_PRIVACY_EMAIL=privacidade@midiamax.com.br
MIDIAMAX_JURISDICTION=
MIDIAMAX_LEGAL_UPDATED_AT=
MIDIAMAX_SOURCE_URL=https://github.com/LeonardoMarcelo/midiamax-app
```

`MIDIAMAX_VERSION` e `MIDIAMAX_COMMIT` são preenchidos pelo `scripts/deploy.sh` a
partir dos labels da imagem — não editar à mão. Alimentam `/codigo-fonte`, que é
obrigação da AGPL §13.

> O texto das páginas cobre o que Meta, TikTok e LGPD verificam, mas **precisa de
> revisão jurídica** antes de submeter app review e antes do primeiro cliente.

---

## 13. GitHub — build args da imagem 🔴

Não vão no `.env`: entram no bundle JS no **build**. Em `midiamax-app` →
Settings → Secrets and variables → **Actions**:

| Tipo | Nome | Valor |
| --- | --- | --- |
| Variable | `APP_NAME` | `MidiaMax` |
| Variable | `APP_DOMAIN` | `app.midiamax.com.br` |
| Secret | `REVERB_APP_KEY` | **idêntico** ao `REVERB_APP_KEY` do `.env` |

Mudar qualquer um exige **rebuild da imagem** — reiniciar o container não basta.

Para baixar a imagem na VPS: `docker login ghcr.io -u LeonardoMarcelo` com um PAT
que tenha `read:packages`.

---

## 14. Checklist de go-live

**Infra**
- [ ] DNS apontando, HTTPS emitido pelo Caddy
- [ ] `APP_KEY` e chaves do Passport geradas **e guardadas fora do servidor**
- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `TELESCOPE_ENABLED=false`
- [ ] `HORIZON_ALLOWED_EMAILS` preenchido
- [ ] Backup automático do Postgres rodando **e testado com um restore real**
- [ ] Cópia dos backups para fora do servidor
- [ ] Monitoramento de uptime externo

**Billing**
- [ ] Produtos e preços criados, `price_...` no `.env`, `PlanSeeder` rodado
- [ ] Rótulos `MIDIAMAX_PRICE_*` batendo com o que o Stripe cobra
- [ ] Webhook criado, eventos habilitados, `whsec_` **daquele** endpoint
- [ ] Um checkout de teste ponta a ponta em modo de teste

**Plataformas**
- [ ] Redirects cadastrados em cada portal, idênticos ao `.env`
- [ ] Plataformas não usadas com `*_ENABLED=false`
- [ ] Webhook do Telegram registrado

**Legal**
- [ ] `/termos`, `/privacidade`, `/exclusao-de-dados` e `/codigo-fonte` abrindo
- [ ] Dados do controlador preenchidos
- [ ] Textos revisados por advogado
- [ ] Repo público sincronizado com o que está em produção (AGPL §13)

**Verificação final**
```bash
for p in / /login /register /termos /privacidade /exclusao-de-dados /codigo-fonte; do
  printf "%-22s %s\n" "$p" "$(curl -s -o /dev/null -w '%{http_code}' https://app.midiamax.com.br$p)"
done
```
Todas 200. Depois: cadastrar, conectar uma rede, agendar um post e ver publicar.
