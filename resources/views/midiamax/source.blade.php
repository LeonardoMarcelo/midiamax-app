{{--
    Oferta de codigo-fonte — AGPL-3.0 secao 13.

    HTML puro de proposito: esta pagina e uma obrigacao de licenca e precisa
    responder mesmo que o build do Vite, o Inertia ou o Reverb estejam quebrados.
    Nao transformar em pagina Inertia.
--}}
@php
    $repository = rtrim((string) config('midiamax.source.repository'), '/');
    $commit = config('midiamax.source.commit');
    $version = config('midiamax.source.version');
    $commitUrl = $commit ? "{$repository}/tree/{$commit}" : null;
    $tarballUrl = $commit ? "{$repository}/archive/{$commit}.tar.gz" : "{$repository}/archive/refs/heads/main.tar.gz";
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('midiamax::brand.source_title') }} — {{ config('midiamax.brand.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            padding: 3rem 1.5rem;
            font: 16px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #ffffff;
            color: #292928;
        }
        main { max-width: 42rem; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin: 0 0 .75rem; }
        p { margin: 0 0 1.5rem; }
        dl { margin: 0 0 2rem; }
        dt { font-size: .8125rem; text-transform: uppercase; letter-spacing: .04em; opacity: .6; }
        dd { margin: .25rem 0 1rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; word-break: break-all; }
        a { color: #6d28d9; }
        .cta {
            display: inline-block; padding: .625rem 1rem; border-radius: .5rem;
            background: #6d28d9; color: #fff; text-decoration: none; font-weight: 600;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #111111; color: #f7f6f3; }
            a { color: #c4b5fd; }
            .cta { background: #7c3aed; }
        }
    </style>
</head>
<body>
    <main>
        <h1>{{ __('midiamax::brand.source_title') }}</h1>
        <p>{{ __('midiamax::brand.source_intro') }}</p>

        <dl>
            <dt>{{ __('midiamax::brand.source_version') }}</dt>
            <dd>{{ $version }}</dd>

            @if ($commit)
                <dt>{{ __('midiamax::brand.source_commit') }}</dt>
                <dd><a href="{{ $commitUrl }}">{{ $commit }}</a></dd>
            @endif

            <dt>{{ __('midiamax::brand.source_repository') }}</dt>
            <dd><a href="{{ $repository }}">{{ $repository }}</a></dd>

            <dt>{{ __('midiamax::brand.source_upstream') }}</dt>
            <dd>
                <a href="{{ config('midiamax.source.upstream') }}">TryPost</a>
                ({{ config('midiamax.source.upstream_ref') }})
            </dd>

            <dt>{{ __('midiamax::brand.source_license') }}</dt>
            <dd><a href="https://www.gnu.org/licenses/agpl-3.0.html">AGPL-3.0</a></dd>
        </dl>

        <a class="cta" href="{{ $tarballUrl }}">{{ __('midiamax::brand.source_download') }}</a>
    </main>
</body>
</html>
