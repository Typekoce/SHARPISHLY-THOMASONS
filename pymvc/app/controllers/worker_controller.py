from app.models.worker_model import WorkerModel
from app.views import render_template

class WorkerController:
    @staticmethod
    def process_task():
        """
        The 'Next Task' entry point.
        Triggered via /worker/process in your CLI loop.
        """
        # We delegate the NP logic to the Model (Fat Model, Skinny Controller)
        worker = WorkerModel()
        success = worker.execute_np_logic()

        if success:
            return "--- NP SUCCESS ---\nStatus: 200\nMessage: Neural Processing complete. Job updated."
        else:
            return "--- NP FAILURE ---\nStatus: 500\nMessage: Model failed to process the upload."
        data = {
            "title": "Users",
            "users": ["Alice", "Bob", "Charlie", "Dana"]
        }
        return render_template("worker.html", data)