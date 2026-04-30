import requests
import csv
import io

from app.utils.Config import Config

class MetadataService:
    @staticmethod
    def get_and_sniff(job_id):
        """Fetches payload from PHP and identifies structure."""
        url = Config.api_url(f"job/payload/{job_id}")
        
        try:
            response = requests.get(url, timeout=5)
            if response.status_code != 200:
                return {"error": f"PHP API returned {response.status_code}"}

            raw_data = response.content
            # Convert bytes to string for text-based analysis
            sample_text = raw_data.decode('utf-8', errors='ignore')

            # 1. Check if it's a CSV
            if ',' in sample_text and '\n' in sample_text:
                dialect = csv.Sniffer().sniff(sample_text[:1024])
                has_header = csv.Sniffer().has_header(sample_text[:1024])
                return {
                    "type": "csv",
                    "length": len(raw_data),
                    "has_header": has_header,
                    "sample": sample_text[:100].strip()
                }

            # 2. Default to Plain Text
            return {
                "type": "text",
                "length": len(raw_data),
                "sample": sample_text[:100].strip()
            }

        except Exception as e:
            return {"error": str(e)}
        
    @staticmethod
    def export_to_disk(job_id, chunks):
        """
        Task 1.1: Export vector to storage/vectors/job_{id}.json
        """
        # Points to the shared storage area
        folder = "storage/vectors"
        filename = f"job_{job_id}.json"
        full_path = os.path.join(folder, filename)

        # Ensure the directory exists (as a safety measure)
        os.makedirs(folder, exist_ok=True)

        payload = {
            "job_id": job_id,
            "data": chunks  # Contains content, meta, and (mock) embedding
        }

        with open(full_path, 'w', encoding='utf-8') as f:
            json.dump(payload, f, indent=4)
        
        print(f"Exported vectors to: {full_path}")