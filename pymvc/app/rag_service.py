import json
import requests
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from app.utils.VectorStorageService import VectorStorageService

# Configuration
OLLAMA_URL = "http://localhost:11434/api/generate"
# Switched to tinydolphin for faster response times on limited hardware
LLM_MODEL = "tinydolphin" 

class RAGHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        try:
            parsed = urlparse(self.path)
            if parsed.path == '/rag/ask':
                qs = parse_qs(parsed.query)
                query = qs.get('query', [''])[0]
                
                if not query:
                    self.send_response(400)
                    self.end_headers()
                    self.wfile.write(json.dumps({'error': 'Missing query'}).encode())
                    return

                # 1. Retrieve context
                context_docs = VectorStorageService.query_relevant_context(query, n_results=3)
                
                # 2. Process, Deduplicate, and Clean Context
                unique_lines = []
                seen = set()
                for doc in context_docs:
                    for line in doc.split('\n'):
                        line_clean = line.strip()
                        if not line_clean or line_clean.lower().replace(' ', '') == 'name,email,tel':
                            continue
                        if line_clean not in seen:
                            unique_lines.append(line_clean)
                            seen.add(line_clean)
                
                if unique_lines:
                    unique_lines.insert(0, "Name, Email, Tel")
                context = "\n".join(unique_lines)
                
                # 3. Build prompt and call Ollama
                if not context or context == "Name, Email, Tel":
                    answer = "Context data insufficient."
                else:
                    prompt = (
                        "You are an internal assistant for SHARPISHLY-THOMASONS.\n"
                        "Answer the user's question using ONLY the context below.\n"
                        f"CONTEXT:\n{context}\n\nQUESTION: {query}\n\nANSWER:"
                    )
                    try:
                        # Increased timeout to 120s to allow for cold-start model loading
                        response = requests.post(
                            OLLAMA_URL, 
                            json={"model": LLM_MODEL, "prompt": prompt, "stream": False},
                            timeout=120
                        )
                        response.raise_for_status()
                        answer = response.json().get("response", "No answer generated.")
                    except requests.exceptions.Timeout:
                        answer = "⚠️ Generation error: The request timed out. The model may be too slow for current settings."
                    except Exception as e:
                        answer = f"⚠️ Generation error: {str(e)}"

                # 4. Return answer
                self.send_response(200)
                self.send_header('Content-Type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps({'status': 'success', 'answer': answer, 'context': context}).encode())
            else:
                self.send_response(404)
                self.end_headers()
        except BrokenPipeError:
            pass
        except Exception as e:
            print(f"RAG server error: {e}")

    def log_message(self, format, *args):
        pass

if __name__ == '__main__':
    server = HTTPServer(('localhost', 8765), RAGHandler)
    print(f'🚀 RAG Microservice running with {LLM_MODEL} on http://localhost:8765')
    server.serve_forever()
