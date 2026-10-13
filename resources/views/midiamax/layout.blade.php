{{--
    Layout das paginas publicas da MidiaMax (codigo-fonte, termos, privacidade,
    exclusao de dados).

    HTML puro de proposito: sao paginas de obrigacao legal e de app review.
    Precisam responder mesmo que o build do Vite, o Inertia ou o Reverb estejam
    quebrados. Nao transformar em pagina Inertia.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('midiamax.brand.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 3rem 1.5rem 5rem;
            font: 16px/1.65 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #ffffff;
            color: #292928;
        }
        main { max-width: 44rem; margin: 0 auto; }
        h1 { font-size: 1.625rem; line-height: 1.25; margin: 0 0 .5rem; }
        h2 { font-size: 1.125rem; margin: 2.5rem 0 .75rem; }
        p, li { margin: 0 0 1rem; }
        ul { padding-left: 1.25rem; }
        li { margin-bottom: .5rem; }
        dl { margin: 0 0 2rem; }
        dt { font-size: .8125rem; text-transform: uppercase; letter-spacing: .04em; opacity: .6; }
        dd { margin: .25rem 0 1rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; word-break: break-all; }
        a { color: #6d28d9; }
        code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .9em; }
        .meta { font-size: .875rem; opacity: .65; margin-bottom: 2.5rem; }
        .cta {
            display: inline-block; padding: .625rem 1rem; border-radius: .5rem;
            background: #6d28d9; color: #fff; text-decoration: none; font-weight: 600;
        }
        .footer {
            margin-top: 4rem; padding-top: 1.5rem; border-top: 1px solid rgba(0,0,0,.1);
            font-size: .875rem; opacity: .7;
        }
        .footer a { margin-right: 1rem; }
        table { border-collapse: collapse; width: 100%; margin: 0 0 1.5rem; font-size: .9375rem; }
        th, td { text-align: left; padding: .5rem .75rem; border-bottom: 1px solid rgba(0,0,0,.1); vertical-align: top; }
        th { font-weight: 600; }
        @media (prefers-color-scheme: dark) {
            body { background: #111111; color: #f7f6f3; }
            a { color: #c4b5fd; }
            .cta { background: #7c3aed; }
            .footer { border-top-color: rgba(255,255,255,.14); }
            th, td { border-bottom-color: rgba(255,255,255,.14); }
        }
    </style>
</head>
<body>
    <main>
        @yield('content')

        <div class="footer">
            <a href="{{ route('midiamax.legal.terms') }}">{{ __('midiamax::legal.terms_title') }}</a>
            <a href="{{ route('midiamax.legal.privacy') }}">{{ __('midiamax::legal.privacy_title') }}</a>
            <a href="{{ route('midiamax.legal.data-deletion') }}">{{ __('midiamax::legal.deletion_title') }}</a>
            <a href="{{ route('midiamax.source') }}">{{ __('midiamax::brand.source_title') }}</a>
        </div>
    </main>
</body>
</html>
