from app.views import render_template
from app.models.agent_model import AgentModel

class AgentController:
    @staticmethod
    def talk(role, message):
        agent = AgentModel()
        return render_template("index.html", agent.get_persona(role, message))

    @staticmethod
    def index():
        return AgentController.talk("Agent", "Welcome to the system.")

    @staticmethod
    def ceo():
        return AgentController.talk("CEO", "Strategic oversight engaged.")

    @staticmethod
    def hr():
        return AgentController.talk("HR", "Resource management active.")

    @staticmethod
    def receptionist():
        # Adding this fixes your 404!
        return AgentController.talk("Receptionist", "NATS JetStream is online.")