class ChunkingService:
    @staticmethod
    def create_chunks(text, size=500):
        """Splits text into manageable pieces for vectorization."""
        # Simple split by character count for now
        return [text[i:i+size] for i in range(0, len(text), size)]

    @staticmethod
    def mock_vector(dimensions=128):
        """Generates a dummy vector for testing the DB schema."""
        import random
        return [round(random.uniform(-1, 1), 4) for _ in range(dimensions)]