#!/bin/bash
# verify-signature.sh
# Usage: ./verify-signature.sh <archive.tar.gz> <signature.sig> <certificate.crt>

set -euo pipefail

if [[ $# -ne 3 ]]; then
  echo "Usage: $0 <archive.tar.gz> <signature.sig> <certificate.crt>"
  exit 1
fi

ARCHIVE="$1"
SIGNATURE="$2"
CERT="$3"

openssl dgst -sha512 -verify <(openssl x509 -in "$CERT" -pubkey -noout) -signature "$SIGNATURE" "$ARCHIVE"
