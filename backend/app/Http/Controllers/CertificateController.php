<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    /**
     * API: List certificates for the authenticated student.
     */
    public function index()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $certificates = Certificate::query()
            ->where('student_id', $userId)
            ->orderByDesc('completion_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Certificate $cert) {
                return [
                    'id' => $cert->id,
                    'course_name' => $cert->course_name,
                    'teacher_name' => $cert->teacher_name,
                    'student_name' => $cert->student_name,
                    'certificate_code' => $cert->certificate_code,
                    'percentage' => $cert->percentage,
                    'score' => $cert->score,
                    'completion_date' => optional($cert->completion_date)->format('Y-m-d'),
                    'view_url' => url('/quiz/certificate/' . $cert->id),
                ];
            });

        return response()->json([
            'certificates' => $certificates,
        ]);
    }

    /**
     * API: Get a specific certificate for the authenticated student.
     */
    public function show($certificateId)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $certificate = Certificate::where('id', $certificateId)
            ->where('student_id', $userId)
            ->first();

        if (!$certificate) {
            return response()->json(['error' => 'Certificate not found.'], 404);
        }

        return response()->json([
            'certificate' => [
                'id' => $certificate->id,
                'course_name' => $certificate->course_name,
                'teacher_name' => $certificate->teacher_name,
                'student_name' => $certificate->student_name,
                'certificate_code' => $certificate->certificate_code,
                'percentage' => $certificate->percentage,
                'score' => $certificate->score,
                'completion_date' => optional($certificate->completion_date)->format('F j, Y'),
                'certificate_text' => $certificate->certificate_text,
            ],
        ]);
    }

    /**
     * Web: Certificate view shell (data loads via API).
     */
    public function showPage($certificateId)
    {
        return view('quiz.certificate', [
            'certificateId' => (int) $certificateId,
        ]);
    }
}
