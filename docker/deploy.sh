#!/usr/bin/env bash

# ==============================================================================
# Script de Despliegue Blue-Green con Cero Downtime y Rollback Automático
# Proyecto: CoopAgua (Laravel + Vue + PostgreSQL + Docker)
#
# Flujo:
#   1. Detecta cuál slot está ACTIVO (blue=8081 / green=8082)
#   2. Despliega la nueva versión en el slot INACTIVO
#   3. Hace Health Check en el nuevo slot
#   4. Si OK → Nginx redirige el tráfico al nuevo slot (cero downtime)
#   5. Si FALLA → el slot viejo sigue activo (rollback automático)
# ==============================================================================

set -e

APP_DIR="${1:-/var/www/coop-agua}"
ACTIVE_PORT_FILE="$APP_DIR/docker/active_port"
MAX_RETRIES=15
RETRY_INTERVAL=4

echo "🚀 Iniciando despliegue Blue-Green en: $APP_DIR"
cd "$APP_DIR"

# ─────────────────────────────────────────────
# 1. Determinar slot activo e inactivo
# ─────────────────────────────────────────────
if [ -f "$ACTIVE_PORT_FILE" ]; then
    ACTIVE_PORT=$(cat "$ACTIVE_PORT_FILE")
else
    ACTIVE_PORT="8081"
fi

if [ "$ACTIVE_PORT" = "8081" ]; then
    ACTIVE_SLOT="blue"
    ACTIVE_COMPOSE="docker-compose.blue.yml"
    NEW_SLOT="green"
    NEW_COMPOSE="docker-compose.green.yml"
    NEW_PORT="8082"
else
    ACTIVE_SLOT="green"
    ACTIVE_COMPOSE="docker-compose.green.yml"
    NEW_SLOT="blue"
    NEW_COMPOSE="docker-compose.blue.yml"
    NEW_PORT="8081"
fi

HEALTH_URL="http://localhost:${NEW_PORT}/api/health"

echo "📊 Slot ACTIVO: ${ACTIVE_SLOT} (puerto ${ACTIVE_PORT})"
echo "🎯 Desplegando en slot: ${NEW_SLOT} (puerto ${NEW_PORT})"

# ─────────────────────────────────────────────
# 2. Descargar los últimos cambios
# ─────────────────────────────────────────────
echo "📥 Descargando los últimos cambios del repositorio..."
git fetch origin main
git reset --hard origin/main

# ─────────────────────────────────────────────
# 3. Verificar/instalar Node.js y compilar frontend
# ─────────────────────────────────────────────
if ! command -v node &> /dev/null; then
    echo "📦 Instalando Node.js 20 (LTS)..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

echo "🎨 Compilando assets del frontend (Vite)..."
npm ci --prefer-offline || npm install
npm run build

# ─────────────────────────────────────────────
# 4. Levantar el nuevo slot (sin tocar el activo)
# ─────────────────────────────────────────────
echo "🐳 Levantando slot ${NEW_SLOT} en puerto ${NEW_PORT}..."
docker compose -f "$NEW_COMPOSE" -p "coop_agua_${NEW_SLOT}" up -d --build --remove-orphans

echo "⏳ Esperando que el slot ${NEW_SLOT} inicialice..."
sleep 10

echo "📦 Ejecutando migraciones de base de datos..."
docker compose -f "$NEW_COMPOSE" -p "coop_agua_${NEW_SLOT}" exec -T app php artisan migrate --force

echo "⚡ Optimizando caché de Laravel..."
docker compose -f "$NEW_COMPOSE" -p "coop_agua_${NEW_SLOT}" exec -T app php artisan config:cache
docker compose -f "$NEW_COMPOSE" -p "coop_agua_${NEW_SLOT}" exec -T app php artisan route:cache
docker compose -f "$NEW_COMPOSE" -p "coop_agua_${NEW_SLOT}" exec -T app php artisan view:cache
docker compose -f "$NEW_COMPOSE" -p "coop_agua_${NEW_SLOT}" exec -T app php artisan storage:link || true

# ─────────────────────────────────────────────
# 5. Health Check en el nuevo slot
# ─────────────────────────────────────────────
echo "🔍 Ejecutando Health Check en ${HEALTH_URL}..."

ATTEMPT=0
HEALTH_OK=false

while [ $ATTEMPT -lt $MAX_RETRIES ]; do
    ATTEMPT=$((ATTEMPT+1))
    echo "  -> Intento $ATTEMPT/$MAX_RETRIES..."

    HTTP_CODE=$(curl -s -o /tmp/health_response.json -w "%{http_code}" "$HEALTH_URL" || echo "000")

    if [ "$HTTP_CODE" = "200" ] && grep -q '"status":"ok"' /tmp/health_response.json 2>/dev/null; then
        HEALTH_OK=true
        break
    fi

    sleep $RETRY_INTERVAL
done

# ─────────────────────────────────────────────
# 6. Cambiar el tráfico (o hacer Rollback)
# ─────────────────────────────────────────────
if [ "$HEALTH_OK" = true ]; then
    echo ""
    echo "✅ Slot ${NEW_SLOT} está saludable. Redirigiendo tráfico..."

    # Reescribir la configuración de Nginx para apuntar al nuevo slot
    sudo tee /etc/nginx/conf.d/coop-agua.conf > /dev/null <<EOF
upstream coop_agua_active {
    server 127.0.0.1:${NEW_PORT};
}

server {
    listen 8080;
    server_name _;

    location / {
        proxy_pass http://coop_agua_active;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_connect_timeout 10s;
        proxy_read_timeout 60s;
    }
}
EOF

    # Recargar Nginx sin cortar conexiones activas (cero downtime)
    sudo nginx -t && sudo nginx -s reload

    # Guardar el nuevo slot como activo
    echo "$NEW_PORT" > "$ACTIVE_PORT_FILE"

    echo ""
    echo "========================================================================"
    echo "🟢 ¡DESPLIEGUE BLUE-GREEN EXITOSO!"
    echo "   Slot anterior : ${ACTIVE_SLOT} (puerto ${ACTIVE_PORT}) — apagando..."
    echo "   Slot activo   : ${NEW_SLOT} (puerto ${NEW_PORT})"
    echo "   La aplicación sigue respondiendo sin interrupción."
    echo "========================================================================"

    # Apagar el slot viejo (DESPUÉS de redirigir el tráfico)
    docker compose -f "$ACTIVE_COMPOSE" -p "coop_agua_${ACTIVE_SLOT}" down || true

    exit 0
else
    echo ""
    echo "========================================================================"
    echo "🚨 ¡FALLO EN EL HEALTH CHECK del slot ${NEW_SLOT}!"
    echo "⚠️  El tráfico sigue en el slot ${ACTIVE_SLOT} (puerto ${ACTIVE_PORT})."
    echo "   Apagando el slot fallido..."
    echo "========================================================================"

    # Apagar el slot nuevo que falló (el activo sigue intacto)
    docker compose -f "$NEW_COMPOSE" -p "coop_agua_${NEW_SLOT}" down || true

    exit 1
fi
