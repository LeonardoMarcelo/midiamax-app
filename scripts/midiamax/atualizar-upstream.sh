#!/usr/bin/env bash
#
# Integra uma release (tag) do TryPost no nosso main.
# Runbook completo: docs-midiamax/02-runbook-atualizacao.md
#
# Uso: scripts/midiamax/atualizar-upstream.sh v2.1.0
#      scripts/midiamax/atualizar-upstream.sh            # lista tags disponiveis
#
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

echo "==> buscando tags do upstream"
git fetch upstream --tags --quiet

# Ultima tag do upstream ja presente no nosso main = nossa base atual.
BASE="$(git tag --list 'v*' --merged main --sort=-creatordate | head -1)"
: "${BASE:?nao achei nenhuma tag do upstream em main — topologia quebrada, ver docs-midiamax/01-modelo-git.md}"

TAG="${1:-}"

if [[ -z "$TAG" ]]; then
    echo
    echo "Base atual do fork: $BASE"
    echo
    echo "Tags do upstream mais recentes:"
    git tag --list 'v*' --sort=-creatordate | head -5 | sed 's/^/  /'
    echo
    echo "Ainda nao integradas:"
    git tag --list 'v*' --no-merged main --sort=creatordate | sed 's/^/  /' || true
    echo
    echo "Uso: $0 <tag>"
    exit 1
fi

if ! git rev-parse -q --verify "refs/tags/${TAG}" >/dev/null; then
    echo "ERRO: tag '${TAG}' nao existe no upstream." >&2
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "ERRO: working tree sujo. Commite ou guarde as mudancas antes." >&2
    git status --short >&2
    exit 1
fi

CURRENT="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$CURRENT" != "main" ]]; then
    echo "ERRO: voce esta em '${CURRENT}'. Rode a partir de main." >&2
    exit 1
fi

echo
echo "=============================================================="
echo " ${BASE}  ->  ${TAG}"
echo "=============================================================="
echo
echo "--- Tamanho do release ---"
git diff --stat "${BASE}" "${TAG}" | tail -1
echo
echo "--- Commits ($(git rev-list --count "${BASE}..${TAG}")) ---"
git log --oneline "${BASE}..${TAG}" | head -40
echo
echo "--- Mexeram em arquivo que NOS modificamos? (ver CUSTOMIZATIONS.md) ---"
TOUCHED=(bootstrap/providers.php resources/css/app.css .gitattributes)
if git diff --quiet "${BASE}" "${TAG}" -- "${TOUCHED[@]}"; then
    echo "  nenhum. Merge deve ser limpo."
else
    git diff --stat "${BASE}" "${TAG}" -- "${TOUCHED[@]}" | sed 's/^/  /'
    echo "  ^ conflito esperado nesses arquivos."
fi
echo
echo "--- Variaveis de ambiente novas (refletir no midiamax-infra) ---"
git diff "${BASE}" "${TAG}" -- .env.example | grep -E "^\+[A-Z_]+=" | sed 's/^/  /' || echo "  nenhuma."
echo
echo "--- Migrations novas ---"
git diff --name-only --diff-filter=A "${BASE}" "${TAG}" -- database/migrations | sed 's/^/  /' || true
echo

BRANCH="update/${TAG}"
if git show-ref --verify --quiet "refs/heads/${BRANCH}"; then
    echo "ERRO: branch '${BRANCH}' ja existe. Apague ou termine a integracao anterior." >&2
    exit 1
fi

read -r -p "Criar ${BRANCH} e fazer o merge de ${TAG}? [s/N] " ok
[[ "$ok" == "s" || "$ok" == "S" ]] || { echo "abortado."; exit 0; }

git checkout -b "${BRANCH}" --quiet

if git merge --no-edit "${TAG}"; then
    echo
    echo "=============================================================="
    echo " MERGE LIMPO em ${BRANCH}"
    echo "=============================================================="
else
    echo
    echo "=============================================================="
    echo " CONFLITOS — resolver com CUSTOMIZATIONS.md ao lado"
    echo "=============================================================="
    git diff --name-only --diff-filter=U | sed 's/^/  /'
    echo
    echo "Regra: manter a versao do upstream E re-aplicar a nossa linha."
    echo "Depois: git add <arquivos> && git commit"
    echo "Desistir: git merge --abort && git checkout main && git branch -D ${BRANCH}"
fi

cat <<'EOF'

Proximos passos (docs-midiamax/02-runbook-atualizacao.md):
  3. build + testes            docker compose build && docker compose exec app php artisan test
  4. staging com dump de prod  php artisan migrate --pretend  (LER a saida)
  5. publicar                  git checkout main && git merge --no-ff <branch>
                               git tag -a mm-<tag>-1 -m "upstream <tag>"
                               git push origin main && git push origin mm-<tag>-1
                               (NUNCA git push --tags)
EOF
