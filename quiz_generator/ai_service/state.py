"""
LangGraph State Management with Pydantic Models
Defines the typed state that flows through the LangGraph workflow.
"""
from typing import List, Dict, Optional, Literal
from pydantic import BaseModel, Field
from datetime import datetime


class MCQ(BaseModel):
    """Single Multiple Choice Question"""
    id: str = Field(..., description="Unique identifier for the question")
    question: str = Field(..., description="The question text")
    choices: Dict[str, str] = Field(..., description="Answer choices with keys A, B, C, D")
    correct_answer: str = Field(..., description="The correct answer key (A, B, C, or D)")
    learning_outcome: str = Field(..., description="Which learning outcome this question tests")


class CourseSetup(BaseModel):
    """Course configuration from Laravel"""
    course_name: str
    teacher_name: str
    student_name: str
    learning_outcomes: List[str]
    passing_score: float = Field(..., ge=0, le=100, description="Passing score as percentage")


class StudentAnswer(BaseModel):
    """Student's answer to a single question"""
    question_id: str
    answer: str = Field(..., pattern="^[ABCD]$", description="Student's answer (A, B, C, or D)")


class ExamState(BaseModel):
    """Main state object that flows through LangGraph nodes"""
    # Course setup (from Laravel)
    course_name: Optional[str] = None
    teacher_name: Optional[str] = None
    student_name: Optional[str] = None
    learning_outcomes: List[str] = Field(default_factory=list)
    passing_score: float = 70.0
    
    # MCQ Generation
    mcqs: List[MCQ] = Field(default_factory=list)
    generation_status: Optional[Literal["pending", "completed", "failed"]] = None
    generation_error: Optional[str] = None
    
    # Validation
    student_answers: List[StudentAnswer] = Field(default_factory=list)
    validation_status: Optional[Literal["pending", "valid", "invalid"]] = None
    validation_errors: List[str] = Field(default_factory=list)
    
    # Grading
    raw_score: Optional[int] = None
    percentage: Optional[float] = None
    passed: Optional[bool] = None
    grading_report: Optional[Dict] = None
    
    # Certificate
    certificate_text: Optional[str] = None
    certificate_generated: bool = False
    completion_date: Optional[str] = None
    
    # Workflow control
    current_step: str = "initial"
    error_message: Optional[str] = None
