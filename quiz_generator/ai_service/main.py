"""
FastAPI Application - REST API for Laravel Integration
Provides endpoints for exam generation and grading.
"""
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import List, Dict, Optional
import os
from pathlib import Path

# Load environment variables from .env file if it exists
try:
    from dotenv import load_dotenv
    env_path = Path(__file__).parent / '.env'
    load_dotenv(dotenv_path=env_path)
except ImportError:
    # python-dotenv not installed, skip .env loading
    pass

# Handle imports for both module and direct execution
try:
    from .state import ExamState, MCQ, StudentAnswer
    from .graph import build_exam_generation_graph, build_grading_graph
except ImportError:
    from state import ExamState, MCQ, StudentAnswer
    from graph import build_exam_generation_graph, build_grading_graph

app = FastAPI(
    title="AI Quiz Generator Service",
    description="LangGraph-based multi-agent system for MCQ generation and grading",
    version="1.0.0"
)

# CORS middleware for Laravel integration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure appropriately for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ==================== REQUEST/RESPONSE SCHEMAS ====================

class CourseSetupRequest(BaseModel):
    """Request payload from Laravel for exam generation"""
    course_name: str
    teacher_name: str
    student_name: str
    learning_outcomes: List[str]
    passing_score: float = Field(default=70.0, ge=0, le=100)


class MCQResponse(BaseModel):
    """MCQ structure for API response"""
    id: str
    question: str
    choices: Dict[str, str]
    correct_answer: str
    learning_outcome: str


class ExamGenerationResponse(BaseModel):
    """Response after exam generation"""
    success: bool
    mcqs: List[MCQResponse]
    total_questions: int
    message: Optional[str] = None
    error: Optional[str] = None


class StudentAnswerRequest(BaseModel):
    """Request payload for grading"""
    course_name: str
    teacher_name: str
    student_name: str
    learning_outcomes: List[str]
    passing_score: float
    mcqs: List[MCQResponse]
    student_answers: List[Dict[str, str]]  # [{"question_id": "q1", "answer": "A"}]


class GradingResponse(BaseModel):
    """Response after grading"""
    success: bool
    raw_score: Optional[int] = None
    percentage: Optional[float] = None
    passed: Optional[bool] = None
    passing_score: float
    total_questions: int
    grading_report: Optional[Dict] = None
    certificate_text: Optional[str] = None
    completion_date: Optional[str] = None
    validation_errors: Optional[List[str]] = None
    error: Optional[str] = None


# ==================== API ENDPOINTS ====================

@app.post("/generate-exam", response_model=ExamGenerationResponse)
async def generate_exam(request: CourseSetupRequest):
    """
    Generate MCQ exam based on learning outcomes.
    
    Laravel sends course setup data, receives structured MCQs.
    """
    try:
        # Initialize state
        state = ExamState(
            course_name=request.course_name,
            teacher_name=request.teacher_name,
            student_name=request.student_name,
            learning_outcomes=request.learning_outcomes,
            passing_score=request.passing_score
        )
        
        # Build and run graph
        graph = build_exam_generation_graph()
        final_state_dict = graph.invoke(state)
        
        # Convert dict back to ExamState (LangGraph returns dict)
        final_state = ExamState(**final_state_dict) if isinstance(final_state_dict, dict) else final_state_dict
        
        # Check for errors
        if final_state.generation_status == "failed":
            return ExamGenerationResponse(
                success=False,
                mcqs=[],
                total_questions=0,
                error=final_state.generation_error
            )
        
        # Convert MCQs to response format
        # Handle case where MCQs might be dicts or MCQ objects
        mcq_responses = []
        for mcq in final_state.mcqs:
            if isinstance(mcq, dict):
                # Convert dict to MCQResponse
                mcq_responses.append(MCQResponse(
                    id=mcq.get("id", ""),
                    question=mcq.get("question", ""),
                    choices=mcq.get("choices", {}),
                    correct_answer=mcq.get("correct_answer", ""),
                    learning_outcome=mcq.get("learning_outcome", "")
                ))
            else:
                # MCQ is already an object
                mcq_responses.append(MCQResponse(
                    id=mcq.id,
                    question=mcq.question,
                    choices=mcq.choices,
                    correct_answer=mcq.correct_answer,
                    learning_outcome=mcq.learning_outcome
                ))
        
        return ExamGenerationResponse(
            success=True,
            mcqs=mcq_responses,
            total_questions=len(mcq_responses),
            message=f"Successfully generated {len(mcq_responses)} questions"
        )
        
    except ValueError as e:
        # Handle API key or configuration errors
        error_msg = str(e)
        if "OPENAI_API_KEY" in error_msg or "API key" in error_msg:
            return ExamGenerationResponse(
                success=False,
                mcqs=[],
                total_questions=0,
                error=f"OpenAI API key error: {error_msg}. Please check your OPENAI_API_KEY environment variable."
            )
        raise HTTPException(status_code=400, detail=error_msg)
    except Exception as e:
        # Extract more detailed error information
        error_str = str(e)
        if "401" in error_str or "invalid_api_key" in error_str.lower():
            return ExamGenerationResponse(
                success=False,
                mcqs=[],
                total_questions=0,
                error=f"Invalid OpenAI API key. Please check your OPENAI_API_KEY. Error: {error_str}"
            )
        return ExamGenerationResponse(
            success=False,
            mcqs=[],
            total_questions=0,
            error=f"Exam generation failed: {error_str}"
        )


