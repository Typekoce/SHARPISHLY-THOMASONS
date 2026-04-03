class TextProcessor:
    def prepare_for_ollama(self, content, file_path=None):
        # Basic chunking logic: split by double newlines or 1000 characters
        # In Phase 2, we can make this more "Semantic"
        chunks = [content[i:i+1000] for i in range(0, len(content), 1000)]
        return chunks