from typing import TypedDict, List
from langgraph.graph import StateGraph, END
from src.processor import TextProcessor
from src.ollama import OllamaService

class AgentState(TypedDict):
    content: str
    chunks: List[str]
    embeddings: List[List[float]]
    is_valid: bool

def librarian_node(state: AgentState):
    """Clean and Chunk the content."""
    processor = TextProcessor()
    chunks = processor.prepare_for_ollama(state['content'])
    return {"chunks": chunks}

def embedding_node(state: AgentState):
    """Generate vectors via Ollama."""
    ollama = OllamaService()
    vectors = ollama.get_embeddings(state['chunks'])
    return {"embeddings": vectors, "is_valid": True}

# Build the Graph
workflow = StateGraph(AgentState)

workflow.add_node("librarian", librarian_node)
workflow.add_node("embedder", embedding_node)

workflow.set_entry_point("librarian")
workflow.add_edge("librarian", "embedder")
workflow.add_edge("embedder", END)

organism = workflow.compile()