#!/usr/bin/env bash
#
# Aponta o .env local para o tunel ngrok que esta rodando e imprime a lista de
# redirect URIs para cadastrar no portal de cada plataforma.
#
#   ngrok http 8000                     # em outro terminal
#   scripts/midiamax/dev-ngrok.sh
#
# Por que isso existe: OAuth exige redirect HTTPS publico e identico ao
# cadastrado no portal. Sao 10 URLs derivadas do dominio — fazer na mao a cada
# reinicio do ngrok e onde o setup quebra.
#
# DICA: a conta gratuita do ngrok da um dominio estatico. Com ele a URL para de
# mudar e voce cadastra os redirects nos portais UMA vez:
#   ngrok http 8000 --url=seu-dominio.ngrok-free.app
#
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

[[ -f .env ]] || { echo "ERRO: .env nao existe neste diretorio." >&2; exit 1; }

# A API local do ngrok expoe o tunel ativo. Nao depende de login na web.
TUNNELS_JSON="$(curl -fsS --max-time 3 http://127.0.0.1:4040/api/tunnels 2>/dev/null || true)"

if [[ -z "$TUNNELS_JSON" ]]; then
    cat >&2 <<'EOF'
ERRO: ngrok nao esta rodando (API local em 127.0.0.1:4040 nao respondeu).

Em outro terminal:
    ngrok http 8000

Ou, com dominio estatico (recomendado — nao precisa reconfigurar os portais):
    ngrok http 8000 --url=seu-dominio.ngrok-free.app

O dominio estatico fica em https://dashboard.ngrok.com/domains
EOF
    exit 1
fi

URL="$(printf '%s' "$TUNNELS_JSON" | python3 -c "
import json,sys
data = json.load(sys.stdin)
https = [t['public_url'] for t in data.get('tunnels', []) if t.get('public_url','').startswith('https://')]
print(https[0] if https else '')
")"

if [[ -z "$URL" ]]; then
    echo "ERRO: ngrok esta rodando mas nao achei tunel https." >&2
    exit 1
fi

HOST="${URL#https://}"

echo "==> tunel: ${URL}"

set_env() {
    local key="$1" value="$2"
    if grep -qE "^${key}=" .env; then
        sed -i.bak -E "s|^${key}=.*|${key}=${value}|" .env && rm -f .env.bak
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

echo "==> atualizando .env"
set_env APP_URL "$URL"
set_env WEBHOOK_URL "$URL"          # Telegram registra o webhook nesta base

# Paginas legais servidas pela propria aplicacao (routes/midiamax.php).
# Sem isso o upstream aponta os links para trypost.it.
set_env LEGAL_TERMS_URL "${URL}/termos"
set_env LEGAL_PRIVACY_URL "${URL}/privacidade"

# Um redirect por plataforma. Nome da variavel e caminho vem do codigo —
# ver docs-midiamax/06-variaveis-ambiente.md.
set_env INSTAGRAM_CLIENT_REDIRECT "${URL}/accounts/instagram/callback"
set_env FACEBOOK_CLIENT_REDIRECT  "${URL}/accounts/facebook/callback"
set_env LINKEDIN_CLIENT_REDIRECT  "${URL}/accounts/linkedin/callback"
set_env TIKTOK_CLIENT_REDIRECT    "${URL}/accounts/tiktok/callback"
set_env PINTEREST_CLIENT_REDIRECT "${URL}/accounts/pinterest/callback"
set_env DISCORD_CLIENT_REDIRECT   "${URL}/accounts/discord/callback"
set_env GOOGLE_CLIENT_REDIRECT    "${URL}/accounts/youtube/callback"
set_env GOOGLE_AUTH_CALLBACK      "${URL}/auth/google/callback"

echo "==> limpando cache de config"
docker compose exec -T app php artisan config:clear >/dev/null 2>&1 \
    || echo "    (container nao esta no ar — rode depois de subir)"

cat <<EOF

=============================================================
 Cadastre estes redirect URIs nos portais
=============================================================

Meta (developers.facebook.com) — os DOIS, no mesmo app:
  ${URL}/accounts/facebook/callback
  ${URL}/accounts/instagram-facebook/callback
Meta > produto Instagram (Instagram Business Login):
  ${URL}/accounts/instagram/callback

LinkedIn (developer.linkedin.com > Auth):
  ${URL}/accounts/linkedin/callback

TikTok (developers.tiktok.com):
  ${URL}/accounts/tiktok/callback

Pinterest (developers.pinterest.com):
  ${URL}/accounts/pinterest/callback

Discord (discord.com/developers > OAuth2 > Redirects):
  ${URL}/accounts/discord/callback

Google (console.cloud.google.com > Credenciais > URIs de redirecionamento):
  ${URL}/accounts/youtube/callback
  ${URL}/auth/google/callback

Telegram: nao usa redirect. Registre o webhook:
  docker compose exec app php artisan telegram:set-webhook

URLs exigidas no App Review (Meta e TikTok):
  Termos:            ${URL}/termos
  Privacidade:       ${URL}/privacidade
  Exclusao de dados: ${URL}/exclusao-de-dados

-------------------------------------------------------------
Teste rapido:
  curl -I ${URL}/login
  abra ${URL}/termos no browser

Observacoes:
  - O websocket (Reverb) continua em localhost e NAO passa pelo tunel. Pelo
    ngrok o calendario nao atualiza em tempo real — nao e defeito do setup.
  - Na primeira visita o ngrok gratuito mostra uma pagina de aviso. Abra o
    dominio no browser uma vez antes de testar OAuth, senao o callback cai nela.
  - Sem dominio estatico, a URL muda a cada reinicio e tudo isso precisa ser
    refeito nos portais. Use --url=<dominio>.ngrok-free.app.
=============================================================
EOF
