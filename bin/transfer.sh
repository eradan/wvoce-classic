#!/usr/bin/env bash
# Trasferisce il tema sul server di produzione con rsync, dall'ultimo commit (come lo zip di
# bin/build-zip.sh): i file di sviluppo restano fuori grazie a export-ignore in .gitattributes, le
# modifiche non salvate in un commit non partono, e sul server si cancellano i file che nel tema
# non esistono più.
#
#   bin/transfer.sh        mostra il riepilogo e chiede conferma
#   bin/transfer.sh -n     prova: elenca cosa verrebbe copiato e cancellato, senza toccare niente
#   bin/transfer.sh -y     senza conferma
#
# Variabili (facoltative):
#   VV_DEST   destinazione rsync (predefinita: la cartella del tema sul server di produzione)
#   VV_OWNER  proprietario dei file sul server (predefinito 1000:1000). Non www-data (33:33): il
#             container di WordPress legge comunque tutto (cartelle 755, file 644), ma non deve poter
#             riscrivere il codice del tema.
set -euo pipefail

dest=${VV_DEST:-root@web-vivavoce-prod:/opt/wvoce.it/volumes/wordpress/wp-content/themes/wvoce-classic/}
owner=${VV_OWNER:-1000:1000}
prova=
conferma=1

while getopts 'ny' opt; do
	case $opt in
		n) prova=--dry-run ;;
		y) conferma= ;;
		*) echo "Uso: bin/transfer.sh [-n] [-y]   (-n prova senza toccare niente, -y senza conferma)" >&2; exit 2 ;;
	esac
done

cd "$(dirname "$0")/.."
versione=$(git show HEAD:style.css | sed -n 's/^Version: *//p' | tr -d '\r')
commit=$(git log -1 --format='%h %s')

if ! git diff --quiet HEAD -- . ':!build'; then
	echo "Attenzione: ci sono modifiche non salvate in un commit, non vengono trasferite." >&2
fi

echo "Tema:         versione $versione ($commit)"
echo "Destinazione: $dest"
echo "Proprietario: $owner, cartelle 755, file 644"
if [ -n "$prova" ]; then
	echo "Modalità:     prova, sul server non cambia niente"
fi

if [ -z "$prova" ] && [ -n "$conferma" ]; then
	read -r -p "Procedo? [s/N] " risposta
	[[ $risposta == [sS] ]] || { echo "Annullato."; exit 1; }
fi

tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
git archive HEAD | tar -x -C "$tmp"

# --chmod: mktemp -d crea la cartella con permessi 700, che rsync porterebbe sulla cartella del tema
# (Apache risponderebbe 403 a tutti i file del tema).
# --delete: sul server spariscono i file che nel tema non ci sono più.
rsync -rlptz --itemize-changes --delete $prova \
	--chmod=D755,F644 --chown="$owner" \
	"$tmp"/ "$dest"

if [ -n "$prova" ]; then
	echo "Prova finita: per trasferire davvero lancia bin/transfer.sh senza -n."
else
	echo "Fatto."
fi
