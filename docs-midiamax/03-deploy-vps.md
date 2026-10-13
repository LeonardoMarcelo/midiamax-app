# 03 — Deploy em VPS própria

Decisão: VPS própria com Docker Compose + Caddy (HTTPS automático). Mais barato e
com controle total; em troca, backup, atualização de SO e monitoramento são nossos.

A configuração de produção **não** mora neste repo — mora em
`LeonardoMarcelo/midiamax-infra` (privado), porque contém segredos.

## Por que precisamos da nossa própria imagem

Não serve usar `ghcr.io/trypostit/trypost:latest`:

1. É a versão **deles**, sem nossas modificações.
2. O bundle JS é compilado no build com `VITE_REVERB_HOST=localhost` e a chave do
   Reverb cravada. Domínio próprio exige **rebuild** (está documentado no
   `compose.prod.yaml` do upstream).
3. `VITE_APP_NAME` também é build arg — a marca entra no bundle em tempo de build.

Nossa imagem: `ghcr.io/leonardomarcelo/midiamax-app`, publicada por
`.github/workflows/midiamax-image.yml` a cada tag `mm-v*`.

## Dimensionamento

A stack sobe: php-fpm + nginx, Postgres 16, Redis 7, workers do Horizon, scheduler,
Reverb (websocket) e Caddy. Processamento de mídia (vídeo/imagem) é o que consome.

| Fase | Sugestão |
| --- | --- |
| Staging | 2 vCPU / 4 GB / 40 GB SSD |
| Produção inicial | 4 vCPU / 8 GB / 80 GB SSD + object storage (R2/S3) |
| Crescendo | separar Postgres (managed), mídia sempre em object storage |

Mídia em disco local não escala e complica backup. Usar **Cloudflare R2** ou S3
desde o começo (`FILESYSTEM_DISK=r2` + `R2_*`), não o default `public`.

## Variáveis que NÃO podem ser esquecidas

Do `compose.prod.yaml` do upstream, os pontos que quebram deploy durável:

- `APP_KEY` — gerar uma vez (`php artisan key:generate --show`) e guardar. Perder = perder dado criptografado.
- `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY` — **obrigatórias**. As chaves em
  `storage/` não são persistidas pelos volumes; sem essas vars, recriar o container
  invalida todo token de API/MCP dos clientes.
- `REVERB_APP_SECRET`, `REVERB_APP_KEY` — a key tem que bater com a compilada no bundle.
- `SELF_HOSTED=false` — liga o modo SaaS (assinatura obrigatória).
- `STRIPE_*` + `STRIPE_*_MONTHLY/YEARLY` (price ids) — exigidos quando `SELF_HOSTED=false`.
- `MIDIAMAX_COMMIT`, `MIDIAMAX_VERSION`, `MIDIAMAX_SOURCE_URL` — oferta de fonte AGPL (§13).
- `MAIL_*` — o default é `log`; sem SMTP real, convite de equipe e reset de senha não chegam.
- `APP_DEBUG=false`.

A cada release do upstream, conferir `git diff <tag-antiga> <tag-nova> -- .env.example`
e refletir as vars novas no compose do repo de infra.

## Checklist de primeiro deploy

- [ ] VPS criada, DNS do domínio apontando para o IP
- [ ] SSH só com chave, senha desabilitada, firewall liberando 22/80/443
- [ ] Docker + Compose instalados
- [ ] `docker login ghcr.io` com PAT (`write:packages` para publicar, `read:packages` para só baixar)
- [ ] `midiamax-infra` clonado no servidor, `.env.prod` preenchido (nunca commitado)
- [ ] `APP_KEY` e chaves do Passport geradas e salvas no gerenciador de segredos
- [ ] `docker compose --profile proxy up -d` (Caddy emite o certificado)
- [ ] `php artisan migrate --force` + seed de planos
- [ ] Webhook do Stripe apontando para `https://<domínio>/stripe/webhook`
- [ ] Backup automático do Postgres rodando **e testado com um restore de verdade**
- [ ] Monitoramento: uptime externo + Horizon + alerta de job falhando
- [ ] `/codigo-fonte` acessível (AGPL §13)

## Operação

```bash
docker compose -f compose.prod.yaml ps
docker compose -f compose.prod.yaml logs -f app
docker compose -f compose.prod.yaml exec app php artisan horizon:status
docker compose -f compose.prod.yaml exec app php artisan queue:failed
```

Deploy e rollback são por **tag de imagem**, nunca por edição de arquivo no servidor:
`scripts/deploy.sh mm-v2.1.0-1`.

Backup antes de todo deploy. Rollback de imagem não desfaz migration destrutiva —
para isso só o dump salva. Ver `02-runbook-atualizacao.md`.
