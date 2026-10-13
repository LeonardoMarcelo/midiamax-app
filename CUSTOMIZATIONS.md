# Inventário de customizações MidiaMax

**Para que serve:** esta é a checklist de conflito de todo merge com o upstream.
A seção 1 lista cada arquivo do **upstream** que nós tocamos — são os únicos
lugares onde `git merge` pode conflitar de verdade. A seção 2 lista o que é 100%
nosso (arquivo novo = zero conflito, mas pode quebrar por mudança de API do upstream).

Regra: tocou arquivo do upstream? Registre aqui **no mesmo commit**.

Base atual: tag upstream **v2.0.0** (`7b9eeec9`).

---

## 1. Arquivos do upstream modificados — 30 arquivos

Eram 4 até o rebrand. Subiu para 30 em 13/10/2026 ao trocar a marca TryPost por
MidiaMax na interface e nos e-mails. **Esse é o custo real de reconfigurar uma
marca num fork**, e cada arquivo aqui é conflito potencial em todo merge.

Divididos por risco de conflito:

| Grupo | Arquivos | Risco |
| --- | --- | --- |
| Núcleo (abaixo) | `bootstrap/providers.php`, `resources/css/app.css`, `.gitattributes` | baixo, 15 linhas |
| `README.md` | 1 | nenhum — `merge=ours` |
| Imagens de marca | 5 em `public/` | nenhum — `merge=ours` |
| E-mails | 13 em `resources/views/mail/` | médio — mudança mecânica, ver abaixo |
| Frontend | 7 em `resources/js` e `resources/views` | médio |

### Rebrand dos e-mails (13 arquivos) — mudança mecânica

Cada template teve: URLs `https://trypost.it` → `{{ config('app.url') }}`,
`>TryPost</a>` → `{{ config('app.name') }}`, copyright e `alt` idem, e a
**tabela de redes sociais do upstream removida** (linkava GitHub, X, YouTube,
Discord e Instagram *deles* — mandar nosso cliente para lá é pior do que o nome
errado).

Em conflito de merge, a resolução é re-aplicar o mesmo script:

```python
TABELA = re.compile(r'\n\s*<table role="presentation"(?:(?!</table>).)*?/images/emails/social/.*?</table>', re.S)
subs = [(r'href="https://trypost\.it"', 'href="{{ config(\'app.url\') }}"'),
        (r'>TryPost</a>', ">{{ config('app.name') }}</a>"),
        (r'alt="TryPost"', 'alt="{{ config(\'app.name\') }}"')]
```

### Frontend (7 arquivos)

| Arquivo | O que mudou |
| --- | --- |
| `resources/js/components/AppLogo.vue` | mark novo (3 barras ascendentes) e wordmark vindo do prop `name` em vez de cravado |
| `resources/js/components/WorkspaceMenuContent.vue` | menu de ajuda lê `config('midiamax.links')` via `Inertia::share`; item sem link não aparece; bloco de status só com `MIDIAMAX_STATUS_URL` |
| `resources/js/lib/docs.ts` | `DOCS_URL` vem de `VITE_DOCS_URL` |
| `resources/js/pages/settings/workspace/Mcp.vue` | idem |
| `resources/js/app.ts` | fallback do nome |
| `resources/views/app.blade.php` | fallback do `<title>` |
| `resources/views/vendor/mail/html/header.blade.php` | `alt` do logo |
| `resources/js/pages/settings/account/Billing.vue` | estado vazio (ver seção 4) |

**O que NÃO foi trocado, de propósito:**

- `resources/js/types/post.ts` → `PostOrigin.TryPost = 'trypost'` é **valor
  gravado no banco**, discriminando post criado no app × importado da rede.
  Trocar quebra dado existente.
- Defaults em `config/app.php`, `config/mail.php` e `config/trypost.php` — são
  `env(..., 'TryPost…')` e **nunca disparam**, porque o `.env` define todos.
  Trocar aumentaria o diff sem mudar nada do que o usuário vê.
  `TRYPOST_USER_AGENT` é a exceção que importa e está definida no `.env`: sem
  ela, as requisições às plataformas se identificam como TryPost.

---

### Os 3 arquivos do núcleo

### `bootstrap/providers.php` (+2 linhas)

