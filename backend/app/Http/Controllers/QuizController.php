<?php

namespace App\Http\Controllers;

use App\Models\SkillRequest;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
    /**
     * Base URL for the external AI quiz service.
     */
    protected string $serviceBaseUrl;

    public function __construct()
    {
        $this->serviceBaseUrl = rtrim(
            (string) config('services.ai_quiz.url', env('AI_SERVICE_URL', 'http://127.0.0.1:8000')),
            '/'
        );
    }

    /**
     * Show the course / exam setup form.
     */
    public function create()
    {
        return view('quiz.setup');
    }

    /**
     * Call the AI service to generate an exam and store it in the session.
     */
    public function generateExam(Request $request)
    {
        // Increase execution time limit for generating 20 questions (can take 60-120 seconds)
        set_time_limit(150);

        $validated = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'teacher_name' => ['required', 'string', 'max:255'],
            'student_name' => ['required', 'string', 'max:255'],
            'learning_outcomes' => ['required', 'string'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        // Split learning outcomes by lines
        $learningOutcomes = array_values(array_filter(array_map('trim', preg_split(
            '/\r\n|\r|\n/',
            $validated['learning_outcomes']
        ))));

        if (empty($learningOutcomes)) {
            return redirect()
                ->route('quiz.setup')
                ->withInput()
                ->withErrors(['learning_outcomes' => 'Please provide at least one learning outcome.']);
        }

        try {
            $response = Http::timeout((int) config('services.ai_quiz.timeout', 120))
                ->post($this->serviceBaseUrl . '/generate-exam', [
                    'course_name' => $validated['course_name'],
                    'teacher_name' => $validated['teacher_name'],
                    'student_name' => $validated['student_name'],
                    'learning_outcomes' => $learningOutcomes,
                    'passing_score' => (int) $validated['passing_score'],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return redirect()
                ->route('quiz.setup')
                ->withInput()
                ->withErrors([
                    'ai' => 'Could not contact the quiz generation service. '
                        . 'Verify it is running at ' . $this->serviceBaseUrl . '. '
                        . 'Error: ' . $e->getMessage(),
                ]);
        } catch (\Throwable $e) {
            return redirect()
                ->route('quiz.setup')
                ->withInput()
                ->withErrors([
                    'ai' => 'An error occurred while generating the exam: ' . $e->getMessage(),
                ]);
        }

        if (! $response->successful()) {
            return redirect()
                ->route('quiz.setup')
                ->withInput()
                ->withErrors([
                    'ai' => 'Quiz generation service returned an error (HTTP '
                        . $response->status() . ').',
                ]);
        }

        // Service returns questions under "mcqs" key
        $payload = $response->json();
        
        // Check for errors in response
        if (isset($payload['success']) && $payload['success'] === false) {
            return redirect()
                ->route('quiz.setup')
                ->withInput()
                ->withErrors(['ai' => $payload['error'] ?? 'Quiz generation failed.']);
        }
        
        // Handle different response formats: "mcqs" (AI service format) or "questions" (fallback)
        $questions = $payload['mcqs'] ?? $payload['questions'] ?? null;

        if (! is_array($questions) || empty($questions)) {
            $errorMsg = $payload['error'] ?? 'Quiz generation service did not return any questions.';
            if (isset($payload['message'])) {
                $errorMsg .= ' ' . $payload['message'];
            }
            return back()
                ->withInput()
                ->withErrors(['ai' => $errorMsg]);
        }
        
        // Ensure questions are properly formatted as arrays
        $formattedQuestions = [];
        foreach ($questions as $q) {
            if (is_array($q)) {
                $formattedQuestions[] = $q;
            } else {
                // Convert object to array
                $formattedQuestions[] = (array) $q;
            }
        }
        
        $questions = $formattedQuestions;

        $exam = [
            'course_name' => $validated['course_name'],
            'teacher_name' => $validated['teacher_name'],
            'student_name' => $validated['student_name'],
            'learning_outcomes' => $learningOutcomes,
            'passing_score' => (int) $validated['passing_score'],
            'questions' => $questions,
        ];

        // Store exam in session; clear any previous results
        session([
            'current_exam' => $exam,
            'exam_results' => null,
        ]);

        return redirect()->route('quiz.exam');
    }

    /**
     * Show the generated exam to the student.
     */
    public function show()
    {
        $exam = session('current_exam');
        $results = session('exam_results');

        if ($results) {
            return redirect()
                ->route('quiz.results')
                ->with('status', 'Quiz already submitted. You cannot edit your answers.');
        }

        if (! $exam) {
            return redirect()
                ->route('quiz.setup')
                ->with('status', 'Start by configuring a course to generate an exam.');
        }

        return response()->view('quiz.exam', [
            'exam' => $exam,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    /**
     * Send the student's answers to the AI service for grading.
     */
    public function gradeExam(Request $request)
    {
        $exam = session('current_exam');
        $results = session('exam_results');

        if ($results) {
            return redirect()
                ->route('quiz.results')
                ->with('status', 'Quiz already submitted. You cannot edit your answers.');
        }

        if (! $exam) {
            return redirect()
                ->route('quiz.setup')
                ->with('status', 'Exam session expired. Please generate a new exam.');
        }

        $answers = $request->input('answers', []);

        // Ensure answers is an array keyed by question index
        if (! is_array($answers)) {
            $answers = [];
        }

        // Transform answers from form format [question_id => answer] 
        // to API format [{"question_id": "q1", "answer": "A"}, ...]
        $studentAnswers = [];
        foreach ($answers as $questionId => $answer) {
            $studentAnswers[] = [
                'question_id' => (string) $questionId,
                'answer' => (string) $answer,
            ];
        }

        try {
            $response = Http::timeout((int) config('services.ai_quiz.timeout', 120))
                ->post($this->serviceBaseUrl . '/grade-exam', [
                    'course_name' => $exam['course_name'],
                    'teacher_name' => $exam['teacher_name'],
                    'student_name' => $exam['student_name'],
                    'learning_outcomes' => $exam['learning_outcomes'],
                    'passing_score' => (float) $exam['passing_score'],
                    'mcqs' => $exam['questions'], // AI service expects 'mcqs' not 'questions'
                    'student_answers' => $studentAnswers,
                ]);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'ai' => 'Could not contact the grading service. '
                        . 'Verify it is running at ' . $this->serviceBaseUrl . '.',
                ]);
        }

        if (! $response->successful()) {
            $errorDetails = $response->json();
            $errorMessage = 'Grading service returned an error (HTTP ' . $response->status() . ').';
            
            // Try to extract more detailed error information from FastAPI validation errors
            if (isset($errorDetails['detail'])) {
                if (is_array($errorDetails['detail'])) {
                    // FastAPI validation errors are arrays
                    $validationErrors = [];
                    foreach ($errorDetails['detail'] as $error) {
                        if (isset($error['msg']) && isset($error['loc'])) {
                            $field = end($error['loc']);
                            $validationErrors[] = $field . ': ' . $error['msg'];
                        }
                    }
                    if (!empty($validationErrors)) {
                        $errorMessage .= ' ' . implode('; ', $validationErrors);
                    }
                } else {
                    $errorMessage .= ' ' . $errorDetails['detail'];
                }
            } elseif (isset($errorDetails['message'])) {
                $errorMessage .= ' ' . $errorDetails['message'];
            }
            
            return back()
                ->withInput()
                ->withErrors(['ai' => $errorMessage]);
        }

        $results = $response->json();

        if (! is_array($results)) {
            return back()
                ->withInput()
                ->withErrors(['ai' => 'Grading service returned an invalid response.']);
        }

        if (!empty($exam['request_id'])) {
            SkillRequest::where('id', $exam['request_id'])
                ->update(['quiz_completed_at' => now()]);
        }

        $passed = $results['passed'] ?? ($results['pass'] ?? false);
        if (!empty($exam['request_id']) && $passed) {
            $request = SkillRequest::with(['skill.user', 'student'])->find($exam['request_id']);
            if ($request && $request->student && $request->skill && $request->skill->user) {
                Certificate::firstOrCreate(
                    ['request_id' => $request->id],
                    [
                        'skill_id' => $request->skill->id,
                        'student_id' => $request->student->id,
                        'teacher_id' => $request->skill->user->id,
                        'course_name' => $request->skill->title,
                        'teacher_name' => $request->skill->user->name,
                        'student_name' => $request->student->name,
                        'certificate_code' => strtoupper(substr(md5($request->id . $request->student->id . now()), 0, 12)),
                        'score' => $results['raw_score'] ?? null,
                        'percentage' => $results['percentage'] ?? null,
                        'passed' => true,
                        'certificate_text' => $results['certificate_text'] ?? null,
                        'completion_date' => now()->toDateString(),
                    ]
                );
            }
        }

        session(['exam_results' => $results]);

        return redirect()->route('quiz.results');
    }

    /**
     * Show the grading results and certificate (if any).
     */
    public function results()
    {
        $exam = session('current_exam');
        $results = session('exam_results');

        if (! $exam || ! $results) {
            return redirect()
                ->route('quiz.setup')
                ->with('status', 'No exam results found. Generate and complete an exam first.');
        }

        return response()->view('quiz.results', [
            'exam' => $exam,
            'results' => $results,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    /**
     * Generate quiz from a completed request and store in session.
     * Used by both web redirect and API flows. Throws on error.
     *
     * @param int $requestId
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    protected function generateAndStoreExamFromRequest($requestId): void
    {
        $request = SkillRequest::with(['skill.user', 'student'])->findOrFail($requestId);

        if ($request->status !== 'completed') {
            throw new \Exception('Quiz can only be generated for completed courses.');
        }

        if ($request->quiz_completed_at) {
            throw new \Exception('Quiz already completed. You cannot retake it.');
        }

        if ($request->quiz_started_at) {
            throw new \Exception('Quiz already started. Please finish the quiz.');
        }

        $skill = $request->skill;
        $student = $request->student;
        $teacher = $skill->user;

        if (!$skill || !$student || !$teacher) {
            throw new \Exception('Missing required information to generate quiz.');
        }

        $learningOutcomesText = $skill->what_youll_learn ?? '';
        if (empty(trim($learningOutcomesText))) {
            throw new \Exception('Cannot generate quiz: Skill has no learning outcomes defined.');
        }

        $learningOutcomes = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $learningOutcomesText)),
            fn($item) => !empty($item)
        ));

        if (empty($learningOutcomes)) {
            throw new \Exception('Cannot generate quiz: No valid learning outcomes found.');
        }

        $passingScore = 70;
        set_time_limit(150);

        try {
            $response = Http::timeout((int) config('services.ai_quiz.timeout', 120))
                ->post($this->serviceBaseUrl . '/generate-exam', [
                    'course_name' => $skill->title,
                    'teacher_name' => $teacher->name,
                    'student_name' => $student->name,
                    'learning_outcomes' => $learningOutcomes,
                    'passing_score' => $passingScore,
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Quiz generation service connection error', ['request_id' => $requestId, 'error' => $e->getMessage()]);
            throw new \Exception('Could not contact the quiz generation service. Please try again later.');
        } catch (\Throwable $e) {
            Log::error('Quiz generation error', ['request_id' => $requestId, 'error' => $e->getMessage()]);
            throw new \Exception('An error occurred while generating the exam: ' . $e->getMessage());
        }

        if (!$response->successful()) {
            Log::error('Quiz generation service returned error', [
                'request_id' => $requestId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new \Exception('Quiz generation service returned an error (HTTP ' . $response->status() . ').');
        }

        $payload = $response->json();
        if (isset($payload['success']) && $payload['success'] === false) {
            throw new \Exception($payload['error'] ?? 'Quiz generation failed.');
        }

        $questions = $payload['mcqs'] ?? $payload['questions'] ?? null;
        if (!is_array($questions) || empty($questions)) {
            $errorMsg = $payload['error'] ?? 'Quiz generation service did not return any questions.';
            if (isset($payload['message'])) {
                $errorMsg .= ' ' . $payload['message'];
            }
            throw new \Exception($errorMsg);
        }

        $formattedQuestions = [];
        foreach ($questions as $q) {
            $formattedQuestions[] = is_array($q) ? $q : (array) $q;
        }

        $request->update(['quiz_started_at' => now()]);

        $exam = [
            'request_id' => $requestId,
            'course_name' => $skill->title,
            'teacher_name' => $teacher->name,
            'student_name' => $student->name,
            'learning_outcomes' => $learningOutcomes,
            'passing_score' => $passingScore,
            'questions' => $formattedQuestions,
        ];

        session([
            'current_exam' => $exam,
            'exam_results' => null,
        ]);
    }

    /**
     * Automatically generate a quiz from a completed skill request.
     * This is called automatically when a teacher marks a course as completed.
     *
     * @param int $requestId The SkillRequest ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateFromRequest($requestId)
    {
        try {
            $this->generateAndStoreExamFromRequest($requestId);
            return redirect()->route('quiz.exam')
                ->with('success', 'Quiz generated successfully! Please complete the exam.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Request not found.');
        } catch (\Throwable $e) {
            Log::error('Unexpected error in generateFromRequest', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * API: Prepare quiz for a completed request (authenticated via Bearer token).
     * Generates quiz, stores in session, returns redirect URL so the SPA can redirect.
     *
     * @param int $requestId
     * @return \Illuminate\Http\JsonResponse
     */
    public function accessQuizForRequestApi($requestId)
    {
        try {
            $request = SkillRequest::with(['skill.user', 'student'])->findOrFail($requestId);

            if ($request->status !== 'completed') {
                return response()->json(['error' => 'Quiz is only available for completed courses.'], 400);
            }

            if ($request->quiz_completed_at) {
                return response()->json(['error' => 'Quiz already completed. You cannot retake it.'], 400);
            }

            if ($request->quiz_started_at) {
                return response()->json(['error' => 'Quiz already started. Please finish the quiz.'], 400);
            }

            $currentUserId = Auth::id();
            if (!$currentUserId || $currentUserId !== $request->student_id) {
                return response()->json(['error' => 'You can only access quizzes for your own completed courses.'], 403);
            }

            // Always generate and store the quiz for this request so the exam matches the skill they clicked (e.g. Python vs Graphic Design).
            // Skipping generation when session already had an exam was causing the wrong quiz to show when switching between completed requests.
            $this->generateAndStoreExamFromRequest($requestId);

            return response()->json([
                'redirect_url' => route('quiz.exam'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Request not found.'], 404);
        } catch (\Throwable $e) {
            Log::error('Error in accessQuizForRequestApi', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Allow students to access quiz for their completed request.
     * This generates the quiz if it doesn't exist, or shows existing quiz.
     * 
     * @param int $requestId The SkillRequest ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accessQuizForRequest($requestId)
    {
        try {
            // Load the request with relationships
            $request = SkillRequest::with(['skill.user', 'student'])->findOrFail($requestId);
            
            // Verify the request is completed
            if ($request->status !== 'completed') {
                return redirect()
                    ->route('requests')
                    ->with('error', 'Quiz is only available for completed courses.');
            }

            // Get current user ID (works with both session and API token auth)
            $currentUserId = Auth::id();
            
            // If not authenticated via session, try to get from request (for API token users)
            if (!$currentUserId && request()->user()) {
                $currentUserId = request()->user()->id;
            }

            // Verify the current user is the student
            if (!$currentUserId || $currentUserId !== $request->student_id) {
                return redirect()
                    ->route('requests')
                    ->with('error', 'You can only access quizzes for your own completed courses.');
            }

            if ($request->quiz_completed_at) {
                return redirect()
                    ->route('requests')
                    ->with('error', 'Quiz already completed. You cannot retake it.');
            }

            if ($request->quiz_started_at) {
                return redirect()
                    ->route('requests')
                    ->with('error', 'Quiz already started. Please finish the quiz.');
            }

            // Check if there's already a quiz in session for this request
            $currentExam = session('current_exam');
            if ($currentExam && isset($currentExam['request_id']) && $currentExam['request_id'] == $requestId) {
                // Quiz already exists in session, redirect to exam
                return redirect()->route('quiz.exam')
                    ->with('info', 'Continuing your quiz.');
            }

            // Generate the quiz for the student
            return $this->generateFromRequest($requestId);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('requests')
                ->with('error', 'Request not found.');
        } catch (\Throwable $e) {
            Log::error('Error accessing quiz for request', [
                'request_id' => $requestId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()
                ->route('requests')
                ->with('error', 'An error occurred while accessing the quiz. Please try again.');
        }
    }

    /**
     * Show warning + loading screen before starting the quiz.
     */
    public function startQuizForRequest($requestId)
    {
        return view('quiz.start', [
            'requestId' => (int) $requestId,
        ]);
    }
}

