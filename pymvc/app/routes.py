import json

from app.controllers.home_controller import HomeController
from app.controllers.user_controller import UserController
from app.controllers.worker_controller import WorkerController
from app.controllers.agent_controller import AgentController
from app.controllers.nats_controller import NatsController
from app.controllers.devices_controller import DevicesController


routes = {
    "/": HomeController.index,
    "/home": HomeController.index,
    "/agent": AgentController.index,
    "/agent/ceo": AgentController.ceo,
    "/agent/hr": AgentController.hr,
    "/users": UserController.index,
    "/users/create": UserController.create,
    "/worker/process":WorkerController.process_task,
    "/nats":NatsController.index,
    "/nats/consume":NatsController.consume,
    "/devices":DevicesController.index,
    '/devices/usb': lambda: json.dumps(DevicesController.get_usb_devices(), indent=2),
}

def route_request(path: str):
    path = path.split('?')[0]        # remove query parameters
    if path in routes:
        try:
            return routes[path]()
        except Exception as e:
            return f"500 - Error: {e}"
    return f"404 - Page not found: {path}"
