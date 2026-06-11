import requests
import logging

class EmailService:
    PHP_ENDPOINT = "http://localhost/php/letterbox/"
    @staticmethod
    def send(job_data):
        job_id = job_data.get('job_id')
        try:
            response = requests.get(f"{EmailService.PHP_ENDPOINT}{job_id}", timeout=10)
            return {"status": "success"} if response.status_code == 200 else {"status": "failed"}
        except Exception as e:
            return {"status": "error", "message": str(e)}
