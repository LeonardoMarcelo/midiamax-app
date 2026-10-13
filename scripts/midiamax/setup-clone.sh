#!/usr/bin/env bash
#
# Configura um clone novo do midiamax-app com a topologia do fork.
# Idempotente: pode rodar de novo sem estragar nada.
#
# Uso: scripts/midiamax/setup-clone.sh
#
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

UPSTREAM_URL="https://github.com/trypostit/trypost.git"

echo "==> remoto upstream (somente leitura)"
if git remote get-url upstream >/dev/null 2>&1; then
    git remote set-url upstream "$UPSTREAM_URL"
else
    git remote add upstream "$UPSTREAM_URL"
fi
# Push no upstream travado de proposito: nossas mudancas nunca vao para la por acidente.
git remote set-url --push upstream DISABLED_NO_PUSH_TO_UPSTREAM

echo "==> config de merge"
git config rerere.enabled true          # memoriza resolucao de conflito e reaproveita
git config rerere.autoupdate true
git config merge.ours.driver true       # exigido pelo merge=ours do .gitattributes
git config merge.conflictstyle zdiff3   # mostra a base comum no conflito

echo "==> branch vendor (espelho intocado do upstream)"
git fetch upstream --tags --quiet
if ! git show-ref --verify --quiet refs/heads/vendor; then
    git branch vendor upstream/main
fi

echo "==> config pessoal do Claude Code fora do diff"
if ! grep -q "^\.claude/settings\.json$" .git/info/exclude 2>/dev/null; then
    printf '\n# MidiaMax: config pessoal do harness, fora do diff do produto\n.claude/settings.json\n.claude/settings.local.json\n' >> .git/info/exclude
fi

echo
echo "Pronto. Topologia:"
git remote -v
git branch -vv
echo
echo "Leia CLAUDE.md e docs-midiamax/01-modelo-git.md antes do primeiro commit."
