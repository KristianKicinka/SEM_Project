#!/bin/sh
set -eu

# -----------------------------------------------------------------------------
# run.sh — start services, wait for DB, run migrations, import SQL
#
# Simple and explicit:
#  - We define DB_ROOT_PASS here (must match MYSQL_ROOT_PASSWORD in compose).
#  - We wait for DB using the WEB container's mysql client (SELECT 1 as root).
#  - Then we run Laravel migrations and import the seed SQL from the WEB container.
# -----------------------------------------------------------------------------

# --- Services (names from docker-compose.yml) --------------------------------
WEB_SERVICE="web"      # Laravel/PHP container (php artisan + mysql client)
DB_SERVICE="db"        # MariaDB container

# --- Secrets / credentials ---------------------------------------------------
# IMPORTANT: Keep this in sync with your docker-compose.yml (MYSQL_ROOT_PASSWORD)
DB_ROOT_PASS="root"

# --- Migration mode ----------------------------------------------------------
# "true"  => php artisan migrate:fresh --force  (DROP & recreate schema; DEV only)
# "false" => php artisan migrate --force        (safer; no drop)
FRESH_MIGRATIONS="${FRESH_MIGRATIONS:-true}"

# --- SQL seed file (path inside the WEB container) ---------------------------
SQL_FILE_PATH="./virtualisation/installationFiles/sem_project_db_data.sql"

# --- App DB connection used for the SQL import (from inside WEB container) ---
DB_HOST="127.0.0.1"   # host networking => DB reachable on localhost
DB_PORT="3306"
DB_NAME="sem_project"
DB_USER="sem_project"
DB_PASS="password"

# --- Optional max wait for DB (seconds). 0 = wait indefinitely ---------------
DB_WAIT_SECS="${DB_WAIT_SECS:-120}"

die() { echo "ERROR: $*" >&2; exit 1; }

echo "==> Starting Docker Compose (detached)"
docker compose up -d --remove-orphans

echo "==> Checking MySQL client & SQL file inside WEB container"
docker compose exec -T --user www-data "$WEB_SERVICE" sh -lc 'command -v mysql >/dev/null 2>&1' \
  || die "MySQL client not found in WEB container."
docker compose exec -T --user www-data "$WEB_SERVICE" sh -lc "[ -f '$SQL_FILE_PATH' ]" \
  || die "SQL file not found at $SQL_FILE_PATH inside WEB container."

echo "==> Waiting for DB (from WEB container using root credentials)..."
if [ "$DB_WAIT_SECS" -gt 0 ]; then
  waited=0
  while ! docker compose exec -T --user www-data "$WEB_SERVICE" sh -lc \
    "mysql -h '$DB_HOST' -P '$DB_PORT' -u root -p'$DB_ROOT_PASS' -e 'SELECT 1' >/dev/null 2>&1"
  do
    sleep 2
    waited=$((waited + 2))
    if [ "$waited" -ge "$DB_WAIT_SECS" ]; then
      echo "Last DB logs:"; docker compose logs "$DB_SERVICE" | tail -n 100 || true
      die "DB did not become ready within ${DB_WAIT_SECS}s"
    fi
  done
else
  docker compose exec -T --user www-data "$WEB_SERVICE" sh -lc \
    "until mysql -h '$DB_HOST' -P '$DB_PORT' -u root -p'$DB_ROOT_PASS' -e 'SELECT 1' >/dev/null 2>&1; do sleep 2; done"
fi
echo "✔ DB is ready."

# --- Run Laravel migrations --------------------------------------------------
if [ "$FRESH_MIGRATIONS" = "true" ]; then
  echo "==> Running Laravel migrations: migrate:fresh --force (DESTRUCTIVE)"
  docker compose exec -T --user www-data "$WEB_SERVICE" sh -lc 'php artisan migrate:fresh --force'
else
  echo "==> Running Laravel migrations: migrate --force"
  docker compose exec -T --user www-data "$WEB_SERVICE" sh -lc 'php artisan migrate --force'
fi
echo "✔ Migrations completed."

# --- Import SQL seed ---------------------------------------------------------
echo "==> Importing SQL seed into database"
docker compose exec -T --user www-data "$WEB_SERVICE" sh -lc \
  "mysql -h '$DB_HOST' -P '$DB_PORT' -u '$DB_USER' -p'$DB_PASS' -D '$DB_NAME' < '$SQL_FILE_PATH'"
echo "✔ SQL import completed."

echo "==> Stack status"
docker compose ps || true

echo "✔ Done. Services are up, migrations ran, and seed data was imported."
