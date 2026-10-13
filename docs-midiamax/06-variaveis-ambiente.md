# 06 — Variáveis de ambiente: configuração completa

Levantado lendo `config/services.php`, `config/trypost.php`, `.env.example`,
`docker/entrypoint.sh` e os controllers de OAuth da tag **v2.0.0**. Não é
suposição — cada nome e cada escopo abaixo está no código.

Plataformas que a MidiaMax vai usar: **Instagram, Facebook, Discord, LinkedIn,
TikTok, Pinterest, Telegram, YouTube (Shorts)**.

O arquivo preenchível fica em `midiamax-infra/.env.example`. Aqui está o
**porquê** de cada variável e **onde conseguir** cada credencial.

---

## Como a configuração chega na aplicação

Em produção **não existe `.env` dentro do container**. O `entrypoint.sh` só gera
`APP_KEY` se já houver um `.env` (linha 55), e a imagem não traz um. Tudo chega
como variável de ambiente de verdade, via `env_file: .env` no
`compose.prod.yaml` do repo de infra.

Duas consequências práticas:

1. **`APP_KEY` tem que ser gerada por você** e colada no `.env`. Não se gera
   sozinha em produção.
2. `${APP_URL}` dentro do `env_file` **é** expandido pelo Docker Compose
   (testado na versão 5.5.0). Mesmo assim o `.env.example` usa URL absoluta nos
   redirects: o valor precisa ser idêntico ao cadastrado no portal de cada
   plataforma, e ter a string literal no arquivo torna o copia-e-cola direto.

`VITE_*` são **build args**, não runtime: entram no bundle JS na hora de buildar
a imagem (`.github/workflows/midiamax-image.yml`). Definir `VITE_APP_NAME` no
`.env` de produção não tem efeito nenhum.

---

## 1. Infraestrutura — sem isto a aplicação não sobe

| Variável | Onde conseguir / valor |
| --- | --- |
| `APP_NAME` | `MidiaMax` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` — `true` em produção expõe stack trace com credenciais |
| `APP_KEY` | `docker compose -f compose.prod.yaml run --rm app php artisan key:generate --show`. **Perder = perder todo dado criptografado** (tokens das redes sociais) |
| `APP_URL` | `https://app.midiamax.com.br` — sem barra no fim |
| `APP_DOMAIN` | `app.midiamax.com.br` — usado pelo Caddy para emitir o certificado |
| `APP_LOCALE` | `pt-BR` |
| `APP_FALLBACK_LOCALE` | `en` |
| `WEBHOOK_URL` | base pública dos webhooks de entrada (Telegram). Vazio = usa `APP_URL` |
| `SELF_HOSTED` | `false` = modo SaaS, assinatura obrigatória |
| `TRYPOST_TARGET` | `production` |

### Banco (o Postgres do próprio compose)

`DB_CONNECTION=pgsql`, `DB_HOST=pgsql`, `DB_PORT=5432`, `DB_DATABASE=midiamax`,
`DB_USERNAME=midiamax`, `DB_PASSWORD=` → senha forte inventada por você;
o serviço `pgsql` do compose lê a mesma variável, então basta definir uma vez.

### Redis, filas, cache, sessão, broadcast

`REDIS_HOST=redis`, `REDIS_PORT=6379`, `REDIS_PASSWORD=null`,
`QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=database`,
`SESSION_LIFETIME=1440`, `BROADCAST_CONNECTION=reverb`.

### Reverb (WebSocket — calendário atualizando em tempo real)

| Variável | Valor |
| --- | --- |
| `REVERB_APP_ID` | `1001` (qualquer número) |
| `REVERB_APP_KEY` | string aleatória — `openssl rand -hex 16` |
| `REVERB_APP_SECRET` | outra string aleatória |
| `REVERB_HOST` | `app.midiamax.com.br` |
| `REVERB_PORT` | `443` |
| `REVERB_SCHEME` | `https` |

**Pega:** `REVERB_APP_KEY` é compilada no bundle JS. Ela tem que ser **idêntica**
ao `secrets.REVERB_APP_KEY` usado no build da imagem. Se divergir, o websocket
não conecta e não há erro no servidor — só no console do browser.

### Passport (tokens de API e MCP) — obrigatório

```bash
openssl genrsa -out oauth-private.key 4096
openssl rsa -in oauth-private.key -pubout -out oauth-public.key
```

