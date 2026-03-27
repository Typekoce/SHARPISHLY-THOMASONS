import re
import unicodedata

class TextProcessor:
    """
    Direct Python port of the PHP TextProcessor logic.
    Ensures consistent cleaning between the PHP Ingestion and Python Embedding.
    """

    def clean(self, text: str) -> str:
        """Strip noise, URLs, and non-printable characters."""
        # 1. Strip HTML/PHP tags (if any)
        text = re.sub(r'<[^>]*?>', '', text)

        # 2. Remove URLs
        url_pattern = r'\b(?:https?://|ftp://|file://|www\.)[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|]'
        text = re.sub(url_pattern, '', text, flags=re.IGNORECASE)

        # 3. Keep only letters, numbers, and basic punctuation
        # This matches the PHP [^\p{L}\p{N}\s\-.,!?\'"…] pattern
        text = "".join(ch for ch in text if unicodedata.category(ch)[0] in 'LN' or ch in ' \-.,!?\'"…')

        # 4. Normalize whitespace and lowercase
        text = re.sub(r'\s+', ' ', text).strip()
        return text.lower()

    def chunk(self, text: str, chunk_size: int = 1000, overlap: int = 100) -> list:
        """
        Splits text into overlapping chunks. 
        Overlap ensures context isn't lost at the boundaries.
        """
        if len(text) <= chunk_size:
            return [text]

        chunks = []
        start = 0
        while start < len(text):
            end = start + chunk_size
            chunks.append(text[start:end])
            start += chunk_size - overlap
            
            # Break if we've reached the end of the string
            if end >= len(text):
                break
                
        return chunks

    def prepare_for_ollama(self, raw_content: str, metadata: dict = None) -> list:
        """Combines cleaning, prefixing, and chunking into one call."""
        clean_text = self.clean(raw_content)
        
        # Add context prefix if metadata exists (e.g., 'Source: sales.csv | ...')
        prefix = ""
        if metadata:
            prefix = " | ".join([f"{k.capitalize()}: {v}" for k, v in metadata.items()])
            prefix = f"{prefix} | "

        final_text = f"{prefix}{clean_text}"
        
        # 1000 chars is roughly 200-300 tokens; safe for nomic-embed-text
        return self.chunk(final_text, chunk_size=1000, overlap=100)