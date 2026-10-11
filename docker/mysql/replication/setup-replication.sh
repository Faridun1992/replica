#!/bin/bash
set -euo pipefail

: "${MYSQL_REPLICATION_PASSWORD:?Set MYSQL_REPLICATION_PASSWORD in .env}"
if [[ ! "$MYSQL_REPLICATION_PASSWORD" =~ ^[a-zA-Z0-9_-]+$ ]]; then
    echo 'MYSQL_REPLICATION_PASSWORD must contain only letters, numbers, underscores or hyphens.' >&2
    exit 1
fi

mysql_writer() {
    mysql --connect-timeout=5 -h mysql_writer -uroot -p"$MYSQL_ROOT_PASSWORD" "$@"
}

mysql_reader() {
    mysql --connect-timeout=5 -h mysql_reader -uroot -p"$MYSQL_ROOT_PASSWORD" "$@"
}

wait_for_mysql() {
    local host="$1"
    for attempt in $(seq 1 60); do
        if mysql --connect-timeout=5 -h "$host" -uroot -p"$MYSQL_ROOT_PASSWORD" -Nse 'SELECT 1' >/dev/null 2>&1; then
            return 0
        fi
        sleep 2
    done
    echo "MySQL server $host did not accept connections." >&2
    return 1
}

wait_for_mysql mysql_writer
wait_for_mysql mysql_reader

mysql_writer -e "CREATE USER IF NOT EXISTS 'replicator'@'%' IDENTIFIED BY '$MYSQL_REPLICATION_PASSWORD'; ALTER USER 'replicator'@'%' IDENTIFIED BY '$MYSQL_REPLICATION_PASSWORD'; GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'replicator'@'%';"

if mysql_reader -Nse 'SHOW REPLICA STATUS' | grep -q .; then
    mysql_reader -e 'SET GLOBAL super_read_only = OFF;'
    mysql_reader -e 'STOP REPLICA; RESET REPLICA ALL;'
else
    # Seed the reader once so databases that existed before binlogging was enabled
    # are present before replication starts.
    mysqldump -h mysql_writer -uroot -p"$MYSQL_ROOT_PASSWORD" \
        --single-transaction --set-gtid-purged=OFF --databases "$MYSQL_DATABASE" testing \
        | mysql_reader
fi

mysql_reader -e "CHANGE REPLICATION SOURCE TO SOURCE_HOST = 'mysql_writer', SOURCE_PORT = 3306, SOURCE_USER = 'replicator', SOURCE_PASSWORD = '$MYSQL_REPLICATION_PASSWORD', SOURCE_AUTO_POSITION = 1, GET_SOURCE_PUBLIC_KEY = 1; START REPLICA;"
mysql_reader -e 'SET GLOBAL super_read_only = ON;'

for attempt in $(seq 1 60); do
    status="$(mysql_reader -E -e 'SHOW REPLICA STATUS' 2>/dev/null || true)"
    if grep -q 'Replica_IO_Running: Yes' <<<"$status" && grep -q 'Replica_SQL_Running: Yes' <<<"$status"; then
        echo 'MySQL read replica is connected and applying events.'
        exit 0
    fi
    sleep 2
done

echo 'MySQL read replica did not become healthy:' >&2
printf '%s\n' "$status" >&2
exit 1
