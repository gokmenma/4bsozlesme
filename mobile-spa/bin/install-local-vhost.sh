#!/usr/bin/env bash
set -euo pipefail

VHOST_FILE="/opt/lampp/etc/extra/httpd-vhosts.conf"
VHOST_SOURCE="/opt/lampp/htdocs/sozlesme-4b/deploy/apache/mobile.4bsozlesme.com.conf"
HOSTS_FILE="/etc/hosts"
DOMAIN="mobile.4bsozlesme.com"

if [[ ! -f "$VHOST_SOURCE" ]]; then
    echo "Vhost şablonu bulunamadı: $VHOST_SOURCE" >&2
    exit 1
fi

if ! grep -q "ServerName $DOMAIN" "$VHOST_FILE"; then
    cp "$VHOST_FILE" "${VHOST_FILE}.bak.$(date +%Y%m%d%H%M%S)"
    {
        printf '\n# >>> %s (Sozlesme 4B Mobile SPA) >>>\n' "$DOMAIN"
        cat "$VHOST_SOURCE"
        printf '# <<< %s (Sozlesme 4B Mobile SPA) <<<\n' "$DOMAIN"
    } >> "$VHOST_FILE"
    echo "Apache vhost eklendi: $DOMAIN"
else
    echo "Apache vhost zaten tanımlı: $DOMAIN"
fi

if ! grep -qE "(^|[[:space:]])${DOMAIN//./\.}([[:space:]]|$)" "$HOSTS_FILE"; then
    cp "$HOSTS_FILE" "${HOSTS_FILE}.bak.$(date +%Y%m%d%H%M%S)"
    printf '127.0.0.1    %s\n' "$DOMAIN" >> "$HOSTS_FILE"
    echo "/etc/hosts kaydı eklendi: $DOMAIN"
else
    echo "/etc/hosts kaydı zaten mevcut: $DOMAIN"
fi

/opt/lampp/bin/httpd -t
/opt/lampp/lampp reloadapache

echo "Hazır: http://$DOMAIN"
