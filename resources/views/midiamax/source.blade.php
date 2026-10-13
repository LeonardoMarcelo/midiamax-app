{{--
    Oferta de codigo-fonte — AGPL-3.0 secao 13.
    Ver resources/views/midiamax/layout.blade.php para o porque do HTML puro.
--}}
@extends('midiamax.layout')

@section('title', __('midiamax::brand.source_title'))

@section('content')
    @php
        $repository = rtrim((string) config('midiamax.source.repository'), '/');
        $commit = config('midiamax.source.commit');
        $version = config('midiamax.source.version');
        $commitUrl = $commit ? "{$repository}/tree/{$commit}" : null;
        $tarballUrl = $commit
            ? "{$repository}/archive/{$commit}.tar.gz"
            : "{$repository}/archive/refs/heads/main.tar.gz";
    @endphp

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
@endsection
