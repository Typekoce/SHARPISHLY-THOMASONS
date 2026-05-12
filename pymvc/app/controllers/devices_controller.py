from app.models.devices_model import Devices


class DevicesController:
    @staticmethod
    def index():
        return "hello world"

    @staticmethod
    def handle_devices():
        model = Devices()
        result = model.execute()
        if result:
            return "200 - OK: Devices operation completed."
        return "500 - Error: Devices operation failed."
