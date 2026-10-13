<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks MidiaMax
|--------------------------------------------------------------------------
|
| Arquivo separado de routes/midiamax.php de proposito: estas rotas sao
| carregadas SEM o grupo "web", ou seja, sem sessao e sem CSRF. Webhook e
| requisicao servidor-para-servidor: com CSRF ligado o Stripe levaria 419 em
| todo evento. A rota nativa do Cashier (stripe/webhook) tambem roda sem
| middleware nenhum, pelo mesmo motivo.
|
| A autenticidade NAO depende de CSRF e sim da assinatura do Stripe, validada
| pelo proprio WebhookController contra STRIPE_WEBHOOK_SECRET.
|
*/

// Alias para a rota nativa stripe/webhook, no padrao de URL que a MidiaMax usa
// nos demais sistemas. As duas continuam validas e caem no mesmo controller.
Route::post(
    '/api/v1/webhooks/cobranca/stripe',
    [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook'],
)->name('midiamax.webhooks.stripe');
