#!/usr/bin/env bash

# ==============================================================================
# Script de Despliegue con Rollback Automático para EC2
# Proyecto: CoopAgua (Laravel + Vue + PostgreSQL + Docker)
# ==============================================================================

set -e # Salir en caso de error no controlado

APP_DIR="${1:-/var/www/coop-agua}"
HEALTH_URL="http://localhost:8080/api/health"
MAX_RETRIES=10
RETRY_INTERVAL=3

echo "🚀 Iniciando proceso de despliegue continuo en: $APP_DIR"

if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
fi

# 1. Guardar commit actual para rollback en caso de fallo
PREVIOUS_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo "")

echo "📥 Descargando los últimos cambios del repositorio..."
git fetch origin main
git reset --hard origin/main

# Verificar e instalar Node.js si no existe en la EC2
if ! command -v node &> /dev/null; then
    echo "📦 Node.js no encontrado. Instalando Node.js 20 (LTS)..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

echo "🎨 Instalando dependencias Node y compilando assets del frontend..."
npm ci --prefer-offline || npm install
npm run build

echo "🐳 Reconstruyendo y actualizando contenedores Docker..."
docker compose up -d --build --remove-orphans

echo "📦 Ejecutando migraciones de base de datos PostgreSQL..."
docker compose exec -T app php artisan migrate --force

echo "⚡ Optimizando caché de Laravel (Config, Rutas, Vistas)..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose exec -T app php artisan storage:link || true

echo "🔍 Ejecutando Health Check en $HEALTH_URL..."

ATTEMPT=0
HEALTH_OK=false

while [ $ATTEMPT -lt $MAX_RETRIES ]; do
    ATTEMPT=$((ATTEMPT+1))
    echo "  -> Intento $ATTEMPT/$MAX_RETRIES..."
    
    RESPONSE=$(curl -s -w "%{http_code}" "$HEALTH_URL" -o /tmp/health_response.json || echo "000")
    
    if [ "$RESPONSE" -eq 200 ] && grep -q '"status":"ok"' /tmp/health_response.json 2>/dev/null; then
        HEALTH_OK=true
        break
    fi
    
    sleep $RETRY_INTERVAL
done

if [ "$HEALTH_OK" = true ]; then
    echo "=============================================================================="
    echo "✅ ¡DESPLIEGUE EXITOSO Y SALUDABLE!"
    echo "   La aplicación responde HTTP 200 OK y la base de datos está conectada."
    echo "=============================================================================="
    exit 0
else
    echo "=============================================================================="
    echo "🚨 ¡FALLO EN EL HEALTH CHECK! Laravel devolvió un error o no responde."
    echo "⚠️  Iniciando ROLLBACK AUTOMÁTICO a la versión anterior..."
    echo "=============================================================================="
    
    if [ -n "$PREVIOUS_COMMIT" ]; then
        git reset --hard "$PREVIOUS_COMMIT"
        docker compose up -d --build
        docker compose exec -T app php artisan config:cache
    fi
    
    echo "❌ Rollback completado. La versión previa sigue activa en producción."
    exit 1
fi
