# Use Python 3.11 slim for a small, fast image
FROM python:3.11-slim

# Set working directory inside the container
WORKDIR /app

# Install system dependencies for mysql-connector and internal tools
RUN apt-get update && apt-get install -p -y \
    build-essential \
    default-libmysqlclient-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements first to leverage Docker cache
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy the rest of the AI source code
COPY . .

# Ensure the entrypoint is executable
RUN chmod +x entrypoint.sh

# The command is handled by docker-compose.yml, 
# but we'll set a default just in case.
CMD ["./entrypoint.sh"]