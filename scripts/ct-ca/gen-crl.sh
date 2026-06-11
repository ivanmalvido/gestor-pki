#!/bin/bash
set -euo pipefail
cd /var/lib/pki
openssl ca -batch \
    -config etc/tls-ca.conf \
    -passin file:/etc/pki/ca-passphrase \
    -gencrl \
    -out crl/tls-ca.crl
cp crl/tls-ca.crl /srv/pki-exchange/crl/tls-ca.crl
chmod 644 /srv/pki-exchange/crl/tls-ca.crl
echo "OK CRL regenerada"
