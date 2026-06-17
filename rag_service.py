import json
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from app.utils.VectorStorageService import VectorStorageService

class RAGHandler(BaseHTTPRequestHandler):
    
    # Shared logic to avoid duplicating RAG processing code
    def process_request(self, query):
        if not query:
            return None
        context_docs = VectorStorageService.query_relevant_context(query, n_results=3)
        return json.dumps({
            'status': 'success', 
            'answer': "\n".join(context_docs)
        }).encode()

    def do_GET(self):
        parsed = urlparse(self.path)
        if parsed.path == '/rag/ask':
            qs = parse_qs(parsed.query)
            query = qs.get('query', [''])[0]
            self.handle_response(self.process_request(query))
        else:
            self.send_error(404)

    def do_POST(self):
        if self.path == '/rag/ask':
            content_length = int(self.headers.get('Content-Length', 0))
            body = self.rfile.read(content_length)
            try:
                data = json.loads(body)
                query = data.get('query', '')
                self.handle_response(self.process_request(query))
            except json.JSONDecodeError:
                self.send_error(400, "Invalid JSON")
        else:
            self.send_error(404)

    def handle_response(self, response_data):
        if response_data:
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(response_data)
        else:
            self.send_error(400, "Missing or invalid query")

    def log_message(self, format, *args):
        pass # Silence logs to keep console clean

if __name__ == '__main__':
    server = HTTPServer(('localhost', 8765), RAGHandler)
    print('🚀 RAG Microservice running on port 8765...')
    server.serve_forever()