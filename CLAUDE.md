# MidiaMax — regras permanentes deste repositório

Este repo é um **fork do TryPost** (https://github.com/trypostit/trypost, AGPL-3.0)
operado como SaaS sob a marca **MidiaMax**. A estratégia completa está em
`docs-midiamax/`. Este arquivo é a versão curta e vale como regra, não sugestão.

Para convenções de código do upstream (Laravel/Inertia/Pest), leia também `AGENTS.md`.

## Identidade

- Usuário GitHub: **sempre `LeonardoMarcelo`**. Nunca outro nome/conta.
- Repos: `LeonardoMarcelo/midiamax-app` (público, este) e
  `LeonardoMarcelo/midiamax-infra` (privado, deploy e segredos).
- Imagem Docker: `ghcr.io/leonardomarcelo/midiamax-app`.

## Topologia Git (não alterar)

| Ref | O que é |
| --- | --- |
| `upstream` | `trypostit/trypost`. **Push desabilitado de propósito.** Só `fetch`. |
| `origin` | `LeonardoMarcelo/midiamax-app`, o fork público. |
| `vendor` | Espelho intocado de `upstream/main`. Nunca commitar aqui. |
| `main` | Nossa produção. Nasceu da **tag** `v2.0.0`, não de `upstream/main`. |

Regras:

1. **Atualização só por tag do upstream** (`v2.1.0`, …), nunca seguindo `upstream/main`.
   O upstream commita todo dia e mantém ~16 branches de agente abertas.
2. **`git merge`, nunca `git rebase`** contra o upstream. Rebase reescreve história
   (quebra deploy e CI) e força re-resolver os mesmos conflitos a cada release.
   `rerere` está ligado para reaproveitar resoluções.
3. **Nunca `git push origin --tags`.** Isso empurraria as tags `v*.*.*` do upstream
   para o nosso fork e dispararia o workflow `release-docker.yml` deles.
   Nossas tags são `mm-v<versão-upstream>-<n>` (ex.: `mm-v2.0.0-1`).
4. Toda atualização passa por `scripts/midiamax/atualizar-upstream.sh` e pelo runbook
   `docs-midiamax/02-runbook-atualizacao.md`. Nunca editar arquivo direto no servidor.

## Regra do diff mínimo (a que decide se isso dói ou não)

Cada linha alterada em arquivo do upstream é conflito futuro garantido.
**Antes de editar um arquivo do upstream, procure o hook equivalente:**

| Preciso de… | Faça assim | Nunca |
| --- | --- | --- |
| Lógica nova | `app/MidiaMax/**` + bind/listener no `MidiaMaxServiceProvider` | editar controller/service do upstream |
| Rota nova | `routes/midiamax.php` | `routes/web.php`, `routes/app.php` |
| Migration | `database/migrations/midiamax/` | `database/migrations/` |
| String nova | `lang-midiamax/{pt-BR,en}/` → `__('midiamax::…')` | editar `lang/pt-BR/*.php` |
| Config | `config/midiamax.php` + env | editar `config/trypost.php` |
| Tema/cores | sobrescrever CSS vars em `resources/css/midiamax-theme.css` | editar `resources/css/app.css` |
| Trocar classe do upstream | `$this->app->bind(Upstream::class, Nossa::class)` | editar a classe original |
| Nome da marca | `APP_NAME` (env) + `VITE_APP_NAME` (build arg) | hardcode em view/componente |
| Logo | substituir o arquivo em `public/images/trypost/` (tem `merge=ours`) | renomear o caminho |

Se **não existir** hook e for inevitável tocar arquivo do upstream:
registre em `CUSTOMIZATIONS.md` (arquivo, linhas, motivo, como re-aplicar) no
**mesmo commit**. Esse arquivo é a checklist de conflito de todo merge.

### Nunca criar pasta nossa dentro de diretório que o upstream varre

Arquivo novo em pasta nova é seguro. Pasta nova **dentro** de uma pasta do
upstream não é: se o código ou os testes deles enumeram aquele diretório, a nossa
pasta entra na lista como se fosse um item deles.

Foi o que aconteceu com `lang/midiamax/`: cinco testes fazem
`glob(lang_path('*'))` e tratam **cada diretório como um locale**, então
"midiamax" virou um locale e `TranslationKeysTest` quebrou tentando ler
`lang/midiamax/repurposes.php`. Daí `lang-midiamax/` na raiz, com hífen.

Antes de criar pasta dentro de diretório do upstream, audite:

```bash
grep -rnE "glob\(|File::directories|scandir|Finder" tests/ app/ | grep -i <diretorio>
```

Sem glob sobre ele, pode. Com glob, use `<nome>-midiamax/` na raiz.
`database/migrations/midiamax/` é seguro porque os globs de migration usam
padrão de nome de arquivo (`migrations/*_nome.php`), não listagem de pasta.

Hoje o diff é de **4 arquivos** (15 linhas + o README substituído, que tem `merge=ours`). Esse número é a métrica de saúde do
fork — se ele crescer rápido, o custo de atualizar cresce junto.

## Compliance AGPL-3.0 (obrigação legal, não opcional)

- Vender o SaaS é permitido. Rodar versão **modificada** como serviço de rede
  obriga a oferecer o fonte correspondente aos usuários (§13).
- Por isso o repo `midiamax-app` é **público** e a rota `/codigo-fonte` existe.
  `MIDIAMAX_COMMIT`/`MIDIAMAX_VERSION` são injetados no build e precisam apontar
  para o commit **exato** em produção.
- Nunca remover `LICENSE.md` nem avisos de copyright.
- **Nenhum segredo neste repo.** Licença exige código, não chaves. Segredo vai em
  `midiamax-infra` (privado). Antes de qualquer push, conferir `.env*`, seeders e fixtures.
- Marca "TryPost", logo e domínio **não** estão cobertos pela licença de copyright —
  rebrand tem que ser completo.

Detalhes e checklist: `docs-midiamax/04-compliance-agpl.md`.

## Ambiente local

- **`php` e `composer` não estão instalados nesta máquina.** Rodar tudo por Docker:
  `docker compose run --rm app php artisan …` / `docker compose exec app …`.
- Desligue formatação automática (Prettier/format-on-save) para arquivos do upstream.
  O editor já reformatou `MEDIA_INTEGRATIONS_SETUP.md` uma vez e isso vira conflito
  de merge sem nenhum ganho. Se acontecer: `git checkout -- <arquivo>`.
- `.claude/settings*.json` e `compose.override.yaml` estão em `.git/info/exclude` —
  config pessoal não entra no diff. (`compose.override.yaml` **não** está no
  `.gitignore` do upstream, apesar do comentário no `.example` dizer que está.)
- Referência completa de variáveis de ambiente, com origem de cada credencial e
  escopos por plataforma: `docs-midiamax/06-variaveis-ambiente.md`. O arquivo
  preenchível é o `.env.example` do repo `midiamax-infra`.

## Antes de vender (bloqueadores reais, maiores que o código)

Os gargalos são aprovação de app nas plataformas (Meta, TikTok, LinkedIn, YouTube),
custo da API do X, e LGPD — não o fork. Status e passos:
`docs-midiamax/05-apis-redes-sociais.md`.
