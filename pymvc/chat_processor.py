import time
import os
import json
from app.services.SearchService import SearchService
from app.services.RagService import RagService

def process_chat_queue():
    path = "storage/vectors/"
    
    while True:
        # Look for request files: chat_123_req.json
        files = [f for f in os.listdir(path) if f.endswith('_req.json')]
        
        for f in files:
            print(f"[*] Processing Question: {f}")
            with open(path + f, 'r') as req_file:
                req_data = json.load(req_file)

            # 1. SEARCH: Get context (Mocked)
            context = SearchService.find_context(1, [0.1, 0.2]) # Hardcoded job_id 1 for now

            # 2. RAG: Build the Prompt
            package = RagService.build_prompt(req_data['question'], context)

            # 3. GENERATE: Mock answer
            answer = RagService.mock_generate_response(package)

            # 4. EXPORT: Write the response file
            res_filename = f.replace('_req.json', '_res.json')
            with open(path + res_filename, 'w') as res_file:
                json.dump({
                    "chat_id": req_data['id'],
                    "answer": answer,
                    "context_used": True
                }, res_file)

            # Clean up the request file
            os.remove(path + f)
            print(f"[✅] Answered: {res_filename}")

        time.sleep(1) # Don't melt the 1GB CPU

if __name__ == "__main__":
    process_chat_queue()