# MidiaMax

Agendamento de redes sociais. Planeje o mês no calendário, arraste um post para
outro horário e publique nativamente nas principais plataformas.

Este repositório é o **código-fonte do serviço hospedado em MidiaMax** — publicado
em cumprimento à seção 13 da AGPL-3.0, que dá a quem usa o serviço o direito ao
código da versão em execução.

## Fork do TryPost

MidiaMax é um fork de **[TryPost](https://github.com/trypostit/trypost)**, projeto
open source licenciado sob AGPL-3.0, mantido por terceiros. Todo o crédito pela
base do produto é deles.

MidiaMax é um serviço independente, sem vínculo, parceria ou endosso do TryPost.
"TryPost" é marca dos seus respectivos detentores.

Nossas modificações sobre o upstream estão inventariadas em
[`CUSTOMIZATIONS.md`](CUSTOMIZATIONS.md), e as regras de operação do fork em
[`CLAUDE.md`](CLAUDE.md) e [`docs-midiamax/`](docs-midiamax/).

## Código da versão em execução

A aplicação em produção expõe a rota **`/codigo-fonte`**, que mostra a versão e o
commit exatos no ar e permite baixar aquele código.

Este repositório contém **apenas código**. Configuração de produção e segredos
(chaves, credenciais, `.env`) não fazem parte do "Corresponding Source" exigido
pela licença e ficam num repositório privado separado.

## Rodando por conta própria

A AGPL permite usar, modificar, hospedar e redistribuir, inclusive
comercialmente. Para instruções de self-host, use a documentação do projeto
original: https://docs.trypost.it

Precisa das suas próprias credenciais de app em cada plataforma (Meta, TikTok,
LinkedIn, Google etc.) — as nossas não são distribuídas.

## Licença

[GNU Affero General Public License v3.0](LICENSE.md), herdada do TryPost.
Se você rodar uma versão modificada como serviço de rede, precisa disponibilizar
suas modificações aos usuários dela (AGPL §13).
