<?php

declare(strict_types=1);

return [

    // Estado vazio da tela de billing. O upstream esconde a pagina inteira
    // quando nao ha assinatura e nao tem v-else — com trial sem cartao, a tela
    // ficava em branco. Strings entregues ao frontend por Inertia::share(),
    // porque o laravel-vue-i18n so le o JSON gerado a partir de lang/.

    'no_plan' => 'Nenhum plano ativo',
    'no_subscription_hint' => 'Você ainda não tem uma assinatura. Escolha um plano para continuar usando depois do período de teste.',
    'choose_plan' => 'Escolher plano',

];
