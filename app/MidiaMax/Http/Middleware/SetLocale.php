<?php

declare(strict_types=1);

namespace App\MidiaMax\Http\Middleware;

use App\Enums\User\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Substitui App\Http\Middleware\App\SetLocale do upstream (bind no
 * MidiaMaxServiceProvider — bootstrap/app.php continua intocado).
 *
 * O original usa `Auth::user()?->locale ?? Locale::DEFAULT`, e Locale::DEFAULT
 * e English cravado no enum. Resultado: visitante nao autenticado ve login,
 * cadastro, recuperacao de senha e a pagina /codigo-fonte sempre em ingles,
 * independente de APP_LOCALE. Para um SaaS brasileiro isso e defeito de
 * produto, nao preferencia.
 *
 * Aqui o fallback de visitante passa a ser APP_LOCALE. Usuario autenticado
 * continua mandando, igual ao upstream.
 *
 * ATENCAO: se o upstream mudar o SetLocale dele, esta classe nao recebe a
 * mudanca. Conferir a cada release (esta listado em CUSTOMIZATIONS.md).
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Auth::user()?->locale
            ?? Locale::tryFrom((string) config('app.locale'))
            ?? Locale::DEFAULT;

        App::setLocale($locale->value);
        View::share('htmlDir', $locale->direction());

        return $next($request);
    }
}
