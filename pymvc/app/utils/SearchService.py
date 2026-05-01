import json
import urllib.request
from app.utils.Config import Config

class SearchService:
    @staticmethod
    def find_context(job_id):
        """
        Retrieves chunks from the PHP API instead of direct DB access.
        """
        url = Config.api_url(f"vector/show/{job_id}")
        
        try:
            # Use standard urllib to keep dependencies zero/low
            with urllib.request.urlopen(url) as response:
                if response.getcode() != 200:
                    return ""
                
                data = json.loads(response.read().decode())
                
                # If PHP returns an error or empty list
                if 'error' in data or not isinstance(data, list):
                    return ""

                # MOCK SEARCH: Take the first 3 chunks found
                # In the future, we will sort these by Cosine Similarity here
                top_chunks = data[:3]
                
                context_block = "\n---\n".join([chunk['content'] for chunk in top_chunks])
                return context_block

        except Exception as e:
            # Log error locally or return empty to fail gracefully
            print(f"SearchService Error: {e}")
            return ""