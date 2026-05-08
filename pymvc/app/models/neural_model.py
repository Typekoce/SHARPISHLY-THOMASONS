from app.models.base_model import BaseModel

class NeuralModel(BaseModel):
    def __init__(self):
        super().__init__()
        self.table = "jobs" # Aligning with your existing jobs schema

    def get_job_data(self, job_id):
        """Fetch job details for processing"""
        return self.find(job_id)

    def update_job_status(self, job_id, status):
        """Update job status during the NP surgery"""
        return self.update(job_id, {"status": status})

    def save_vector_reference(self, job_id, collection_name, vector_count):
        """Store the link to the Vector DB without storing raw vectors"""
        data = {
            "status": "vectorized",
            "vector_collection": collection_name,
            "vector_count": vector_count
        }
        return self.update(job_id, data)
