<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas MidiaMax
|--------------------------------------------------------------------------
|
| Arquivo novo, carregado por MidiaMaxServiceProvider. Rota nossa nunca entra
| em routes/web.php nem routes/app.php — esses sao do upstream.
|
*/

// AGPL-3.0 secao 13: quem interage com a aplicacao pela rede tem direito ao
// fonte da versao em execucao. Publica, sem auth e sem Inertia de proposito —
// tem que responder mesmo se o front quebrar.
Route::view('/codigo-fonte', 'midiamax.source')->name('midiamax.source');
