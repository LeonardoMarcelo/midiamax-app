# 00 — Estratégia: revender TryPost como MidiaMax

Decidido em 13/10/2026. Leia primeiro; o resto de `docs-midiamax/` é execução.

## O que estamos comprando

TryPost (`trypostit/trypost`), **AGPL-3.0-only**. Laravel 13 / PHP 8.2+ / Vue+Inertia+TS /
Postgres / Redis / Docker. Criado em jan/2026, release `v2.0.0` em 10/10/2026, commits
quase diários, releases tagueadas ~mensais.

**O modo SaaS já vem pronto** — isso é o que torna o negócio viável:

- `SELF_HOSTED=false` liga o fluxo de assinatura
- `laravel/cashier` (Stripe): planos, trial de 8 dias, cupons de primeiro mês, checkout
- Workspaces = multi-tenancy real, com isolamento por cliente
- Passport (API/MCP), Horizon (filas), Reverb (websockets), PostHog, Nightwatch

Não precisamos construir billing nem tenancy. O trabalho é marca, operação,
credenciais próprias e conformidade.

## As 3 regras que a licença impõe

1. **Pode vender.** AGPL permite uso comercial e hospedagem paga, sem royalty.
2. **Modificou e serviu pela rede → tem que oferecer o fonte** aos usuários (§13).
   Por isso `midiamax-app` é público e existe a rota `/codigo-fonte`.
3. **Marca não é licenciada.** "TryPost", logo e domínio são marca. Rebrand completo.

Consequência estratégica: **nosso diferencial não pode ser código**, porque o código
é obrigatoriamente público. O concorrente — inclusive o próprio mantenedor, que opera
o SaaS em trypost.it — pode copiar qualquer feature nossa.

Diferencial defensável: nicho, português do Brasil, suporte humano, onboarding
feito-pra-você, integrações locais (Pix, WhatsApp, agências/franquias), preço, SLA.

## Arquitetura de repositórios

| Repo | Visibilidade | Conteúdo |
| --- | --- | --- |
| `LeonardoMarcelo/midiamax-app` | **público** | aplicação + nossas modificações |
| `LeonardoMarcelo/midiamax-infra` | **privado** | `.env` de produção, compose, Caddy, scripts de deploy e backup |

Por que o app é público: o §13 nos obriga a publicar as modificações de qualquer
forma. Lutar contra isso com mirror privado dá trabalho e não muda a obrigação.
Em troca ganhamos o "Sync fork" do GitHub e a possibilidade de mandar PR de correção
de volta — o que reduz o nosso diff.

Segredo **não é** código-fonte: a licença exige o código, nunca as chaves. Por isso
o `.env` real e as credenciais ficam no repo privado.

## Princípio operacional

> **Diff mínimo.** Cada linha alterada em arquivo do upstream é um conflito futuro.

Começamos com diff zero e só adicionamos modificação que pague o próprio custo de
manutenção. Hoje: 3 arquivos, 15 linhas (ver `CUSTOMIZATIONS.md`). Todo o resto da
customização vive em arquivos novos, pendurados num único service provider.

Marca e configuração saem por env e build arg, não por edição de código.

## Riscos aceitos

| Risco | Mitigação |
| --- | --- |
| Projeto novo (9 meses), breaking changes reais (v1→v2 em 5 meses) | Ancorar em tags, nunca em `main`; staging obrigatório antes de produção |
| Mantenedor é concorrente direto (trypost.it) | Diferencial em serviço e nicho, não em feature |
| Relicenciamento futuro: eles detêm o copyright e podem fechar versões novas | A versão que temos continua AGPL para sempre. Diff mínimo mantém o fork independente viável |
| AGPL permite que nossos próprios clientes self-hostem | Preço e proposta de valor focados em "não quero operar servidor" |
| Aprovação de app nas plataformas é o gargalo real | Iniciar os pedidos **antes** do produto estar pronto — ver `05-apis-redes-sociais.md` |

## Ordem de execução

1. ✅ Fork + topologia git + camada de customização + docs *(feito)*
2. Pedidos de app review nas plataformas (Meta, TikTok, LinkedIn, Google) — **começa agora, é o caminho crítico**
3. Conta Stripe + planos/preços em BRL
4. VPS + deploy de staging a partir da nossa imagem
5. Identidade visual (logo, cores, domínio) + páginas legais (termos, privacidade, LGPD)
6. Deploy de produção + primeiros clientes
