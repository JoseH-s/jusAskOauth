#!/bin/bash
set -e

cd /app

echo "==> Preparando a aplicacao Laravel para producao"

# Garante o link simbolico de storage (ignora erro se ja existir).
php artisan storage:link || true

# Aplica as migracoes pendentes. --force e obrigatorio fora do ambiente local.
php artisan migrate --force

# Caches de producao (config/rotas/views/eventos).
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Iniciando nginx + php-fpm via supervisord"

# Encontra o supervisord gerado pelo Nixpacks.
SUPERVISORD=$(find /nix/store -name "supervisord" 2>/dev/null | head -1)
echo "supervisord encontrado em: $SUPERVISORD"

# Encontra o supervisord.conf.
SUPERVISORD_CONF=$(find / -name "supervisord.conf" 2>/dev/null | grep -v proc | head -1)
echo "supervisord.conf encontrado em: $SUPERVISORD_CONF"

# Inicia usando supervisord, que ja sabe iniciar nginx + php-fpm corretamente.
exec "$SUPERVISORD" -c "$SUPERVISORD_CONF"
