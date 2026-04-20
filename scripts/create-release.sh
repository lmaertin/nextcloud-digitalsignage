#!/bin/bash
# Create a release archive for Nextcloud App Store

VERSION=$(grep '<version>' appinfo/info.xml | sed 's/.*<version>\(.*\)<\/version>/\1/')

echo "Creating release archive for version $VERSION..."

# Create archive excluding development files (respects .gitattributes)
git archive --format=tar.gz --prefix=digitalsignage/ -o "digitalsignage-$VERSION.tar.gz" HEAD

echo "✓ Created: digitalsignage-$VERSION.tar.gz"
echo ""
echo "To verify excluded files:"
echo "  tar -tzf digitalsignage-$VERSION.tar.gz | grep -E '(tests|scripts|phpunit)'"
