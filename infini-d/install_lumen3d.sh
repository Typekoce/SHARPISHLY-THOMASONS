#!/bin/sh
# Installs Lumen3D for the current user (no root needed).
set -e
DIR="$HOME/.local/share/lumen3d"; BIN="$HOME/.local/bin"; APPS="$HOME/.local/share/applications"
command -v python3 >/dev/null || { echo "python3 is required"; exit 1; }
python3 -c "import tkinter" 2>/dev/null || { echo "Tkinter missing. Debian/Ubuntu: sudo apt install python3-tk | Fedora: sudo dnf install python3-tkinter | Arch: sudo pacman -S tk"; exit 1; }
mkdir -p "$DIR" "$BIN" "$APPS"
cp "$(dirname "$0")/lumen3d.py" "$DIR/"; chmod +x "$DIR/lumen3d.py"
ln -sf "$DIR/lumen3d.py" "$BIN/lumen3d"
cat > "$APPS/lumen3d.desktop" << D
[Desktop Entry]
Type=Application
Name=Lumen3D
Comment=3D scene editor with keyframe animation
Exec=$BIN/lumen3d
Terminal=false
Categories=Graphics;3DGraphics;
D
echo "Installed. Run 'lumen3d' or find it in your applications menu."
