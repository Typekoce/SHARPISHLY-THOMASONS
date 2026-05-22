import json
import requests
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from app.utils.VectorStorageService import VectorStorageService

class RAGHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path == '/rag/ask':
            query = parse_qs(parsed.query).get('query', [''])[0]
            
            # Retrieve context from VectorStorageService
            context_docs = VectorStorageService.query_relevant_context(query)
            context = "\n".join(context_docs) if context_docs else "No context found."
            
            # Minimal prompt to avoid overhead
            prompt = f"Context: {context}\nQuestion: {query}\nAnswer only from context:"
            
            try:
                # Call Ollama directly with a 60s timeout
                response = requests.post(
                    "http://localhost:11434/api/generate",
                    json={"model": "tinydolphin", "prompt": prompt, "stream": False},
                    timeout=5000
                )
                answer = response.json().get("response", "No answer generated.")
            except Exception as e:
                answer = f"Error: {str(e)}"
            
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({'answer': answer}).encode())

if __name__ == '__main__':
    server = HTTPServer(('localhost', 8765), RAGHandler)
    print('🚀 RAG Service (Simplified) running on http://localhost:8765')
    server.serve_forever()
