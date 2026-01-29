<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class QuizController extends Controller
{
    /**
     * AI Service base URL
     * Configure this in your .env file: AI_SERVICE_URL=http://localhost:8000
     */
    private $aiServiceUrl;

    public function __construct()
    {
        $this->aiServiceUrl = env('AI_SERVICE_URL', 'http://localhost:8000');
    }

    /**
     * Show the course setup form
     */
    public function create()
    {
        return view('quiz.setup');
    }

    /**
     * Generate exam based on course setup
     * POST /quiz/generate
     */
    public function generateExam(Request $request)
    {
        $request->validate([
            'course_name' => 'required|string|max:255',
            'teacher_name' => 'required|string|max:255',
            'student_name' => 'required|string|max:255',
            'learning_outcomes' => 'required|array|min:1',
            'learning_outcomes.*' => 'required|string',
            'passing_score' => 'required|numeric|min:0|max:100',
        ]);

        try {
            // Prepare payload for AI service
            $payload = [
                'course_name' => $request->course_name,
                'teacher_name' => $request->teacher_name,
                'student_name' => $request->student_name,
                'learning_outcomes' => $request->learning_outcomes,
                'passing_score' => (float) $request->passing_score,
            ];

            // Call AI service
            $response = Http::timeout(60)->post(
                $this->aiServiceUrl . '/generate-exam',
                $payload
            );

            if (!$response->successful()) {
                Log::error('AI Service Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return back()->withErrors([
                    'error' => 'Failed to generate exam. Please try again.'
                ])->withInput();
            }

            $data = $response->json();

            if (!$data['success']) {
                return back()->withErrors([
                    'error' => $data['error'] ?? 'Failed to generate exam'
                ])->withInput();
            }

            // Store exam data in session for grading
            Session::put('exam_data', [
                'course_name' => $request->course_name,
                'teacher_name' => $request->teacher_name,
                'student_name' => $request->student_name,
                'learning_outcomes' => $request->learning_outcomes,
                'passing_score' => $request->passing_score,
                'mcqs' => $data['mcqs'],
            ]);

            // Redirect to exam view
            return redirect()->route('quiz.show');
            
        } catch (\Exception $e) {
            Log::error('Exam Generation Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => 'An error occurred while generating the exam. Please try again.'
            ])->withInput();
        }
    }

    /**
     * Display the exam to the student
     * GET /quiz
     */
    public function show()
    {
        $examData = Session::get('exam_data');

        if (!$examData) {
            return redirect()->route('quiz.create')
                ->withErrors(['error' => 'No exam found. Please generate an exam first.']);
        }

        return view('quiz.exam', compact('examData'));
    }

    /**
     * Grade the exam
     * POST /quiz/grade
     */
    public function gradeExam(Request $request)
    {
        $examData = Session::get('exam_data');

        if (!$examData) {
            return redirect()->route('quiz.create')
                ->withErrors(['error' => 'No exam found. Please generate an exam first.']);
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'required|in:A,B,C,D',
        ]);

        try {
            // Prepare student answers
            $studentAnswers = [];
            foreach ($request->answers as $questionId => $answer) {
                $studentAnswers[] = [
                    'question_id' => $questionId,
                    'answer' => $answer,
                ];
            }

            // Prepare payload for AI service
            $payload = [
                'course_name' => $examData['course_name'],
                'teacher_name' => $examData['teacher_name'],
                'student_name' => $examData['student_name'],
                'learning_outcomes' => $examData['learning_outcomes'],
                'passing_score' => $examData['passing_score'],
                'mcqs' => $examData['mcqs'],
                'student_answers' => $studentAnswers,
            ];

            // Call AI service
            $response = Http::timeout(60)->post(
                $this->aiServiceUrl . '/grade-exam',
                $payload
            );

            if (!$response->successful()) {
                Log::error('AI Service Grading Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return back()->withErrors([
                    'error' => 'Failed to grade exam. Please try again.'
                ]);
            }

            $gradingData = $response->json();

            if (!$gradingData['success']) {
                return back()->withErrors([
                    'error' => $gradingData['error'] ?? 'Grading failed',
                    'validation_errors' => $gradingData['validation_errors'] ?? []
                ]);
            }

            // Store grading results in session
            Session::put('grading_results', $gradingData);

            // Clear exam data from session
            Session::forget('exam_data');

            // Redirect to results page
            return redirect()->route('quiz.results');
            
        } catch (\Exception $e) {
            Log::error('Exam Grading Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => 'An error occurred while grading the exam. Please try again.'
            ]);
        }
    }

    /**
     * Display grading results
     * GET /quiz/results
     */
    public function results()
    {
        $results = Session::get('grading_results');

        if (!$results) {
            return redirect()->route('quiz.create')
                ->withErrors(['error' => 'No results found.']);
        }

        return view('quiz.results', compact('results'));
    }
}
