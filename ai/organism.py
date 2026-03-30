from typing import TypedDict, Annotated, List
from langgraph.graph import StateGraph, END

class AgentState(TypedDict):
    """The 'Organism' memory state."""
    content: str
    chunks: List[str]
    critique: str
    is_valid: bool

def chunking_node(state: AgentState):
    """Logic to break down text."""
    # Logic from processor.py would be called here
    return {"is_valid": True}

def antagonist_node(state: AgentState):
    """The Agent that looks for contradictions."""
    # AI logic to critique the ingestion
    return {"critique": "No contradictions found."}

# Define the workflow
workflow = StateGraph(AgentState)

workflow.add_node("librarian", chunking_node)
workflow.add_node("antagonist", antagonist_node)

workflow.set_entry_point("librarian")
workflow.add_edge("librarian", "antagonist")
workflow.add_edge("antagonist", END)

organism = workflow.compile()