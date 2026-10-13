<?php

declare(strict_types=1);

namespace App\MidiaMax\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Ponto de entrada unico de toda customizacao MidiaMax.
 *
 * Regra do fork: nada nosso vive dentro de arquivo do upstream. Binds que
 * substituem classes do TryPost, listeners, rotas, migrations e traducoes
 * entram aqui. Cada arquivo do upstream que precisar ser tocado tem que ser
 * registrado em CUSTOMIZATIONS.md — e vai ser conflito em todo merge.
 */
class MidiaMaxServiceProvider extends ServiceProvider
{
    /**
     * Register any MidiaMax services.
     *
     * Para trocar uma implementacao do upstream, faca bind aqui em vez de
     * editar a classe original:
     *
     *   $this->app->bind(\App\Services\Foo::class, \App\MidiaMax\Services\Foo::class);
     */
    public function register(): void
    {
        $this->normalizeUpstreamConfig();
        $this->overrideUpstreamImplementations();
    }

    /**
     * Correcoes de bug do upstream feitas por cima da config, sem editar o
     * arquivo deles. Cada item e candidato a PR no upstream — quando for
     * aceito, remover daqui.
     */
    protected function normalizeUpstreamConfig(): void
    {
        // config/cashier.php:143 faz env('CASHIER_TRIAL_DAYS', 8) sem cast, e
        // CreateUser.php:39 passa o valor direto para now()->addDays(), que
        // exige int|float. Com a variavel definida no .env (o proprio
        // .env.example do upstream traz CASHIER_TRIAL_DAYS=8) o valor chega
        // como string '8' e o cadastro quebra com TypeError do Carbon —
        // em modo SaaS com REQUIRE_CARD_FOR_TRIAL=false, ninguem consegue
        // criar conta. Sem a variavel definida, o default int funciona e o bug
        // fica invisivel.
        config(['cashier.trial_days' => (int) config('cashier.trial_days')]);
    }

    /**
     * Troca de implementacao do upstream sem editar o arquivo deles. Funciona
     * inclusive para middleware: o pipeline do Laravel resolve middleware pelo
     * container, entao bootstrap/app.php continua intocado.
     *
     * Toda entrada aqui tem que estar em CUSTOMIZATIONS.md e ser reconferida a
     * cada release do upstream — bind nao avisa quando a classe original muda.
     */
    protected function overrideUpstreamImplementations(): void
    {
        $this->app->bind(
            \App\Http\Middleware\App\SetLocale::class,
            \App\MidiaMax\Http\Middleware\SetLocale::class,
        );
    }

    /**
     * Bootstrap any MidiaMax services.
     */
    public function boot(): void
    {
        $this->loadMidiaMaxMigrations();
        $this->loadMidiaMaxRoutes();
        $this->loadMidiaMaxTranslations();
    }

    /**
     * Migrations proprias ficam fora de database/migrations para que merges do
     * upstream nunca conflitem e os nomes de arquivo nunca colidam.
     */
    protected function loadMidiaMaxMigrations(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/midiamax'));
    }

    /**
     * Rotas proprias em arquivo proprio. Carregadas depois das do upstream, o
     * que importa se o upstream tiver fallback/catch-all.
     *
     * O guard de routesAreCached() nao e opcional: o entrypoint da imagem roda
     * `php artisan route:cache` em producao. Nossa rota entra no cache (o
     * route:cache boota a app e passa por aqui), e sem o guard ela seria
     * registrada de novo a cada request em cima do cache.
     * E o mesmo guard que o loadRoutesFrom() do Laravel faz internamente — nao
     * usamos ele aqui porque precisamos aplicar o middleware group "web".
     */
    protected function loadMidiaMaxRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware('web')->group(base_path('routes/midiamax.php'));
    }

    /**
     * Strings novas vivem no namespace "midiamax::" (__('midiamax::brand.x')).
     * Sobrescrever string do upstream editando lang/pt-BR/*.php e conflito
     * garantido no proximo release.
     */
    protected function loadMidiaMaxTranslations(): void
    {
        $this->loadTranslationsFrom(base_path('lang-midiamax'), 'midiamax');
    }
}
