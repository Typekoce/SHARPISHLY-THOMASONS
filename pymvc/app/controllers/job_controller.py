from app.controllers.base_controller import BaseController
from app.models.job_model import JobModel

class JobController(BaseController):
    @staticmethod
    def get_status():
        model = JobModel()
        return BaseController.json_response(
            model.execute(), 
            "Job status retrieved.", 
            "Job operation failed."
        )