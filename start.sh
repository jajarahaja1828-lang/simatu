#!/bin/bash
set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

MYSQL_DATADIR="/home/runner/workspace/.mysql-data"
MYSQL_SOCKET="/home/runner/workspace/.mysql.sock"
MYSQL_PID="/home/runner/workspace/.mysql.pid"

export MYSQL_SOCKET="$MYSQL_SOCKET"

echo -e "${GREEN}=== SIMATU Startup ===${NC}"

# Stop any existing MySQL
if [ -f "$MYSQL_PID" ]; then
    PID=$(cat "$MYSQL_PID" 2>/dev/null || true)
    if [ -n "$PID" ] && kill -0 "$PID" 2>/dev/null; then
        echo -e "${YELLOW}Stopping existing MySQL (PID: $PID)...${NC}"
        kill "$PID" 2>/dev/null || true
        sleep 2
    fi
    rm -f "$MYSQL_PID"
fi

# Also kill any lingering mysqld processes
pkill -f "mysqld.*mysql-data" 2>/dev/null || true
sleep 1

# Initialize MySQL data directory
if [ ! -d "$MYSQL_DATADIR/mysql" ]; then
    echo -e "${GREEN}Initializing MySQL data directory...${NC}"
    mkdir -p "$MYSQL_DATADIR"
    mysql_install_db \
        --user="$(whoami)" \
        --datadir="$MYSQL_DATADIR" \
        --skip-name-resolve \
        2>/dev/null || mysql_install_db --datadir="$MYSQL_DATADIR" 2>/dev/null
    echo -e "${GREEN}MySQL initialized.${NC}"
fi

# Start MySQL server in background (--daemonize not supported in this version)
echo -e "${GREEN}Starting MySQL server...${NC}"
mysqld \
    --datadir="$MYSQL_DATADIR" \
    --socket="$MYSQL_SOCKET" \
    --pid-file="$MYSQL_PID" \
    --skip-networking \
    --skip-name-resolve \
    --character-set-server=utf8mb4 \
    --collation-server=utf8mb4_unicode_ci \
    --innodb-buffer-pool-size=64M \
    --user="$(whoami)" \
    --log-error=/tmp/mysql-error.log \
    2>/dev/null &
MYSQLD_PID=$!

# Detect which privileged MySQL user we can connect as (runner or root)
DB_USER="$(whoami)"

# Wait for MySQL to be ready
echo -e "${GREEN}Waiting for MySQL...${NC}"
for i in $(seq 1 30); do
    if mysql --socket="$MYSQL_SOCKET" -u "$DB_USER" -e "SELECT 1" 2>/dev/null; then
        echo -e "${GREEN}MySQL is ready! (user: $DB_USER)${NC}"
        break
    fi
    if ! kill -0 "$MYSQLD_PID" 2>/dev/null; then
        echo -e "${RED}MySQL process died. Check /tmp/mysql-error.log${NC}"
        cat /tmp/mysql-error.log 2>/dev/null || true
        exit 1
    fi
    if [ $i -eq 30 ]; then
        echo -e "${RED}MySQL failed to start within 30s. Check /tmp/mysql-error.log${NC}"
        cat /tmp/mysql-error.log 2>/dev/null || true
        exit 1
    fi
    sleep 1
done

# Create database and user
echo -e "${GREEN}Setting up database...${NC}"
mysql --socket="$MYSQL_SOCKET" -u "$DB_USER" -e "
    CREATE DATABASE IF NOT EXISTS simatu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL PRIVILEGES ON simatu.* TO '$DB_USER'@'localhost';
    FLUSH PRIVILEGES;
" 2>/dev/null || true

# Run init SQL (idempotent — IF NOT EXISTS guards in the schema)
mysql --socket="$MYSQL_SOCKET" -u "$DB_USER" simatu < /home/runner/workspace/artifacts/simatu/init.sql 2>/dev/null && \
    echo -e "${GREEN}Database schema loaded.${NC}" || \
    echo -e "${YELLOW}Schema already loaded or minor error (OK).${NC}"

# Start PHP built-in server (foreground — keeps workflow alive)
PORT="${PORT:-25651}"
echo -e "${GREEN}Starting PHP server on port $PORT...${NC}"
echo -e "${GREEN}App ready at http://localhost:$PORT${NC}"

cd /home/runner/workspace/artifacts/simatu/public
exec php -S 0.0.0.0:$PORT \
    -d display_errors=On \
    -d error_reporting=E_ALL \
    -d session.save_path=/tmp \
    router.php
