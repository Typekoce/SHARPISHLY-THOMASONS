class NeuralPipeline:
    """
    Simple but extensible text cleaning pipeline.
    Receives raw text from PHP and prepares it for embedding.
    """

    def __init__(self, raw_text: str):
        if not isinstance(raw_text, str):
            raise TypeError("NeuralPipeline expects a string input")
        self.raw_data = raw_text

    def clean(self):
        """
        Performs basic but effective text normalization.
        """
        text = self.raw_data

        # Basic cleaning
        text = text.strip()
        text = ' '.join(text.split())                    # normalize whitespace
        text = text.replace('\r\n', '\n').replace('\r', '\n')

        # Optional: Remove common unwanted patterns
        # text = re.sub(r'Page \d+ of \d+', '', text)    # page numbers, etc.

        self.raw_data = text
        print(f"🧹 Cleaned text: {len(self.raw_data)} characters")
        return self

    def get_processed_text(self) -> str:
        """Return the final cleaned text."""
        return self.raw_data