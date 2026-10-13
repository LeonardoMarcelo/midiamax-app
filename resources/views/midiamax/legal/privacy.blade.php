{{--
    Politica de Privacidade — MidiaMax.

    Esta pagina e exigida pelo app review da Meta, da TikTok e do Google, e pela
    LGPD. O conteudo abaixo cobre as secoes que esses processos verificam, mas
    NAO substitui revisao juridica: valide com advogado antes de submeter
    qualquer app review. Checklist em docs-midiamax/07-setup-local-ngrok.md.
--}}
@extends('midiamax.layout')

@section('title', __('midiamax::legal.privacy_title'))

@section('content')
    @php
        $brand = config('midiamax.brand.name');
        $company = config('midiamax.company');
        $controller = $company['legal_name'] ?: $brand;
        $privacyEmail = $company['privacy_email'];
        $updatedAt = $company['legal_updated_at'];
    @endphp

    <h1>{{ __('midiamax::legal.privacy_title') }}</h1>
    @if ($updatedAt)
        <p class="meta">{{ __('midiamax::legal.updated_at', ['date' => $updatedAt]) }}</p>
    @endif

    <p>
        Esta política explica quais dados a {{ $brand }} coleta, por que coleta, com
        quem compartilha e como você exerce seus direitos. Ela se aplica ao uso do
        serviço em <code>{{ config('app.url') }}</code>.
    </p>

    <h2>1. Quem é o controlador</h2>
    <p>
        O controlador dos dados é <strong>{{ $controller }}</strong>@if ($company['cnpj']), CNPJ {{ $company['cnpj'] }}@endif.
        @if ($company['address']) Endereço: {{ $company['address'] }}. @endif
        Contato para assuntos de privacidade:
        <a href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>.
    </p>

    <h2>2. Dados que coletamos</h2>
    <table>
        <tr>
            <th>Categoria</th>
            <th>O que é</th>
            <th>Por que</th>
        </tr>
        <tr>
            <td>Cadastro</td>
            <td>Nome, e-mail, senha (armazenada apenas como hash), idioma e fuso</td>
            <td>Criar e manter sua conta</td>
        </tr>
        <tr>
            <td>Conteúdo</td>
            <td>Textos, imagens e vídeos que você envia, agenda ou publica</td>
            <td>Executar o serviço de agendamento e publicação</td>
        </tr>
        <tr>
            <td>Contas sociais conectadas</td>
            <td>Tokens de acesso, identificador, nome de usuário, foto e métricas públicas das contas que você conecta</td>
            <td>Publicar em seu nome e exibir suas métricas</td>
        </tr>
        <tr>
            <td>Faturamento</td>
            <td>Plano, status da assinatura e identificadores do processador de pagamento</td>
            <td>Cobrança e controle de acesso</td>
        </tr>
        <tr>
            <td>Uso e segurança</td>
            <td>Endereço IP, data e hora de acesso, logs de erro e de publicação</td>
            <td>Operar, depurar e proteger o serviço</td>
        </tr>
    </table>
    <p>
        <strong>Não armazenamos dados de cartão de crédito.</strong> O pagamento é
        processado diretamente pelo nosso processador, que recebe esses dados sem
        passar por nossos servidores.
    </p>

    <h2>3. Dados das plataformas que você conecta</h2>
    <p>
        Ao conectar uma conta (Instagram, Facebook, LinkedIn, TikTok, Pinterest,
        YouTube, Telegram ou Discord), você autoriza o acesso por meio do mecanismo
        oficial de cada plataforma. Guardamos o token de acesso de forma criptografada
        e o usamos exclusivamente para:
    </p>
    <ul>
        <li>publicar e agendar o conteúdo que você mesmo criou no serviço;</li>
        <li>exibir a você as métricas e as publicações da sua própria conta.</li>
    </ul>
    <p>
        Não usamos esses dados para treinar modelos de inteligência artificial, não os
        vendemos e não os compartilhamos com terceiros para publicidade. Você pode
        desconectar qualquer conta a qualquer momento nas configurações do serviço;
        a desconexão apaga o token correspondente.
    </p>

    <h2>4. Base legal (LGPD)</h2>
    <ul>
        <li><strong>Execução de contrato</strong> (art. 7º, V): cadastro, conteúdo, contas conectadas e faturamento.</li>
        <li><strong>Legítimo interesse</strong> (art. 7º, IX): logs de uso, segurança e prevenção a fraude e abuso.</li>
        <li><strong>Consentimento</strong> (art. 7º, I): comunicações de marketing, quando houver, com descadastro a qualquer momento.</li>
        <li><strong>Obrigação legal</strong> (art. 7º, II): registros fiscais e de acesso exigidos por lei.</li>
    </ul>

    <h2>5. Com quem compartilhamos</h2>
    <p>
        Compartilhamos apenas o necessário, com operadores que prestam serviço para nós:
    </p>
    <ul>
        <li><strong>Plataformas sociais</strong> que você conectar — para publicar o seu conteúdo;</li>
        <li><strong>Processador de pagamento</strong> — para cobrança da assinatura;</li>
        <li><strong>Provedor de envio de e-mail</strong> — para e-mails transacionais;</li>
        <li><strong>Hospedagem e armazenamento de arquivos</strong> — para operar o serviço;</li>
        <li><strong>Provedor de IA</strong> — somente quando você usa um recurso de geração de texto, e apenas com o conteúdo daquela solicitação.</li>
    </ul>
    <p>Não vendemos dados pessoais.</p>

    <h2>6. Por quanto tempo guardamos</h2>
    <ul>
        <li>Dados de conta: enquanto a conta existir.</li>
        <li>Arquivos enviados e não utilizados: removidos automaticamente após o prazo de retenção configurado no serviço.</li>
        <li>Histórico de publicações: removido automaticamente após o prazo de retenção configurado no serviço.</li>
        <li>Após a exclusão da conta: apagamos ou anonimizamos os dados, exceto o que a lei obrigar a manter (por exemplo, registros fiscais).</li>
    </ul>

    <h2>7. Seus direitos</h2>
    <p>
        A LGPD (art. 18) garante a você: confirmação de tratamento, acesso, correção,
        anonimização ou exclusão, portabilidade, informação sobre compartilhamentos e
        revogação de consentimento. Para exercer qualquer um deles, escreva para
        <a href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>. Respondemos em
        até 15 dias.
    </p>
    <p>
        Para apagar sua conta e seus dados, veja
        <a href="{{ route('midiamax.legal.data-deletion') }}">{{ __('midiamax::legal.deletion_title') }}</a>.
    </p>

    <h2>8. Segurança</h2>
    <p>
        Tráfego sempre em HTTPS, senhas armazenadas apenas como hash, tokens das
        plataformas criptografados em repouso e acesso administrativo restrito.
        Nenhum sistema é imune a incidentes: se ocorrer um que traga risco relevante
        a você, comunicaremos você e a ANPD conforme a lei.
    </p>

    <h2>9. Transferência internacional</h2>
    <p>
        Parte dos nossos operadores (plataformas sociais, processador de pagamento,
        hospedagem) processa dados fora do Brasil. Nesses casos a transferência se
        apoia nas hipóteses do art. 33 da LGPD.
    </p>

    <h2>10. Cookies</h2>
    <p>
        Usamos cookies estritamente necessários para manter sua sessão autenticada e
        proteger formulários contra fraude. Se e quando houver cookies de análise,
        eles serão informados aqui.
    </p>

    <h2>11. Menores</h2>
    <p>
        O serviço não é destinado a menores de 18 anos e não coletamos
        intencionalmente dados dessa faixa.
    </p>

    <h2>12. Alterações</h2>
    <p>
        Podemos atualizar esta política. Mudanças relevantes serão comunicadas por
        e-mail ou dentro do serviço antes de entrarem em vigor.
    </p>

    <h2>13. Contato</h2>
    <p>
        <a href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>
    </p>
@endsection
