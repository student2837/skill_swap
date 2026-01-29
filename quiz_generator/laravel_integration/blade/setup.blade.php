<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Setup - Quiz Generator</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 2.5rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .learning-outcomes {
            margin-top: 0.5rem;
        }
        
        .outcome-item {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .outcome-item input {
            flex: 1;
        }
        
        .btn-remove {
            padding: 0.5rem 1rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn-remove:hover {
            background: #dc2626;
        }
        
        .btn-add {
            padding: 0.5rem 1rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .btn-add:hover {
            background: #059669;
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
        
        .help-text {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Course Setup</h1>
        <p class="subtitle">Configure your course and learning outcomes to generate an exam</p>
        
        @if($errors->any())
            <div class="alert alert-error">
                <ul style="margin-left: 1.5rem;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form method="POST" action="{{ route('quiz.generate') }}">
            @csrf
            
            <div class="form-group">
                <label for="course_name">Course Name *</label>
                <input type="text" 
                       id="course_name" 
                       name="course_name" 
                       value="{{ old('course_name') }}" 
                       required 
                       placeholder="e.g., Introduction to Machine Learning">
            </div>
            
            <div class="form-group">
                <label for="teacher_name">Teacher/Instructor Name *</label>
                <input type="text" 
                       id="teacher_name" 
                       name="teacher_name" 
                       value="{{ old('teacher_name') }}" 
                       required 
                       placeholder="e.g., Dr. Jane Smith">
            </div>
            
            <div class="form-group">
                <label for="student_name">Student Name *</label>
                <input type="text" 
                       id="student_name" 
                       name="student_name" 
                       value="{{ old('student_name') }}" 
                       required 
                       placeholder="e.g., John Doe">
            </div>
            
            <div class="form-group">
                <label for="learning_outcomes">Learning Outcomes *</label>
                <p class="help-text">Each learning outcome will be tested by at least one question</p>
                <div class="learning-outcomes" id="outcomes-container">
                    @if(old('learning_outcomes'))
                        @foreach(old('learning_outcomes') as $index => $outcome)
                            <div class="outcome-item">
                                <input type="text" 
                                       name="learning_outcomes[]" 
                                       value="{{ $outcome }}" 
                                       required 
                                       placeholder="e.g., Understand the basics of neural networks">
                                @if($index > 0)
                                    <button type="button" class="btn-remove" onclick="removeOutcome(this)">Remove</button>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="outcome-item">
                            <input type="text" 
                                   name="learning_outcomes[]" 
                                   required 
                                   placeholder="e.g., Understand the basics of neural networks">
                        </div>
                    @endif
                </div>
                <button type="button" class="btn-add" onclick="addOutcome()">+ Add Learning Outcome</button>
            </div>
            
            <div class="form-group">
                <label for="passing_score">Passing Score (%) *</label>
                <input type="number" 
                       id="passing_score" 
                       name="passing_score" 
                       value="{{ old('passing_score', 70) }}" 
                       min="0" 
                       max="100" 
                       step="0.1" 
                       required>
                <p class="help-text">Minimum percentage required to pass (0-100)</p>
            </div>
            
            <button type="submit" class="btn-submit">Generate Exam</button>
        </form>
    </div>
    
    <script>
        function addOutcome() {
            const container = document.getElementById('outcomes-container');
            const newItem = document.createElement('div');
            newItem.className = 'outcome-item';
            newItem.innerHTML = `
                <input type="text" 
                       name="learning_outcomes[]" 
                       required 
                       placeholder="e.g., Understand the basics of neural networks">
                <button type="button" class="btn-remove" onclick="removeOutcome(this)">Remove</button>
            `;
            container.appendChild(newItem);
        }
        
        function removeOutcome(button) {
            const container = document.getElementById('outcomes-container');
            if (container.children.length > 1) {
                button.parentElement.remove();
            }
        }
    </script>
</body>
</html>