`PASSPORT_PRIVATE_KEY` e `PASSPORT_PUBLIC_KEY`, com `\n` literal nas quebras de
linha. Sem elas, Passport cai para arquivos em `storage/`, que **não são
persistidos pelos volumes**: cada recriação de container gera chaves novas e
invalida todo token de API/MCP dos seus clientes.

### Armazenamento de mídia

`FILESYSTEM_DISK=r2` + `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_ENDPOINT`,
`R2_BUCKET`, `R2_URL`, `R2_REGION=auto`.

Onde: Cloudflare dashboard → R2 → criar bucket → *Manage R2 API Tokens*.
`R2_URL` é a URL pública do bucket — sem ela a mídia não renderiza.
Alternativas suportadas: `s3` (AWS), `spaces` (DigitalOcean), `local`.

Disco local não escala e complica backup: use object storage desde o início.

### E-mail

`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT=587`, `MAIL_USERNAME`,
`MAIL_PASSWORD`, `MAIL_SCHEME=smtp`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.

**`MAIL_SCHEME=tls` não existe no Laravel** e derruba todo envio com
*"The tls scheme is not supported"*. Use `smtp` (STARTTLS, porta 587) ou
`smtps` (TLS, porta 465). O `compose.prod.yaml` do upstream traz `tls`, que
está errado — testado em 13/10/2026.

O mailer `resend` existe em `config/mail.php` mas o pacote
`resend/resend-laravel` **não está instalado**: `MAIL_MAILER=resend` quebra.
Para usar Resend, vá por SMTP — `smtp.resend.com`, porta 587, usuário
`resend`, senha = API key.

O default da imagem é `log`: sem SMTP real, convite de equipe, verificação de
e-mail e reset de senha **não chegam**. Resend, Postmark, SES ou Brevo
resolvem — e configure SPF/DKIM do domínio ou cai em spam.

---

## 2. Billing (obrigatório porque `SELF_HOSTED=false`)

| Variável | Onde conseguir |
| --- | --- |
| `STRIPE_KEY` / `STRIPE_SECRET` | https://dashboard.stripe.com/apikeys |
| `STRIPE_WEBHOOK_SECRET` | Stripe → Developers → Webhooks → endpoint `https://app.midiamax.com.br/stripe/webhook` ou o alias `…/api/v1/webhooks/cobranca/stripe`. O `whsec_` é **por endpoint**: usar o de outro endpoint faz todo evento ser rejeitado com 403 |
| `STRIPE_WORKSPACE_MONTHLY` / `_YEARLY` | price IDs (`price_...`) dos produtos criados no Stripe |
| `STRIPE_SOCIALS_MONTHLY` / `_YEARLY` | idem |
| `STRIPE_WORKSPACES_MONTHLY` / `_YEARLY` | idem |
| `REQUIRE_CARD_FOR_TRIAL` | `true` exige cartão no checkout antes de usar o app |
| `CASHIER_TRIAL_DAYS` | `8` no default. `0` desliga o trial |
| `CASHIER_ALLOW_PROMOTION_CODES` | `false`. Stripe proíbe cupom e campo de promo na mesma sessão |
| `STRIPE_SOCIALS_FIRST_MONTH_COUPON_ID` | opcional, cupom de primeiro mês |
| `STRIPE_WORKSPACES_FIRST_MONTH_COUPON_ID` | opcional |

Os price IDs são lidos pelo `PlanSeeder` — crie os produtos no Stripe **antes**
de rodar o seed.

---

## 3. As 8 plataformas

Regra geral: os apps são **seus**, nunca os do upstream. O padrão de redirect é
`https://app.midiamax.com.br/accounts/<plataforma>/callback` e ele tem que ser
cadastrado **exatamente igual** no portal (protocolo, subdomínio, sem barra final).

### Instagram — dois caminhos, ambos aparecem como "Instagram"

O código tem duas conexões distintas. Vale ter as duas: a direta é mais simples
para o cliente, a via Facebook é a que funciona para contas administradas por
agência.

**(a) Instagram direto** — Instagram Business Login

| Variável | Valor |
| --- | --- |
| `INSTAGRAM_CLIENT_ID` / `INSTAGRAM_CLIENT_SECRET` | https://developers.facebook.com → seu app → produto **Instagram** → *API setup with Instagram login* |
| `INSTAGRAM_CLIENT_REDIRECT` | `https://app.midiamax.com.br/accounts/instagram/callback` |

