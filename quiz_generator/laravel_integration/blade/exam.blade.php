<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam - Quiz Generator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 2.5rem;
        }
        
        .header {
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .exam-info {
            color: #666;
            font-size: 0.95rem;
        }
        
        .exam-info strong {
            color: #333;
        }
        
        .question {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .question-number {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .question-text {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .choices {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .choice {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .choice:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .choice input[type="radio"] {
            margin-right: 0.75rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .choice label {
            flex: 1;
            cursor: pointer;
            color: #333;
        }
        
        .learning-outcome-badge {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: #e0e7ff;
            color: #4338ca;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 1rem;
        }
        
        .btn-submit:hover {
            background: #5568d3;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .required-note {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $examData['course_name'] }}</h1>
            <div class="exam-info">
                <strong>Student:</strong> {{ $examData['student_name'] }} | 
                <strong>Instructor:</strong> {{ $examData['teacher_name'] }} | 
                <strong>Questions:</strong> {{ count($examData['mcqs']) }} | 
                <strong>Passing Score:</strong> {{ $examData['passing_score'] }}%
            </div>
        </div>
        
        @if($errors->any())
            <div class="alert alert-error">
                <ul style="margin-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <p class="required-note">Please answer all questions. All questions are required.</p>
        
        <form method="POST" action="{{ route('quiz.grade') }}" id="exam-form">
            @csrf
            
            @foreach($examData['mcqs'] as $index => $mcq)
                <div class="question">
                    <div class="question-number">Question {{ $index + 1 }}</div>
                    <div class="question-text">{{ $mcq['question'] }}</div>
                    
                    <div class="choices">
                        @foreach(['A', 'B', 'C', 'D'] as $choice)
                            <div class="choice">
                                <input type="radio" 
                                       id="q{{ $mcq['id'] }}_{{ $choice }}" 
                                       name="answers[{{ $mcq['id'] }}]" 
                                       value="{{ $choice }}" 
                                       required>
                                <label for="q{{ $mcq['id'] }}_{{ $choice }}">
                                    <strong>{{ $choice }}.</strong> {{ $mcq['choices'][$choice] }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    
                    @if(isset($mcq['learning_outcome']) && $mcq['learning_outcome'])
                        <span class="learning-outcome-badge">
                            Learning Outcome: {{ $mcq['learning_outcome'] }}
                        </span>
                    @endif
                </div>
            @endforeach
            
            <button type="submit" class="btn-submit">Submit Exam</button>
        </form>
    </div>
    
    <script>
        // Prevent form submission if not all questions are answered
        document.getElementById('exam-form').addEventListener('submit', function(e) {
            const totalQuestions = {{ count($examData['mcqs']) }};
            const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
            
            if (answeredQuestions < totalQuestions) {
                e.preventDefault();
                alert('Please answer all questions before submitting.');
                return false;
            }
        });
    </script>
</body>
</html>
