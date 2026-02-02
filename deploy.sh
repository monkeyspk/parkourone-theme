#!/bin/bash
# ParkourONE Theme - Deploy Script
# Erstellt ein ZIP für FileZilla Upload

THEME_DIR="$(cd "$(dirname "$0")" && pwd)"
THEME_NAME="parkourone-theme"
OUTPUT_DIR="$HOME/Desktop"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
ZIP_NAME="${THEME_NAME}_${TIMESTAMP}.zip"

echo "📦 Erstelle Theme-ZIP..."

cd "$THEME_DIR/.."
zip -r "$OUTPUT_DIR/$ZIP_NAME" "$THEME_NAME" \
    -x "*.git/*" \
    -x "*node_modules*" \
    -x "*.DS_Store" \
    -x "*deploy.sh"

echo ""
echo "✅ Fertig! ZIP erstellt:"
echo "   $OUTPUT_DIR/$ZIP_NAME"
echo ""
echo "📤 Upload via FileZilla:"
echo "   1. ZIP auf Desktop finden"
echo "   2. Entpacken"  
echo "   3. Ordner nach /wp-content/themes/ hochladen"
