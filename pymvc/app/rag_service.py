import json
import requests
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs
from app.utils.VectorStorageService import VectorStorageService

# Configuration
OLLAMA_URL = "http://localhost:11434/api/generate"
LLM_MODEL = "llama3"

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
            
            # 2. Process and Deduplicate Context
            all_lines = []
            for doc in context_docs:
                for line in doc.split('\n'):
                    line = line.strip()
                    if line and line.lower() != 'name,email,tel':
                        all_lines.append(line)
            
            unique_lines = list(dict.fromkeys(all_lines))
            if unique_lines:
                unique_lines.insert(0, "Name, Email, Tel")
            
            context = "\n".join(unique_lines)
            
            # 3. Build prompt and call Ollama
            if not context:
                answer = "No relevant information found in the knowledge base."
            else:
                prompt = (
                    "You are an internal assistant for SHARPISHLY-THOMASONS.\n"
                    "Answer the user's question using ONLY the context below.\n"
                    "If the context does not contain the answer, say 'Context data insufficient.'\n\n"
                    f"CONTEXT:\n{context}\n\n"
                    f"QUESTION: {query}\n\n"
                    "ANSWER:"
                )
                try:
                    response = requests.post(
                        OLLAMA_URL,
                        json={
                            "model": LLM_MODEL,
                            "prompt": prompt,
                            "stream": False
                        },
                        timeout=15
                    )
                    response.raise_for_status()
                    result = response.json()
                    answer = result.get("response", "No answer generated.")
                except Exception as e:
                    answer = f"⚠️ Generation error: {str(e)}"

            # 4. Return answer
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({
                'status': 'success', 
                'answer': answer,
                'context': context
            }).encode())
        else:
            self.send_response(404)
            self.end_headers()

    def log_message(self, format, *args):
        pass # Silence logs

if __name__ == '__main__':
    server = HTTPServer(('localhost', 8765), RAGHandler)
    print('🚀 RAG Microservice with LLM running on http://localhost:8765')
    server.serve_forever()