Registra o `MidiaMaxServiceProvider`, sempre como **último** da lista (precisa
rodar depois do `AppServiceProvider` para poder sobrescrever binds dele).

- **Conflito esperado:** só se o upstream adicionar/remover provider. Resolução:
  manter os providers deles + a nossa linha no fim.
- **Re-aplicar:** adicionar o `use App\MidiaMax\Providers\MidiaMaxServiceProvider;`
  e `MidiaMaxServiceProvider::class,` no fim do array.

### `resources/css/app.css` (+1 linha)

`@import './midiamax-theme.css';` como **último** `@import`, antes do `@plugin`.

- **Por que nessa posição:** `@import` em CSS só é válido no topo do arquivo.
  Nosso tema vence a ordem por especificidade (`:root:root`), não por posição —
  ver comentário em `resources/css/midiamax-theme.css`.
- **Conflito esperado:** se o upstream mexer no bloco de imports. Resolução:
  manter os imports deles e deixar o nosso por último.

### `.gitattributes` (+12 linhas)

Bloco `# ===== MidiaMax =====` com `merge=ours` nos arquivos de marca (logos,
favicons, README), para que merge do upstream nunca sobrescreva nossa identidade.

- **Exige** `git config merge.ours.driver true` em cada clone —
  `scripts/midiamax/setup-clone.sh` faz isso.
- **Conflito esperado:** baixo (append no fim). Resolução: manter os dois blocos.

### `README.md` (substituído)

O repo é público, então a primeira coisa que alguém lê tem que dizer o que ele é:
o fonte do serviço MidiaMax, publicado por obrigação da AGPL §13, fork do TryPost
com crédito e sem sugerir vínculo.

- **Conflito esperado:** nenhum — `merge=ours` no `.gitattributes`.
- **Efeito colateral:** mudanças de README do upstream são descartadas em silêncio.
  Se precisar ver o que eles escreveram: `git show v2.1.0:README.md`.

---

## 2. Arquivos 100% nossos (sem conflito possível)

| Caminho | Papel | Risco |
| --- | --- | --- |
| `app/MidiaMax/**` | Toda lógica nossa. Provider é o único ponto de entrada. | Bind quebra se o upstream renomear a classe alvo |
| `config/midiamax.php` | Marca + oferta de fonte AGPL | — |
| `routes/midiamax.php` | Rotas públicas nossas (`/codigo-fonte`, legais) | Colisão de path se o upstream criar a mesma rota |
| `routes/midiamax-webhooks.php` | Alias `POST /api/v1/webhooks/cobranca/stripe` → `WebhookController` do Cashier. Carregado **sem** o grupo `web` (webhook não tem sessão nem CSRF) | Quebra se o Cashier renomear o controller |
| `resources/views/midiamax/layout.blade.php` | Layout das páginas públicas. HTML puro de propósito | — |
| `resources/views/midiamax/source.blade.php` | Página de oferta de fonte (§13) | — |
| `resources/views/midiamax/legal/*.blade.php` | Termos, privacidade e exclusão de dados. Exigidos por app review e LGPD | Precisam de revisão jurídica antes de submeter app review |
| `resources/css/midiamax-theme.css` | Override das CSS vars | Upstream renomear variável de tema |
| `lang-midiamax/{pt-BR,en}/` | Strings novas, namespace `midiamax::` | — |
| `database/migrations/midiamax/` | Migrations nossas | — |
| `docs-midiamax/**`, `CLAUDE.md`, `CUSTOMIZATIONS.md` | Documentação | — |
| `scripts/midiamax/**` | Automação de update e setup | — |
| `.github/workflows/midiamax-*.yml` | CI nossa (check de upstream, build de imagem) | — |

## 3. Binds que substituem classes do upstream

Zero linhas alteradas em arquivo do upstream — mas **bind não avisa quando a
classe original muda**. Reconferir cada item a cada release.

### `App\Http\Middleware\App\SetLocale` → `App\MidiaMax\Http\Middleware\SetLocale`

O original usa `Auth::user()?->locale ?? Locale::DEFAULT`, e `Locale::DEFAULT` é
`English` cravado em `app/Enums/User/Locale.php`. Consequência: visitante não
autenticado vê login, cadastro, recuperação de senha e `/codigo-fonte` sempre em
inglês, ignorando `APP_LOCALE`. Para um SaaS brasileiro isso é defeito de produto.

