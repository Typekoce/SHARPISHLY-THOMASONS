# ================================================
# Sharpishly AI Service - Lean Dockerfile
# Optimized for API-driven logic (No DB/Redis bloat)
# ================================================

FROM python:3.11-slim

# Prevent .pyc files and enable unbuffered logging
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PYTHONPATH=/app

WORKDIR /app

# --- System Dependencies (Stripped to Essentials) ---
# Removed: default-libmysqlclient-dev, pkg-config
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    curl \
    libgl1 \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Upgrade pip and install ONLY core requirements
COPY requirements.txt .
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Create non-root user
RUN useradd -m -u 1000 appuser && \
    chown -R appuser:appuser /app

# Copy application code
COPY --chown=appuser:appuser . .

USER appuser

EXPOSE 8000

# Default command for the PyMVC server
# Override with 'command: python3 worker.py' in docker-compose for the worker
CMD ["uvicorn", "server:app", "--host", "0.0.0.0", "--port", "8000"]