#!/bin/bash
# generate-app-registration-json.sh
# Usage: ./generate-app-registration-json.sh <app-id>
# Output: JSON for app registration (stdout)

set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 <app-id>"
  exit 1
fi

APP_ID="$1"
CERT="$(dirname "$0")/../certificates/digitalsignage.crt"
KEY="$(dirname "$0")/../certificates/digitalsignage.key"

# Read certificate (remove line breaks for JSON)
CERT_CONTENT=$(awk 'BEGIN{ORS="\\n"} {print}' "$CERT")

# Generate signature (base64)
SIGNATURE=$(echo -n "$APP_ID" | openssl dgst -sha512 -sign "$KEY" | openssl base64 -A)

# Output plain text for web form
echo "----- Certificate (copy into the web form) -----"
awk '{print}' "$CERT"
echo
echo "----- Signature (copy into the web form) -----"
echo "$SIGNATURE"
