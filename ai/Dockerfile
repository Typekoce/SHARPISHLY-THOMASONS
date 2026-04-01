# Use Python 3.11 slim for a small, fast image
FROM python:3.11-slim

# Set working directory
WORKDIR /app

# --- CRITICAL SYSTEM DEPENDENCIES ---
# 1. build-essential/libmysql: For mysql-connector-python
# 2. curl: For the Handshake with the PHP backend
# 3. libgl1/libglib: REQUIRED for OpenCV (cv2) to function in headless Docker
RUN apt-get update && apt-get install -y \
    build-essential \
    default-libmysqlclient-dev \
    pkg-config \
    curl \
    libgl1-mesa-glx \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements and install
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy source code
COPY . .

# Ensure scripts are executable
RUN chmod +x entrypoint.sh

# The entrypoint/command is orchestrated by docker-compose.yml
CMD ["./entrypoint.sh"]