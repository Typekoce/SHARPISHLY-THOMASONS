#!/bin/sh
# Installs Tabula for the current user on Ubuntu/Debian (no root for the app itself).
set -e
DIR="$HOME/.local/share/tabula"; BIN="$HOME/.local/bin"; APPS="$HOME/.local/share/applications"
python3 -c "import tkinter" 2>/dev/null || { echo "Missing Tkinter. Run: sudo apt install python3-tk"; exit 1; }
python3 -c "import pymysql" 2>/dev/null || echo "Note: PyMySQL not found, so only SQLite will work. For MySQL/MariaDB run: sudo apt install python3-pymysql"
mkdir -p "$DIR" "$BIN" "$APPS"
cp "$(dirname "$0")/tabula.py" "$DIR/"; chmod +x "$DIR/tabula.py"; ln -sf "$DIR/tabula.py" "$BIN/tabula"
cat > "$APPS/tabula.desktop" << D
[Desktop Entry]
Type=Application
Name=Tabula
Comment=SQL client for MySQL/MariaDB and SQLite
Exec=$BIN/tabula
Terminal=false
Categories=Development;Database;
D
echo "Installed. Run 'tabula' or find it in your applications menu."