Escopos que o código pede: `instagram_business_basic`,
`instagram_business_content_publish`, `instagram_business_manage_insights`.

**(b) Instagram via Página do Facebook** — usa **o mesmo app do Facebook**
(driver `facebook`, credenciais `FACEBOOK_CLIENT_*`). Cadastre também o redirect
`https://app.midiamax.com.br/accounts/instagram-facebook/callback` no app Meta.

Escopos: `public_profile`, `pages_show_list`, `pages_read_engagement`,
`business_management`, `instagram_basic`, `instagram_content_publish`,
`instagram_manage_insights`.

Pré-requisito do cliente: conta Instagram **Business ou Creator** vinculada a uma
Página do Facebook. Conta pessoal não publica por API.

### Facebook (Páginas)

| Variável | Valor |
| --- | --- |
| `FACEBOOK_CLIENT_ID` / `FACEBOOK_CLIENT_SECRET` | https://developers.facebook.com → *Meus apps* → criar app tipo **Business** → Configurações → Básico (App ID / Chave Secreta) |
| `FACEBOOK_CLIENT_REDIRECT` | `https://app.midiamax.com.br/accounts/facebook/callback` |

Escopos: `public_profile`, `pages_show_list`, `pages_read_engagement`,
`pages_manage_posts`, `read_insights`, `business_management`.

**Meta (Instagram + Facebook) é o processo mais longo:** App Review de cada
escopo + Verificação do Negócio com CNPJ + app em modo Live. Exige política de
privacidade, página de exclusão de dados e vídeo mostrando o fluxo completo.
`META_PAGE_WALK_SECONDS=20` controla o tempo de paginação na Graph API.

### LinkedIn

| Variável | Valor |
| --- | --- |
| `LINKEDIN_CLIENT_ID` / `LINKEDIN_CLIENT_SECRET` | https://developer.linkedin.com → *Create app* (precisa de uma Página de empresa sua) → Auth |
| `LINKEDIN_CLIENT_REDIRECT` | `https://app.midiamax.com.br/accounts/linkedin/callback` |
| `LINKEDIN_SCOPES` | default `openid,profile,email,w_member_social` |

Produtos a solicitar na aba *Products*: **Sign In with LinkedIn using OpenID
Connect** e **Share on LinkedIn**. São self-service, liberam rápido.

Publicar em **Página de empresa** é outra história: exige a **Community
Management API**, que é programa de parceiro e dificilmente sai no começo. Por
isso o `.env.example` começa com `LINKEDIN_PAGE_ENABLED=false` — ligue quando
tiver a aprovação, com `LINKEDIN_PAGE_SCOPES`.

### TikTok

| Variável | Valor |
| --- | --- |
| `TIKTOK_CLIENT_ID` / `TIKTOK_CLIENT_SECRET` | https://developers.tiktok.com → *Manage apps* → app → Client key / Client secret |
| `TIKTOK_CLIENT_REDIRECT` | `https://app.midiamax.com.br/accounts/tiktok/callback` |
| `TIKTOK_SCOPES` | default `user.info.basic,user.info.profile,user.info.stats,video.publish,video.upload,video.list` |

Produto a adicionar: **Content Posting API**. Publicar direto no perfil exige o
**audit** de Direct Post; sem ele o conteúdo só chega como rascunho no app do
usuário. O comentário no `.env.example` do upstream avisa: app novo sem Display
API deve enxugar os escopos para `user.info.basic,video.publish,video.upload`,
senão a autorização falha.

O portal da TikTok exige links de Termos e Privacidade visíveis nas telas de
autenticação — já previsto em `config/trypost.php` na chave `legal`.

### Pinterest

| Variável | Valor |
| --- | --- |
| `PINTEREST_CLIENT_ID` / `PINTEREST_CLIENT_SECRET` | https://developers.pinterest.com → *My apps* → criar app (precisa de conta business) |
| `PINTEREST_CLIENT_REDIRECT` | `https://app.midiamax.com.br/accounts/pinterest/callback` |

Escopos: `boards:read`, `boards:write`, `pins:read`, `pins:write`,
`user_accounts:read`. App começa em *Trial access*; produção exige review.

### YouTube (Shorts)

| Variável | Valor |
| --- | --- |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | https://console.cloud.google.com → projeto → APIs e Serviços → Credenciais → **ID do cliente OAuth** tipo *Aplicativo da Web* |
| `GOOGLE_CLIENT_REDIRECT` | `https://app.midiamax.com.br/accounts/youtube/callback` |

