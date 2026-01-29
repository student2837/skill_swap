# Setup Guide - AI Quiz Generator

Complete setup instructions for the AI Quiz Generator system with OpenAI integration.

## Prerequisites

- **Python 3.11+** (check with `python3 --version`)
- **PHP 8.1+** with Composer (for Laravel integration)
- **OpenAI API Key**: Get one at https://platform.openai.com/api-keys
- **Internet connection** (for API calls)

## Quick Start (5 Minutes)

### Step 1: Setup Python AI Service

```bash
# Navigate to AI service directory
cd ai_service

# Create virtual environment (if not exists)
python3 -m venv venv

# Activate virtual environment
source venv/bin/activate  # On Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Set API key
export OPENAI_API_KEY=your-openai-api-key-here

# Test configuration
python test_config.py

# Start the service
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

The service will be available at `http://localhost:8000`

### Step 2: Verify Service is Running

Open a new terminal and test:

```bash
# Health check
curl http://localhost:8000/health

# Or open in browser
open http://localhost:8000/docs  # macOS
# Or visit: http://localhost:8000/docs
```

Expected response:
```json
{
  "status": "healthy",
  "service": "AI Quiz Generator",
  "version": "1.0.0"
}
```

## Detailed Setup

### Python AI Service Setup

#### 1.1 Create Virtual Environment

```bash
cd ai_service
python3 -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
```

#### 1.2 Install Dependencies

```bash
pip install --upgrade pip
pip install -r requirements.txt
```

**Dependencies installed**:
- `fastapi`: Web framework
- `uvicorn`: ASGI server
- `langgraph`: Workflow orchestration
- `langchain`: LLM integration
- `langchain-openai`: OpenAI API integration
- `pydantic`: Data validation
- And more...

#### 1.3 Configure API Key

**Option A: Environment Variable (Recommended)**
```bash
export OPENAI_API_KEY=your-openai-api-key-here
```

**Option B: Create .env File**
```bash
cat > .env << EOF
OPENAI_API_KEY=your-openai-api-key-here
HOST=0.0.0.0
PORT=8000
EOF
```

**Option C: Add to Shell Profile**
```bash
# Add to ~/.zshrc or ~/.bashrc
echo 'export OPENAI_API_KEY=your-openai-api-key-here' >> ~/.zshrc
source ~/.zshrc
```

#### 1.4 Test Configuration

```bash
python test_config.py
```

Expected output:
```
Testing OpenAI API Configuration...
--------------------------------------------------
✅ API Key found: your-openai-api-key-here
✅ LLM initialized successfully

Testing API connection...
✅ API Response: Hello, OpenAI is working!...
🎉 OpenAI configuration is working correctly!
```

#### 1.5 Start the Service

**Development (with auto-reload)**:
```bash
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

**Production**:
```bash
uvicorn main:app --host 0.0.0.0 --port 8000 --workers 4
```

**Using the startup script**:
```bash
./run.sh
```

### Laravel Integration Setup

#### 2.1 Copy Files to Laravel Project

```bash
# Copy controller
cp laravel_integration/QuizController.php /path/to/laravel/app/Http/Controllers/

# Copy routes (add to routes/web.php)
# Copy contents from laravel_integration/routes.php

