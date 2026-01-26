#!/bin/bash
# scripts/upload-to-appstore.sh
# Registers a release with the Nextcloud App Store via API (JSON, no direct upload!)
# Usage: ./scripts/upload-to-appstore.sh <version> <download-url> [--nightly]

set -euo pipefail



# Parse arguments
NIGHTLY="false"
POSITIONAL=()
while [[ $# -gt 0 ]]; do
  case $1 in
    --nightly)
      NIGHTLY="true"
      shift
      ;;
    *)
      POSITIONAL+=("$1")
      shift
      ;;
  esac
done
set -- "${POSITIONAL[@]}"

if [[ $# -lt 2 ]]; then
  echo "Usage: $0 <version> <download-url> [--nightly]"
  exit 1
fi

VERSION="$1"
DOWNLOAD_URL="$2"
APP_NAME="digitalsignage"
ARCHIVE="digitalsignage-${VERSION}.tar.gz"
SIGNATURE="digitalsignage-${VERSION}.tar.gz.sig"
API_URL="https://apps.nextcloud.com/api/v1/apps/releases"



if [[ -z "${NC_APPSTORE_TOKEN:-}" ]]; then
  echo "NC_APPSTORE_TOKEN is not set!"
  exit 1
fi

if [[ ! -f "$ARCHIVE" ]]; then
  echo "Release archive $ARCHIVE not found!"
  exit 1
fi

if [[ ! -f "$SIGNATURE" ]]; then
  echo "Signature file $SIGNATURE not found!"
  exit 1
fi



echo "Registering release $ARCHIVE with the Nextcloud App Store (JSON API)..."

# Encode signature as base64
SIGNATURE_B64=$(base64 -w 0 "$SIGNATURE")


# Build valid JSON body
if [[ "$NIGHTLY" == "true" ]]; then
  JSON_BODY=$(printf '{"download":"%s","signature":"%s","nightly":true}' "$DOWNLOAD_URL" "$SIGNATURE_B64")
else
  JSON_BODY=$(printf '{"download":"%s","signature":"%s"}' "$DOWNLOAD_URL" "$SIGNATURE_B64")
fi

# API call
response=$(curl -s -w "%{http_code}" -o /tmp/appstore_response.txt -X POST "$API_URL" \
  -H "Authorization: Token $NC_APPSTORE_TOKEN" \
  -H "Content-Type: application/json" \
  -d "$JSON_BODY")

cat /tmp/appstore_response.txt

if [[ "$response" == "201" || "$response" == "200" ]]; then
  echo "App Store release registered successfully!"
  exit 0
else
  echo "App Store API error, status $response"
  exit 1
fi
