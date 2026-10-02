#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${1:-/var/www/byznio}"
DOMAIN="${MAIL_DOMAIN:-byznio.cz}"
MAIL_HOSTNAME="${MAIL_HOSTNAME:-mail.byznio.cz}"
MAP="/etc/postfix/byznio_transport"
ACCESS="/etc/postfix/byznio_recipient_access"
DKIM_DIR="/etc/opendkim/keys/${DOMAIN}"

if [[ "$(id -u)" != "0" ]]; then echo "Spusťte jako root." >&2; exit 1; fi
if [[ ! -f "$APP_DIR/.env" || ! -f "$APP_DIR/database/app.sqlite" ]]; then echo "Nenalezeno Byznio v $APP_DIR (očekávám .env a database/app.sqlite)." >&2; exit 1; fi
if ! command -v apt-get >/dev/null 2>&1; then echo "Tento instalátor je připraven pro Debian/Ubuntu (apt-get)." >&2; exit 1; fi

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y postfix opendkim opendkim-tools python3 ca-certificates

postconf -e "myhostname = ${MAIL_HOSTNAME}"
postconf -e "mydomain = ${DOMAIN}"
postconf -e "myorigin = \$mydomain"
postconf -e "inet_interfaces = all"
postconf -e "inet_protocols = ipv4"
postconf -e "mydestination = \$myhostname, localhost.\$mydomain, localhost"
postconf -e "relay_domains = ${DOMAIN}"
postconf -e "smtpd_relay_restrictions = permit_mynetworks, reject_unauth_destination"
postconf -e "smtpd_recipient_restrictions = permit_mynetworks, check_recipient_access hash:${ACCESS}, reject_unauth_destination"
postconf -e "transport_maps = hash:${MAP}"
postconf -e "message_size_limit = 26214400"
postconf -e "mailbox_size_limit = 0"
postconf -e "disable_vrfy_command = yes"
postconf -e "smtpd_helo_required = yes"
postconf -e "smtpd_tls_security_level = may"
postconf -e "smtp_tls_security_level = may"
postconf -e "mynetworks = 127.0.0.0/8 [::1]/128"

cp "$APP_DIR/mailserver/byznio-pipe.cf" /etc/postfix/byznio-pipe.cf
if ! grep -q '^byznio-pipe ' /etc/postfix/master.cf; then cat /etc/postfix/byznio-pipe.cf >> /etc/postfix/master.cf; fi
install -m 0755 "$APP_DIR/bin/process-inbound-mail.php" /usr/local/bin/byznio-inbound-mail.php
cat > /usr/local/bin/byznio-inbound-mail <<EOF
#!/usr/bin/env bash
exec /usr/bin/php ${APP_DIR}/bin/process-inbound-mail.php "\$@"
EOF
chmod 0755 /usr/local/bin/byznio-inbound-mail

# OpenDKIM setup.
mkdir -p "$DKIM_DIR"
chown -R opendkim:opendkim /etc/opendkim/keys
if [[ ! -f "$DKIM_DIR/mail.private" ]]; then
  opendkim-genkey -b 2048 -d "$DOMAIN" -D "$DKIM_DIR" -s mail -v
  mv "$DKIM_DIR/mail.private" "$DKIM_DIR/mail.private" 2>/dev/null || true
  chown opendkim:opendkim "$DKIM_DIR/mail.private" "$DKIM_DIR/mail.txt"
  chmod 0600 "$DKIM_DIR/mail.private"
fi
cat > /etc/opendkim/TrustedHosts <<EOF
127.0.0.1
localhost
${DOMAIN}
*.${DOMAIN}
EOF
cat > /etc/opendkim/KeyTable <<EOF
mail._domainkey.${DOMAIN} ${DOMAIN}:mail:${DKIM_DIR}/mail.private
EOF
cat > /etc/opendkim/SigningTable <<EOF
*@${DOMAIN} mail._domainkey.${DOMAIN}
EOF
cat > /etc/opendkim.conf <<EOF
Syslog yes
UMask 002
Canonicalization relaxed/simple
Mode sv
SubDomains no
OversignHeaders From
Socket inet:8891@127.0.0.1
PidFile /run/opendkim/opendkim.pid
UserID opendkim
TrustAnchorFile /usr/share/dns/root.key
ExternalIgnoreList refile:/etc/opendkim/TrustedHosts
InternalHosts refile:/etc/opendkim/TrustedHosts
KeyTable refile:/etc/opendkim/KeyTable
SigningTable refile:/etc/opendkim/SigningTable
EOF
postconf -e "milter_default_action = accept"
postconf -e "milter_protocol = 6"
postconf -e "smtpd_milters = inet:127.0.0.1:8891"
postconf -e "non_smtpd_milters = inet:127.0.0.1:8891"

# Keep application environment aligned with the own mail server.
if grep -q '^MAIL_TRANSPORT=' "$APP_DIR/.env"; then sed -i 's/^MAIL_TRANSPORT=.*/MAIL_TRANSPORT=local/' "$APP_DIR/.env"; else printf '\nMAIL_TRANSPORT=local\n' >> "$APP_DIR/.env"; fi
if grep -q '^MAIL_DOMAIN=' "$APP_DIR/.env"; then sed -i "s#^MAIL_DOMAIN=.*#MAIL_DOMAIN=${DOMAIN}#" "$APP_DIR/.env"; else printf 'MAIL_DOMAIN=%s\n' "$DOMAIN" >> "$APP_DIR/.env"; fi
if grep -q '^MAIL_POSTFIX_MAP=' "$APP_DIR/.env"; then sed -i "s#^MAIL_POSTFIX_MAP=.*#MAIL_POSTFIX_MAP=${MAP}#" "$APP_DIR/.env"; else printf 'MAIL_POSTFIX_MAP=%s\n' "$MAP" >> "$APP_DIR/.env"; fi

# Initial recipient map + periodic sync.
cd "$APP_DIR"
/usr/bin/php bin/sync-mail-recipients.php
cat > /etc/cron.d/byznio-mail-recipients <<EOF
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
* * * * * root cd ${APP_DIR} && /usr/bin/php bin/sync-mail-recipients.php >/dev/null 2>&1
EOF
chmod 0644 /etc/cron.d/byznio-mail-recipients

systemctl enable --now opendkim
postfix check
systemctl enable --now postfix
systemctl restart opendkim
systemctl restart postfix

printf '\n=== Byznio mail server installed ===\n'
printf 'Hostname: %s\n' "$MAIL_HOSTNAME"
printf 'Domain:   %s\n' "$DOMAIN"
printf 'Inbound:  MX -> %s\n' "$MAIL_HOSTNAME"
printf '\nDKIM TXT record (add to WEDOS):\n'
cat "$DKIM_DIR/mail.txt"
printf '\nSPF suggestion (merge with any existing SPF, do not create duplicate SPF records):\nv=spf1 ip4:46.36.44.105 ~all\n'
printf '\nNext checks:\n  ss -ltnp | grep :25\n  postfix status\n  postmap -q test@${DOMAIN} hash:${MAP}\n'
printf '\nDo NOT switch MX until port 25/PTR are verified.\n'
