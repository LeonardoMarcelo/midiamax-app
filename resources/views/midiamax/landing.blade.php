{{--
    Landing page da MidiaMax.

    Servida em "/" para visitante nao autenticado, por
    App\MidiaMax\Http\Middleware\Authenticate. O upstream nao tem landing: a
    rota "/" dele fica atras do middleware auth e so redireciona para o
    calendario (o site de marketing do TryPost e outro projeto, fechado).

    HTML puro, sem Inertia: e a primeira pagina que um visitante ve e precisa
    carregar mesmo se o bundle do Vite estiver quebrado.

    Os precos vem de config('midiamax.billing_labels'), a MESMA fonte que
    alimenta a tela de billing — para nao repetir o descompasso de mostrar um
    valor aqui e cobrar outro no Stripe.
--}}
@php
    $brand = config('midiamax.brand.name');
    $labels = config('midiamax.billing_labels');
    $canRegister = ! config('trypost.self_hosted');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $brand }} — agendamento de redes sociais</title>
    <meta name="description" content="Planeje o mês inteiro num calendário, arraste para reagendar e publique no Instagram, Facebook, LinkedIn, TikTok, Pinterest, YouTube, Telegram e Discord.">
    <style>
        :root {
            color-scheme: light dark;
            --bg: #ffffff; --fg: #292928; --muted: #6b6b68;
            --card: #fafaf9; --line: rgba(0,0,0,.10);
            --accent: #6d28d9; --accent-fg: #ffffff;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #111111; --fg: #f7f6f3; --muted: #a3a39e;
                --card: #191919; --line: rgba(255,255,255,.12);
                --accent: #7c3aed;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--bg); color: var(--fg);
            font: 16px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        .wrap { max-width: 64rem; margin: 0 auto; padding: 0 1.5rem; }
        header { padding: 1.5rem 0; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .logo { font-weight: 700; font-size: 1.125rem; letter-spacing: -.01em; }
        nav a { color: var(--fg); text-decoration: none; font-weight: 500; font-size: .9375rem; margin-left: 1.5rem; }
        nav a:hover { color: var(--accent); }
        .btn {
            display: inline-block; padding: .6875rem 1.25rem; border-radius: .5rem;
            background: var(--accent); color: var(--accent-fg); text-decoration: none;
            font-weight: 600; font-size: .9375rem; border: 1px solid transparent;
        }
        .btn-ghost { background: transparent; color: var(--fg); border-color: var(--line); }
        .hero { padding: 4.5rem 0 3.5rem; max-width: 42rem; }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); line-height: 1.1; letter-spacing: -.02em; margin: 0 0 1.25rem; }
        .hero p { font-size: 1.125rem; color: var(--muted); margin: 0 0 2rem; }
        .cta-row { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; }
        .note { font-size: .875rem; color: var(--muted); margin-top: 1rem; }
        section { padding: 3.5rem 0; border-top: 1px solid var(--line); }
        h2 { font-size: 1.5rem; letter-spacing: -.01em; margin: 0 0 .5rem; }
        .sub { color: var(--muted); margin: 0 0 2rem; }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: .75rem; padding: 1.5rem; }
        .card h3 { margin: 0 0 .5rem; font-size: 1rem; }
        .card p { margin: 0; color: var(--muted); font-size: .9375rem; }
        .platforms { display: flex; flex-wrap: wrap; gap: .5rem; }
        .chip {
            border: 1px solid var(--line); border-radius: 2rem; padding: .375rem .875rem;
            font-size: .875rem; background: var(--card);
        }
        .price { font-size: 2rem; font-weight: 700; letter-spacing: -.02em; margin: .5rem 0 .25rem; }
        .price small { font-size: .875rem; font-weight: 400; color: var(--muted); }
        .plan ul { list-style: none; padding: 0; margin: 1.25rem 0 1.5rem; }
        .plan li { padding-left: 1.5rem; position: relative; margin-bottom: .5rem; font-size: .9375rem; }
        .plan li::before { content: "✓"; position: absolute; left: 0; color: var(--accent); font-weight: 700; }
        footer { border-top: 1px solid var(--line); padding: 2rem 0 3rem; font-size: .875rem; color: var(--muted); }
        footer a { color: var(--muted); margin-right: 1.25rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <span class="logo">{{ $brand }}</span>
            <nav>
                <a href="#planos">Planos</a>
                <a href="{{ route('login') }}">Entrar</a>
            </nav>
        </header>

        <div class="hero">
            <h1>Um calendário para todas as suas redes sociais</h1>
            <p>
                Planeje o mês inteiro numa tela só, arraste um post para outro horário
                e publique no Instagram, Facebook, LinkedIn, TikTok, Pinterest,
                YouTube, Telegram e Discord — sem precisar abrir oito aplicativos.
            </p>
            <div class="cta-row">
                @if ($canRegister)
                    <a class="btn" href="{{ route('register') }}">Começar agora</a>
                @endif
                <a class="btn btn-ghost" href="{{ route('login') }}">Já tenho conta</a>
            </div>
            @if ($canRegister)
                <p class="note">Teste sem compromisso. Cancele quando quiser.</p>
            @endif
        </div>
    </div>

    <section>
        <div class="wrap">
            <h2>Publique nativamente</h2>
            <p class="sub">Conexão oficial com cada plataforma, sem automação improvisada.</p>
            <div class="platforms">
                <span class="chip">Instagram</span>
                <span class="chip">Facebook</span>
                <span class="chip">LinkedIn</span>
                <span class="chip">TikTok</span>
                <span class="chip">Pinterest</span>
                <span class="chip">YouTube &amp; Shorts</span>
                <span class="chip">Telegram</span>
                <span class="chip">Discord</span>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <h2>O que você ganha</h2>
            <p class="sub">O essencial para manter a presença em dia sem virar refém do improviso.</p>
            <div class="grid">
                <div class="card">
                    <h3>Calendário que você enxerga</h3>
                    <p>O mês inteiro de relance. Arraste um post para outro dia ou horário e pronto.</p>
                </div>
                <div class="card">
                    <h3>Vários clientes, sem confusão</h3>
                    <p>Cada marca no seu próprio espaço, com contas, posts e métricas separados.</p>
                </div>
                <div class="card">
                    <h3>Equipe com papéis</h3>
                    <p>Convide quem escreve e quem aprova. Dá para exigir aprovação antes de publicar.</p>
                </div>
                <div class="card">
                    <h3>Métricas no mesmo lugar</h3>
                    <p>Acompanhe o desempenho das contas conectadas sem abrir uma aba por rede.</p>
                </div>
                <div class="card">
                    <h3>Ajuda para escrever</h3>
                    <p>Geração e ajuste de legenda com IA, direto no compositor, quando você quiser.</p>
                </div>
                <div class="card">
                    <h3>Seus dados são seus</h3>
                    <p>Exporte ou apague quando decidir. Software livre, com o código-fonte publicado.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="planos">
        <div class="wrap">
            <h2>Planos</h2>
            <p class="sub">Sem fidelidade. No plano anual, dois meses saem de graça.</p>
            <div class="grid">
                <div class="card plan">
                    <h3>Socials</h3>
                    <div class="price">{{ $labels['socials_monthly'] }} <small>/mês</small></div>
                    <small class="price-note" style="color:var(--muted)">
                        ou {{ $labels['socials_yearly'] }}/ano ({{ $labels['socials_yearly_per_month'] }}/mês)
                    </small>
                    <ul>
                        <li>1 espaço de trabalho</li>
                        <li>Todas as redes sociais</li>
                        <li>Calendário e agendamento</li>
                        <li>Métricas das contas conectadas</li>
                    </ul>
                    @if ($canRegister)
                        <a class="btn" href="{{ route('register') }}">Assinar</a>
                    @endif
                </div>
                <div class="card plan">
                    <h3>Workspaces</h3>
                    <div class="price">{{ $labels['workspaces_monthly'] }} <small>/mês</small></div>
                    <small class="price-note" style="color:var(--muted)">
                        ou {{ $labels['workspaces_yearly'] }}/ano ({{ $labels['workspaces_yearly_per_month'] }}/mês)
                    </small>
                    <ul>
                        <li>Espaços de trabalho ilimitados</li>
                        <li>Ideal para agências</li>
                        <li>Equipe com papéis e aprovação</li>
                        <li>Tudo do plano Socials</li>
                    </ul>
                    @if ($canRegister)
                        <a class="btn" href="{{ route('register') }}">Assinar</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="wrap">
            <a href="{{ route('midiamax.legal.terms') }}">Termos de Uso</a>
            <a href="{{ route('midiamax.legal.privacy') }}">Política de Privacidade</a>
            <a href="{{ route('midiamax.legal.data-deletion') }}">Exclusão de dados</a>
            <a href="{{ route('midiamax.source') }}">Código-fonte</a>
            <p style="margin-top:1rem">
                {{ config('midiamax.company.legal_name') ?: $brand }}
                @if (config('midiamax.company.cnpj')) · CNPJ {{ config('midiamax.company.cnpj') }} @endif
            </p>
        </div>
    </footer>
</body>
</html>
