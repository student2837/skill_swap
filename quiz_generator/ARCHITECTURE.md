# AI Quiz Generator - System Architecture

## Overview

A production-ready AI multi-agent system built with LangGraph that integrates with Laravel (Blade) applications. The system generates multiple-choice questions based on learning outcomes, grades student submissions, and generates certificates for passing students.

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Application                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │   Blade UI   │  │  Controller │  │   Sessions  │       │
│  │  (Frontend)  │  │             │  │              │       │
│  └──────┬───────┘  └──────┬─────┘  └──────┬───────┘       │
│         │                  │                │               │
│         └──────────────────┼────────────────┘               │
│                            │                                 │
└────────────────────────────┼─────────────────────────────────┘
                             │ HTTP REST API
                             │ (JSON)
                             ▼
┌─────────────────────────────────────────────────────────────┐
│              Python AI Microservice (FastAPI)                │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              FastAPI Application                     │   │
│  │  • POST /generate-exam                              │   │
│  │  • POST /grade-exam                                 │   │
│  │  • GET  /health                                     │   │
│  └──────────────────┬──────────────────────────────────┘   │
│                     │                                        │
│                     ▼                                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │            LangGraph Workflow Engine                 │   │
│  │                                                       │   │
│  │  ┌──────────────────────────────────────────────┐  │   │
│  │  │         Exam Generation Graph                 │  │   │
│  │  │  generate_mcq → END                          │  │   │
│  │  └──────────────────────────────────────────────┘  │   │
│  │                                                       │   │
│  │  ┌──────────────────────────────────────────────┐  │   │
│  │  │         Grading Graph                         │  │   │
│  │  │  supervisor → [valid?] → grading →           │  │   │
│  │  │              [passed?] → certificate → END    │  │   │
│  │  └──────────────────────────────────────────────┘  │   │
│  └──────────────────┬──────────────────────────────────┘   │
│                     │                                        │
│                     ▼                                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              LangGraph Nodes                         │   │
│  │  • MCQ Generation Agent                              │   │
│  │  • Supervisor Node (Validation)                      │   │
│  │  • Grading Agent                                     │   │
│  │  • Certificate Generation Agent                      │   │
│  └──────────────────┬──────────────────────────────────┘   │
│                     │                                        │
│                     ▼                                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │         OpenAI (LLM Provider)                        │   │
│  │  • Model: gpt-4                                     │   │
│  │  • API: OpenAI API                                 │   │
│  │  • High-quality text generation                     │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Component Architecture

### 1. Laravel Backend (PHP)

**Purpose**: Handles HTTP requests, session management, and UI rendering.

**Key Components**:
- **QuizController**: Main controller managing the quiz workflow
  - `create()`: Displays course setup form
  - `generateExam()`: Calls AI service to generate MCQs
  - `show()`: Displays exam to student
  - `gradeExam()`: Submits answers for grading
  - `results()`: Displays grading results and certificate

- **Blade Templates**: Frontend UI components
  - `setup.blade.php`: Course configuration form
  - `exam.blade.php`: Exam display and submission
  - `results.blade.php`: Results and certificate display

- **Routes**: RESTful endpoints for quiz workflow

### 2. Python AI Microservice

**Purpose**: Handles all AI/LLM operations using LangGraph for orchestration.

#### 2.1 State Management (`state.py`)

Uses Pydantic models for type-safe state management:

- **ExamState**: Main state object flowing through LangGraph
  - Course configuration (name, teacher, student, outcomes, passing score)
  - MCQ generation status and results
  - Validation status and errors
  - Grading results (score, percentage, pass/fail)
  - Certificate text and generation status
  - Workflow control fields

- **MCQ**: Individual question structure
  - id, question text, choices (A-D), correct answer, learning outcome

- **StudentAnswer**: Student response structure
  - question_id, answer (A, B, C, or D)

#### 2.2 LangGraph Nodes (`nodes.py`)

**MCQ Generation Agent** (`generate_mcq_node`)
- **Input**: Course setup (name, outcomes, etc.)
- **Process**: 
  - Uses OpenAI GPT-4 LLM to generate questions based on learning outcomes
  - Ensures each outcome is tested by ≥1 question
  - Validates JSON response structure
- **Output**: List of structured MCQs
- **Error Handling**: Returns error status if generation fails

**Supervisor Node** (`supervisor_node`)
- **Input**: Student answers + MCQs
- **Process**: Validates answer completeness and format
  - Checks all questions are answered
  - Validates answer format (A, B, C, or D)
  - Checks for duplicate question IDs
  - Verifies question IDs exist
