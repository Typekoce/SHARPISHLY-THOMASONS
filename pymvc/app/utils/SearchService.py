import json
import urllib.request
from app.utils.Config import Config

class SearchService:
    @staticmethod
    def find_context(job_id):
        """
        Retrieves chunks from the PHP API instead of direct DB access.
        Standardizes on response.status for 200 OK check.
        """
        url = Config.api_url(f"vector/show/{job_id}")
        
        try:
            # Use urllib to maintain zero-dependency profile on seaview
            with urllib.request.urlopen(url) as response:
                if response.status != 200:
                    return ""
                
                raw = response.read().decode("utf-8")
                data = json.loads(raw)
                
                # Defensive parsing for PHP error shapes or non-list returns
                if isinstance(data, dict) and "error" in data:
                    return ""
                
                if not isinstance(data, list) or not data:
                    return ""

                # MOCK SEARCH: Grabbing first 3 until semantic query is wired in
                top_chunks = data[:3]
                
                # Join content safely using get() to avoid KeyErrors
                return "\n---\n".join(
                    chunk.get("content", "") 
                    for chunk in top_chunks 
                    if chunk.get("content")
                )

        except Exception as e:
            # TODO: Transition to AppLogger once confirmed
            print(f"SearchService Error: {e}")
            return ""
