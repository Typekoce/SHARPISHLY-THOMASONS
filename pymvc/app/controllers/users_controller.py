from app.models.users_model import Users


class UsersController:
    @staticmethod
    def ():
        model = Users()
        result = model.execute()
        if result:
            return "200 - OK: "
        return "500 - Error: Users operation failed."