@app.post("/grade-exam", response_model=GradingResponse)
async def grade_exam(request: StudentAnswerRequest):
    """
    Grade student answers and generate certificate if passed.
    
    Laravel sends student answers, receives grading report and optional certificate.
    """
    try:
        # Convert MCQs from request to state format
        # request.mcqs contains MCQResponse objects (Pydantic models), not dicts
        mcqs = []
        for mcq in request.mcqs:
            # Handle both Pydantic objects and dicts (for flexibility)
            if isinstance(mcq, dict):
                mcqs.append(MCQ(
                    id=mcq["id"],
                    question=mcq["question"],
                    choices=mcq["choices"],
                    correct_answer=mcq["correct_answer"],
                    learning_outcome=mcq.get("learning_outcome", "")
                ))
            else:
                # MCQResponse object (Pydantic model) - use attribute access
                mcqs.append(MCQ(
                    id=mcq.id,
                    question=mcq.question,
                    choices=mcq.choices,
                    correct_answer=mcq.correct_answer,
                    learning_outcome=mcq.learning_outcome
                ))
        
        # Convert student answers to state format
        student_answers = [
            StudentAnswer(
                question_id=answer["question_id"],
                answer=answer["answer"]
            )
            for answer in request.student_answers
        ]
        
        # Initialize state
        state = ExamState(
            course_name=request.course_name,
            teacher_name=request.teacher_name,
            student_name=request.student_name,
            learning_outcomes=request.learning_outcomes,
            passing_score=request.passing_score,
            mcqs=mcqs,
            student_answers=student_answers
        )
        
        # Build and run graph
        graph = build_grading_graph()
        final_state_dict = graph.invoke(state)
        
        # Convert dict back to ExamState (LangGraph returns dict)
        final_state = ExamState(**final_state_dict) if isinstance(final_state_dict, dict) else final_state_dict
        
        # Check for validation errors
        if final_state.validation_status == "invalid":
            return GradingResponse(
                success=False,
                passing_score=request.passing_score,
                total_questions=len(mcqs),
                validation_errors=final_state.validation_errors,
                error="Validation failed"
            )
        
        # Return grading results
        return GradingResponse(
            success=True,
            raw_score=final_state.raw_score,
            percentage=final_state.percentage,
            passed=final_state.passed,
            passing_score=request.passing_score,
            total_questions=len(mcqs),
            grading_report=final_state.grading_report,
            certificate_text=final_state.certificate_text,
            completion_date=final_state.completion_date
        )
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Grading failed: {str(e)}")


@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": "AI Quiz Generator",
        "version": "1.0.0"
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
