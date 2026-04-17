from app.views import render_template

class AgentController:
    @staticmethod
    def index():
        data = {
            "title": "PyMVC Agent",
            "message": "Welcome to your Python MVC project!",
            "status": "Running successfully"
        }
        return render_template("index.html", data)
