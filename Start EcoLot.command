#!/bin/bash
set -euo pipefail
export PATH="/usr/local/bin:/opt/homebrew/bin:$PATH"
cd "$(dirname "$0")"
on_error() {
    echo "EcoLot could not start. Check the error above."
    read -r -p "Press Enter to close..." || true
}
trap on_error ERR
if ! command -v docker >/dev/null 2>&1; then
    echo "Install Docker Desktop, then try again."
    false
fi
if ! docker info >/dev/null 2>&1; then
    echo "Opening Docker Desktop..."
    open -a Docker
    ready=false
    for ((attempt=0; attempt<90; attempt++)); do
        if docker info >/dev/null 2>&1; then
            ready=true
            break
        fi
        sleep 2
    done
    if [ "$ready" != true ]; then
        echo "Docker is not ready. Finish Docker Desktop setup and try again."
        false
    fi
fi
echo "Starting EcoLot backend, database and phpMyAdmin..."
docker compose up --build -d --wait --wait-timeout 300
echo "App: http://localhost:8080"
echo "phpMyAdmin: http://localhost:8081 (app_user / user_pass)"
open "http://localhost:8080"
