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
| Todas publicas e sem Inertia de proposito: sao paginas de obrigacao legal e de
| app review, tem que responder mesmo se o front quebrar.
|
*/

// AGPL-3.0 secao 13: quem interage com a aplicacao pela rede tem direito ao
// fonte da versao em execucao.
Route::view('/codigo-fonte', 'midiamax.source')->name('midiamax.source');

// Exigidos pelo app review da Meta e da TikTok (link visivel nas telas de
// autenticacao) e pela LGPD. LEGAL_TERMS_URL e LEGAL_PRIVACY_URL apontam para
// ca — sem isso o upstream manda o usuario para trypost.it.
Route::view('/termos', 'midiamax.legal.terms')->name('midiamax.legal.terms');
Route::view('/privacidade', 'midiamax.legal.privacy')->name('midiamax.legal.privacy');

// A Meta pede uma URL de exclusao de dados no App Review.
Route::view('/exclusao-de-dados', 'midiamax.legal.data-deletion')->name('midiamax.legal.data-deletion');
