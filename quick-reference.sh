#!/bin/bash
# Digital Signage - Quick Start Guide
# 
# Schnelle Referenz für häufig gebrauchte Befehle

echo "🚀 Digital Signage - Quick Reference"
echo "===================================="
echo ""

# Installation
echo "📦 INSTALLATION"
echo "  cp -r digitalsignage /path/to/nextcloud/apps/"
echo "  occ app:enable digitalsignage"
echo ""

# Sprachen-Management
echo "🌍 LANGUAGE MANAGEMENT"
echo "  php l10n-helper.php list      # Verfügbare Sprachen zeigen"
echo "  php l10n-helper.php validate  # Übersetzungen überprüfen"
echo "  php l10n-helper.php add fr    # Französisch hinzufügen"
echo ""

# Testing
echo "🧪 TESTING"
echo "  Display: https://nextcloud.local/apps/digitalsignage/"
echo "  Public:  https://nextcloud.local/apps/digitalsignage/public?token=ABC123"
echo ""

# Publishing
echo "📤 PUBLISHING"
echo "  1. git tag v1.0.0"
echo "  2. git push origin v1.0.0"
echo "  3. https://apps.nextcloud.com - Submit app"
echo ""

# Documentation
echo "📚 DOCUMENTATION"
echo "  COMPLETION_SUMMARY.md         - Übersicht (START HERE!)"
echo "  MULTILINGUAL_IMPLEMENTATION.md - Technische Details"
echo "  PUBLICATION_GUIDE.md          - App Store Anleitung"
echo "  DEVELOPER_GUIDE.md            - Entwickler Reference"
echo ""

echo "✅ Alle Dateien sind bereit!"
