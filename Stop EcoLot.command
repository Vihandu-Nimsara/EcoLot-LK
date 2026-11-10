#!/bin/bash
set -euo pipefail
export PATH="/usr/local/bin:/opt/homebrew/bin:$PATH"
cd "$(dirname "$0")"
if ! docker compose stop; then
    read -r -p "Could not stop EcoLot. Press Enter to close..." || true
    exit 1
fi
echo "EcoLot stopped. Database data is preserved."
