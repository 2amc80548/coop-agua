#!/usr/bin/env bash

# ==============================================================================
# Script de Despliegue Blue-Green con Cero Downtime y Rollback Automático
# Proyecto: CoopAgua (Laravel + Vue + PostgreSQL + Docker)
#
# Arquitectura:
#   Internet → Nginx host (puerto 80)
#                  ↓
#          🔵 Blue  (8080)  ←→  🟢 Green (8081)
#                  ↓
#         DB compartida (coop_agua_db — nunca se reinicia)
#
# Flujo de cada deploy:
#   1. Detecta slot ACTIVO (blue=8080 / green=8081)
#   2. Despliega nueva versión en slot INACTIVO
#   3. Health Check en el slot nuevo
#   4. OK → Nginx redirige tráfico (cero downtime) → apaga slot viejo
#   5. FALLA → slot viejo sigue activo (rollback automático)
# ==============================================================================

set -e

APP_DIR="${1:-/var/www/coop-agua}"
ACTIVE_PORT_FILE="$APP_DIR/docker/active_port"
SEEDED_FLAG_FILE="$APP_DIR/docker/db_seeded"   # Flag para saber si ya se hizo seed
MAX_RETRIES=20
RETRY_INTERVAL=4

echo "🚀 Iniciando despliegue Blue-Green en: $APP_DIR"
cd "$APP_DIR"

# ─────────────────────────────────────────────────────────────────
# 1. Asegurar que la base de datos compartida esté corriendo
# ─────────────────────────────────────────────────────────────────
echo "🗄️  Verificando base de datos compartida (coop_agua_db)..."

# Leer variables del .env para pasarlas a PostgreSQL
_DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
_DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")
_DB_PASSWORD=$(grep '^DB_PASSWORD=' .env | cut -d '=' -f2 | tr -d '"' | tr -d "'")

# Valores por defecto si están vacíos
_DB_DATABASE="${_DB_DATABASE:-coop_agua_db}"
_DB_USERNAME="${_DB_USERNAME:-coop_user}"
_DB_PASSWORD="${_DB_PASSWORD:-coop_password_secret}"

echo "   → DB: $_DB_DATABASE | USER: $_DB_USERNAME"

DB_DATABASE="$_DB_DATABASE" \
DB_USERNAME="$_DB_USERNAME" \
DB_PASSWORD="$_DB_PASSWORD" \
docker compose -f docker-compose.db.yml -p coop_agua_db up -d

# ─────────────────────────────────────────────────────────────────
# 2. Determinar slot activo e inactivo
# ─────────────────────────────────────────────────────────────────
if [ -f "$ACTIVE_PORT_FILE" ]; then
    ACTIVE_PORT=$(cat "$ACTIVE_PORT_FILE")
else
    ACTIVE_PORT="8080"
fi

if [ "$ACTIVE_PORT" = "8080" ]; then
    ACTIVE_SLOT="blue"
    ACTIVE_COMPOSE="docker-compose.blue.yml"
    NEW_SLOT="green"
    NEW_COMPOSE="docker-compose.green.yml"
    NEW_PORT="8081"
else
    ACTIVE_SLOT="green"
    ACTIVE_COMPOSE="docker-compose.green.yml"
    NEW_SLOT="blue"
    NEW_COMPOSE="docker-compose.blue.yml"
    NEW_PORT="8080"
fi

HEALTH_URL="http://localhost:${NEW_PORT}/api/health"

echo "📊 Slot ACTIVO  : ${ACTIVE_SLOT} (puerto ${ACTIVE_PORT}) — recibe tráfico"
echo "🎯 Desplegando en: ${NEW_SLOT} (puerto ${NEW_PORT})"

# ─────────────────────────────────────────────────────────────────
# 3. Descargar los últimos cambios
# ─────────────────────────────────────────────────────────────────
echo "📥 Descargando los últimos cambios del repositorio..."
git fetch origin main
git reset --hard origin/main

# Arreglar permisos si el contenedor anterior cambió el owner
sudo chown -R "$USER":"$USER" storage bootstrap/cache 2>/dev/null || true
sudo chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# Corregir DB_HOST en el .env (la DB compartida se llama "coop_agua_db")
if [ -f ".env" ]; then
    sed -i 's/^DB_HOST=.*/DB_HOST=coop_agua_db/' .env
    # Mostrar solo el valor, sin duplicar el nombre de la variable
    DB_HOST_VAL=$(grep '^DB_HOST=' .env | cut -d'=' -f2)
    echo "✅ .env verificado: DB_HOST=$DB_HOST_VAL"
fi

