from app.models.health_model import HealthModel

class HealthController:
    @staticmethod
    def check_system():
        model = HealthModel()
        result = model.execute()
        if result:
            return "200 - OK: All systems green. Models operational."
        return "500 - Error: Health operation failed."
