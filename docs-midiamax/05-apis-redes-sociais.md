# 05 — APIs das redes sociais: o gargalo real

Este é o caminho crítico do projeto, não o código. O fork fica pronto em um dia;
aprovação de app na Meta e na TikTok leva semanas e pode ser recusada.

**Comece os pedidos antes do produto estar pronto.** Vários processos exigem um app
funcionando com domínio público, política de privacidade e vídeo de demonstração —
então precisamos de staging público cedo.

Credenciais são **nossas**, nunca as do upstream. Cada plataforma vira um par
`<PLATAFORMA>_CLIENT_ID` / `_SECRET` no `compose.prod.yaml` do repo `midiamax-infra`.
Redirect URI padrão da aplicação: `${APP_URL}/accounts/<plataforma>/callback`.

## As 15 plataformas suportadas pelo código

Fonte: `app/Enums/SocialAccount/Platform.php`.

### Grupo A — review pesado, verificação de empresa (semanas)

| Plataforma | O que precisa |
| --- | --- |
| Facebook (página) | App Review para `pages_manage_posts` + `pages_read_engagement`, Business Verification com CNPJ |
| Instagram + Instagram-Facebook | App Review para `instagram_content_publish` / `instagram_basic`. Conta do cliente tem que ser Business/Creator vinculada a uma Página |
| Threads | Threads API, dentro do mesmo app Meta |
| TikTok | Content Posting API com **audit** para "Direct Post"; sem o audit só dá para mandar rascunho para o app do usuário |
| YouTube | Google Cloud project + OAuth verification (escopo sensível) + **aumento de quota** |
| Google Business Profile | Formulário de solicitação de acesso à API, aprovação manual do Google |

O que a Meta pede na prática: app em modo Live, URL pública de política de
privacidade e de exclusão de dados, screencast mostrando o fluxo completo de
autorização e publicação, e verificação do negócio com documento do CNPJ.

### Grupo B — review moderado

| Plataforma | O que precisa |
| --- | --- |
| LinkedIn (perfil) | Produto "Share on LinkedIn" / `w_member_social` — self-service, rápido |
| LinkedIn Page | Community Management API — aprovação de parceiro, bem mais difícil |
| Pinterest | App em "standard access"; produção exige review |

### Grupo C — pago

| Plataforma | O que precisa |
| --- | --- |
| X (Twitter) | Tier pago da API para publicar. Entra direto no custo unitário por cliente — modelar no preço antes de lançar |

### Grupo D — self-service (dá para lançar com estas)

| Plataforma | O que precisa |
| --- | --- |
| Bluesky | AT Protocol, app password do próprio usuário |
| Mastodon | app por instância |
| Telegram | bot token |
| Discord | bot/webhook |

## Armadilha de SaaS: quota é por APP, não por cliente

Self-host tem 1 app para 1 empresa. SaaS tem 1 app para **todos** os clientes —
todo rate limit e toda quota passam a ser compartilhados, e um cliente grande
degrada os outros.

O caso mais duro é o YouTube: a quota default do Data API é 10.000 unidades/dia e
um upload de vídeo custa ~1.600 unidades — ou seja, ~6 uploads por dia **no total**,
para a plataforma inteira. Sem aumento de quota aprovado, não existe produto.
(Confirmar os números atuais no console do Google antes de prometer feature.)

Implicações a tratar antes de vender:

- pedir aumento de quota/rate limit em cada plataforma, justificando o uso multi-tenant
- monitorar consumo por plataforma (Horizon + Nightwatch já dão parte disso)
- ter mensagem de erro honesta no produto quando a quota da plataforma estourar
- escalonar: planos com limite de publicações, fila com prioridade

Alguns termos de plataforma tratam "publicar em nome de terceiros" como caso de uso
específico, e revenda/white-label pode ter regra própria. Ler os termos de cada uma
antes de cobrar, não depois.

## Ordem recomendada

1. **Instagram + Facebook** — é o que o cliente brasileiro realmente quer, e é o
   processo mais longo. Abrir primeiro, mesmo com o produto incompleto.
2. **TikTok** — segundo processo mais longo, audit separado.
3. **LinkedIn perfil** + **Grupo D** — rápido, permite ter produto demonstrável e
   fechar os primeiros clientes enquanto Meta e TikTok estão em análise.
4. **YouTube** — abrir o pedido de quota cedo, é o que mais demora a liberar.
5. **X** — só quando o preço do plano fechar a conta.
6. **Pinterest, LinkedIn Page, Google Business** — depois.

## Pré-requisitos que travam todos os pedidos

- [ ] Domínio próprio com HTTPS e staging público
- [ ] Política de privacidade publicada (LGPD)
- [ ] Termos de uso publicados
- [ ] Endpoint/página de exclusão de dados (a Meta exige)
- [ ] CNPJ para Business Verification
- [ ] Vídeo de demonstração do fluxo de autorização + publicação

Os links de Termos e Privacidade são configuráveis no `config/trypost.php`
(chave `legal`) — o próprio upstream deixa isso pronto porque o review da TikTok
exige que apareçam nas telas de autenticação.
