"""
LangGraph Workflow Definition
Orchestrates the multi-agent system with conditional routing.
"""
from typing import Literal
from langgraph.graph import StateGraph, END

# Handle imports for both module and direct execution
try:
    from .state import ExamState
    from .nodes import generate_mcq_node, supervisor_node, grading_node, certificate_node
except ImportError:
    from state import ExamState
    from nodes import generate_mcq_node, supervisor_node, grading_node, certificate_node


def should_grade(state: ExamState) -> Literal["grade", "error"]:
    """Conditional routing: proceed to grading if validation passed"""
    if state.validation_status == "valid":
        return "grade"
    return "error"


def should_generate_certificate(state: ExamState) -> Literal["certificate", "end"]:
    """Conditional routing: generate certificate only if passed"""
    if state.passed:
        return "certificate"
    return "end"


def build_exam_generation_graph() -> StateGraph:
    """
    Builds the LangGraph workflow for exam generation.
    Flow: generate_mcq -> END
    """
    workflow = StateGraph(ExamState)
    
    # Add nodes
    workflow.add_node("generate_mcq", generate_mcq_node)
    
    # Set entry point
    workflow.set_entry_point("generate_mcq")
    
    # Add edges
    workflow.add_edge("generate_mcq", END)
    
    return workflow.compile()


def build_grading_graph() -> StateGraph:
    """
    Builds the LangGraph workflow for grading and certificate generation.
    Flow: supervisor -> (valid) -> grading -> (passed) -> certificate -> END
    """
    workflow = StateGraph(ExamState)
    
    # Add nodes
    workflow.add_node("supervisor", supervisor_node)
    workflow.add_node("grading", grading_node)
    workflow.add_node("certificate", certificate_node)
    
    # Set entry point
    workflow.set_entry_point("supervisor")
    
    # Add conditional edges
    workflow.add_conditional_edges(
        "supervisor",
        should_grade,
        {
            "grade": "grading",
            "error": END
        }
    )
    
    workflow.add_conditional_edges(
        "grading",
        should_generate_certificate,
        {
            "certificate": "certificate",
            "end": END
        }
    )
    
    workflow.add_edge("certificate", END)
    
    return workflow.compile()
