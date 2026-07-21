# ===================== PYTHON LIBRARIES =====================
echo -e "\n=== Installing Python Dependencies ==="
[ ! -d "$VENV" ] && python3 -m venv "$VENV"
"$VENV/bin/pip" install --upgrade pip --quiet
"$VENV/bin/pip" install requests chromadb ollama watchdog --quiet