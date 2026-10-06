#!/usr/bin/env bash
# Zip del tema pronto da caricare (Aspetto → Temi → Aggiungi → Carica): build/wvoce-classic.zip con
# dentro la cartella wvoce-classic, il nome che il tema ha sul server. Contiene l'ultimo commit
# (le modifiche non salvate restano fuori); i file di sviluppo sono esclusi da .gitattributes.
set -euo pipefail

cd "$(dirname "$0")/.."
nome=wvoce-classic
versione=$(git show HEAD:style.css | sed -n 's/^Version: *//p' | tr -d '\r')

if ! git diff --quiet HEAD -- . ':!build'; then
	echo "Attenzione: ci sono modifiche non salvate in un commit, nello zip non entrano." >&2
fi

mkdir -p build
git archive --format=zip --prefix="$nome/" -o "build/$nome.zip" HEAD
echo "build/$nome.zip (versione $versione)"
