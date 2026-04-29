class ChunkingService:
    @staticmethod
    def create_chunks(text, job_id, size=500, overlap=50):
        """
        Splits text and attaches metadata to each segment.
        Includes a 'sliding window' overlap to preserve context between chunks.
        """
        chunks = []
        text_length = len(text)
        start = 0
        chunk_index = 0

        while start < text_length:
            # Determine the end of the current slice
            end = start + size
            content = text[start:end]

            # Generate structured metadata for this specific slice
            metadata = {
                "source_job": job_id,
                "chunk_num": chunk_index,
                "char_start": start,
                "char_end": min(end, text_length),
                "total_length": text_length
            }

            chunks.append({
                "content": content,
                "meta": metadata
            })

            # Move the window forward, subtracting overlap to maintain continuity
            start += (size - overlap)
            chunk_index += 1

        return chunks

    @staticmethod
    def mock_vector(dimensions=128):
        """Generates a dummy vector for testing."""
        import random
        return [round(random.uniform(-1, 1), 4) for _ in range(dimensions)]