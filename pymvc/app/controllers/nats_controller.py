import os
import json
import requests

class NatsController:

    @staticmethod
    def _ensure_dirs(base_dir):
        """Guarantees directory layouts exist securely."""
        for folder in ['ingest', 'process', 'fail']:
            os.makedirs(os.path.join(base_dir, folder), exist_ok=True)

    @staticmethod
    def consume():
        """
        Scans the NATS ingest directory for pending job handshakes.
        Moves valid items to 'process' atomically to prevent race conditions.
        """
        base_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '../../../storage/uploads/nats'))
        NatsController._ensure_dirs(base_dir)
        
        ingest_dir = os.path.join(base_dir, 'ingest')
        process_dir = os.path.join(base_dir, 'process')

        for filename in os.listdir(ingest_dir):
            if filename.endswith('.json') and not filename.endswith('.tmp'):
                ingest_path = os.path.join(ingest_dir, filename)
                process_path = os.path.join(process_dir, filename)
                
                try:
                    # Atomic directory shift to claim the job ownership
                    os.rename(ingest_path, process_path)
                    
                    with open(process_path, 'r') as f:
                        job_wrapper = json.load(f)
                    
                    job_data = job_wrapper.get('data', {})
                    job_data['job_id'] = job_wrapper.get('job_id')
                    
                    return job_data, process_path
                except Exception as e:
                    print(f"⚠️ Failed to safely consume handshake message packet: {e}")
                    continue
        return None

    @staticmethod
    def update_php(job_id: int, status: str, chunks: list = None):
        """
        Pushes state tracking payloads back up to the main MVC framework via PUT.
        """
        url = f"http://sharpishly.dev/php/job/update/{job_id}"
        payload = {
            "status": status,
            "chunks": chunks or []
        }
        try:
            headers = {"Content-Type": "application/json"}
            requests.put(url, json=payload, headers=headers, timeout=5)
        except Exception as e:
            print(f"⚠️ Communication breakdown updating PHP state for job {job_id}: {e}")

    @staticmethod
    def acknowledge(file_path: str):
        """
        Cleans up the file system transaction safely and remains noisy on failure.
        """
        try:
            if os.path.exists(file_path):
                os.remove(file_path)
                print(f"🧹 Clean Closeout: Removed transaction file {os.path.basename(file_path)}")
            else:
                print(f"🧹 Acknowledge skipped; file missing: {file_path}")
        except Exception as e:
            print(f"⚠️ Failed to acknowledge handshake file context: {e}")

    @staticmethod
    def fail_job(file_path: str):
        """
        Dead-letter queue pattern. Shifts unprocessable handshakes out of process/.
        """
        try:
            if os.path.exists(file_path):
                fail_dir = os.path.abspath(os.path.join(os.path.dirname(file_path), '../fail'))
                fail_path = os.path.join(fail_dir, os.path.basename(file_path))
                os.rename(file_path, fail_path)
                print(f"❌ Pipeline Failure: Isolated handshake file to dead-letter queue: {fail_path}")
        except Exception as e:
            print(f"⚠️ Emergency state transition failed to move file to fail/: {e}")
