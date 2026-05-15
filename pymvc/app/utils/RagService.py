import ollama
from app.utils.VectorStorageService import VectorStorageService

class RagService:
    LLM_MODEL = "llama3"

    @staticmethod
    def ask(question: str, job_id: int = None) -> str:
        """
        Gathers truth text vectors from ChromaDB (globally or job-scoped) 
        and runs local LLM context synthesis.
        """
        # 1. Pull relevant chunks via the unified database service query method
        matched_chunks = VectorStorageService.query_relevant_context(question, n_results=3, job_id=job_id)
        context_window = "\n".join([f"- {chunk}" for chunk in matched_chunks])
        
        if not context_window:
            context_window = "No context data found in database storage."

        # 2. Forge prompt constraints to anchor the model response text
        prompt = (
            "You are an internal automation module for SHARPISHLY-THOMASONS.\n"
            "Answer the user query accurately relying strictly on the context items listed below.\n"
            "If the knowledge baseline doesn't contain details, say 'Context data insufficient.'\n\n"
            f"CONTEXT BASELINE:\n{context_window}\n\n"
            f"USER QUERY: {question}\n\n"
            "ANSWER:"
        )
        
        # 3. Request inference stream generation from local runtime instance
        try:
            response = ollama.generate(
                model=RagService.LLM_MODEL,
                prompt=prompt,
                stream=False
            )
            return response.get("response", "").strip()
        except Exception as e:
            return f"⚠️ Neural Generation Failed: {e}"
