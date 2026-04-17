from app.views import render_template

class UserController:
    @staticmethod
    def index():
        data = {
            "title": "Users",
            "users": ["Alice", "Bob", "Charlie", "Dana"]
        }
        return render_template("users.html", data)

    @staticmethod
    def create():
        return "<h2>User creation form will be here soon...</h2>"
