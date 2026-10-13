{{--
    Termos de Uso — MidiaMax.

    Exigido pelo app review da Meta e da TikTok (link visivel nas telas de
    autenticacao). Cobre as secoes que esses processos verificam, mas NAO
    substitui revisao juridica — valide com advogado antes de submeter app review
    e antes de cobrar do primeiro cliente.
--}}
@extends('midiamax.layout')

@section('title', __('midiamax::legal.terms_title'))

@section('content')
    @php
        $brand = config('midiamax.brand.name');
        $company = config('midiamax.company');
        $provider = $company['legal_name'] ?: $brand;
        $supportEmail = config('midiamax.brand.support_email');
        $updatedAt = $company['legal_updated_at'];
    @endphp

    <h1>{{ __('midiamax::legal.terms_title') }}</h1>
    @if ($updatedAt)
        <p class="meta">{{ __('midiamax::legal.updated_at', ['date' => $updatedAt]) }}</p>
    @endif

    <p>
        Estes termos regem o uso da {{ $brand }}, serviço de agendamento e publicação
        em redes sociais disponível em <code>{{ config('app.url') }}</code>. Ao criar
        uma conta, você concorda com eles.
    </p>

    <h2>1. Quem presta o serviço</h2>
    <p>
        O serviço é prestado por <strong>{{ $provider }}</strong>@if ($company['cnpj']), CNPJ {{ $company['cnpj'] }}@endif.
        Contato: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
    </p>

    <h2>2. O que o serviço faz</h2>
    <p>
        A {{ $brand }} permite criar, organizar, agendar e publicar conteúdo nas contas
        de rede social que você conectar, além de exibir métricas dessas contas. A
        publicação depende das APIs das plataformas e das permissões que você conceder.
    </p>

    <h2>3. Sua conta</h2>
    <ul>
        <li>Você é responsável por manter suas credenciais em segurança e por tudo que ocorrer na sua conta.</li>
        <li>Os dados de cadastro devem ser verdadeiros e atualizados.</li>
        <li>O serviço é para maiores de 18 anos.</li>
        <li>Você só pode conectar contas de rede social que você administra ou está autorizado a administrar.</li>
    </ul>

    <h2>4. Planos, cobrança e cancelamento</h2>
    <ul>
        <li>Os preços e os limites de cada plano são os exibidos no serviço no momento da contratação.</li>
        <li>A assinatura é recorrente e renova automaticamente até que você cancele.</li>
        <li>O cancelamento pode ser feito a qualquer momento no próprio serviço e vale para o fim do ciclo já pago, sem novas cobranças.</li>
        <li>Quando houver período de teste, ele é informado na contratação; se não houver cancelamento até o fim do teste, a cobrança se inicia.</li>
        <li>Direito de arrependimento: contratações feitas à distância podem ser desfeitas em até 7 dias, com devolução do valor pago, conforme o art. 49 do Código de Defesa do Consumidor.</li>
        <li>Mudanças de preço são comunicadas com pelo menos 30 dias de antecedência e valem para o ciclo seguinte.</li>
    </ul>

    <h2>5. Uso aceitável</h2>
    <p>Ao usar a {{ $brand }}, você não vai:</p>
    <ul>
        <li>violar os termos, as políticas ou os limites de uso das plataformas conectadas;</li>
        <li>publicar conteúdo ilegal, que viole direitos de terceiros, incite violência ou discriminação, ou constitua spam;</li>
        <li>usar o serviço para automação abusiva, engajamento artificial ou burlar limites das plataformas;</li>
        <li>tentar obter acesso não autorizado ao serviço, a outras contas ou à nossa infraestrutura.</li>
    </ul>
    <p>
        O descumprimento pode levar à suspensão imediata, com aviso quando for possível.
    </p>

    <h2>6. Seu conteúdo</h2>
    <p>
        O conteúdo que você envia continua sendo seu. Você nos concede apenas a licença
        necessária para armazenar, processar e publicar esse conteúdo nas contas que
        você conectou — é o que faz o serviço funcionar. Você é responsável pelo
        conteúdo que publica e por ter os direitos sobre ele.
    </p>

    <h2>7. Dependência de plataformas de terceiros</h2>
    <p>
        A publicação e as métricas dependem de APIs de terceiros (Meta, LinkedIn,
        TikTok, Pinterest, Google/YouTube, Telegram, Discord). Mudanças, limites de
        uso, indisponibilidades ou revogações dessas APIs podem afetar ou impedir
        recursos do serviço, sem que isso configure defeito da nossa parte. Fazemos
        esforço razoável para nos adaptar a essas mudanças.
    </p>

    <h2>8. Disponibilidade e suporte</h2>
    <p>
        Trabalhamos para manter o serviço disponível, mas pode haver interrupções para
        manutenção ou por fatores fora do nosso controle. Suporte por
        <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.
    </p>

    <h2>9. Software e licença</h2>
    <p>
        A {{ $brand }} é construída sobre software livre licenciado em AGPL-3.0. O
        código-fonte da versão em execução está disponível em
        <a href="{{ route('midiamax.source') }}">{{ __('midiamax::brand.source_title') }}</a>.
        A licença do software não transfere a você a marca, o nome ou a identidade
        visual da {{ $brand }}.
    </p>

    <h2>10. Privacidade</h2>
    <p>
        O tratamento de dados pessoais está descrito na
        <a href="{{ route('midiamax.legal.privacy') }}">{{ __('midiamax::legal.privacy_title') }}</a>,
        que faz parte destes termos.
    </p>

    <h2>11. Encerramento</h2>
    <p>
        Você pode encerrar sua conta quando quiser. Podemos encerrar ou suspender a
        prestação em caso de violação destes termos, de exigência legal ou de
        descontinuação do serviço — neste último caso, com aviso prévio razoável e
        possibilidade de exportar seus dados.
    </p>

    <h2>12. Limitação de responsabilidade</h2>
    <p>
        Na máxima extensão permitida pela lei aplicável, não respondemos por lucros
        cessantes, perda de oportunidade ou danos indiretos decorrentes do uso do
        serviço. Nada aqui afasta direitos que a legislação consumerista garanta a
        você de forma inafastável.
    </p>

    <h2>13. Alterações destes termos</h2>
    <p>
        Podemos alterar estes termos. Mudanças relevantes serão comunicadas por e-mail
        ou dentro do serviço com antecedência razoável. O uso após a vigência implica
        concordância.
    </p>

    <h2>14. Lei aplicável e foro</h2>
    <p>
        Aplica-se a legislação brasileira.
        @if ($company['jurisdiction'])
            Fica eleito o foro de {{ $company['jurisdiction'] }} para dirimir controvérsias,
            ressalvado o direito do consumidor de demandar no foro do seu domicílio.
        @else
            Controvérsias serão resolvidas no foro do domicílio do consumidor, quando
            aplicável a legislação consumerista.
        @endif
    </p>
@endsection
