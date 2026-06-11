#!/bin/bash
# Wrapper invocado por SSH command=
set -euo pipefail
CMD="${SSH_ORIGINAL_COMMAND:-}"
read -ra ARGS <<< "$CMD"
ACTION="${ARGS[0]:-}"

case "$ACTION" in
  sign)
    [[ ${#ARGS[@]} -ge 4 ]] || { echo "USO: sign <csr> <profile> <name>" >&2; exit 2; }
    sudo /opt/pki/sign.sh "${ARGS[1]}" "${ARGS[2]}" "${ARGS[3]}"
    ;;
  revoke)
    [[ ${#ARGS[@]} -ge 2 ]] || { echo "USO: revoke <serial> [reason]" >&2; exit 2; }
    sudo /opt/pki/revoke.sh "${ARGS[1]}" "${ARGS[2]:-unspecified}"
    ;;
  gen-crl)
    sudo /opt/pki/gen-crl.sh
    ;;
  *)
    echo "ERROR: accion no permitida: $ACTION" >&2
    exit 2
    ;;
esac
