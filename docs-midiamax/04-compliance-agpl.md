# 04 — Compliance AGPL-3.0

Não é burocracia opcional: é a condição que nos dá o direito de usar e vender o
software. Violar a AGPL faz a licença **terminar** (§8) — sem licença, qualquer uso
passa a ser violação de copyright.

Isto é orientação de engenharia, não parecer jurídico. Antes de faturar o primeiro
cliente, passe o modelo por um advogado — especialmente a parte de código proprietário.

## O que a licença permite

- Usar, modificar, hospedar e **cobrar** por isso. Sem royalty, sem autorização prévia.
- Rebrandear (a licença de copyright permite; **marca é outra história** — ver abaixo).
- Redistribuir, inclusive comercialmente.

## O que a licença obriga

### §13 — oferta de fonte para usuários da rede

Rodando versão **modificada** como serviço, todo usuário que interage com ela tem
direito ao "Corresponding Source" **daquela versão**.

Como cumprimos:

| Peça | Onde |
| --- | --- |
| Repo público com nossas modificações | `github.com/LeonardoMarcelo/midiamax-app` |
| Página de oferta, pública e sem login | rota `/codigo-fonte` (`routes/midiamax.php`) |
| Commit exato em produção | `MIDIAMAX_COMMIT` injetado no build da imagem |
| Download da versão exata | tarball `…/archive/<commit>.tar.gz` |

**Pendência:** colocar o link para `/codigo-fonte` no footer da aplicação e na página
de login. Hoje a rota existe mas não está linkada em lugar nenhum — isso é o mínimo
para "provides a way for users to get its source".

### §5 — preservar avisos

`LICENSE.md` e os avisos de copyright ficam. Nossas modificações podem levar nosso
copyright **adicional**, nunca substituir o deles.

### §4/§6 — o todo continua AGPL

Nossas modificações herdam a AGPL. Não existe "parte privada" dentro do mesmo
programa: tema, branding, integrações nossas — tudo é publicável e publicado.

## O que a licença NÃO cobre: marca

"TryPost", o logo e o domínio são **marca registrada/trade dress**, não copyright.
A licença de software não concede uso de marca. Portanto:

- rebrand completo: `APP_NAME`, `VITE_APP_NAME`, logos, favicon, e-mails transacionais
- não usar "TryPost" no marketing, no domínio, no nome do app ou em comparativo enganoso
- atribuição honesta é permitida e saudável: "baseado no projeto open source TryPost"
  na página `/codigo-fonte`, sem sugerir endosso ou parceria

## Segredo ≠ código-fonte

A licença exige o código, nunca a configuração. Ficam **fora** do repo público:

- `.env` de produção, senhas de banco, `APP_KEY`, chaves do Passport
- client id/secret das plataformas sociais
- chaves de Stripe, S3/R2, SMTP, PostHog
- dados de cliente (óbvio, mas: nunca dump nem fixture com dado real)

Tudo isso vive em `LeonardoMarcelo/midiamax-infra` (privado).

**Antes de cada push no repo público:**

```bash
git diff --cached | grep -iE "sk_live|sk_test|_secret|_token|APP_KEY|BEGIN (RSA|PRIVATE)"
git ls-files | grep -E "^\.env" ; echo "(só .env.example/.ci/.testing devem aparecer)"
```

## Se um dia quisermos código proprietário

Para ficar fora do alcance da AGPL, a parte proprietária tem que ser um **programa
separado** de verdade — processo próprio, comunicação por rede/API, sem linkar nem
compartilhar estruturas internas. É zona cinzenta e a FSF interpreta de forma
restritiva. Decisão de advogado, antes de escrever a primeira linha.

Alternativa mais segura: o diferencial é serviço, não código. Ver `00-estrategia.md`.

## Checklist antes de vender

- [ ] `LICENSE.md` presente e intocado
- [ ] Link para `/codigo-fonte` no footer e na tela de login
- [ ] `MIDIAMAX_COMMIT` e `MIDIAMAX_VERSION` corretos na imagem em produção
- [ ] `/codigo-fonte` abre sem login e o tarball baixa
- [ ] Repo `midiamax-app` público e sincronizado com o que está em produção
- [ ] Nenhuma string "TryPost" visível ao usuário final (exceto a atribuição)
- [ ] Varredura de segredos no histórico do repo público
- [ ] Termos de uso e política de privacidade publicados (LGPD) — também exigidos no
      app review da TikTok e da Meta
- [ ] Revisão de advogado sobre o modelo de revenda
