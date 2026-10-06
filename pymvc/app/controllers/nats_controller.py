import json
import os
import re
from pathlib import Path
import requests

class NatsController:
    JOB_FILE_PATTERN = re.compile(r"^[A-Za-z0-9_.-]+\.json$")

    @staticmethod
    def _base_dir() -> Path:
        # nats_controller.py is in pymvc/app/controllers/
        # parents[3] reaches SHARPISHLY-THOMASONS root
        return (
            Path(__file__).resolve()
            .parents[3]
            / "storage"
            / "uploads"
            / "nats"
        )

    @classmethod
    def _ensure_dirs(cls, base_dir: Path) -> None:
        for folder in ("ingest", "process", "fail"):
            (base_dir / folder).mkdir(
                mode=0o750,
                parents=True,
                exist_ok=True
            )

    @classmethod
    def consume(cls):
        """
        Scans the NATS ingest directory securely using scandir and atomic replacement
        to prevent race conditions between workers.
        """
        base_dir = cls._base_dir()
        cls._ensure_dirs(base_dir)

        ingest_dir = base_dir / "ingest"
        process_dir = base_dir / "process"

        if not ingest_dir.exists():
            return None

        for entry in os.scandir(ingest_dir):
            if not entry.is_file():
                continue

            filename = entry.name

            if (
                not cls.JOB_FILE_PATTERN.fullmatch(filename) or
                filename.endswith(".tmp")
            ):
                continue

            source = ingest_dir / filename
            destination = process_dir / filename

            try:
                # Atomic cross-platform replacement/rename
                os.replace(source, destination)

                with destination.open("r", encoding="utf-8") as handle:
                    job_wrapper = json.load(handle)

                if not isinstance(job_wrapper, dict):
                    raise ValueError("Job wrapper must be an object")

                job_data = job_wrapper.get("data", {})

                if not isinstance(job_data, dict):
                    raise ValueError("Job data must be an object")

                job_data = dict(job_data)
                job_data["job_id"] = job_wrapper.get("job_id")

                print(f"📦 Successfully claimed handshake: {filename}")
                return job_data, str(destination)

            except FileNotFoundError:
                # Another worker claimed the file first.
                continue
            except (OSError, json.JSONDecodeError, ValueError) as exc:
                print(f"⚠️ Failed to consume {filename}: {exc}")
                continue

        return None
        
    @staticmethod
    def update_php(job_id: int, status: str, chunks: list = None):
        url = f"http://sharpishly.dev/php/job/update/{job_id}"
        payload = {
            "status": status,
            "chunks": chunks or []
        }
        try:
            headers = {"Content-Type": "application/json"}
            requests.put(url, json=payload, headers=headers, timeout=5)
        except Exception as e:
            print(f"⚠️ Communication breakdown updating PHP state for job {job_id}: {e}")

    @staticmethod
    def acknowledge(file_path: str):
        try:
            path = Path(file_path)
            if path.exists():
                path.unlink()
                print(f"🧹 Clean Closeout: Removed transaction file {path.name}")
        except Exception as e:
            print(f"⚠️ Failed to acknowledge handshake file context: {e}")

    @staticmethod
    def fail_job(file_path: str):
        try:
            path = Path(file_path)
            if path.exists():
                fail_dir = path.parents[1] / "fail"
                fail_dir.mkdir(parents=True, exist_ok=True)
                fail_path = fail_dir / path.name
                path.replace(fail_path)
                print(f"❌ Pipeline Failure: Isolated handshake file to dead-letter queue: {fail_path}")
        except Exception as e:
            print(f"⚠️ Emergency state transition failed to move file to fail/: {e}")

# Backward-compatible import alias for ingestion_worker.py
IngestionController = NatsController