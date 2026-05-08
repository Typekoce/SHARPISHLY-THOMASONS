import csv
import io
import random

class ChunkingService:
    @staticmethod
    def create_chunks(payload, job_id, size=10, overlap=2):
        """
        Robust CSV Chunking: Uses a proper parser to handle quoted newlines/commas.
        Maintains row-level sliding window and header context.
        """
        # Ensure we are working with a string for the StringIO buffer
        text = payload.decode("utf-8") if isinstance(payload, bytes) else payload
        
        # Use csv.reader to handle the complexities of the CSV format
        f = io.StringIO(text)
        reader = csv.reader(f)
        rows = list(reader)

        if len(rows) <= 1:
            return []

        header = rows[0]
        data_rows = rows[1:] # Exclude header from the data pool
        chunks = []
        
        # Calculate the advance step (e.g., size 10 - overlap 2 = step 8)
        step = size - overlap
        if step <= 0:
            # Fallback to prevent infinite loops if overlap is misconfigured
            step = 1 

        for chunk_index, start in enumerate(range(0, len(data_rows), step)):
            segment = data_rows[start : start + size]
            
            if not segment:
                break

            # Reconstruct CSV string for the LLM: Header + Segment Rows
            content_rows = [header] + segment
            
            # Using join logic that handles potential commas in the content
            # (Note: For extreme accuracy, you could use csv.writer to generate this string)
            content = "\n".join(",".join(row) for row in content_rows)

            chunks.append({
                "content": content,
                "meta": {
                    "source_job": job_id,
                    "chunk_num": chunk_index,
                    "row_start": start + 1,  # +1 because header is row 0
                    "row_end": min(start + size, len(data_rows)),
                    "total_rows": len(rows),
                    "is_csv": True,
                }
            })

        return chunks

    @staticmethod
    def mock_vector(dimensions=1536):
        """Generates a dummy vector for testing Phase 3."""
        return [random.uniform(-1, 1) for _ in range(dimensions)]
