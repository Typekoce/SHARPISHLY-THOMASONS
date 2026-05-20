import json
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from app.utils.VectorStorageService import VectorStorageService

class RAGHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path == '/rag/ask':
            qs = parse_qs(parsed.query)
            query = qs.get('query', [''])[0]
            
            if not query:
                self.send_response(400)
                self.end_headers()
                self.wfile.write(json.dumps({'error': 'Missing query'}).encode())
                return

            # 1. Retrieve context from ChromaDB
            context_docs = VectorStorageService.query_relevant_context(query, n_results=3)
            context = "\n".join(context_docs)
            
            # 2. Return the context (or you could pipe this into an Ollama generation call here)
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({
                'status': 'success', 
                'answer': context 
            }).encode())
        else:
            self.send_response(404)
            self.end_headers()

    def log_message(self, format, *args):
        pass # Silence logs

if __name__ == '__main__':
    server = HTTPServer(('localhost', 8765), RAGHandler)
    print('🚀 RAG Microservice running on http://localhost:8765')
    server.serve_forever()
