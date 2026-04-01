# ================================================
# Sharpishly AI Service - Dockerfile
# Python 3.11 slim + OpenCV + MySQL + Redis + Ollama client
# ================================================

FROM python:3.11-slim

# Prevent .pyc files and enable unbuffered logging (important for Docker logs)
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PYTHONPATH=/app

# Set working directory
WORKDIR /app

# --- System Dependencies ---
# libgl1 + libglib2.0-0 are required for OpenCV (headless)
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    default-libmysqlclient-dev \
    pkg-config \
    curl \
    libgl1 \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Upgrade pip and install Python dependencies (better caching)
COPY requirements.txt .
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Create non-root user for better security
RUN useradd -m -u 1000 appuser && \
    chown -R appuser:appuser /app

# Copy application code
COPY --chown=appuser:appuser . .

# Switch to non-root user
USER appuser

# Make sure entrypoint is executable
RUN chmod +x entrypoint.sh 2>/dev/null || true

# Default command is overridden by docker-compose.yml
# This acts as a safety fallback
CMD ["python", "-m", "worker_entry"]