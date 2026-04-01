import requests
import mysql.connector

def get_diagnosis():
    # 1. Fetch the last 5 ERROR logs from your logs table
    db = mysql.connector.connect(host="db", user="root", password="password", database="sharpishly")
    cursor = db.cursor(dictionary=True)
    cursor.execute("SELECT message, context, created_at FROM logs WHERE level = 'ERROR' ORDER BY created_at DESC LIMIT 5")
    errors = cursor.fetchall()

    if not errors:
        return "✅ System Pulse: Healthy. No errors detected."

    # 2. Format logs for the AI
    log_dump = "\n".join([f"[{e['created_at']}] {e['message']} | Context: {e['context']}" for e in errors])

    # 3. Ask Ollama for a Prescription
    prompt = f"Analyze these logs and provide a Prescription:\n{log_dump}"
    
    response = requests.post("http://ollama:11434/api/generate", json={
        "model": "llama3.1",
        "system": "You are the System Architect. Provide a Root Cause and a CURE (bash command).",
        "prompt": prompt,
        "stream": False
    })

    return response.json()['response']

# Output the 'Prescription' to your Health Dashboard
print(get_diagnosis())
