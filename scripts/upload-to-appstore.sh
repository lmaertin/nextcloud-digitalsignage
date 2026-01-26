#!/bin/bash
# scripts/upload-to-appstore.sh
# Uploads a release to the Nextcloud App Store via API
# Usage: ./scripts/upload-to-appstore.sh <version>

set -euo pipefail


# Argumente parsen
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

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 <version> [--nightly]"
  exit 1
fi

VERSION="$1"
APP_NAME="digitalsignage"
ARCHIVE="digitalsignage-${VERSION}.tar.gz"
SIGNATURE="digitalsignage-${VERSION}.tar.gz.sig"
API_URL="https://apps.nextcloud.com/api/v1/appstore/apps/${APP_NAME}/releases"

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


echo "Uploading $ARCHIVE to Nextcloud App Store..."

if [[ "$NIGHTLY" == "true" ]]; then
  echo "Nightly-Flag ist gesetzt. Upload als Nightly-Release."
  response=$(curl -s -w "%{http_code}" -o /tmp/appstore_response.txt -X POST "$API_URL" \
    -H "Authorization: Token $NC_APPSTORE_TOKEN" \
    -F "archive=@$ARCHIVE" \
    -F "signature=@$SIGNATURE" \
    -F "version=$VERSION" \
    -F "nightly=true")
else
  response=$(curl -s -w "%{http_code}" -o /tmp/appstore_response.txt -X POST "$API_URL" \
    -H "Authorization: Token $NC_APPSTORE_TOKEN" \
    -F "archive=@$ARCHIVE" \
    -F "signature=@$SIGNATURE" \
    -F "version=$VERSION")
fi

cat /tmp/appstore_response.txt

if [[ "$response" == "201" ]]; then
  echo "Upload successful!"
  exit 0
else
  echo "Upload failed with status $response"
  exit 1
fi
