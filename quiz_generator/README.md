# AI Quiz Generator - Production-Ready LangGraph System

A production-ready AI multi-agent system built with LangGraph that integrates with Laravel (Blade) applications. The system generates multiple-choice questions based on learning outcomes, grades student submissions, and generates certificates for passing students.

## 🏗️ Architecture

- **Backend**: Laravel (PHP) - Handles HTTP, sessions, UI
- **AI Service**: Python (FastAPI + LangGraph) - Handles AI/LLM operations
- **Frontend**: Blade Templates - User interface
- **Communication**: REST API (HTTP/JSON)

## 📋 Features

- ✅ **MCQ Generation**: AI-powered question generation based on learning outcomes
- ✅ **Validation**: Comprehensive answer validation before grading
- ✅ **Automated Grading**: Instant scoring with detailed reports
- ✅ **Certificate Generation**: Automatic certificate generation for passing students
- ✅ **Human-in-the-Loop**: Web-based interaction via Blade forms
- ✅ **Type-Safe State**: Pydantic models for state management
- ✅ **Conditional Routing**: Smart workflow control based on results

## 🚀 Quick Start

### Prerequisites

- Python 3.11+
- PHP 8.1+ with Laravel
- OpenAI API key

### 1. Setup Python AI Service

```bash
cd ai_service
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# Set API key
export OPENAI_API_KEY=your-openai-api-key-here

# Test configuration
python test_config.py

# Start the service
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

The service will be available at `http://localhost:8000`

**📖 See `SETUP.md` for complete setup instructions.**

### 2. Setup Laravel Integration

1. Copy files from `laravel_integration/` to your Laravel project:
   - `QuizController.php` → `app/Http/Controllers/`
   - Routes → Add to `routes/web.php`
   - Blade templates → `resources/views/quiz/`

2. Configure `.env`:
```env
AI_SERVICE_URL=http://localhost:8000
```

3. Install HTTP client (if not already installed):
```bash
composer require guzzlehttp/guzzle
# Or use Laravel's built-in Http facade
```

### 3. Test the System

1. Start Python service: `python ai_service/main.py`
2. Start Laravel: `php artisan serve`
3. Visit: `http://localhost:8000/quiz/setup`
4. Fill in course details and generate exam

## 📁 Project Structure

```
quiz_generator/
├── ai_service/              # Python AI microservice
│   ├── __init__.py
│   ├── main.py              # FastAPI application
│   ├── state.py             # Pydantic state models
│   ├── nodes.py             # LangGraph node implementations
│   ├── graph.py             # LangGraph workflow definitions
│   └── requirements.txt     # Python dependencies
│
├── laravel_integration/     # Laravel integration code
│   ├── QuizController.php   # Main controller
│   ├── routes.php           # Route definitions
│   └── blade/               # Blade templates
│       ├── setup.blade.php  # Course setup form
│       ├── exam.blade.php   # Exam display
│       └── results.blade.php # Results display
│
├── example_payloads.json    # Example API payloads
├── ARCHITECTURE.md          # Detailed architecture docs
└── README.md                # This file
```

## 🔌 API Endpoints

### POST /generate-exam

Generate MCQs based on learning outcomes.

**Request**:
```json
{
  "course_name": "Introduction to Machine Learning",
  "teacher_name": "Dr. Jane Smith",
  "student_name": "John Doe",
  "learning_outcomes": [
    "Understand supervised learning",
    "Differentiate classification and regression"
  ],
  "passing_score": 75.0
}
```

**Response**:
```json
{
  "success": true,
  "mcqs": [...],
  "total_questions": 4,
  "message": "Successfully generated 4 questions"
}
```

### POST /grade-exam

Grade student answers and generate certificate if passed.

**Request**:
```json
{
  "course_name": "...",
  "teacher_name": "...",
  "student_name": "...",
  "learning_outcomes": [...],
  "passing_score": 75.0,
  "mcqs": [...],
  "student_answers": [
    {"question_id": "q1", "answer": "A"}
  ]
}
```

**Response**:
```json
{
  "success": true,
  "raw_score": 4,
  "percentage": 100.0,
  "passed": true,
  "grading_report": {...},
  "certificate_text": "...",
  "completion_date": "..."
}
```

See `example_payloads.json` for complete examples.

## 🔄 Workflow

### Exam Generation Flow

```
1. User fills course setup form (Blade)
2. Laravel → POST /generate-exam → FastAPI
3. LangGraph: MCQ Generation Agent
4. FastAPI → Returns MCQs → Laravel
5. Laravel stores in session, displays exam
```

### Grading Flow

```
1. Student submits answers (Blade form)
2. Laravel → POST /grade-exam → FastAPI
3. LangGraph: Supervisor → Grading → Certificate (if passed)
4. FastAPI → Returns results → Laravel
5. Laravel displays results and certificate
```

## 🧪 Testing

### Test Python Service

```bash
# Health check
curl http://localhost:8000/health

# Generate exam
curl -X POST http://localhost:8000/generate-exam \
  -H "Content-Type: application/json" \
  -d @example_payloads.json
```

### Test Laravel Integration

1. Visit setup page: `http://localhost:8000/quiz/setup`
2. Fill form and submit
3. Complete exam
4. View results

## 🔧 Configuration

### Environment Variables

**Python Service**:
- `OPENAI_API_KEY`: Your OpenAI API key (required)

**Laravel**:
- `AI_SERVICE_URL`: URL of Python service (default: `http://localhost:8000`)

### Customization

- **LLM Model**: Change in `nodes.py` (`ChatOpenAI(model="gpt-4")`)
- **Temperature**: Adjust in `nodes.py` (`temperature=0.7`)
- **Passing Score**: Set per exam in course setup
- **Styling**: Modify Blade templates CSS

## 📚 Documentation

- **Setup Guide**: See `SETUP.md` for complete setup instructions
- **Architecture**: See `ARCHITECTURE.md` for detailed system design
- **Example Payloads**: See `ai_service/example_payloads.json` for API examples
- **Code Comments**: Inline documentation in all files

## 🛠️ Development

### Adding New Nodes

1. Create node function in `nodes.py`
2. Add node to graph in `graph.py`
3. Update state model if needed in `state.py`

### Extending Functionality

- **PDF Generation**: Add PDF library, extend certificate node
- **Database**: Add models, store exams/results
- **Email**: Integrate email service in Laravel
- **Analytics**: Add tracking endpoints

## 🚨 Production Considerations

1. **Security**:
   - Configure CORS properly
   - Use environment variables for secrets
   - Implement rate limiting
   - Add authentication

2. **Performance**:
   - Add caching
   - Use async operations
   - Consider queue system

3. **Monitoring**:
   - Add structured logging
   - Health checks
   - Error tracking

4. **Deployment**:
   - Use Docker for Python service
   - Deploy separately from Laravel
   - Use load balancer

## 📝 License

This is a production-ready implementation. Customize as needed for your use case.

## 🤝 Support

For issues or questions:
1. Check `ARCHITECTURE.md` for design details
2. Review example payloads in `example_payloads.json`
3. Check code comments for implementation details

---

**Built with**: LangGraph, FastAPI, Laravel, OpenAI GPT-4