Habilitar no projeto: **YouTube Data API v3** e **YouTube Analytics API**.
Escopos: `https://www.googleapis.com/auth/youtube.upload` e
`.../auth/youtube.readonly` — são escopos sensíveis, então precisam de
**verificação do app** no Google (tela de consentimento, domínio verificado,
vídeo de demonstração).

**Shorts não tem API própria.** É publicação normal pela Data API; o YouTube
classifica como Short pelo formato (vertical, até 3 min). Não há variável nem
endpoint separado.

**O gargalo real é quota:** o default do Data API é 10.000 unidades/dia e um
upload custa ~1.600 → **~6 vídeos por dia para a plataforma inteira**, somando
todos os clientes. Peça aumento de quota no console assim que criar o projeto;
é o pedido que mais demora. Confirme os números atuais no console antes de
prometer a feature.

`GOOGLE_AUTH_ENABLED` e `GOOGLE_AUTH_CALLBACK` são outra coisa: login com Google
na MidiaMax. Mesmo client ID, redirect diferente (`/auth/google/callback`).
Deixe `false` se não quiser oferecer login social.

### Telegram

| Variável | Valor |
| --- | --- |
| `TELEGRAM_BOT_TOKEN` | https://t.me/BotFather → `/newbot` → copie o token |
| `TELEGRAM_BOT_USERNAME` | o `@usuario` do bot, sem o `@` |
| `TELEGRAM_WEBHOOK_SECRET` | string aleatória sua (`openssl rand -hex 32`) |
| `WEBHOOK_URL` | base pública, se diferente de `APP_URL` |

Depois de preencher, registrar o webhook **uma vez**:

```bash
docker compose -f compose.prod.yaml exec app php artisan telegram:set-webhook
```

É **um bot único compartilhado** por todos os clientes — o cliente adiciona o
seu bot ao canal dele. Sem app review, sem custo. A plataforma mais fácil das oito.

### Discord

| Variável | Valor |
| --- | --- |
| `DISCORD_CLIENT_ID` / `DISCORD_CLIENT_SECRET` | https://discord.com/developers/applications → *New Application* → OAuth2 |
| `DISCORD_BOT_TOKEN` | mesma aplicação → aba **Bot** → *Reset Token* |
| `DISCORD_CLIENT_REDIRECT` | `https://app.midiamax.com.br/accounts/discord/callback` |

Configurar na aplicação:

- OAuth2 → *Redirects*: adicionar a URL acima
- Bot → ativar **Server Members Intent** (busca de menções depende disso)
- escopos: `bot`, `identify`, `guilds` (`DISCORD_SCOPES`)
- permissões do bot: bitfield **248832** (`DISCORD_PERMISSIONS`) = ver canal,
  enviar mensagem, embutir links, anexar arquivos, ler histórico, mencionar

Detalhe de arquitetura: **a postagem usa o bot token, não o token OAuth do
usuário**. O OAuth só serve para o cliente autorizar a entrada do bot no servidor
dele. Sem app review; verificação do Discord só é exigida acima de 100 servidores.

---

## 4. Desligar as plataformas que não vamos usar — importante

`Platform::isEnabled()` (em `app/Enums/SocialAccount/Platform.php`) **só olha a
flag, nunca a credencial**. Plataforma habilitada e sem credencial mostra um
botão "Conectar" que estoura no OAuth — o cliente vê o produto quebrado.

Todas as flags default para `true`, então é obrigatório desligar explicitamente:

```env
X_ENABLED=false
THREADS_ENABLED=false
BLUESKY_ENABLED=false
MASTODON_ENABLED=false
GOOGLE_BUSINESS_ENABLED=false
LINKEDIN_PAGE_ENABLED=false
```

E as nossas oito ficam ligadas (default `true`, explícitas no `.env.example`):
`INSTAGRAM_ENABLED`, `INSTAGRAM_FACEBOOK_ENABLED`, `FACEBOOK_ENABLED`,
`LINKEDIN_ENABLED`, `TIKTOK_ENABLED`, `PINTEREST_ENABLED`, `YOUTUBE_ENABLED`,
`TELEGRAM_ENABLED`, `DISCORD_ENABLED`.

---

## 5. Opcionais

### IA (geração de legenda, repurpose)

