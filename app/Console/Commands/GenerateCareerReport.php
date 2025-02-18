<?php

namespace App\Console\Commands;

use App\Exceptions\PrinciplesApiException;
use App\Helpers\DataTransformer;
use App\Helpers\Onet;
use App\Services\OpenAIService;
use App\Services\PrinciplesService;
use Illuminate\Console\Command;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\CareerReport;
use Illuminate\Support\Facades\Blade;
use App\Models\Student;
use Illuminate\Support\Str;
class GenerateCareerReport extends Command
{
    protected $signature = 'app:generate-career-report {reportId}';
    protected $description = 'Generates a career report based on a CareerReport record ID';

    private string $careerTitle;

    public PrinciplesService $principlesService;
    public array $data;
    /**
     * @var array|string[]
     */
    /**
     * @var array|array[]
     */
    private array $reportSections;
    private OpenAIService $openAIService;
    private $context;
    private array $personality_profile;


    public function __construct(
        PrinciplesService $principlesService,
        OpenAIService     $openAIService,
    )
    {
        parent::__construct();
        $this->principlesService = $principlesService;
        $this->openAIService = $openAIService;
        $this->context = "You are an expert career coach and researcher with 20 years of experience helping young people discover careers they enjoy and can thrive in. ";
        $this->careerTitle = "";
        $this->reportSections = [];
    }

    /**
     * @throws PrinciplesApiException
     * @throws \Exception
     */
    public function handle(): void
    {

        Log::info("STARTING CAREER REPORT GENERATION");

        $reportId = $this->argument('reportId');
        $report = CareerReport::find($reportId);

        $student = $report->student;
        $accountId = $student->principles_account_uid;
        $studentName = $student->first_name . ' ' . $student->last_name;


        $this->careerTitle = Onet::getOnetJobTitleByCode($report->onet_soc_code);

        Log::info("Career title: " . $this->careerTitle);

        Log::info("Report ID: $reportId for Student: " . $report->student->first_name . " " . $report->student->last_name);
        // Check if uploaded JSON exists
        $this->reportSections = $report->report_template;

//        Log::info(json_encode($this->reportSections , JSON_PRETTY_PRINT));

        // Prepare sections
        $preparedSections = $this->prepareSections($report->onet_soc_code, $accountId);
        $this->personality_profile = array_merge($this->personality_profile, $student->toArray());
        $report->update(['processed_template' => $preparedSections]);
//        Log::info(json_encode($preparedSections , JSON_PRETTY_PRINT));

        // Store sections
        $responses = [];

        Log::info("Report has " . count($preparedSections) . " entries");
        $sectionCount = 1;

        foreach ($preparedSections as $sectionId => $sectionData) {
            Log::info("Section $sectionId  ($sectionCount/" . count($preparedSections).")");
            $sectionCount++;

            $response = '';

            // Handle function-based description
            if (isset($sectionData['getDescription'])) {
                $description = $sectionData['getDescription']($this->data);
            } else {
                $description = $sectionData['description'] ?? '';
            }

            // Handle prompt-based response
            if (!empty($sectionData['prompt'])) {
                $threadId = $this->openAIService->createThread($this->context);

                $modifiedPrompt = "Student: {$studentName}\n" . $sectionData['prompt'];
                $runId = $this->openAIService->sendMessageToThread($threadId, $modifiedPrompt);
                $response = $this->openAIService->getResponse($threadId, $runId);
                // Close the thread after processing the prompt
                Log::info("Response: " . Str::limit($response, 300));
                $this->openAIService->closeThread($threadId);
            }
            // Trim JSON code block markers and whitespace if present
            if (str_starts_with($response, '```json')) {
                $response = substr($response, 7);
            }
            if (str_ends_with($response, '```')) {
                $response = substr($response, 0, -3);
            }
            $response = trim($response);

            $report->addToContent($sectionId, [
                'title' => $sectionData['title'] ?? '',
                'sub_title' => $sectionData['sub_title'] ?? '',
                'description' => $description,
                'response' => json_decode($response, true)
            ]);
        }

        // $this->generatePdf($careerTitle, $accountId, $responses);
        $this->info("Career report generated successfully!");
    }