- **Output**: Validation status (valid/invalid) with error messages
- **Routing**: Routes to grading if valid, ends workflow if invalid

**Grading Agent** (`grading_node`)
- **Input**: Validated answers + MCQs
- **Process**: 
  - Compares student answers to correct answers
  - Calculates raw score and percentage
  - Determines pass/fail status
  - Generates detailed grading report
- **Output**: Scores, pass/fail status, detailed report

**Certificate Generation Agent** (`certificate_node`)
- **Input**: Grading results (only if passed)
- **Process**: 
  - Uses OpenAI GPT-4 LLM to generate professional certificate text
  - Includes student name, course name, teacher, completion date
- **Output**: Certificate text with completion date
- **Conditional**: Only runs if `passed == true`

#### 2.3 LangGraph Workflows (`graph.py`)

**Exam Generation Graph**:
```
Entry → generate_mcq → END
```
- Simple linear workflow
- Generates MCQs from learning outcomes

**Grading Graph**:
```
Entry → supervisor → [valid?] → grading → [passed?] → certificate → END
                        ↓                        ↓
                      error                    end
```
- Conditional routing based on validation and pass status
- Supervisor validates before grading
- Certificate only generated if passed

#### 2.4 FastAPI Application (`main.py`)

**Endpoints**:

1. **POST /generate-exam**
   - Receives: Course setup from Laravel
   - Returns: Structured MCQs
   - Uses: Exam Generation Graph
   - Timeout: 120 seconds (for LLM processing)

2. **POST /grade-exam**
   - Receives: MCQs + student answers
   - Returns: Grading report + certificate (if passed)
   - Uses: Grading Graph
   - Timeout: 120 seconds

3. **GET /health**
   - Health check endpoint
   - Returns service status

**Request/Response Schemas**:
- `CourseSetupRequest`: Course configuration
- `ExamGenerationResponse`: MCQ generation results
- `StudentAnswerRequest`: Student answers for grading
- `GradingResponse`: Grading results and certificate

## Data Flow

### Flow 1: Exam Generation

```
1. User fills Blade form (course_name, outcomes, etc.)
   ↓
2. Laravel POST /quiz/generate → QuizController::generateExam()
   ↓
3. Laravel HTTP POST → FastAPI /generate-exam
   ↓
4. FastAPI creates ExamState, runs Exam Generation Graph
   ↓
5. MCQ Generation Agent:
   - Builds prompt with learning outcomes
   - Calls OpenAI GPT-4 LLM
   - Parses JSON response
   - Validates question coverage
   ↓
6. FastAPI returns JSON with MCQs
   ↓
7. Laravel stores MCQs in session, redirects to exam view
   ↓
8. Blade renders exam form
```

### Flow 2: Grading & Certificate

```
1. Student submits answers via Blade form
   ↓
2. Laravel POST /quiz/grade → QuizController::gradeExam()
   ↓
3. Laravel HTTP POST → FastAPI /grade-exam
   ↓
4. FastAPI creates ExamState, runs Grading Graph
   ↓
5. Supervisor Node:
   - Validates all questions answered
   - Validates answer format
   - If invalid → return error
   - If valid → continue
   ↓
6. Grading Agent:
   - Compares answers to correct answers
   - Calculates scores
   - Determines pass/fail
   ↓
7. Conditional Routing:
   - If passed → Certificate Agent generates certificate
   - If failed → Skip certificate
   ↓
8. FastAPI returns grading report + certificate
   ↓
9. Laravel stores results, redirects to results view
   ↓
10. Blade displays results and certificate
```

## Technology Stack

### Backend (Laravel)
- **Framework**: Laravel (PHP 8.1+)
- **Frontend**: Blade Templates
- **HTTP Client**: Laravel HTTP Facade
- **Session Management**: Laravel Sessions

### AI Service (Python)
- **Framework**: FastAPI 0.104.1
- **Server**: Uvicorn
- **Orchestration**: LangGraph 0.2.0+
- **LLM Integration**: LangChain 0.2.0+
- **LLM Provider**: OpenAI (via langchain-openai)
- **State Management**: Pydantic 2.7.4+
- **Type Safety**: Python 3.11+ type hints

### AI Provider
- **Service**: OpenAI
- **Model**: gpt-4 (or gpt-4-turbo, gpt-3.5-turbo for faster/cheaper)
- **API**: OpenAI API
- **Advantages**: High quality, excellent for educational content, reliable, widely used

## Key Design Decisions

### 1. Separation of Concerns

- **Laravel**: HTTP handling, sessions, UI rendering, user interaction
- **Python**: AI/LLM operations, workflow orchestration, business logic
- **Communication**: REST API (HTTP/JSON) - language-agnostic

