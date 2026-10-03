#!/usr/bin/env bash
set -euo pipefail
APP_DIR="${1:-/var/www/byznio}"
CRON_FILE="/etc/cron.d/byznio-reports"
PHP_BIN="$(command -v php || echo /usr/bin/php)"
printf '5 7 * * * www-data cd %q && %q bin/send-reports.php >> storage/logs/reports-cron.log 2>&1\n' "$APP_DIR" "$PHP_BIN" | sudo tee "$CRON_FILE" >/dev/null
sudo chmod 644 "$CRON_FILE"
echo "Byznio report cron installed: $CRON_FILE"
