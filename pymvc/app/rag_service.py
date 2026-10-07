import json
import logging
import requests
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from app.utils.VectorStorageService import VectorStorageService

logger = logging.getLogger(__name__)
logging.basicConfig(level=logging.INFO)

def generate_answer(prompt: str) -> str:
    # (connect_timeout, read_timeout)
    response = requests.post(
        "http://127.0.0.1:11434/api/generate",
        json={
            "model": "tinydolphin",
            "prompt": prompt,
            "stream": False
        },
        timeout=(2, 30)
    )

    response.raise_for_status()

    payload = response.json()
    answer = payload.get("response")

    if not isinstance(answer, str) or not answer.strip():
        raise RuntimeError("Ollama returned no usable response")

    return answer.strip()

class RAGHandler(BaseHTTPRequestHandler):
    def send_json(self, status_code: int, payload: dict) -> None:
        body = json.dumps(
            payload,
            ensure_ascii=False
        ).encode("utf-8")

        try:
            self.send_response(status_code)
            self.send_header(
                "Content-Type",
                "application/json; charset=utf-8"
            )
            self.send_header("Content-Length", str(len(body)))
            self.send_header("Connection", "close")
            self.end_headers()
            self.wfile.write(body)
            self.wfile.flush()
        except (BrokenPipeError, ConnectionResetError):
            logger.info("RAG client disconnected before response was delivered")

    def do_GET(self):
        parsed = urlparse(self.path)

        if parsed.path in ("/health", "/healthz/live"):
            self.send_json(200, {
                "status": "online",
                "service": "rag",
                "mode": "liveness"
            })
            return

        if parsed.path == "/healthz/ready":
            self.send_json(200, {
                "status": "ready",
                "service": "rag"
            })
            return

        if parsed.path != "/rag/ask":
            self.send_json(404, {"error": "not_found"})
            return

        query = parse_qs(parsed.query).get("query", [""])[0].strip()

        if not query:
            self.send_json(400, {"error": "query_required"})
            return

        try:
            context_docs = VectorStorageService.query_relevant_context(query)
            context = "\n".join(context_docs) if context_docs else "No context found."

            prompt = (
                f"Context:\n{context}\n\n"
                f"Question:\n{query}\n\n"
                "Answer only from the supplied context."
            )

            answer = generate_answer(prompt)

            self.send_json(200, {
                "answer": answer,
                "filters": {
                    "target_focus": "web_design"
                },
                "mocked": False
            })
        except requests.Timeout:
            logger.warning("Ollama request timed out")
            self.send_json(504, {
                "error": "generation_timeout"
            })
        except requests.RequestException:
            logger.exception("Ollama request failed")
            self.send_json(502, {
                "error": "generation_unavailable"
            })
        except Exception:
            logger.exception("RAG request failed")
            self.send_json(500, {
                "error": "rag_generation_failed"
            })

if __name__ == "__main__":
    server = HTTPServer(("127.0.0.1", 8765), RAGHandler)
    print("🚀 RAG service running securely on http://127.0.0.1:8765")

    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
    finally:
        server.server_close()