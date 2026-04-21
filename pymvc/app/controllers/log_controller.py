from app.controllers.base_controller import BaseController
from app.models.log_model import LogModel

class LogController(BaseController):
    @staticmethod
    def record_event():
        model = LogModel()
        return BaseController.json_response(
            model.execute(), 
            "System event logged.", 
            "Log operation failed."
        )