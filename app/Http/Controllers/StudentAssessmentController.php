<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\PrinciplesService;
use Illuminate\Http\JsonResponse;

class StudentAssessmentController extends Controller
{
    /**
     * Update the assessment status for all students.
     *
     * @return JsonResponse
     */
    public function updateAssessments(): JsonResponse
    {
        $service = new PrinciplesService();

        Student::all()->each(function ($student) use ($service) {
            try {
                $questions = $service->getNextQuestions($student->principles_account_uid);
                $student->update([
                    'assessment_complete' => $questions['assessmentProgress']['assessmentComplete'],
                    'shortscale_complete' => $questions['assessmentProgress']['shortscaleComplete'],
                ]);
            } catch (\Exception $e) {
                // Handle exceptions if needed
            }
        });

        return response()->json(['message' => 'Assessments updated successfully!']);
    }
} 