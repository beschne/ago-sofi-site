#!/usr/bin/env bash
# Deployt den Inhalt von site/ per rsync auf den Server.
# Siehe DEPLOYMENT.md fuer den einmaligen Setup (Key, nginx, TLS).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SITE_DIR="$SCRIPT_DIR/../site"

REMOTE_HOST="cerberus@92.205.236.81"
REMOTE_PATH="/var/www/sofi.agorion.de/"
SSH_KEY="$HOME/.ssh/ago-sofi-deploy"

rsync -avz --delete \
    -e "ssh -i $SSH_KEY -o IdentitiesOnly=yes" \
    --exclude ".DS_Store" \
    "$SITE_DIR/" "$REMOTE_HOST:$REMOTE_PATH"

echo "Deploy abgeschlossen: https://sofi.agorion.de"