# ─────────────────────────────────────────────────────────────────
# 4. Instalar Node.js si no existe y compilar frontend
# ─────────────────────────────────────────────────────────────────
if ! command -v node &> /dev/null; then
    echo "📦 Instalando Node.js 20 (LTS)..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

echo "🎨 Compilando assets del frontend (Vite → public/build/manifest.json)..."
npm ci --prefer-offline || npm install
npm run build

# ─────────────────────────────────────────────────────────────────
# 5. Levantar el nuevo slot (sin tocar el activo)
# ─────────────────────────────────────────────────────────────────
echo "🐳 Levantando slot ${NEW_SLOT} en puerto ${NEW_PORT}..."
docker compose -f "$NEW_COMPOSE" -p "coop_${NEW_SLOT}" up -d --build --remove-orphans

echo "⏳ Esperando que el slot ${NEW_SLOT} inicialice (10s)..."
sleep 10

echo "📦 Ejecutando migraciones de base de datos..."
docker compose -f "$NEW_COMPOSE" -p "coop_${NEW_SLOT}" exec -T app php artisan migrate --force

# ─────────────────────────────────────────────────────────────────
# Ejecutar Seeds solo en el PRIMER despliegue (si no existe la flag)
# ─────────────────────────────────────────────────────────────────
if [ ! -f "$SEEDED_FLAG_FILE" ]; then
    echo "🌱 Primer despliegue detectado — ejecutando Seeds de datos iniciales..."
    docker compose -f "$NEW_COMPOSE" -p "coop_${NEW_SLOT}" exec -T app php artisan db:seed --force
    touch "$SEEDED_FLAG_FILE"
    echo "✅ Seeds ejecutados. No se volverán a ejecutar en futuros deploys."
else
    echo "🌱 Seeds: ya ejecutados anteriormente (docker/db_seeded existe) — omitiendo."
fi

echo "⚡ Optimizando caché de Laravel..."
docker compose -f "$NEW_COMPOSE" -p "coop_${NEW_SLOT}" exec -T app php artisan config:cache
docker compose -f "$NEW_COMPOSE" -p "coop_${NEW_SLOT}" exec -T app php artisan route:cache
docker compose -f "$NEW_COMPOSE" -p "coop_${NEW_SLOT}" exec -T app php artisan view:cache
docker compose -f "$NEW_COMPOSE" -p "coop_${NEW_SLOT}" exec -T app php artisan storage:link 2>/dev/null || true

# ─────────────────────────────────────────────────────────────────
# 6. Health Check en el nuevo slot
# ─────────────────────────────────────────────────────────────────
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

# ─────────────────────────────────────────────────────────────────
# 7. Cambiar el tráfico (o hacer Rollback)
# ─────────────────────────────────────────────────────────────────
if [ "$HEALTH_OK" = true ]; then
    echo ""
    echo "✅ Slot ${NEW_SLOT} está saludable. Redirigiendo tráfico..."

    # Reescribir Nginx para apuntar al nuevo slot
    sudo tee /etc/nginx/conf.d/coop-agua.conf > /dev/null <<EOF
upstream coop_agua_active {
    server 127.0.0.1:${NEW_PORT};
}

server {
    listen 80;
    server_name _;
    client_max_body_size 64M;

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

    # Recargar Nginx SIN cortar conexiones activas
    sudo nginx -t && sudo nginx -s reload

    # Guardar el nuevo slot como activo
    echo "$NEW_PORT" > "$ACTIVE_PORT_FILE"

    echo ""
    echo "========================================================================"
    echo "🟢 ¡DESPLIEGUE BLUE-GREEN EXITOSO!"
    echo "   Tráfico redirigido: ${ACTIVE_SLOT}(${ACTIVE_PORT}) → ${NEW_SLOT}(${NEW_PORT})"
    echo "   Slot activo ahora : ${NEW_SLOT} en puerto ${NEW_PORT}"
    echo "   Apagando slot anterior (${ACTIVE_SLOT})..."
    echo "========================================================================"

    # Apagar el slot viejo DESPUÉS de redirigir (cero downtime)
    docker compose -f "$ACTIVE_COMPOSE" -p "coop_${ACTIVE_SLOT}" down || true

    exit 0
else
    echo ""
    echo "========================================================================"
    echo "🚨 FALLO en el Health Check del slot ${NEW_SLOT}!"
    echo "⚠️  Tráfico sigue en ${ACTIVE_SLOT} (puerto ${ACTIVE_PORT}) — sin afectación."
    echo "   Apagando slot fallido..."
    echo "========================================================================"

    # Apagar el slot nuevo que falló (el activo sigue intacto)
    docker compose -f "$NEW_COMPOSE" -p "coop_${NEW_SLOT}" down || true

    exit 1
fi
