import os
import json
from app.models.worker_model import WorkerModel

class WorkerController:
    @staticmethod
    def process_task():
        """Manual CLI diagnostic entry point."""
        worker = WorkerModel()
        success = worker.execute_np_logic()
        if success:
            return "--- NP SUCCESS ---\nStatus: 200\nMessage: Neural Processing complete."
        return "--- NP FAILURE ---\nStatus: 500"

    @staticmethod
    def consume(job_payload):
        """
        The NATS JetStream Consumer.
        job_payload: Dictionary passed from the NATS listener.
        """
        try:
            # The payload now comes from the NATS message, not a file
            worker = WorkerModel()
            success = worker.execute_np_logic(job_payload)

            if success:
                # NATS handles the 'Acknowledge' via the listener script
                return f"200 - Job {job_payload.get('job_id')} Processed Successfully"
            
            return f"500 - Job {job_payload.get('job_id')} failed logic."

        except Exception as e:
            return f"500 - Consumer Error: {str(e)}"