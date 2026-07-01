import logging
import re

# Minimal, dependency-free validator
class DataValidator:
    # Define the strict schema for your jobs
    SCHEMA = {
        'job_id': {'type': int},
        'user_email': {'type': str, 'regex': r'^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$'},
        'action': {'type': str, 'allowed': ['process', 'archive', 'index']}
    }

    @staticmethod
    def validate(data: dict) -> bool:
        try:
            for key, rules in DataValidator.SCHEMA.items():
                val = data.get(key)
                
                # Type check
                if not isinstance(val, rules['type']):
                    raise ValueError(f"Invalid type for {key}")
                
                # Regex check (for strings)
                if 'regex' in rules and not re.match(rules['regex'], val):
                    raise ValueError(f"Invalid format for {key}")
                
                # Allowed values check
                if 'allowed' in rules and val not in rules['allowed']:
                    raise ValueError(f"Illegal value for {key}")
            
            return True
        except ValueError as e:
            logging.error(f"Security Alert: Malformed data rejected: {e}")
            return False

# Usage in your shop_worker.py
# job_data = db.fetch_next_job()
# if DataValidator.validate(job_data):
#     process_job(job_data)
# else:
#     log_and_quarantine(job_data)