from models.home_model import HomeModel

class HomeController:
    def index(self):
        return HomeModel().get()