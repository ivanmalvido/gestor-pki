#!/bin/bash
# sign.sh <csr_path> <profile:server|client> <nombre_salida>
set -euo pipefail

CSR="${1:?CSR requerido}"
PROFILE="${2:?Perfil requerido (server|client)}"
NAME="${3:?Nombre de salida requerido}"

[[ -f "$CSR" ]] || { echo "ERROR: CSR no existe: $CSR" >&2; exit 1; }

case "$PROFILE" in
  server) EXT="server_ext" ;;
  client) EXT="client_ext" ;;
  *) echo "ERROR: Perfil invalido (server|client)" >&2; exit 1 ;;
esac

SAFE=$(printf "%s" "$NAME" | tr -c "A-Za-z0-9.-" "_")
OUT="/srv/pki-exchange/certs/${SAFE}.crt"

cd /var/lib/pki
openssl ca -batch -notext \
    -config etc/tls-ca.conf \
    -extensions "$EXT" \
    -passin file:/etc/pki/ca-passphrase \
    -in "$CSR" \
    -out "$OUT"

chmod 644 "$OUT"
echo "OK $OUT"
