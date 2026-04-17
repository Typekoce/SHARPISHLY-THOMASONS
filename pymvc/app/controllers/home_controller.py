from app.views import render_template

class HomeController:
    @staticmethod
    def index():
        data = {
            "title": "PyMVC Home",
            "message": "Welcome to your Python MVC project!",
            "status": "Running successfully"
        }
        return render_template("index.html", data)
