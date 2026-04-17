from app.models.job_model import JobModel

class JobController:
    @staticmethod
    def get_status():
        model = JobModel()
        result = model.execute()
        if result:
            return "200 - OK: Job status retrieved."
        return "500 - Error: Job operation failed."
