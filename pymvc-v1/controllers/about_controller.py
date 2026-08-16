from models.about_model import AboutModel

class AboutController:
    def index(self):
        return AboutModel().get()