A nossa versão usa `APP_LOCALE` como fallback de visitante; usuário autenticado
continua mandando, igual ao upstream.

Funciona porque o pipeline do Laravel resolve middleware pelo container, então
`bootstrap/app.php` (que registra `SetLocale::class` no grupo `web`) fica intocado.

- **Conferir a cada release:** `git diff <base> <nova-tag> -- app/Http/Middleware/App/SetLocale.php app/Enums/User/Locale.php`
- **Verificar que o bind ainda vale:**
  `php artisan tinker --execute="echo get_class(app(App\Http\Middleware\App\SetLocale::class));"`

## 4. Correções de bug do upstream feitas por cima da config

Em `MidiaMaxServiceProvider::normalizeUpstreamConfig()`. Zero linhas em arquivo
do upstream. Cada item é candidato a PR — quando for aceito, remover daqui.

### `cashier.trial_days` precisa de cast para int

`config/cashier.php:143` faz `env('CASHIER_TRIAL_DAYS', 8)` sem cast e
`app/Actions/User/CreateUser.php:39` passa o valor direto para
`now()->addDays()`, que exige `int|float`.

Com a variável definida no `.env` — e o próprio `.env.example` do upstream traz
`CASHIER_TRIAL_DAYS=8` — o valor chega como string `'8'` e o **cadastro quebra
com TypeError do Carbon**. Em modo SaaS com `REQUIRE_CARD_FOR_TRIAL=false`,
ninguém consegue criar conta. Sem a variável definida o default `int` funciona,
o que deixa o bug invisível.

- **Conferir a cada release:** `git diff <base> <nova-tag> -- config/cashier.php app/Actions/User/CreateUser.php`
- **Verificar:** `php artisan tinker --execute="echo gettype(config('cashier.trial_days'));"` → `integer`

### Preço exibido ≠ preço cobrado (override de tradução)

`MidiaMaxServiceProvider::alignBillingLabels()`.

O upstream **cravou os valores em `lang/<locale>/billing.php`** (chave
`billing.subscribe.prices.*`) e a tela de billing só traduz a chave. O número na
tela não tem relação nenhuma com o `price` do Stripe: trocar o preço lá e não
aqui faz o cliente ver um valor e ser cobrado outro — problema de CDC, não de UX.

Valores em `config/midiamax.billing_labels`, que **têm que bater** com
`STRIPE_SOCIALS_MONTHLY` e `STRIPE_WORKSPACES_MONTHLY`. Conferir os dois juntos
a cada mudança de preço.

**Armadilha do `addLines()`:** ele marca o grupo como carregado. Chamado antes de
`lang/<locale>/billing.php` ser lido, o translator nunca lê o arquivo e **todas**
as outras chaves de billing viram chave crua na tela (acontece em silêncio —
nenhum erro, nenhum teste quebra). Por isso há um `get('billing.plans.monthly')`
forçando a carga antes do override. Verificar depois de qualquer mexida:

```bash
php artisan tinker --execute="app()->setLocale('pt-BR');
  echo __('billing.plans.monthly');"   # tem que sair 'Mensal', nao a chave
```

### Landing page em `/` para visitante

`App\MidiaMax\Http\Middleware\Authenticate` (bind sobre
`Illuminate\Auth\Middleware\Authenticate`).

O upstream não tem landing: a rota `/` dele fica atrás do `auth` e só redireciona
para o calendário — o site de marketing do TryPost é outro projeto, fechado.
Testado: uma rota nossa em `/` **nunca é alcançada**, porque as rotas do upstream
são carregadas antes. Por isso a intervenção é no middleware, que é o ponto onde
se decide o que fazer com o visitante.

Comportamento verificado:

| Quem | Em | Resultado |
| --- | --- | --- |
| visitante | `/` | landing, HTTP 200, sem redirect |
| visitante | `/settings/profile` | 302 para `/login` (link profundo preservado) |
| autenticado | `/` | 302 para `/schedule/calendar` |

- **Conferir a cada release:** `git diff <base> <nova-tag> -- routes/app.php`
  (se mudarem a rota `/`) e a assinatura de `Authenticate::handle()` do framework.
