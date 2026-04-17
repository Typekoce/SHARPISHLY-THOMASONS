from app.models.cleanup_model import CleanupModel

class CleanupController:
    @staticmethod
    def purge_artifacts():
        model = CleanupModel()
        result = model.execute()
        if result:
            return "200 - OK: Temporary storage cleared."
        return "500 - Error: Cleanup operation failed."
