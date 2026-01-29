"""
LangGraph Node Implementations
Each node represents a step in the multi-agent workflow.
"""
import json
import uuid
import os
from typing import Dict, Any
from datetime import datetime
from pathlib import Path
from langchain_openai import ChatOpenAI

# Load environment variables from .env file if it exists
try:
    from dotenv import load_dotenv
    env_path = Path(__file__).parent / '.env'
    load_dotenv(dotenv_path=env_path)
except ImportError:
    # python-dotenv not installed, skip .env loading
    pass

# Handle different langchain versions for prompts
try:
    from langchain_core.prompts import ChatPromptTemplate
except ImportError:
    from langchain.prompts import ChatPromptTemplate

# Handle imports for both module and direct execution
try:
    from .state import ExamState, MCQ, StudentAnswer
except ImportError:
    from state import ExamState, MCQ, StudentAnswer


# LLM will be initialized lazily when needed
_llm = None

def get_llm():
    """Lazy initialization of LLM to avoid errors if API key is missing at import time"""
    global _llm
    if _llm is None:
        api_key = os.getenv("OPENAI_API_KEY")
        if not api_key:
            raise ValueError(
                "OPENAI_API_KEY environment variable is not set. "
                "Please set it before using the service. "
                "You can either:\n"
                "1. Export it: export OPENAI_API_KEY='your-api-key-here'\n"
                "2. Create a .env file in the ai_service folder with: OPENAI_API_KEY=your-api-key-here"
            )
        try:
            _llm = ChatOpenAI(
                temperature=0.7,
                model="gpt-4",
                api_key=api_key
            )
        except Exception as e:
            raise ValueError(
                f"Failed to initialize OpenAI client: {str(e)}. "
                "Please check that your OPENAI_API_KEY is correct and valid."
            )
    return _llm