- 121 testes de `tests/Feature/Auth` passando com o bind ativo.

### Estado vazio da tela de billing

`resources/js/pages/settings/account/Billing.vue`.

O upstream esconde a página inteira atrás de `v-if="hasSubscription"` e **não
tem `v-else`**. Com `REQUIRE_CARD_FOR_TRIAL=false` não existe assinatura durante
o teste, então `/settings/account/billing` ficava **completamente em branco**
para todo usuário em trial. Com `REQUIRE_CARD_FOR_TRIAL=true` o problema não
aparece, porque o checkout cria a assinatura antes.

As strings novas chegam pelo prop `midiamax.billing` (`Inertia::share`), não por
`$t()`: o `laravel-vue-i18n` do frontend só lê o JSON gerado a partir de `lang/`,
e as nossas traduções vivem em `lang-midiamax/`.

- **Conferir a cada release:** `git diff <base> <nova-tag> -- resources/js/pages/settings/account/Billing.vue`

### Marca nas traduções e depoimentos de terceiros

Havia **46 chaves com "TryPost" em cada um dos 16 idiomas** — 736 strings,
incluindo `auth.login.title` ("Entrar no TryPost").

Resolvido por **plugin de Vite nosso** (`resources/js/midiamax/vite-rebrand-i18n.ts`,
registrado em `vite.config.ts` logo depois do `i18n()`): ele reescreve os
`lang/php_<locale>.json` **gerados**, deixando os arquivos de idioma do upstream
intocados. Troca acompanha sozinha qualquer string nova que eles criem — 16
diretórios de idioma ficaram fora do nosso diff.

Resultado medido: 736 → 16 ocorrências, todas em `auth.reviews.*`, excluídas de
propósito.

**Por que `auth.reviews.*` fica de fora:** são depoimentos de pessoas e empresas
**reais** elogiando o TryPost (Paulo Dantas/chatadv.com.br, Diego/Globalfy.com,
Pedro/templated.io…), com foto, mais um selo "5/5 no G2". Trocar a marca ali
criaria endosso falso atribuído a gente identificável e uma nota de avaliação
que a MidiaMax não tem.

Por isso o painel que exibia esses depoimentos foi **removido** das telas de
login e cadastro (`panel` deixou de ser passado em `pages/auth/Login.vue` e
`Register.vue`; o layout já suporta coluna única). A exclusão no plugin é a
segunda trava, caso alguém reative o painel.

Quando houver depoimentos reais de clientes da MidiaMax, dá para reativar o
`panel` e preencher `auth.reviews.*` pelo mesmo mecanismo de override.

## 5. Decisões que valem lembrar

- **`release-docker.yml` do upstream foi mantido intocado.** Ele dispara em tags
  `v*.*.*`; nossas tags são `mm-v*`, então ele nunca roda por acidente — desde que
  ninguém faça `git push origin --tags`.
- **Branding do frontend é build arg** (`VITE_APP_NAME`), não edição de componente:
  o `docker/Dockerfile` já expõe `VITE_APP_NAME`, `VITE_REVERB_*`, `VITE_POSTHOG_*`.
- **Imagem própria é obrigatória**, não opcional: a imagem publicada pelo upstream
  tem `VITE_REVERB_HOST=localhost` e a chave do Reverb cravados no bundle. Domínio
  próprio exige rebuild.
- **Logos ficam em `public/images/trypost/`** com o nome original. Renomear a pasta
  exigiria editar os componentes Vue que referenciam o caminho — diff maior, zero ganho.
- **Traduções em `lang-midiamax/`, não em `lang/midiamax/`.** Cinco testes do
  upstream fazem `glob(lang_path('*'))` e tratam cada diretório como um locale
  (`tests/Unit/WebhookTranslationsTest.php`, `tests/Unit/PostsErrorsTranslationTest.php`,
  `tests/Feature/Repurpose/TranslationKeysTest.php`). Com a pasta dentro de `lang/`,
  "midiamax" era lido como locale e o `TranslationKeysTest` quebrava procurando
  `lang/midiamax/repurposes.php`. Um gerador de `lang/php_*.json` (gitignored)
  também produzia um `php_midiamax.json` fantasma. Regra geral em `CLAUDE.md`.
