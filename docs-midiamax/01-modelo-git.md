# 01 — Modelo Git do fork

## Topologia

```
upstream (trypostit/trypost) ──fetch──> vendor   (espelho intocado)
                                 │
                                 └─merge tag──>  main  (nossa produção)
                                                  │
                                                  └──> origin (LeonardoMarcelo/midiamax-app)
```

| Ref | Papel | Pode commitar? |
| --- | --- | --- |
| `upstream` | remoto do TryPost. Push **desabilitado** (`DISABLED_NO_PUSH_TO_UPSTREAM`) | — |
| `origin` | nosso fork público | sim |
| `vendor` | espelho de `upstream/main`, só para `git diff` e investigação | **não** |
| `main` | produção. Começou na tag `v2.0.0` | sim |
| `update/<tag>` | branch temporária de integração de release do upstream | sim |

## Config obrigatória em cada clone

```bash
git config rerere.enabled true        # memoriza resolução de conflito e reaproveita
git config rerere.autoupdate true
git config merge.ours.driver true     # necessário pelo merge=ours do .gitattributes
git config merge.conflictstyle zdiff3 # mostra a base comum no conflito
```

`scripts/midiamax/setup-clone.sh` aplica tudo isso.

## As 4 regras

### 1. Atualizar só por tag, nunca por `main`

O upstream commita quase todo dia e mantém ~16 branches de agente abertas
(`cursor/*`, `claude/*`, `codex/*`). `upstream/main` não é um ponto estável.
Releases tagueadas (`v1.0.0` … `v2.0.0`) são.

### 2. `merge`, nunca `rebase`

| | merge | rebase |
| --- | --- | --- |
| Conflito | resolve **uma vez**, `rerere` reaproveita | re-resolve os mesmos a cada release |
| História | preservada; `git log` mostra de onde veio | reescrita; quebra CI, deploy e clones |
| Rollback | `git revert` do merge | reset forçado |

### 3. Nunca `git push origin --tags`

`git fetch upstream --tags` traz as tags `v*.*.*` deles para o nosso clone. Empurrá-las
para o `origin` dispararia o workflow `release-docker.yml` **do upstream**, que roda
no nosso fork e publicaria uma imagem errada (sem `VITE_APP_NAME`, com o host do
Reverb apontando para localhost).

Push sempre explícito:

```bash
git push origin main
git push origin mm-v2.0.0-1     # nossa tag, uma por vez
```

Nossas tags: `mm-v<versão-upstream>-<n>`. `mm-v2.0.0-1` = primeira release nossa
sobre o upstream v2.0.0. Não casa com `v*.*.*`, então só o nosso workflow dispara.

### 4. Nada nosso dentro de arquivo do upstream

A tabela de hooks está em `CLAUDE.md`. Resumo: arquivo novo + `MidiaMaxServiceProvider`.

## Receitas

### Sobrescrever uma classe do upstream

```php
// app/MidiaMax/Providers/MidiaMaxServiceProvider.php → register()
$this->app->bind(\App\Services\Publisher::class, \App\MidiaMax\Services\Publisher::class);
```

Se precisar só mudar um comportamento, estenda a classe do upstream em
`app/MidiaMax/` e sobrescreva o método — assim as mudanças deles continuam chegando.

**Funciona até para middleware**, que é o caso menos óbvio: o pipeline do Laravel
resolve middleware pelo container, então dá para trocar a implementação sem tocar
em `bootstrap/app.php`. Exemplo real já no repo: `SetLocale`
(ver `CUSTOMIZATIONS.md`, seção 3).

Verificar que um bind está de pé:

```bash
docker compose exec app php artisan tinker \
  --execute="echo get_class(app(App\Http\Middleware\App\SetLocale::class));"
```

O preço do bind: ele **não avisa** quando a classe original muda. Todo bind entra
em `CUSTOMIZATIONS.md` com o comando de `git diff` do arquivo a vigiar.

### Sobrescrever uma tradução do upstream

Não edite `lang/pt-BR/*.php`. No `boot()` do provider:

```php
// Carrega lang/midiamax-overrides/ com prioridade sobre o lang/ do upstream
$this->app['translator']->addNamespace(...);  // ou
$this->loadJsonTranslationsFrom(base_path('lang/midiamax-overrides'));
```

Na prática: se for uma string só, avalie se vale. Em geral é mais barato abrir PR
no upstream corrigindo a tradução pt-BR deles.

### Ver o que o upstream mudou desde a nossa base

```bash
git fetch upstream --tags
git log --oneline v2.0.0..upstream/main            # commits novos
git diff --stat v2.0.0 v2.1.0                      # tamanho do próximo release
git diff v2.0.0 v2.1.0 -- bootstrap/providers.php  # mexeram num arquivo nosso?
```

### Ver o nosso diff total (métrica de saúde do fork)

```bash
git diff --stat v2.0.0 -- . ':!docs-midiamax' ':!CLAUDE.md' ':!CUSTOMIZATIONS.md' ':!scripts/midiamax'
```

### Contribuir correção de volta (reduz nosso diff)

```bash
git checkout -b fix/algo upstream/main   # parte do upstream, não do nosso main
# ... correção isolada, sem nada de MidiaMax ...
git push origin fix/algo                 # abrir PR em trypostit/trypost
```

Depois que o PR for aceito, remova a correção do nosso `main` e deixe o merge trazê-la.