def generate_mcq_node(state: ExamState) -> Dict[str, Any]:
    """
    MCQ Generation Agent Node
    Generates multiple-choice questions based on learning outcomes.
    Each learning outcome must be tested by at least one MCQ.
    """
    try:
        state.generation_status = "pending"
        state.current_step = "mcq_generation"
        
        if not state.learning_outcomes:
            state.generation_status = "failed"
            state.generation_error = "No learning outcomes provided"
            return {"generation_status": "failed", "generation_error": state.generation_error}
        
        # Build prompt for MCQ generation
        outcomes_text = "\n".join([f"- {outcome}" for outcome in state.learning_outcomes])
        
        prompt = ChatPromptTemplate.from_messages([
            ("system", """You are an expert educational assessment designer. 
            Generate high-quality multiple-choice questions that accurately test the provided learning outcomes.
            Each question must:
            1. Directly assess at least one learning outcome
            2. Have exactly 4 choices (A, B, C, D)
            3. Have one clearly correct answer
            4. Include plausible distractors
            5. Be clear and unambiguous
            
            Return ONLY valid JSON in this exact format:
            {{
                "questions": [
                    {{
                        "id": "q1",
                        "question": "Question text here?",
                        "choices": {{
                            "A": "First choice",
                            "B": "Second choice",
                            "C": "Third choice",
                            "D": "Fourth choice"
                        }},
                        "correct_answer": "A",
                        "learning_outcome": "Which learning outcome this tests"
                    }}
                ]
            }}"""),
            ("human", f"""Course: {state.course_name}
Teacher: {state.teacher_name}
Student: {state.student_name}

Learning Outcomes:
{outcomes_text}

Generate between 10 and 20 multiple-choice questions (aim for 20 if the learning outcomes allow). Ensure EACH learning outcome is tested by at least one question.
Distribute the questions across all learning outcomes, with more questions for broader or more important outcomes.

Return the JSON response now.""")
        ])
        
        chain = prompt | get_llm()
        response = chain.invoke({})
        
        # Parse LLM response
        content = response.content.strip()
        
        # Extract JSON from markdown code blocks if present
        if "```json" in content:
            content = content.split("```json")[1].split("```")[0].strip()
        elif "```" in content:
            content = content.split("```")[1].split("```")[0].strip()
        
        questions_data = json.loads(content)
        
        # Convert to MCQ objects
        mcqs = []
        for q_data in questions_data.get("questions", []):
            mcq = MCQ(
                id=q_data.get("id", f"q{len(mcqs) + 1}"),
                question=q_data["question"],
                choices=q_data["choices"],
                correct_answer=q_data["correct_answer"],
                learning_outcome=q_data.get("learning_outcome", "")
            )
            mcqs.append(mcq)
        
        # Validate that we have a reasonable number of questions (min 5, max 25)
        min_questions, max_questions = 5, 25
        if len(mcqs) < min_questions:
            state.generation_status = "failed"
            state.generation_error = f"Expected at least {min_questions} questions, but generated {len(mcqs)} questions"
            return {"generation_status": "failed", "generation_error": state.generation_error}
        if len(mcqs) > max_questions:
            state.generation_status = "failed"
            state.generation_error = f"Expected at most {max_questions} questions, but generated {len(mcqs)} questions"
            return {"generation_status": "failed", "generation_error": state.generation_error}
        
        # Validate that all learning outcomes are covered
        covered_outcomes = set(mcq.learning_outcome for mcq in mcqs)
        if len(covered_outcomes) < len(state.learning_outcomes):
            state.generation_status = "failed"
            state.generation_error = f"Not all learning outcomes are covered. Covered: {len(covered_outcomes)}, Required: {len(state.learning_outcomes)}"
            return {"generation_status": "failed", "generation_error": state.generation_error}
        
        state.mcqs = mcqs
        state.generation_status = "completed"
        
        return {
            "mcqs": mcqs,
            "generation_status": "completed",
            "current_step": "mcq_generation_complete"
        }
        
    except ValueError as e:
        # Handle API key errors
        state.generation_status = "failed"
        error_msg = str(e)
        if "OPENAI_API_KEY" in error_msg or "API key" in error_msg:
            state.generation_error = f"OpenAI API key error: {error_msg}"
        else:
            state.generation_error = f"Configuration error: {error_msg}"
        return {"generation_status": "failed", "generation_error": state.generation_error}
    except json.JSONDecodeError as e:
        state.generation_status = "failed"
        state.generation_error = f"Failed to parse LLM response as JSON: {str(e)}"
        return {"generation_status": "failed", "generation_error": state.generation_error}
    except Exception as e:
        state.generation_status = "failed"
        error_str = str(e)
        # Check for OpenAI API errors
        if "401" in error_str or "invalid_api_key" in error_str.lower() or "Incorrect API key" in error_str:
            state.generation_error = (
                f"MCQ generation failed: Invalid OpenAI API key. "
                f"Please check your OPENAI_API_KEY environment variable. "
                f"Error details: {error_str}"
            )
        else:
            state.generation_error = f"MCQ generation failed: {error_str}"
        return {"generation_status": "failed", "generation_error": state.generation_error}


def supervisor_node(state: ExamState) -> Dict[str, Any]:
    """
    Supervisor Node - Validates student answers before grading
    Checks:
    1. All questions are answered
    2. Answers match expected format (A, B, C, or D)
    3. No duplicate question IDs
    """
    state.current_step = "validation"
    state.validation_status = "pending"
    state.validation_errors = []
    
    # Check if MCQs exist
    if not state.mcqs:
        state.validation_status = "invalid"
        state.validation_errors.append("No questions available to validate")
        return {
            "validation_status": "invalid",
            "validation_errors": state.validation_errors
        }
    
    # Check if answers provided
    if not state.student_answers:
        state.validation_status = "invalid"
        state.validation_errors.append("No student answers provided")
        return {
            "validation_status": "invalid",
            "validation_errors": state.validation_errors
        }
    
    # Get all question IDs
    question_ids = {mcq.id for mcq in state.mcqs}
    answered_ids = {answer.question_id for answer in state.student_answers}
    
    # Check if all questions are answered
    missing_questions = question_ids - answered_ids
    if missing_questions:
        state.validation_status = "invalid"
        state.validation_errors.append(
            f"Missing answers for questions: {', '.join(sorted(missing_questions))}"
        )
    
    # Check for duplicate question IDs
    seen_ids = set()
    duplicates = []
    for answer in state.student_answers:
        if answer.question_id in seen_ids:
            duplicates.append(answer.question_id)
        seen_ids.add(answer.question_id)
    
    if duplicates:
        state.validation_status = "invalid"
        state.validation_errors.append(
            f"Duplicate answers for questions: {', '.join(duplicates)}"
        )
    
    # Check answer format
    invalid_answers = []
    for answer in state.student_answers:
        if answer.answer not in ["A", "B", "C", "D"]:
            invalid_answers.append(f"{answer.question_id}: {answer.answer}")
        if answer.question_id not in question_ids:
            invalid_answers.append(f"{answer.question_id}: Invalid question ID")
    
    if invalid_answers:
        state.validation_status = "invalid"
        state.validation_errors.append(
            f"Invalid answer format: {', '.join(invalid_answers)}"
        )
    
    # Final validation result
    if state.validation_errors:
        state.validation_status = "invalid"
        return {
            "validation_status": "invalid",
            "validation_errors": state.validation_errors
        }
    else:
        state.validation_status = "valid"
        return {
            "validation_status": "valid",
            "current_step": "validation_complete"
        }


