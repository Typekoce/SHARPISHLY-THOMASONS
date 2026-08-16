from urllib.parse import urlparse
from controllers.home_controller import HomeController
from controllers.about_controller import AboutController

class Router:
    def __init__(self):
        self.routes = {
            "/": (HomeController, "index"),
            "/about": (AboutController, "index")
        }

    def dispatch(self, url):
        path = urlparse(url).path
        if path in self.routes:
            cls, action = self.routes[path]
            return getattr(cls(), action)()
        return "404 Not Found"

class App:
    def run(self, request_url="/"):
        router = Router()
        return router.dispatch(request_url)

if __name__ == "__main__":
    app = App()
    print(app.run("/"))       # Output: Home Page Content
    print(app.run("/about"))  # Output: About Us Content