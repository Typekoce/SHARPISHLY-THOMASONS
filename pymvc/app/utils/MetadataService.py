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