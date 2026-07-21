# ===================== OLLAMA SETUP =====================
echo -e "\n=== Setting up Ollama ==="
if ! command -v ollama &>/dev/null; then
  curl -fsSL https://ollama.com/install.sh | sh
fi

if ! pgrep -x ollama >/dev/null; then
  ollama serve >/tmp/ollama.log 2>&1 &
  sleep 5
fi

pull_if_missing() {
  ollama list | grep -Fq "$1" || ollama pull "$1"
}

pull_if_missing "llama3"
pull_if_missing "jina/jina-embeddings-v2-small-en"