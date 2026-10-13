<?php

declare(strict_types=1);

return [

    // Estado vazio da tela de billing. O upstream esconde a pagina inteira
    // quando nao ha assinatura e nao tem v-else — com trial sem cartao, a tela
    // ficava em branco. Strings entregues ao frontend por Inertia::share(),
    // porque o laravel-vue-i18n so le o JSON gerado a partir de lang/.
    //
    // Sem botao de assinar de proposito: WelcomeController::redirectIfUnavailable
    // manda de volta para o calendario quem ja tem acesso ao app, entao
    // durante o trial o checkout e inalcancavel por design do upstream.

    'no_plan' => 'Nenhum plano ativo',
    'no_subscription_hint' => 'Você está no período de teste e ainda não tem assinatura. Ao final do teste, o sistema pede a escolha de um plano para continuar.',
    'choose_plan' => 'Escolher plano',

];
