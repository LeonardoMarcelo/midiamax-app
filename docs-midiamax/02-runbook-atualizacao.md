# 02 — Runbook: puxar uma atualização do upstream

Cadência: ~1 release do upstream por mês. Orçamento realista: **1–2h por release**,
mais tempo na primeira vez e em release major (v1→v2 mexeu em contrato de API).

Nunca pule o staging. Nunca edite arquivo no servidor.

---

## 0. Antes de começar

```bash
cd ~/Documents/ferramentas/pessoal/socialcrm
git status                    # tem que estar limpo
git checkout main
```

## 1. Ver o que vem

```bash
git fetch upstream --tags
git tag --sort=-creatordate | head -5          # tags disponíveis
git log --oneline v2.0.0..v2.1.0               # o que mudou
git diff --stat v2.0.0 v2.1.0                  # tamanho
```

Leia o release notes em https://github.com/trypostit/trypost/releases.
Procure especificamente por: mudança de contrato de API/MCP/webhook, migration
destrutiva, mudança em env var, bump de versão do Laravel/PHP.

**Checagem dirigida:** o release tocou algum arquivo que nós modificamos?

```bash
git diff v2.0.0 v2.1.0 -- bootstrap/providers.php resources/css/app.css .gitattributes
```

Se sim, já sabe onde vai ser o conflito.

## 2. Integrar

```bash
scripts/midiamax/atualizar-upstream.sh v2.1.0
```

O script cria `update/v2.1.0` a partir de `main` e faz o merge da tag. Em conflito,
ele para e lista os arquivos. Resolva com `CUSTOMIZATIONS.md` do lado:

- arquivo do upstream que nós tocamos → manter a versão deles **+ re-aplicar a nossa linha**
- arquivo 100% nosso em conflito → não deveria acontecer; investigue
- logos/marca → `merge=ours` já resolve sozinho

```bash
git status                    # ver conflitos
# resolver
git add <arquivos>
git commit                    # mensagem padrão do merge já vem preenchida
```

## 3. Build e teste

`php`/`composer` não estão instalados na máquina local — tudo por Docker.

```bash
docker compose build
docker compose up -d
docker compose exec app composer install
docker compose exec app npm ci
docker compose exec app npm run build
docker compose exec app php artisan test
```

Checar também se o release introduziu env var nova:

```bash
git diff v2.0.0 v2.1.0 -- .env.example
```

Toda var nova tem que entrar no `compose.prod.yaml` do repo `midiamax-infra`.

## 4. Staging com dump de produção

```bash
# no repo midiamax-infra
scripts/backup-db.sh                 # backup da produção, sempre
scripts/restore-db.sh staging <dump> # restaura na staging
```

```bash
docker compose exec app php artisan migrate --pretend   # LER a saída antes
docker compose exec app php artisan migrate
```

`--pretend` existe para pegar migration destrutiva antes dela rodar. O upstream liga
`DB::prohibitDestructiveCommands()` em produção, mas isso não cobre migration
que faz `dropColumn`.

**Smoke test manual, sempre estes 6:**

1. login + 2FA
2. conectar uma conta social (OAuth round-trip completo)
3. agendar um post e ver ele publicar
4. calendário carrega e drag-and-drop funciona
5. checkout do Stripe (modo teste) completa e cria assinatura
6. Horizon processando fila e Reverb conectando (websocket no browser, sem erro no console)

## 5. Publicar

```bash
git checkout main
git merge --no-ff update/v2.1.0
git branch -d update/v2.1.0
git push origin main

git tag -a mm-v2.1.0-1 -m "upstream v2.1.0"
git push origin mm-v2.1.0-1      # NUNCA --tags
```

A tag `mm-v*` dispara `.github/workflows/midiamax-image.yml`, que builda e publica
`ghcr.io/leonardomarcelo/midiamax-app:mm-v2.1.0-1`.

## 6. Produção

No repo `midiamax-infra`:

```bash
scripts/backup-db.sh                      # de novo, imediatamente antes
scripts/deploy.sh mm-v2.1.0-1
```

Depois do deploy, confirmar:

- `/codigo-fonte` mostra a versão e o commit **novos** (obrigação AGPL §13)
- Horizon sem job falhando
- um post agendado publica

## Rollback

```bash
scripts/deploy.sh mm-v2.0.0-3        # tag anterior
```

Se a migration do release novo for destrutiva, imagem antiga **não basta** —
restaure o dump. É por isso que o backup do passo 6 não é opcional.

---

## Automação de alerta

`.github/workflows/midiamax-upstream-check.yml` roda toda segunda-feira, tenta o
merge da última tag do upstream num branch descartável e abre/atualiza uma issue
dizendo se o merge é limpo e quais arquivos conflitam. Serve para descobrir o
estrago **antes** de precisar atualizar com urgência (ex.: correção de segurança).