    /**
     * @throws PrinciplesApiException
     */
    protected function prepareSections(string $onetsocCode, string $accountId): array
    {
        $student = Student::where('principles_account_uid', $accountId)->first();
//        $salary = Onet::getSalaryInfo($this->careerTitle);
        $tasks = Onet::getTasks($onetsocCode);
        $workActivities = Onet::getWorkActivities($onetsocCode);
        $detailedWorkActivities = Onet::getDetailedWorkActivities($onetsocCode);
        $workContext = Onet::getWorkContext($onetsocCode);
        $skills = Onet::getSkills($onetsocCode);
        $abilities = Onet::getAbilities($onetsocCode);
        $workValues = Onet::getWorkValues($onetsocCode);
        $workStyles = Onet::getWorkStyles($onetsocCode);
//        $projectedGrowthRate = Onet::getProjectedGrowthRate($this->careerTitle);
        $relatedOccupations = Onet::getRelatedOccupations($onetsocCode);
        $knowledge = Onet::getKnowledge($onetsocCode);
        $education = Onet::getEducation($onetsocCode);

        $interests = Onet::getInterests($onetsocCode)->implode(', ');
        $ppmScores = $this->principlesService->getPpmScores($accountId);
        $riasecScores = DataTransformer::ppmToRiasec($this->principlesService->getPpmScores($accountId));
        $personalityProfile = $this->principlesService->getResults($accountId);
        $occupationWeightings = DataTransformer::transformRecords(Onet::getOnetJobWeights($onetsocCode));
        $careerCompatibilityScore = $this->principlesService->getCareerCompatibilityScore($accountId, $occupationWeightings);

        $careerCompatibilityScorePercentage = round((($careerCompatibilityScore['customOccupationsErrorMargins']["errorMargins"]["ea_"]["value"] + 1) / 2) * 100, 0);

        $this->personality_profile = $personalityProfile;

        $this->data = [
            'user'=> array_merge($student->toArray(), [
                'personality_profile' => $this->personality_profile,
                'ppmScores' => $this->formatPpmScore($ppmScores),
                'riasecScores' => $riasecScores,
                'career_compatibility_score' => $careerCompatibilityScore,
                'career_compatibility_score_percentage' => $careerCompatibilityScorePercentage,
            ]),
            'occupation' => [
                'knowledge' => $knowledge,
                'related_occupations' => $relatedOccupations,
                'education' => $education,
                'detailed_work_activities' => $detailedWorkActivities,
                'work_activities' => $workActivities,
                'work_context' => $workContext,
                'work_values' => $workValues,
                'work_styles' => $workStyles,
                'abilities' => $abilities,
                'skills' => $skills,
                'tasks' => $tasks,
                'interests' => $interests,
                'occupation_weightings' => $occupationWeightings,
                'career_title' => $this->careerTitle,
                'onetsoc_code' => $onetsocCode,
            ]
        ];

        return array_map(function ($sectionData) {

            // Replace user data placeholders
            // Process each field that needs placeholder replacement
            $fields = ['prompt', 'title', 'description'];
            $processedFields = [];
            
            foreach ($fields as $field) {
                $text = $sectionData[$field] ?? '';
                
                // Helper function to format value for placeholder replacement
                $formatValue = fn($value) => is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : 
                                           (is_string($value) ? "\"$value\"" : $value);
                // Replace both user and occupation placeholders
                foreach (['user', 'occupation'] as $section) {
                    foreach ($this->data[$section] as $key => $value) {
                        $placeholder = "{{{$section}.{$key}}}";
                        $text = str_replace($placeholder, $formatValue($value), $text);
                    }
                }
                
                $processedFields[$field] = $text;
            }

            return [
                'title' => $processedFields['title'],
                'description' => $processedFields['description'],
                'prompt' => $processedFields['prompt']
            ];
        }, $this->reportSections);
    }

    protected function formatPpmScore($ppmScores): string
    {
        return collect($ppmScores)
            ->map(fn($values, $key) => (string)$key . " - {$values['rawScore']};")
            ->implode(' ');
    }

    public static function getEducationFormatted(Collection $educationData): string
    {

        if ($educationData->isEmpty()) {
            return "No education data available for this occupation.";
        }

        $output = "Education\n";
        $output .= "How much education does a new hire need to perform a job in this occupation? Respondents said:\n";

        foreach ($educationData as $data) {
            $output .= "{$data->percentage}% responded: {$data->education_requirement} required\n";
        }

        return $output;
    }
}
