<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Results - Quiz Generator</title>
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
        
        h1 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        
        .score-card {
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            margin-bottom: 2rem;
        }
        
        .score-percentage {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .score-label {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 1rem;
            font-size: 1.1rem;
        }
        
        .status-passed {
            background: #10b981;
            color: white;
        }
        
        .status-failed {
            background: #ef4444;
            color: white;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .question-result {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .question-result.correct {
            background: #f0fdf4;
            border-color: #10b981;
        }
        
        .question-result.incorrect {
            background: #fef2f2;
            border-color: #ef4444;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .question-text {
            font-weight: 500;
            color: #333;
        }
        
        .result-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .result-badge.correct {
            background: #10b981;
            color: white;
        }
        
        .result-badge.incorrect {
            background: #ef4444;
            color: white;
        }
        
        .answer-details {
            margin-top: 0.75rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .answer-details strong {
            color: #333;
        }
        
        .certificate {
            margin-top: 2rem;
            padding: 2rem;
            background: #fefce8;
            border: 2px solid #facc15;
            border-radius: 12px;
        }
        
        .certificate h2 {
            color: #854d0e;
            margin-bottom: 1rem;
        }
        
        .certificate-content {
            color: #333;
            line-height: 1.8;
            white-space: pre-line;
            font-size: 1.05rem;
        }
        
        .btn-home {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 2rem;
            transition: background 0.3s;
        }
        
        .btn-home:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Exam Results</h1>
        
        <div class="score-card">
            <div class="score-percentage">{{ number_format($results['percentage'], 1) }}%</div>
            <div class="score-label">Your Score</div>
            <div class="status-badge {{ $results['passed'] ? 'status-passed' : 'status-failed' }}">
                {{ $results['passed'] ? 'PASSED' : 'FAILED' }}
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value">{{ $results['raw_score'] }}</div>
                <div class="stat-label">Correct Answers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $results['total_questions'] - $results['raw_score'] }}</div>
                <div class="stat-label">Incorrect Answers</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $results['total_questions'] }}</div>
                <div class="stat-label">Total Questions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $results['passing_score'] }}%</div>
                <div class="stat-label">Passing Score</div>
            </div>
        </div>
        
        <h2 style="margin-bottom: 1rem; color: #333;">Question Details</h2>
        
        @if(isset($results['grading_report']['question_results']))
            @foreach($results['grading_report']['question_results'] as $result)
                <div class="question-result {{ $result['is_correct'] ? 'correct' : 'incorrect' }}">
                    <div class="question-header">
                        <div class="question-text">{{ $result['question'] }}</div>
                        <div class="result-badge {{ $result['is_correct'] ? 'correct' : 'incorrect' }}">
                            {{ $result['is_correct'] ? '✓ Correct' : '✗ Incorrect' }}
                        </div>
                    </div>
                    <div class="answer-details">
                        <strong>Your Answer:</strong> {{ $result['student_answer'] }}<br>
                        <strong>Correct Answer:</strong> {{ $result['correct_answer'] }}<br>
                        @if(isset($result['learning_outcome']))
                            <strong>Learning Outcome:</strong> {{ $result['learning_outcome'] }}
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
        
        @if($results['passed'] && isset($results['certificate_text']))
            <div class="certificate">
                <h2>🎓 Certificate of Completion</h2>
                <div class="certificate-content">{{ $results['certificate_text'] }}</div>
                @if(isset($results['completion_date']))
                    <p style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
                        <strong>Date:</strong> {{ $results['completion_date'] }}
                    </p>
                @endif
            </div>
        @endif
        
        <a href="{{ route('quiz.create') }}" class="btn-home">Create New Exam</a>
    </div>
</body>
</html>
