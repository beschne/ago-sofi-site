#!/usr/bin/env bash
# Manuelles Backup: Datenbank (ago_sofi) + hochgeladene Fotos (site/uploads/).
# Der Code selbst liegt in GitHub und wird hier bewusst nicht mit gesichert.
# Aufruf: ./deploy/backup.sh
set -euo pipefail

REMOTE_HOST="cerberus@92.205.236.81"
SSH_KEY="$HOME/.ssh/ago-sofi-deploy"
REMOTE_UPLOADS="/var/www/sofi.agorion.de/uploads"
ZIEL_DIR="$HOME/Desktop"

ZEITSTEMPEL="$(date +%Y-%m-%d_%H%M)"
ARBEITSVERZEICHNIS="$(mktemp -d)"
trap 'rm -rf "$ARBEITSVERZEICHNIS"' EXIT

mkdir -p "$ZIEL_DIR"

echo "1/3 Datenbank-Dump erstellen..."
ssh -i "$SSH_KEY" -o IdentitiesOnly=yes "$REMOTE_HOST" \
    'sudo -n mysqldump --single-transaction ago_sofi' > "$ARBEITSVERZEICHNIS/ago_sofi.sql"

echo "2/3 Fotos herunterladen..."
mkdir -p "$ARBEITSVERZEICHNIS/uploads"
rsync -az -e "ssh -i $SSH_KEY -o IdentitiesOnly=yes" \
    "$REMOTE_HOST:$REMOTE_UPLOADS/" "$ARBEITSVERZEICHNIS/uploads/"

echo "3/3 ZIP erstellen..."
ZIP_DATEI="$ZIEL_DIR/$ZEITSTEMPEL-ago-sofi-backup.zip"
(cd "$ARBEITSVERZEICHNIS" && zip -rq "$ZIP_DATEI" ago_sofi.sql uploads)

echo "Fertig: $ZIP_DATEI"
