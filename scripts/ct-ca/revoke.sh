#!/bin/bash
# revoke.sh <serial_hex> [reason]
set -euo pipefail

SERIAL="${1:?Serial requerido}"
REASON="${2:-unspecified}"

cd /var/lib/pki
CERT="ca/tls-ca/${SERIAL}.pem"
[[ -f "$CERT" ]] || { echo "ERROR: cert no encontrado: $CERT" >&2; exit 1; }

openssl ca -batch \
    -config etc/tls-ca.conf \
    -passin file:/etc/pki/ca-passphrase \
    -revoke "$CERT" \
    -crl_reason "$REASON"

openssl ca -batch \
    -config etc/tls-ca.conf \
    -passin file:/etc/pki/ca-passphrase \
    -gencrl \
    -out crl/tls-ca.crl

cp crl/tls-ca.crl /srv/pki-exchange/crl/tls-ca.crl
chmod 644 /srv/pki-exchange/crl/tls-ca.crl
echo "OK revocado serial=$SERIAL reason=$REASON"
