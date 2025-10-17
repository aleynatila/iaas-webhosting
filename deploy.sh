#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
if [ -f "$ROOT_DIR/.env" ]; then
  echo "Using .env file"
else
  if [ -f "$ROOT_DIR/.env.example" ]; then
    cp "$ROOT_DIR/.env.example" "$ROOT_DIR/.env"
    echo "Copied .env.example to .env. Edit .env before running docker-compose if needed."
  fi
fi

echo "Starting Docker Compose stack..."
docker compose up -d --build
echo "Stack started. Visit http://localhost:${NGINX_PORT:-8080}"
