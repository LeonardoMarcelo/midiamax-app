<?php

declare(strict_types=1);

namespace App\MidiaMax\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve a landing page em "/" para visitante nao autenticado.
 *
 * Por que no middleware e nao numa rota nossa: o upstream registra
 * GET "/" em routes/app.php dentro do grupo auth, e as rotas deles sao
 * carregadas ANTES das nossas — testado, uma rota nossa em "/" nunca e
 * alcancada. Entao o unico ponto em que da para intervir sem editar arquivo
 * do upstream e aqui, no middleware que decide o que fazer com o visitante.
 *
 * Sem isto, visitante em "/" e empurrado direto para /login e o produto nao
 * tem vitrine nenhuma — o site de marketing do TryPost e outro projeto, que
 * nao vem no codigo aberto.
 *
 * Vale so para "/" exatamente. Qualquer outra rota protegida continua mandando
 * para /login, que e o certo para link profundo.
 *
 * Registrado por bind no MidiaMaxServiceProvider (o pipeline do Laravel resolve
 * middleware pelo container), entao bootstrap/app.php fica intocado.
 */
class Authenticate extends BaseAuthenticate
{
    /**
     * @param  string[]  ...$guards
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        if ($this->shouldShowLanding($request)) {
            return response()->view('midiamax.landing');
        }

        return parent::handle($request, $next, ...$guards);
    }

    protected function shouldShowLanding(Request $request): bool
    {
        return $request->isMethod('GET')
            && $request->is('/')
            && ! $this->auth->guard()->check();
    }
}