### 2. Human-in-the-Loop

- Implemented via Laravel Blade forms, NOT Python CLI
- Student interaction happens in browser
- Laravel manages state between steps (sessions)
- No blocking Python input() calls

### 3. State Management

- **LangGraph State**: Typed with Pydantic (type safety, validation)
- **Laravel Sessions**: Store exam data between HTTP requests
- **Stateless AI Service**: No database required (can be extended)

### 4. Error Handling

- Validation errors returned as structured JSON
- Laravel handles user-facing error messages
- AI service logs errors, returns error responses
- Graceful degradation at each step

### 5. Conditional Routing

- LangGraph conditional edges for workflow control
- Certificate generation only if passed
- Validation errors stop workflow early
- Type-safe routing with Literal types

### 6. Type Safety

- Pydantic models for all data structures
- Type hints throughout Python code
- Request/response validation
- Compile-time and runtime type checking

## API Contracts

### Generate Exam Request

```json
{
  "course_name": "Introduction to Python",
  "teacher_name": "Dr. Smith",
  "student_name": "John Doe",
  "learning_outcomes": [
    "Understand basic Python syntax",
    "Write simple functions"
  ],
  "passing_score": 70.0
}
```

### Generate Exam Response

```json
{
  "success": true,
  "mcqs": [
    {
      "id": "q1",
      "question": "What is the correct syntax for a Python function?",
      "choices": {
        "A": "def function_name():",
        "B": "function function_name():",
        "C": "def function_name:",
        "D": "function function_name:"
      },
      "correct_answer": "A",
      "learning_outcome": "Understand basic Python syntax"
    }
  ],
  "total_questions": 2,
  "message": "Successfully generated 2 questions"
}
```

### Grade Exam Request

```json
{
  "course_name": "Introduction to Python",
  "teacher_name": "Dr. Smith",
  "student_name": "John Doe",
  "learning_outcomes": [...],
  "passing_score": 70.0,
  "mcqs": [...],
  "student_answers": [
    {"question_id": "q1", "answer": "A"},
    {"question_id": "q2", "answer": "B"}
  ]
}
```

### Grade Exam Response

```json
{
  "success": true,
  "raw_score": 1,
  "percentage": 50.0,
  "passed": false,
  "passing_score": 70.0,
  "total_questions": 2,
  "grading_report": {
    "total_questions": 2,
    "correct_answers": 1,
    "incorrect_answers": 1,
    "raw_score": 1,
    "percentage": 50.0,
    "passed": false,
    "question_results": [...]
  },
  "certificate_text": null
}
```

## Deployment Architecture

### Development

```
Laravel: php artisan serve (port 8000)
AI Service: uvicorn main:app --reload (port 8000)
```

### Production

```
┌─────────────────┐
│  Load Balancer  │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐ ┌────────┐
│Laravel │ │Laravel │
│Server 1│ │Server 2│
└───┬────┘ └───┬────┘
    │          │
    └────┬─────┘
         │
         ▼
┌─────────────────┐
│  AI Service     │
│  (Python)       │
│  Multiple       │
│  Workers        │
└─────────────────┘
```

## Security Considerations

1. **API Keys**: Stored in environment variables, never committed
2. **CORS**: Configured for Laravel origin (adjust for production)
3. **Input Validation**: Pydantic models validate all inputs
4. **Error Messages**: Don't expose sensitive information
5. **Rate Limiting**: Should be added in production
6. **Authentication**: Should be added for production use

## Performance Characteristics

- **Exam Generation**: 30-60 seconds (LLM processing time)
- **Grading**: < 1 second (local computation)
- **Certificate Generation**: 5-10 seconds (LLM processing, if passed)
- **Health Check**: < 100ms

## Extension Points

1. **Database Integration**: Store exams, results, certificates
2. **PDF Generation**: Convert certificate text to PDF
3. **Email Integration**: Send certificates via email
4. **Analytics**: Track student performance, question difficulty
5. **Question Bank**: Pre-generate and cache questions
6. **Multi-language**: Support multiple languages for questions
7. **Question Types**: Extend beyond multiple-choice
8. **Adaptive Testing**: Adjust difficulty based on performance

## Monitoring & Observability

- **Health Endpoint**: `/health` for service status
- **Error Logging**: Structured error responses
- **Performance**: Track LLM response times
- **Usage Metrics**: Track exam generation and grading counts

## Testing Strategy

1. **Unit Tests**: Test each LangGraph node independently
2. **Integration Tests**: Test FastAPI endpoints
3. **E2E Tests**: Test full Laravel → AI service flow
4. **Mock LLM**: Use mock responses for consistent testing