`AI_TEXT_PROVIDER` (`openai|anthropic|gemini|openrouter|…`) e
`AI_AUDIO_PROVIDER`, mais a chave do provedor escolhido: `OPENAI_API_KEY`,
`ANTHROPIC_API_KEY`, `GEMINI_API_KEY`, `OPENROUTER_API_KEY`,
`ELEVENLABS_API_KEY`. Tudo vazio desliga as features de IA.

O app esconde os recursos de IA sozinho quando não há chave: o
`HandleInertiaRequests` publica `aiEnabled` para o frontend com base nisso.

**Fixe o modelo com um alias `-latest`.** Os modelos default do pacote podem ser
aposentados pelo provedor, e aí o driver reporta *"AI provider is overloaded"* —
mensagem que despista completamente, porque a causa real é um 404
*"no longer available to new users"*. Aconteceu em 13/10/2026 com
`gemini-2.5-flash` e `gemini-2.0-flash`. Diagnostique sempre chamando a API
direto antes de culpar o provedor:

```bash
curl -s "https://generativelanguage.googleapis.com/v1beta/models?key=$GEMINI_API_KEY"
```

Em uso hoje: `GEMINI_TEXT_MODEL=gemini-flash-lite-latest` (testado, gerando em
pt-BR). Aliases `-latest` acompanham a troca de geração sozinhos.

Custo de IA é por uso e sai do seu bolso: modele no preço do plano antes de ligar.

### Fontes de mídia no compositor

- `UNSPLASH_ACCESS_KEY` / `UNSPLASH_SECRET_KEY` — https://unsplash.com/developers
- `GOOGLE_MEDIA_*` (Drive e Photos) + `GOOGLE_DRIVE_ENABLED`,
  `GOOGLE_PHOTOS_ENABLED` — projeto Google **separado** do YouTube; passo a passo
  em `MEDIA_INTEGRATIONS_SETUP.md`. Nada herda de `GOOGLE_CLIENT_ID`
- `CANVA_CLIENT_ID` / `CANVA_CLIENT_SECRET` + `CANVA_ENABLED` — exige integração
  pública aprovada pela Canva

### Observabilidade

- `NIGHTWATCH_ENABLED` / `NIGHTWATCH_TOKEN` — telemetria Laravel
- `POSTHOG_ENABLED` / `POSTHOG_API_KEY` / `POSTHOG_HOST` — produto. Também
  precisa dos build args `VITE_POSTHOG_*` para o lado do browser
- `GTM_ID` — Google Tag Manager
- `TELESCOPE_ENABLED=false` em produção, sempre

### Retenção e operação

`MEDIA_UPLOAD_RETENTION_HOURS=24`, `POST_HISTORY_RETENTION_DAYS=730`
(`0` ou vazio é rejeitado), `PUBLICATION_DISCOVERY_INTERVAL_HOURS=3`,
`EXTERNAL_POSTS_IMPORT_LIMIT=50`, `REPURPOSE_POLL_INTERVAL_MINUTES=15`.

Os defaults servem; mexa só se tiver motivo.

---

## 6. Build args (imagem), não runtime

Configurados em *Settings → Secrets and variables → Actions* do `midiamax-app`:

| Onde | Nome | Valor |
| --- | --- | --- |
| Variable | `APP_NAME` | `MidiaMax` |
| Variable | `APP_DOMAIN` | `app.midiamax.com.br` |
| Secret | `REVERB_APP_KEY` | igual ao `REVERB_APP_KEY` do `.env` |

Mudar qualquer um exige **rebuild da imagem** — não basta reiniciar o container.

---

## 7. Ordem de preenchimento

1. Infra (seção 1) → a app já sobe e você consegue criar conta
2. Flags da seção 4 → a interface já mostra só as 8 plataformas
3. Telegram e Discord → funcionam **hoje**, sem review. Use para validar o
   produto ponta a ponta enquanto o resto está em análise
4. LinkedIn perfil e Pinterest → aprovação rápida
5. Stripe → já dá para cobrar
6. Meta (Instagram + Facebook), TikTok e quota do YouTube → abra os pedidos no
   **dia 1**, são semanas de espera (`05-apis-redes-sociais.md`)

Depois de mudar qualquer variável em produção:

```bash
docker compose -f compose.prod.yaml up -d
docker compose -f compose.prod.yaml exec app php artisan config:clear
```

O entrypoint roda `config:cache` no boot — variável nova sem reiniciar o
container não tem efeito.
