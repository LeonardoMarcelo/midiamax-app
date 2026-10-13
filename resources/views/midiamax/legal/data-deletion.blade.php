{{--
    Instrucoes de exclusao de dados.

    A Meta exige uma URL de exclusao de dados no App Review (aceita pagina com
    instrucoes, nao precisa ser callback automatico). Tambem serve como prova de
    atendimento ao art. 18 da LGPD.
--}}
@extends('midiamax.layout')

@section('title', __('midiamax::legal.deletion_title'))

@section('content')
    @php
        $brand = config('midiamax.brand.name');
        $privacyEmail = config('midiamax.company.privacy_email');
    @endphp

    <h1>{{ __('midiamax::legal.deletion_title') }}</h1>

    <p>
        Você pode apagar seus dados da {{ $brand }} a qualquer momento. Há duas formas,
        dependendo do que você quer remover.
    </p>

    <h2>1. Desconectar uma conta de rede social</h2>
    <p>
        Para revogar o nosso acesso a uma conta específica, sem apagar sua conta na
        {{ $brand }}: entre no serviço, vá em <strong>Contas</strong>, escolha a conta
        e clique em desconectar. Isso apaga imediatamente o token de acesso daquela
        plataforma dos nossos servidores.
    </p>
    <p>
        Você também pode revogar o acesso diretamente na plataforma — por exemplo, nas
        configurações de aplicativos e sites da sua conta do Facebook, Instagram,
        LinkedIn, TikTok, Pinterest ou Google. A revogação pelo lado da plataforma tem
        efeito imediato e nossos acessos param de funcionar.
    </p>

    <h2>2. Apagar sua conta e todos os seus dados</h2>
    <p>
        Envie um pedido de exclusão para
        <a href="mailto:{{ $privacyEmail }}?subject=Pedido%20de%20exclus%C3%A3o%20de%20dados">{{ $privacyEmail }}</a>,
        a partir do e-mail cadastrado na sua conta.
    </p>
    <p>O que acontece depois do pedido:</p>
    <ul>
        <li>confirmamos o recebimento e a identidade do titular;</li>
        <li>apagamos sua conta, seus posts e rascunhos, seus arquivos de mídia, as contas sociais conectadas e os respectivos tokens de acesso;</li>
        <li>concluímos em até 15 dias;</li>
        <li>mantemos apenas o que a lei obriga — por exemplo, registros fiscais de pagamentos já realizados — e nada além disso.</li>
    </ul>
    <p>
        A exclusão é definitiva: não conseguimos restaurar o conteúdo depois de
        apagado. Publicações que já saíram nas suas redes sociais não são removidas
        das plataformas por este pedido — elas pertencem à sua conta lá, e precisam ser
        apagadas na própria plataforma.
    </p>

    <h2>3. Outros direitos</h2>
    <p>
        Acesso, correção, portabilidade e demais direitos previstos no art. 18 da LGPD
        também são atendidos pelo mesmo endereço. Detalhes na
        <a href="{{ route('midiamax.legal.privacy') }}">{{ __('midiamax::legal.privacy_title') }}</a>.
    </p>
@endsection
