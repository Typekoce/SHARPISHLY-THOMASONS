import asyncio
import json
import os
import sys
from nats.aio.client import Client as NATS

async def run_worker():
    nc = NATS()
    try:
        # Grounded to local NATS binary
        await nc.connect("nats://127.0.0.1:4222")
        print("✅ Connected to NATS at 127.0.0.1:4222")
    except Exception as e:
        print(f"❌ Connection failed: {e}")
        return

    # HEARTBEAT RESPONDER (Request/Reply)
    async def heartbeat_handler(msg):
        subject = msg.subject
        reply = msg.reply
        print(f"💓 Heartbeat request received on {subject}")
        data = json.dumps({
            "status": "online",
            "engine": "PyMVC",
            "pid": os.getpid(),
            "load": os.getloadavg()[0]
        }).encode()
        await nc.publish(reply, data)

    # JOB CONSUMER (Subscriber)
    async def job_handler(msg):
        try:
            data = json.loads(msg.data.decode())
            job_id = data.get('id')
            # Consistent with Location.php pathing
            file_path = f"/home/vboxuser/Documents/SHARPISHLY-THOMASONS/storage/nats/{job_id:03d}_job.json"
            
            if os.path.exists(file_path):
                print(f"🚀 Processing Job #{job_id}...")
                # Simulate processing
                await asyncio.sleep(1) 
                await nc.publish("job.completed", json.dumps({"id": job_id, "status": "success"}).encode())
            else:
                print(f"⚠️ Job file missing: {file_path}")
        except Exception as e:
            print(f"❌ Job error: {e}")

    await nc.subscribe("heartbeat.python", cb=heartbeat_handler)
    await nc.subscribe("job.new", cb=job_handler)

    print("⚡ PyMVC Worker is listening for heartbeats and jobs...")
    while True:
        await asyncio.sleep(1)

if __name__ == '__main__':
    try:
        asyncio.run(run_worker())
    except KeyboardInterrupt:
        print("\n🛑 Worker stopped by user.")