def grading_node(state: ExamState) -> Dict[str, Any]:
    """
    Grading Agent Node
    Compares student answers to correct answers and computes scores.
    """
    state.current_step = "grading"
    
    # Create lookup for correct answers
    correct_answers = {mcq.id: mcq.correct_answer for mcq in state.mcqs}
    
    # Create lookup for student answers
    student_answers_dict = {answer.question_id: answer.answer for answer in state.student_answers}
    
    # Grade each question
    total_questions = len(state.mcqs)
    correct_count = 0
    question_results = []
    
    for mcq in state.mcqs:
        student_answer = student_answers_dict.get(mcq.id)
        is_correct = student_answer == mcq.correct_answer
        
        if is_correct:
            correct_count += 1
        
        question_results.append({
            "question_id": mcq.id,
            "question": mcq.question,
            "student_answer": student_answer,
            "correct_answer": mcq.correct_answer,
            "is_correct": is_correct,
            "learning_outcome": mcq.learning_outcome
        })
    
    # Calculate scores
    raw_score = correct_count
    percentage = (correct_count / total_questions) * 100 if total_questions > 0 else 0
    passed = percentage >= state.passing_score
    
    state.raw_score = raw_score
    state.percentage = percentage
    state.passed = passed
    state.grading_report = {
        "total_questions": total_questions,
        "correct_answers": correct_count,
        "incorrect_answers": total_questions - correct_count,
        "raw_score": raw_score,
        "percentage": round(percentage, 2),
        "passing_score": state.passing_score,
        "passed": passed,
        "question_results": question_results
    }
    
    return {
        "raw_score": raw_score,
        "percentage": percentage,
        "passed": passed,
        "grading_report": state.grading_report,
        "current_step": "grading_complete"
    }


def certificate_node(state: ExamState) -> Dict[str, Any]:
    """
    Certificate Generation Agent Node
    Generates diploma text only if student passed.
    """
    if not state.passed:
        return {
            "certificate_generated": False,
            "certificate_text": None,
            "current_step": "certificate_skipped"
        }
    
    state.current_step = "certificate_generation"
    
    try:
        completion_date = datetime.now().strftime("%B %d, %Y")
        state.completion_date = completion_date
        
        prompt = ChatPromptTemplate.from_messages([
            ("system", """You are a professional certificate/diploma writer.
            Generate a formal, professional certificate text for a student who has successfully completed a course.
            The certificate should be:
            - Professional and formal in tone
            - Include all required information
            - Suitable for official documentation
            - Clear and concise"""),
            ("human", f"""Generate a certificate/diploma text for:

Student Name: {state.student_name}
Course Name: {state.course_name}
Teacher/Instructor: {state.teacher_name}
Completion Date: {completion_date}
Score: {state.percentage:.2f}%

Generate the full certificate text now. Make it professional and suitable for official documentation.""")
        ])
        
        chain = prompt | get_llm()
        response = chain.invoke({})
        
        certificate_text = response.content.strip()
        state.certificate_text = certificate_text
        state.certificate_generated = True
        
        return {
            "certificate_text": certificate_text,
            "certificate_generated": True,
            "completion_date": completion_date,
            "current_step": "certificate_complete"
        }
        
    except Exception as e:
        state.error_message = f"Certificate generation failed: {str(e)}"
        return {
            "certificate_generated": False,
            "error_message": state.error_message
        }
