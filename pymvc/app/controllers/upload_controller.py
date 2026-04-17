from app.models.upload_model import UploadModel

class UploadController:
    @staticmethod
    def handle_ingestion():
        model = UploadModel()
        result = model.execute()
        if result:
            return "200 - OK: File received and staged for NP."
        return "500 - Error: Upload operation failed."
