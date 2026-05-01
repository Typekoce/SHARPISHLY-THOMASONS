class RagService:
    @staticmethod
    def build_prompt(question, context):
        """
        The RAG Template: This forces the AI to stay 'inside the box' 
        of the uploaded document.
        """
        system_instructions = (
            "You are a helpful assistant. Use the provided context to answer the user. "
            "If the answer isn't in the context, say you don't know. Do not make things up."
        )

        prompt = f"""
        CONTEXT FROM UPLOADED DOCUMENT:
        {context}

        USER QUESTION:
        {question}

        FINAL ANSWER:
        """
        return {"system": system_instructions, "prompt": prompt}

    @staticmethod
    def mock_generate_response(prompt_package):
        """
        Task 3.1: Mock the AI response until we hook up Ollama.
        """
        return f"MOCK AI RESPONSE: I found information in your document regarding your question. [Sample Data based on context]."