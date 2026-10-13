<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Marca
    |--------------------------------------------------------------------------
    |
    | APP_NAME cobre o backend; VITE_APP_NAME (build arg do Docker) cobre o
    | bundle JS. Os dois tem que bater.
    |
    */

    'brand' => [
        'name' => env('APP_NAME', 'MidiaMax'),
        'legal_name' => env('MIDIAMAX_LEGAL_NAME', 'MidiaMax'),
        'support_email' => env('MIDIAMAX_SUPPORT_EMAIL', 'suporte@midiamax.com.br'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Links de apoio exibidos na interface
    |--------------------------------------------------------------------------
    |
    | O upstream cravou no menu de ajuda os links das propriedades DELES (docs,
    | Discord, GitHub, status). Mandar nosso cliente para la e pior do que
    | mostrar o nome errado. Aqui os links sao nossos, e o que estiver vazio
    | simplesmente nao aparece no menu.
    |
    | Compartilhados com o frontend por Inertia::share() no provider.
    |
    */

    'links' => [
        'docs' => env('MIDIAMAX_DOCS_URL'),
        'community' => env('MIDIAMAX_COMMUNITY_URL'),
        'feedback' => env('MIDIAMAX_FEEDBACK_URL'),
        'status' => env('MIDIAMAX_STATUS_URL'),
        'source' => env('MIDIAMAX_SOURCE_URL', 'https://github.com/LeonardoMarcelo/midiamax-app'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Precos exibidos na interface
    |--------------------------------------------------------------------------
    |
    | ATENCAO: o valor que o cliente ve NAO vem do Stripe. O upstream cravou os
    | precos em lang/<locale>/billing.php (chave billing.subscribe.prices.*) e a
    | tela de billing apenas traduz aquela chave. Ou seja: mudar o preco no
    | Stripe nao muda o que aparece na tela — e exibir um valor e cobrar outro e
    | problema de CDC, nao so de UX.
    |
    | Estes rotulos sao injetados por MidiaMaxServiceProvider::alignBillingLabels()
    | e tem que bater com os price IDs em STRIPE_SOCIALS_MONTHLY e
    | STRIPE_WORKSPACES_MONTHLY. Conferir os dois juntos a cada mudanca de preco.
    |
    */

    'billing_labels' => [
        'socials_monthly' => env('MIDIAMAX_PRICE_SOCIALS_MONTHLY', 'R$ 129,99'),
        'socials_yearly' => env('MIDIAMAX_PRICE_SOCIALS_YEARLY', 'R$ 1.299,90'),
        'socials_yearly_per_month' => env('MIDIAMAX_PRICE_SOCIALS_YEARLY_PER_MONTH', 'R$ 108,32'),
        'workspaces_monthly' => env('MIDIAMAX_PRICE_WORKSPACES_MONTHLY', 'R$ 369,99'),
        'workspaces_yearly' => env('MIDIAMAX_PRICE_WORKSPACES_YEARLY', 'R$ 3.699,90'),
        'workspaces_yearly_per_month' => env('MIDIAMAX_PRICE_WORKSPACES_YEARLY_PER_MONTH', 'R$ 308,32'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dados do controlador (paginas legais)
    |--------------------------------------------------------------------------
    |
    | Alimentam /termos, /privacidade e /exclusao-de-dados. As paginas omitem a
    | linha quando o valor esta vazio, para nunca renderizar placeholder — mas
    | app review da Meta e da TikTok exige identificacao do responsavel, entao
    | preencher antes de submeter. Ver docs-midiamax/07-setup-local-ngrok.md.
    |
    */

    'company' => [
        'legal_name' => env('MIDIAMAX_LEGAL_NAME'),
        'cnpj' => env('MIDIAMAX_CNPJ'),
        'address' => env('MIDIAMAX_ADDRESS'),
        'privacy_email' => env('MIDIAMAX_PRIVACY_EMAIL', env('MIDIAMAX_SUPPORT_EMAIL')),
        'jurisdiction' => env('MIDIAMAX_JURISDICTION'),
        'legal_updated_at' => env('MIDIAMAX_LEGAL_UPDATED_AT'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Oferta de codigo-fonte (AGPL-3.0 secao 13)
    |--------------------------------------------------------------------------
    |
    | Rodar uma versao MODIFICADA como servico de rede obriga a oferecer o
    | codigo correspondente a quem usa o servico. commit/version sao injetados
    | no build da imagem e apontam para o commit exato em producao — nao para
    | "o repo em geral". Ver docs-midiamax/04-compliance-agpl.md.
    |
    */

    'source' => [
        'repository' => env('MIDIAMAX_SOURCE_URL', 'https://github.com/LeonardoMarcelo/midiamax-app'),
        'version' => env('MIDIAMAX_VERSION', 'dev'),
        'commit' => env('MIDIAMAX_COMMIT'),
        'upstream' => 'https://github.com/trypostit/trypost',
        'upstream_ref' => env('MIDIAMAX_UPSTREAM_REF', 'v2.0.0'),
    ],

];
