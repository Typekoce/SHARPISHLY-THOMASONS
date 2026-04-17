from app.models.log_model import LogModel

class LogController:
    @staticmethod
    def record_event():
        model = LogModel()
        result = model.execute()
        if result:
            return "200 - OK: System event logged."
        return "500 - Error: Log operation failed."