# Copy Blade templates
mkdir -p /path/to/laravel/resources/views/quiz
cp laravel_integration/blade/*.blade.php /path/to/laravel/resources/views/quiz/
```

#### 2.2 Configure Laravel Environment

Add to your Laravel `.env`:

```env
AI_SERVICE_URL=http://localhost:8000
```

#### 2.3 Register Routes

Add to `routes/web.php`:

```php
use App\Http\Controllers\QuizController;

Route::get('/quiz/setup', [QuizController::class, 'create'])->name('quiz.create');
Route::post('/quiz/generate', [QuizController::class, 'generateExam'])->name('quiz.generate');
Route::get('/quiz', [QuizController::class, 'show'])->name('quiz.show');
Route::post('/quiz/grade', [QuizController::class, 'gradeExam'])->name('quiz.grade');
Route::get('/quiz/results', [QuizController::class, 'results'])->name('quiz.results');
```

#### 2.4 Start Laravel

```bash
cd /path/to/laravel
php artisan serve
```

Visit: `http://localhost:8000/quiz/setup`

## Testing the System

### Test 1: Health Check

```bash
curl http://localhost:8000/health
```

### Test 2: Generate Exam

```bash
curl -X POST http://localhost:8000/generate-exam \
  -H "Content-Type: application/json" \
  -d '{
    "course_name": "Test Course",
    "teacher_name": "Dr. Test",
    "student_name": "Test Student",
    "learning_outcomes": [
      "Understand basic concepts",
      "Apply knowledge"
    ],
    "passing_score": 70.0
  }'
```

### Test 3: Full Flow Test

```bash
cd ai_service
source venv/bin/activate
python test_full_flow.py
```

This tests: health check → generate exam → grade exam → certificate

### Test 4: Laravel Integration

1. Start Python service: `uvicorn main:app --host 0.0.0.0 --port 8000`
2. Start Laravel: `php artisan serve`
3. Visit: `http://localhost:8000/quiz/setup`
4. Fill form and generate exam
5. Complete exam and view results

## Configuration Options

### Environment Variables

**Python Service**:
- `OPENAI_API_KEY`: Your OpenAI API key (required)
- `HOST`: Server host (default: 0.0.0.0)
- `PORT`: Server port (default: 8000)

**Laravel**:
- `AI_SERVICE_URL`: URL of Python service (default: http://localhost:8000)
- `AI_SERVICE_TIMEOUT`: Request timeout in seconds (default: 60)

### Customization

**Change LLM Model**:
Edit `ai_service/nodes.py`:
```python
llm = ChatOpenAI(
    model="gpt-4",          # Options: gpt-4, gpt-4-turbo, gpt-3.5-turbo
    temperature=0.7,        # Adjust creativity (0.0-1.0)
    api_key=os.getenv("OPENAI_API_KEY")
)
```

**Change Port**:
```bash
uvicorn main:app --host 0.0.0.0 --port 8001
# Update Laravel .env: AI_SERVICE_URL=http://localhost:8001
```

**Adjust Timeout**:
Edit `laravel_integration/QuizController.php`:
```php
$response = Http::timeout(120)->post(...);  // 120 seconds
```

## Troubleshooting

### Issue: "Module not found"

**Solution**:
```bash
cd ai_service
source venv/bin/activate
pip install -r requirements.txt
```

### Issue: "API key not found"

**Solution**:
```bash
export OPENAI_API_KEY=your-openai-api-key-here
# Verify: echo $OPENAI_API_KEY
```

### Issue: "Connection refused"

**Solution**:
- Make sure Python service is running
- Check port 8000 is not in use: `lsof -i :8000`
- Try different port: `uvicorn main:app --port 8001`

### Issue: "Timeout" or "Slow response"

**Solution**:
- Exam generation takes 30-60 seconds (normal)
- Increase timeout in Laravel controller
- Check internet connection
- Verify OpenAI API is accessible

### Issue: "Import errors"

**Solution**:
- Make sure you're in `ai_service` directory
- Virtual environment is activated
- Run: `pip install -r requirements.txt`

### Issue: "Validation failed"

**Solution**:
- Ensure all questions are answered
- Answers must be A, B, C, or D
- Check validation errors in response

## Production Deployment

### Python Service

**Option 1: Uvicorn with Workers**
```bash
uvicorn main:app --host 0.0.0.0 --port 8000 --workers 4
```

**Option 2: PM2 Process Manager**
```bash
pm2 start "uvicorn main:app --host 0.0.0.0 --port 8000" --name quiz-service
pm2 save
pm2 startup
```

**Option 3: Docker**
```dockerfile
FROM python:3.11-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt
COPY . .
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
```

### Laravel

1. Configure production URL:
   ```env
   AI_SERVICE_URL=https://your-ai-service.com
   ```

2. Set up proper CORS in `main.py`:
   ```python
   allow_origins=["https://your-laravel-app.com"]
   ```

3. Add error handling and logging

4. Set up monitoring and alerts

## Next Steps

1. ✅ Service is running
2. ✅ Test with `test_full_flow.py`
3. ✅ Integrate with Laravel frontend
4. Customize for your needs
5. Deploy to production
6. Add monitoring and logging

## Support

For issues:
1. Check service logs
2. Verify environment variables
3. Test endpoints with curl/Postman
4. Review error messages in responses
5. Check `ARCHITECTURE.md` for system design details
