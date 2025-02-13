<?php

namespace App\Http\Controllers;

use App\Helpers\Onet;
use App\Jobs\GenerateCareerReportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Models\JcrTemplate;
use App\Models\CareerReport;
use App\Models\Student;

class CareerReportController extends Controller
{

    public function displayCareerReport(CareerReport $careerReport)
    {
        
        $fallbackTemplate = $careerReport->processed_template ?? $careerReport->report_template;
        // Ensure all sections from processed_template exist in content
        $content = $careerReport->content;
        if ($fallbackTemplate) {
            foreach ($fallbackTemplate as $sectionId => $section) {
                if (!isset($careerReport->content[$sectionId])) {
                    $content[$sectionId] = [
                        'title' => $section['title'] ?? '',
                        'sub_title' => $section['sub_title'] ?? '',
                        'description' => $section['description'] ?? '',
                        'response' => null
                    ];
                }
            }
        }

        return view('career-reports.show', [
            'careerReport' => $careerReport,
            'content' => $content
        ]);
    }

    /**
     * Get all career reports with their associated jobs.
     *
     * @return JsonResponse
     */
    public function listCareerReports(): JsonResponse
    {
        $reports = CareerReport::with(['job', 'student'])->orderByDesc('id')->get();
        
        return response()->json([
            'reports' => $reports
        ]);
    }
    /**
     * Generate a career report job.
     */
    public function generateCareerReport(Request $request): JsonResponse
    {
        $accountId = $request->input('accountId');
        $student = Student::where('principles_account_uid', $accountId)->first();
        if (!$student) {
            return response()->json(['error' => 'Student not found'], 500);
        }

        $careerTitle = $request->input('occupation');
        
        // load the template from the database
        $templateId = $request->input('templateId');
        $template = JcrTemplate::find($templateId);
        if (!$template) {
            return response()->json(['error' => 'Template not found'], 500);
        }
        $templateSections = $template->content;
        
        // check to see which sections the user wants to generate
        $selectedSectionIds = $request->input('sectionIds');
        if ($selectedSectionIds) {
            $templateSections = array_intersect_key($templateSections, array_flip($selectedSectionIds));
        }

        $socCode = Onet::getOnetSocCode($careerTitle);
        if (!$socCode) {
            return response()->json(['error' => 'Invalid occupation title'], 500);
        }

        // create a new career report
        $careerReport = CareerReport::create([
            'student_id' => $student->id,
            'onet_soc_code' => $socCode,
            'report_template' => $templateSections,
            'content' => []
        ]);

        // Dispatch the Artisan command as a queued job
        $job = new GenerateCareerReportJob($careerReport);
        $jobId = Bus::dispatch($job);

        \Log::info('Job ID: ' . $jobId);

        $careerReport->update([
            'job_id' => $jobId
        ]);

        return response()->json([
            'message' => 'Career report generation has been queued.',
            'job_id' => $jobId,
        ]);
    }

    /**
     * Allow users to download the generated PDF.
     */
    public function downloadCareerReport($accountId, $careerTitle): BinaryFileResponse|JsonResponse
    {
        $careerTitle = str_replace('_', ' ', $careerTitle);
        $socCode = Onet::getOnetSocCode($careerTitle);

        $filePath = "/public/reports/career_report_{$accountId}_{$socCode}.pdf";

        if (!Storage::exists($filePath)) {
            return response()->json(['error' => 'Report not ready yet. Try again later.'], 404);
        }

        return Response::download(storage_path("app/{$filePath}"));
    }

}
