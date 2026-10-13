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
