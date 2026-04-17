class AgentModel:
    # Single source of truth for agent personas
    ROLES = {
        "ceo": {"title": "Chief Executive", "msg": "Strategic oversight & system integrity."},
        "receptionist": {"title": "Front Desk", "msg": "Inbound ticket & NATS orchestration."},
        "human_resources": {"title": "HR Manager", "msg": "User permissions & access logs."},
        "agent": {"title": "Field Operative", "msg": "Executing Neural Processing tasks."}
    }

    def get_persona(self, role, message):
        return {
            "title": f"PyMVC {role}",
            "message": message,
            "status": "Running successfully"
        }