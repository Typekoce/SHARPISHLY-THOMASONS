import re
import unicodedata
import csv
import io

class TextProcessor:
    """
    Enhanced Processor: Handles both flat text and structured CSV data.
    Ensures context-aware chunking for the Neural Engine.
    """

    def clean(self, text: str) -> str:
        """Strip noise, URLs, and non-printable characters."""
        text = re.sub(r'<[^>]*?>', '', text)
        url_pattern = r'\b(?:https?://|ftp://|file://|www\.)[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]'
        text = re.sub(url_pattern, '', text, flags=re.IGNORECASE)
        
        # Normalize to basic printable characters
        text = "".join(ch for ch in text if unicodedata.category(ch)[0] in 'LN' or ch in ' \-.,!?\'"…')
        return re.sub(r'\s+', ' ', text).strip().lower()

    def process_csv(self, raw_content: str) -> list:
        """Converts CSV rows into descriptive text strings for embedding."""
        chunks = []
        f = io.StringIO(raw_content)
        reader = csv.DictReader(f)
        
        for row in reader:
            # Flatten row into a sentence: "brand: nike, price: 90, category: shoes"
            row_text = ", ".join([f"{k}: {v}" for k, v in row.items() if v])
            chunks.append(self.clean(row_text))
            
        return chunks

    def chunk_text(self, text: str, size: int = 1000, overlap: int = 100) -> list:
        """Standard overlapping chunks for long-form text documents."""
        if len(text) <= size: return [text]
        
        chunks = []
        start = 0
        while start < len(text):
            end = start + size
            chunks.append(text[start:end])
            start += size - overlap
            if end >= len(text): break
        return chunks

    def prepare_for_ollama(self, raw_content: str, file_path: str = "") -> list:
        """
        Main entry point. Detects file type and prepares embeddings.
        """
        # 1. Check if it's a CSV based on file extension or content
        if file_path.lower().endswith('.csv') or (',' in raw_content[:100] and '\n' in raw_content[:100]):
            return self.process_csv(raw_content)
        
        # 2. Fallback to standard text cleaning and chunking
        clean_text = self.clean(raw_content)
        return self.chunk_text(clean_text)