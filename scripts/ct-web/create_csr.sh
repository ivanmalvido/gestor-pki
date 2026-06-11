#!/bin/bash
set -euo pipefail
CN="${1:?Uso: $0 <CN> [OU]}"
OU="${2:-IF3-03}"
OUTDIR="/srv/pki-exchange/csr"
SAFE=$(printf "%s" "$CN" | tr -c 'A-Za-z0-9.-' '_')
KEY="$OUTDIR/${SAFE}.key"
CSR="$OUTDIR/${SAFE}.csr"
openssl req -new -newkey rsa:2048 -nodes \
    -keyout "$KEY" -out "$CSR" \
    -subj "/C=ES/ST=Galicia/O=ASIR/OU=$OU/CN=$CN" \
    -addext "subjectAltName=DNS:$CN"
echo "Clave: $KEY"
echo "CSR  : $CSR"
