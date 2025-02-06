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

class GenerateCareerReport extends Command
{
    protected $signature = 'app:generate-career-report {accountId?} {careerTitle?}';
    protected $description = 'Generates a career report for users with a specific job title. Optionally for a single user.';

    public PrinciplesService $principlesService;
    public array $data;
    /**
     * @var array|string[]
     */
    private array $customParagraphs;
    /**
     * @var array|array[]
     */
    private array $promptTemplates;
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

        $this->promptTemplates = [];

        $this->customParagraphs = [
            2 => "This career is known for its dynamic nature and continuous evolution, making it an exciting choice.",
            5 => "Individuals pursuing this path often find themselves adapting to new trends and technologies."
        ];
    }

    /**
     * @throws PrinciplesApiException
     */
    public function handle(): void
    {
        $careerTitle = $this->argument('careerTitle');
        $accountId = $this->argument('accountId');

        $careerTitle = str_replace("_", " ", $careerTitle);


        // Check if uploaded JSON exists
        $jsonFilePath = storage_path("app/json/career_report_template.json");
        if (file_exists($jsonFilePath)) {
            $jsonContent = file_get_contents($jsonFilePath);
            $this->promptTemplates = json_decode($jsonContent, true);
            Log::info("Using uploaded JSON: {$jsonFilePath}");
        } else {
            $this->promptTemplates = $this->defaultPromptTemplates();
            Log::info("Using default prompt templates.");
        }

        // Prepare prompts
        $preparedPrompts = $this->preparePrompts($careerTitle, $accountId);

        // Initialize OpenAI thread
        $context = isset($this->context) ? str_replace(
            array_map(fn($key) => "{{{$key}}}", array_keys($this->data)),
            array_values($this->data),
            $this->context
        ) : null;

        $threadId = $this->openAIService->createThread($context);

        $responses = [];

        foreach ($preparedPrompts as $index => $promptData) {
            Log::info("Prompt $index of " . count($preparedPrompts) - 1);

            $response = '';

            // Handle function-based description
            if (isset($promptData['getDescription'])) {
                $description = $promptData['getDescription']($this->data);
            } else {
                $description = $promptData['description'] ?? '';
            }

            // Handle prompt-based response
            if (!empty($promptData['prompt'])) {
                $runId = $this->openAIService->sendMessageToThread($threadId, $promptData['prompt'], $this->personality_profile);
                $response = $this->openAIService->getResponse($threadId, $runId);
            }

            $responses[] = [
                'title' => $promptData['title'] ?? '',
                'sub_title' => $promptData['sub_title'] ?? '',
                'description' => $description,
                'response' => $response
            ];

            if (isset($this->customParagraphs[$index + 1])) {
                $responses[] = [
                    'title' => 'Additional Insights',
                    'description' => '',
                    'response' => $this->customParagraphs[$index + 1]
                ];
            }

            sleep(10);
        }

        $this->generatePdf($careerTitle, $accountId, $responses);
        $this->info("Career report generated successfully!");
    }

    /**
     * @throws PrinciplesApiException
     */
    protected function preparePrompts(string $careerTitle, string $accountId): array
    {

//        $salary = Onet::getSalaryInfo($careerTitle);
        $tasks = Onet::getTasks($careerTitle)->implode(', ');
        $workActivities = Onet::getWorkActivities($careerTitle)->implode(', ');
        $detailedWorkActivities = Onet::getDetailedWorkActivities($careerTitle)->implode(',');
        $workContext = Onet::getWorkContext($careerTitle)->implode(',');
        $skills = Onet::getSkills($careerTitle)->implode(', ');
        $abilities = Onet::getAbilities($careerTitle)->implode(',');
        $workValues = Onet::getWorkValues($careerTitle)->implode(',');
        $workStyles = Onet::getWorkStyles($careerTitle)->implode(',');
//        $projectedGrowthRate = Onet::getProjectedGrowthRate($careerTitle);
        $relatedOccupations = Onet::getRelatedOccupations($careerTitle)->implode(',');
        $knowledge = Onet::getKnowledge($careerTitle);
        $education = Onet::getEducation($careerTitle);

        $interests = Onet::getInterests($careerTitle)->implode(', ');
        $ppmScores = $this->principlesService->getPpmScores($accountId);
        $personalityProfile = $this->principlesService->getResults($accountId);
        $occupationWeightings = DataTransformer::extractNestedValues(
            $ppmScores,      // Source array
            'ppmScore',    // Key where the values exist
            'ea_',         // Key to store transformed values under
            'rawScore'     // Value to extract from each item
        );
        $careerCompatibilityScore = $this->principlesService->getCareerCompatibilityScore($accountId, $occupationWeightings);

        $careerCompatibilityScorePercentage = (($careerCompatibilityScore['customOccupationsErrorMargins']["errorMargins"]["ea_"]["value"] + 1) / 2) * 100;
        $this->personality_profile = $personalityProfile;

        $this->data = [
            'knowledge' => $knowledge,
            'personality_profile' => json_encode($this->personality_profile),
            'related_occupations' => $relatedOccupations,
            'education' => $education,
            'tasks' => $tasks,
            'detailed_work_activities' => $detailedWorkActivities,
            'work_activities' => $workActivities,
            'work_context' => $workContext,
            'work_values' => $workValues,
            'work_styles' => $workStyles,
            'abilities' => $abilities,
            'skills' => $skills,
            'interests' => $interests,
            'ppmScores' => $this->formatPpmScore($ppmScores),
            'occupation_weightings' => json_encode($occupationWeightings, JSON_PRETTY_PRINT),
            'career_compatibility_score' => json_encode($careerCompatibilityScore, JSON_PRETTY_PRINT),
            'career_compatibility_score_percentage' => $careerCompatibilityScorePercentage,
            'career_title' => $careerTitle,
        ];

        return array_map(function ($promptData) {
            $preparedPrompt = isset($promptData['prompt']) ? str_replace(
                array_map(fn($key) => "{{{$key}}}", array_keys($this->data)),
                array_values($this->data),
                $promptData['prompt']
            ) : null;

            $preparedTitle = isset($promptData['title']) ? str_replace(
                array_map(fn($key) => "{{{$key}}}", array_keys($this->data)),
                array_values($this->data),
                $promptData['title']
            ) : null;

            // Handle dynamic description from function
            $preparedDescription = '';
            if (isset($promptData['description'])) {
                $preparedDescription = str_replace(
                    array_map(fn($key) => "{{{$key}}}", array_keys($this->data)),
                    array_values($this->data),
                    $promptData['description']
                );
            } elseif (isset($promptData['getDescription'])) {
                $preparedDescription = $promptData['getDescription']($this->data);
            }

            return [
                'title' => $preparedTitle,
                'description' => $preparedDescription,
                'prompt' => $preparedPrompt
            ];
        }, $this->promptTemplates);
    }

    protected function generatePdf(string $careerTitle, string $accountId, array $responses): void
    {
        $socCode = Onet::getOnetSocCode($careerTitle);
        $pdfData = ['careerTitle' => $careerTitle, 'responses' => $responses];
        $pdf = PDF\Pdf::loadView('pdfs.career_report', $pdfData);
        $fileName = "career_report_{$accountId}_{$socCode}.pdf";
        Storage::put("public/reports/{$fileName}", $pdf->output());
        $this->info("Career report generated: storage/app/public/reports/{$fileName}");
    }

    protected function formatPpmScore($ppmScores): string
    {
        return collect($ppmScores['ppmScore'])
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

    protected function defaultPromptTemplates(): array
    {
        return [
            [
                'title' => 'Career compatibility score %: {{career_compatibility_score_percentage}}',
            ],
            [
                'prompt' => "Generate a highly personalized, engaging, and emotionally compelling message in 150 words that gives the user an overview on his compatibility with the {{career_title}} career. This introduction should be based on the user's personality profile {{personality_profile}},  and compatibility score of {{career_compatibility_score_percentage}} with the profession of {{career_title}}.
Always mention the user's name to personalize the report.
 Adjust the enthusiasm based on their Compatibility Score {{career_compatibility_score_percentage}}:
	•	90–100: Maximum excitement, reinforcing that this career is a strong fit.
	•	75–89: Positive but realistic, showing strong alignment with some considerations.
	•	50–74: Balanced, pointing out both strengths and areas to explore.
	•	Below 50: Constructive realism, highlighting challenges and alternative directions.

 Make the user feel an emotion—validation, excitement, curiosity, or motivation.
Use a storytelling tone that makes them visualize themselves in the role.

	Final Instructions:
	•	Always use the user's name.
	•	Keep the introduction in around 150 words, with no bullet points, just paragraphs.
	•	Weave in insights based on their personality traits without explicitly mentioning their scores.
	•	Make the introduction engaging, personal, and emotional—not just informational.
	•	Ensure different compatibility scores lead to different tones:
	•	High fit = excitement
	•	Moderate fit = balanced perspective
	•	Low fit = constructive realism",
            ],
            [
                'title' => "Overview",
                'prompt' => "Write a concise, user-friendly overview of the {{career_title}} career in one paragraph. Describe the primary purpose of the career and its significance in the industry. Use clear, engaging language to explain the profession's core functions and why it matters."
            ],
            [
                'title' => "On the job, you would:",
                'prompt' => "List a maximum of 5 key responsibilities and common tasks typically associated with the {{career_title}} career. Present this information in a concise bullet-point format without subtitles. Each point should clearly outline a specific duty or responsibility performed on a day-to-day basis in this role. Ensure the descriptions are action-oriented, clear, and relevant to the profession."
            ],
            [
                'title' => "Key insights:",
                'sub_title' => "Average salary 2023 (in the U.S.)",
                'prompt' => "Extract and present the average annual and hourly salary for the career of {{career_title}} from the ONET database {{Median wages (2023) $ hourly, $ annual}}. The response should be structured, concise, and include factors influencing salary variations.

Format the output as follows:

'$ XX,XXX hourly, $ XX.XX annual'

Salary can vary based on several factors:
Location: Salaries tend to be higher in major urban areas or tech hubs due to cost of living and demand. However, no specific cities should be mentioned.
Industry and Specialization: Some industries typically offer higher salaries, while others, like non-profits or smaller businesses, may have lower starting pay.
Demand: If demand for {{career_title}} is particularly strong in certain industries or specialties, this can increase salary potential.
Seniority and Experience: Entry-level professionals typically earn less, while those with years of experience, advanced degrees, or leadership roles can command significantly higher pay."
            ],
            [
                'sub_title' => "Future safety score",
                'prompt' => "Calculate and generate a 'Future safety score' for the {{career_title}} career, assessing long-term stability and demand. The score must take into account the Projected Growth Rate (2022-2033) {{Projected Growth Rate (2022-2033) %}} for the career {{career_title}}.

Response Structure:
Future Safety Score:
	•	Assign a score from 1 to 10 (1 = Low stability, 10 = Very high stability).
	•	This score must be based on the Projected Growth Rate (2022-2033) {{Projected Growth Rate (2022-2033) %}} for the career of {{career_title}} and considering factors such as demand, automation risks, and industry trends.
Explanation: After assigning the score, explain why it's appropriate by addressing each factor:
	•	Industry trends: Detail how 'industry trends' impact the career's stability. Clearly state the Projected Growth Rate (2022-2033) {{Projected Growth Rate (2022-2033) %}} for the {{career_title}} career. Explain briefly if the career is expanding, stable, or shrinking and in which industries it is most relevant.
	•	Impact of automation: Assess whether AI or automation pose risks to this career. Explain the role automation might play in replacing or complementing tasks.
	•	Demand across industries: Highlight industries where demand is strong and any shifts in opportunities (e.g., transitioning from print to digital in design). Mention any emerging specializations that could increase job security.

Ensure the explanation is user-friendly and provides actionable insights for the user to understand the career's long-term viability and any challenges they might face.

Example Output Format:
\"The career of a {{career_title}} offers [steady/high/declining] job security, with [strong/moderate/limited] demand across industries. However, challenges such as automation and market trends could impact certain aspects of the profession.
	•	Industry Trends: Career {{career_title}} is expected to grow at a Projected Growth Rate (2022-2033) {{Projected Growth Rate (2022-2033) %}}, indicating [steady/moderate/high] job opportunities in the coming years.
	•	Impact of Automation: AI and automation are [low/medium/high] threats, with tools like [relevant automation tools] streamlining tasks. However, specializations in [specific niche] remain in demand.
	•	Demand Across Industries: While [declining industries] may shrink, [growing industries] such as [examples like tech, marketing, healthcare, etc.] are expanding, offering strong job prospects.
Ensure every career report follows this structured, data-driven, and predictable format.\"
"],
            [
                'sub_title' => "Work setting",
                'prompt' => "Generate a response for the 'Work setting' section of the {{career_title}} report. The response must follow this exact format and wording style, ensuring clarity and consistency.

Response Structure:
1. Work Setting (First Line, Fixed Format)
	•	Choose one of the following options based on your knowledge of the industry and career standards:
	•	Usually onsite
	•	Usually hybrid
	•	Usually remote
2. Brief Work Setting Overview (One Sentence):
	•	Provide a short explanation of where professionals in this field typically work, ensuring clarity and brevity.
3. Industries (Bullet Points, Fixed Style):
	•	List 2–3 industries where this profession is commonly found.
	•	Ensure examples are broad enough to apply universally but still relevant.
4. Flexibility (One Sentence):
	•	Explain the degree of flexibility in this career (e.g., remote work potential, client meetings, or required in-office collaboration).
5. Final Summary (One Sentence):
	•	Summarize how this work setting impacts work-life balance or adaptability for professionals in this field."
            ],
            [
                'sub_title' => "Flexibility",
                'prompt' => "Evaluate the career of {{career_title}} based on its level of work schedule flexibility and freelancing opportunities.
	1.	Rating: Assign one of three levels—\"Usually low,\" \"Usually medium,\" or \"Usually high\"—based on:
	•	The potential for freelancing or self-employment.
	•	Control over work schedules (e.g., setting your own hours vs. adhering to strict schedules).
	•	The prevalence of rigid deadlines and time-sensitive tasks.
	2.	Explanation: After assigning the rating, explain why it's appropriate by addressing the following:
	•	Provide specific examples of roles or industries within the profession where flexibility is typical or limited.
	•	Clarify whether flexibility varies with seniority, specialization, or employment type (e.g., freelance vs. full-time).
	•	Highlight any trade-offs, such as greater flexibility requiring increased responsibility or irregular income.
Ensure the explanation is easy to understand, actionable, and helps the user gauge how much control they might have over their time in this career."
            ],
            [
                'sub_title' => "Work-life balance",
                'prompt' => "Evaluate the work-life balance of the career of {{career_title}} using one of three ratings: 'Usually low,' Usually medium,' or 'Usually high.'
	1.	Rating: Assign the appropriate rating based on these factors:
	•	Work Hours & Schedule: Assess the predictability and regularity of work hours, including whether overtime or long/irregular hours are common.
	•	Flexibility: Evaluate how much control professionals have over their schedules, including opportunities for remote, hybrid, or freelance work.
	•	Workload Intensity: Consider the pace of work and likelihood of overwhelming workloads or high-pressure demands.
	•	Industry & Career Stage: Factor in how norms and career progression influence work-life balance.
	•	Boundaries and Spillover: Analyze how well personal and professional boundaries are maintained, including the frequency of after-hours work.
	2.	Explanation: Below the rating, provide a user-friendly explanation that clarifies why the score was assigned.
	•	Offer specific examples of roles or industries where this rating applies.
	•	Discuss any variations by career stage, industry, or specialization."
            ],
            [
                'sub_title' => "Time to your first paycheck",
                'prompt' => "Evaluate how quickly someone entering the {{career_title}} career can start earning a sustainable income. Provide a rating using one of the following categories:
	•	Immediate (0–6 months) – Careers where income can be generated quickly, often through freelancing, gig work, or self-taught skills.
	•	Fast (6 months – 2 years) – Some training or certifications are needed, but entry-level jobs or freelance work can start relatively soon.
	•	Moderate (2–5 years) – Requires a degree, certification, or structured experience before reaching a stable income.
	•	Long-Term (5+ years) – Extensive education, licensing, and experience are required before making a good salary.

1. Assign a rating (First row output)
	•	Always begin with one of the four ratings above, ensuring it reflects the typical timeline for earning in this career.

2. Provide an explanation based on these factors:
	•	Education & Training Requirements – How much education is needed before you can start earning?
	•	Entry-Level Job Availability – Are beginner-friendly jobs or freelance gigs accessible?
	•	Freelancing vs. Employment – Can someone start earning independently, or do they need credentials first?
	•	Time to Build Credibility & Demand – Does this career require years of networking and experience before income becomes stable?

Keep explanations clear, realistic, and actionable to help users understand how fast they can start earning."
            ],
            [
                'sub_title' => "Potential to pivot to other careers",
                'prompt' => "Evaluate how likely it is for professionals in the {{career_title}} career to transition into other fields using their acquired skills.
	1.	Rating: Assign a rating of \"Usually Low,\" \"Usually Medium,\" or \"Usually High,\" based on:
	•	Versatility and Demand of Skills: Are the skills learned in this profession (e.g., leadership, technical expertise, communication) applicable and in demand across other careers?
	•	Range of Related Professions: How many and how diverse are the roles that could benefit from these skills?
	•	Ease of Transition: How easy is it to move into these roles with minimal additional training or certifications?
	2.	Explanation: After assigning the rating, provide:
	•	An explanation of why this career has the rated pivot potential.
	•	The key skills acquired in this profession that are transferable to other fields.
•	A list of related professions that are common or logical pivots for this career.
	•	Examples of how these skills can be applied in related professions.
	•	Any challenges or limitations (e.g., industries where pivoting might require significant retraining or specialized certifications)."
            ],
            [
                'title' => 'You as a {{career_title}}',
            ],
            [
                'title' => 'Intro info',
                'prompt' => "Generate a deeply personalized introduction to the \"You as a {{career_title}}\" section by analyzing the user's RIASEC scores: {{ppmScores}}, and personality test results {{personality_profile}} in comparison to the career's {{career_title}} ideal RIASEC profile: {{interests}} and its key attributes. The response should highlight their strengths, unique fit, and potential areas of growth, while providing a realistic yet inspiring outlook on their career fit. The result should feel specific, insightful, and motivational—not generic.

Use the following structure:
1. Engaging Introduction (2–3 sentences):
	•	Address the user by name and highlight their strongest alignment with the career.
	•	Make them feel seen, using their specific traits to frame why this career could be a natural fit for them.
	•	If they score highly in the career's key RIASEC domains, emphasize their potential to stand out. If their alignment is moderate or low, frame the response with a mix of positives and growth areas.
2. Deep Dive into RIASEC Fit (2–3 sentences):
	•	Compare the user's RIASEC scores against the ideal RIASEC profile for the career (from ONET).
	•	Specifically mention their scores and how they align (e.g., \"Your high Enterprising (5.66) and Social (5.1) scores suggest you might thrive in leadership roles rather than pure execution.\").
	•	If they have an imbalanced or unexpected score, provide insights into how that could shape their experience in the career.
3. Personality & Work Style Alignment (3–4 sentences):
	•	Identify key traits from their personality test that would help them succeed in this career (e.g., high leadership, strategic thinking, resilience, creativity, or detail orientation).
	•	If their personality suggests they might thrive in a specialization, mention that (e.g., \"Your strategic mindset and problem-solving abilities mean you could excel in branding, UX/UI, or creative direction rather than traditional execution-based roles.\").
	•	Acknowledge potential friction points (e.g., \"Your strong preference for structure may sometimes clash with the fluid nature of design projects that require multiple revisions and iterations.\").
4. Framing the Career Beyond Just a Job (2 sentences):
	•	Close with a high-energy, inspiring statement that positions the career as more than just a job—it's an opportunity to make an impact.
	•	If the user's fit is high, emphasize their unique strengths in a motivational way. If it's moderate or low, frame it as an opportunity for growth and self-discovery or suggestion for pursuing other careers.

Final instructions:
- Use the user's name in the response to make it personalized.
- Mention the user's exact RIASEC scores and personality profile % scores to back up insights.
- Avoid generic statements—the response should feel like it was written specifically for them.
- Frame negatives constructively, but be direct and blunt
- Ensure consistency in tone—the message should be clear, engaging, and inspiring, without being overly hyped."
            ],
            [
                'title' => "Compatibility score explained",
                'prompt' => "Generate a Compatibility Score Table starting from the user's compatibility score {{career compatibility score}} with the career of {{career_title}}, by comparing user's Personality Test Results {{personality_profile}} and RIASEC Scores: {{interests}} with the key attributes required for the selected {{career_title}} based on ONET data.
1. Determine Key Attributes from ONET for the selected {{career_title}}:
	•	Skills are: {{skills}}
	•	Abilities are: {{abilities}}
	•	Work values are: {{work_values}}
	•	Work styles are: {{work_styles}}
	•	Identify the 10 most critical attributes required for success in this career.
	•	Always include the RIASEC Interests of the career as one attribute: {{interests}}, comparing it with the user's RIASEC scores.
2. Compare with the User's Profile:
	•	Use the Personality Test Results and RIASEC Scores to assign a User Score for each attribute.
	•	The user's score should be based strictly on measured personality traits and interests—do not infer attributes that are not explicitly tested.
	•	If an important career attribute does not have a matching trait in the user's test results, do not include it in the table.
3. Define Table Columns:
	•	\"Attribute\": The key trait, skill, or work style required for success in the career.
	•	\"Importance for the role\": Rate the attribute's significance for the profession as Low, Medium, or High, based on ONET data.
	•	\"Your score\": Assign the user's score based on their RIASEC and personality test results.
	•	\"Alignment Explanation\": Provide a clear and concise explanation of how well the user aligns with the attribute. Use engaging and easy-to-understand language that avoids vague or overly technical phrasing.
4. Ensure Clarity and Relevance:
	•	Limit the table to a maximum of 10 attributes.
	•	Only include attributes measured in the user's personality test and RIASEC scores.
	•	Use practical, user-friendly explanations to help the user understand their strengths and areas for improvement.
	•	Ensure the alignment explanations provide insight into where the user excels and where they may need to develop skills for the career.

Key Constraints:
- Limit the table to 10 attributes maximum.
- Only include attributes that have measurable data from the user's personality test & RIASEC scores.
- Use clear and engaging language—avoid vague or overly generic explanations.
- **Ensure the final output feels insightful, motivating, and highly personalized to the user.
"
            ],
            [
                'title' => "Top things you might enjoy as a {{career_title}}",
                'prompt' => "Generate a deeply personalized response by comparing the user's RIASEC scores: Realistic {{ppmScores}} and personality traits {{personality_profile}} with the career's {{career_title}} ideal ONET profile, including Work Activities {{work_activities}}, Detailed work activities {{detailed_work_activities}}, Skills {{skills}}, Work context {{work_context}}, Work styles {{work_styles}}, and Work values {{work_values}}.

Structure the response as follows:
\"Top things you might enjoy as a {{career_title}}\"
	•	Identify 5 different key aspects of the career that align strongly with the user's RIASEC scores, personality traits, and ONET profile for the career of {{career_title}}, taking into account Work activities, skills, Work Context, Work styles, and work values.
	•	For each point, provide a clear and engaging explanation based on how the user's attributes make that aspect enjoyable.
	•	If their RIASEC fit and personality profile is moderate, highlight areas where they might stand out or bring a unique approach to the role.
	•	If the user has high scores in leadership, innovation, or strategic thinking, suggest higher-level or specialized roles they might thrive in (e.g., management, strategy-based roles, consulting, etc.).
	•	If the user has a low fit for the career, focus on aspects they might still find engaging, transferable skills they bring, or alternative paths within the field that could be more fulfilling.


Final instructions
- Each bullet point must have a short, bolded subtitle to improve readability.
- Avoid mentioning numerical scores—instead, focus on descriptive analysis.
- Ensure the tone is motivational yet realistic—celebrate strengths but also provide thoughtful guidance.
- Keep the explanations engaging and clear, avoiding overly technical jargon.
- For low-fit users, provide actionable alternatives rather than simply stating misalignment."
            ],
            [
                'title' => "Top things you might dislike as a {{career_title}}",
                'prompt' => "Generate a deeply personalized response by comparing the user's RIASEC scores: {{ppmScores}} and personality traits {{personality_profile}} and personality traits with the career's ideal ONET profile, including Work Activities {{work_activities}}, Detailed work activities {{detailed_work_activities}}, Skills {{skills}}, Work context {{work_context}}, Work styles {{work_styles}}, and Work values {{work_values}}.

Structure the response as follows:
\"Top things you might dislike as a {{career_title}}\"

	•	Select 5 different aspects of the career that may be challenging, frustrating, or misaligned with the user's personality traits and work preferences. Look into the user's moderate to low scores that are important for the career to point them out.
	•	Ensure variety in the selected aspects by covering different themes such as:
	•	Work environment (e.g., fast-paced, competitive, rigid, structured vs. unstructured).
	•	Work context (e.g. users are conflict-averse or introverted but need to talk daily with people)
        •	Job stability and flexibility (e.g., contract-based work, unpredictable income, freelancing challenges).
	•	Pressure and deadlines (e.g., frequent revisions, stakeholder expectations, high-stress work culture).
	•	Collaboration requirements (e.g., high levels of teamwork vs. solitary work).
	•	Skill development and industry shifts (e.g., staying updated on technology, job automation risks).

Ensure Diversity & Personalization
	•	Each bullet point must focus on a distinct challenge—avoid redundancy.
	•	Use the user's personality traits to explain why they may find these aspects frustrating.

Final instructions
- Each bullet point must have a short, bolded subtitle to improve readability.
- Avoid mentioning numerical scores—instead, focus on descriptive analysis.
- Be blunt and realistic.
- Keep the explanations engaging and clear, avoiding overly technical jargon.
- For low-fit users, provide actionable alternatives besides stating misalignment."
            ],
            [
                'title' => "A day in the life of a {{career_title}}",
                'prompt' => "Create an engaging, story-like narrative in around 300 words that paints a vivid picture of what a typical day might look like for someone in this role {{career_title}}. Use the information provided about the Tasks {{tasks}}, Work activities {{work_activities}}, Work context {{work_context}}, and Detailed activities {{detailed_work_activities}} from the ONET database, together with user's personality test results {{personality_profile}} and RIASEC scores: {{ppmScores}}

Include the following elements:
	1.	Daily Flow:
	•	Provide a chronological breakdown of the professional's day, from starting their morning to wrapping up at the end of the day.
	•	Highlight key tasks they perform at various points during their workday.
	2.	Interactions and Scenarios:
	•	Include specific examples of interactions they might have (e.g., working with team members, presenting ideas to clients, or solving problems).
	•	Show how these interactions influence their work and add variety to their day.
	3.	Work Environment:
	•	Describe the setting where they work (e.g., office, studio, remote setup, client site).
	•	Include sensory details to make the narrative immersive, such as the hum of computer systems, brainstorming sessions, or client meetings.
	4.	Challenges and Highlights:
	•	Mention the challenges they may encounter (e.g., tight deadlines, creative blocks, or client revisions).
	•	Highlight rewarding aspects of the work, like seeing a project come to life, collaborating successfully, or receiving positive feedback.
	5.	Formatting for the Report:
	•	Present the narrative under the heading:
\"A day in the life of a {{career_title}}\"

Final instructions:
- Always use the user's name in 3rd person to make the story feel personal.
- Ensure alignment with their RIASEC & personality profile to create a more realistic and tailored experience.
- Balance excitement with realism—highlight both challenges and rewards in the role.
- Make it immersive with descriptive language and sensory details.
- Keep it structured yet engaging, ensuring a smooth and natural flow throughout the day."
            ],
            [
                "title" => "What you'd be doing most of the time as a {{career_title}}",
                'prompt' => "Generate a structured, easy-to-read summary of a typical workday based on real-world data from ONET's Work Activities {{work activities separated by comma}}, Detailed Work Activities {{detailed_work_activities}}, and Tasks {{work_activities}}, sections for the {{career_title}} career. The section should be expandable, showing the 5 most relevant activities first, with an option to expand for more details.

1. Data Extraction:
	•	Retrieve all relevant activities directly from the ONET for the given {{career_title}} as bullet points.
	•	Use both the 'Work Activities' and 'Detailed Work Activities' sections to provide a comprehensive overview.
2. Prioritization & Expandability:
	•	Identify the 5 most essential work activities for the career and display them first.
	•	Structure the section so that users can expand to see additional activities if they want more details.
	•	Ensure the first 5 bullet points capture the core responsibilities of the role.
3. Rewriting for Clarity & Engagement:
	•	Rephrase each activity in a friendly, engaging, and easy-to-understand manner.
	•	Avoid jargon while maintaining accuracy in describing the tasks.
	•	Ensure the descriptions make sense to someone who is new to the field.
4. Formatting for the Career Report:
	•	Present the information under the heading:
\"What you'd be doing most of the time as a {{career_title}}"
            ],
            [
                'title' => "What {{career_title}} say about their day-to-day job",
                "prompt" => "Generate an engaging, structured overview of the daily work environment for the specified career of {{career_title}} based on real-world data from ONET's Work Context: {{work_context}}. The section should be expandable, with 5 key aspects shown first and an option to expand for more insights.

1. Data Extraction:
	•	Retrieve all relevant work environment insights from ONET's Work Context: {{work_context}} for the given career {{career_title}}.
2. Prioritization & Expandability:
	•	Select the 5 most relevant aspects of the work environment and display them first.
	•	Structure the section so that users can expand to see additional insights if they want more details.
3. Formatting for the Career Report:
	•	Present the information under the heading:
\"What {{career_title}} say about their day-to-day job"
            ],
            [
                "title" => "Impact of this career on your personal life",
                "sub_title" => "The {{career_title}} life - The good, the bad, the inevitable",
                "prompt" => "Generate a relatable, humorous, and shareable list of 10 things that happen when you become a {{career_title}}—a mix of perks and struggles that naturally come with the job. These should be lighthearted, fun, and highly shareable while staying rooted in the realities of the profession.

AI Output Structure & Tone:
	•	Bullet Points: Create 10 relatable scenarios that professionals in this career experience.
	•	Tone: Funny, conversational, and highly shareable—like an inside joke for people in that field.
	•	Balance: The list should include both perks and struggles—things that make the job awesome and things that professionals love to complain about.
	•	No Negativity! Even \"struggles\" should be lighthearted, not discouraging.

Identify Career-Specific Perks & Challenges:

Use insights from the ONET database, including Work Context {{work_context}}, Work Activities {{work_activities}}, Detailed Work Activities {{detailed_work_activities}}, and Work Styles {{work_styles}} to determine common struggles in this career {{career_title}}.

For Perks:
	•	Identify cool things about the career that extend into personal life.
	•	Highlight industry skills that become useful outside of work.
	•	Include fun, slightly exaggerated observations (e.g., for graphic designers: \"Your gifts will always be next level.\" For doctors: \"Every person you know will call you for medical advice.\").

For Challenges:
	•	Incorporate industry-specific pain points such as tight deadlines, dealing with clients, repetitive tasks, technical challenges, or unusual work habits.
	•	Frame difficult aspects of the job in a humorous way (e.g., for designers: \"Clients will always say, 'I don't know what I want, but I'll know when I see it.'\"

Final instructions:
- Always generate exactly 10 points—a mix of perks and struggles.
- Use humor, exaggeration, and sarcasm lightly—keep it relatable, not offensive.
- Ensure industry-specific references to make the list hyper-relevant for each career.
- Always format the output consistently, so it looks polished and professional."
            ],
            [
                "title" => "The people you'll work with & meet as a {{career_title}}",
                "prompt" => "Generate a fun, engaging, and structured list that highlights the types of people a professional in the given career {{career_title}} will frequently interact with. The goal is to show how this career expands the user's professional network, status, and influence while making them excited about the opportunities to connect with high-profile individuals.

Instructions:
1. Identify Key People & Stakeholders
	•	Consider who the {{career_title}} professional reports to, collaborates with, and influences in their role.
	•	Include both direct colleagues and external contacts they frequently work with (e.g., for a marketing manager, this could be graphic designers, copywriters, and business executives).
2. Highlight High-Status & Influential Connections
	•	Identify opportunities where the professional might work directly with executives, entrepreneurs, influencers, investors, or other high-profile figures.
	•	Consider industries where networking could open major career doors (e.g., tech startups, entertainment, luxury brands).
	•	Emphasize roles where the professional shapes or influences industries (e.g., journalists working with politicians, designers working with celebrity brands).
3. Write an Engaging & Relatable List
	•	Use bullet points with emojis to make the section visually appealing.
	•	Each bullet point should have a bolded title to make the list easy to scan.
	•	Keep descriptions short, clear, and fun while emphasizing why these relationships are valuable for career growth.
	•	Aim for 8–10 unique groups of people, tailored to the specific career.

4. Ensure a Motivational Closing Statement
	•	End the section with a high-energy wrap-up that reinforces the idea that this career isn't just about work—it's about the powerful network and industry access it provides.
	•	Inspire the user by connecting their role to exciting opportunities, collaborations, and career advancement."
            ],
            [
                "title" => "How you can develop your career as a {{career_title}}",
                "sub_title" => "Typical Career Path",
                "prompt" => "Generate a structured career progression roadmap for the given {{career_title}}. Extract role progression and responsibilities from salary.com or other verified sources to ensure accuracy.

Instructions:

1. Structure the Career Path as Follows:
	•	Entry-Level Position: Start with the most junior role (e.g., internship, apprenticeship, assistant-level).
	•	Mid-Level Roles: Include progression stages that require more experience or certification (e.g., licensed, specialist, supervisor).
	•	Senior-Level Roles: Include leadership or high-expertise positions.
	•	Top-Level or Specialized Paths: Mention career advancements such as executive roles, business ownership, or specialized fields within the profession.

Each career level should include:
🔹 Job Title (Clear and standardized, e.g., \"Apprentice Electrician\")
Responsibilities: Briefly summarize key responsibilities at this stage.
Progression Timeline: Estimated years required to advance to the next level.
⚡ Specialization & Alternative Paths (Optional: If the career has multiple routes, include key specialization opportunities.)

2. Extract Reliable Career Data from:
	•	Salary.com (For average career timelines and salary expectations.)
	•	Other Industry-Specific Databases (If needed, refer to government or industry regulatory bodies.)

3. End with a Career Growth Note:
Note: Career progression can vary depending on [insert factors relevant to the career, such as licensing, specialization, industry growth, legislation in the country, freelance vs. corporate paths, etc.]


Example Output Format:
[Entry-Level Role] (First step in the profession, focused on training and learning.)
Responsibilities: [Briefly outline main tasks.]
|
| [Estimated Timeframe] (How long it takes before promotion.)
|
[Mid-Level Role] (More independent work, advanced skills, and responsibility.)
Responsibilities: [Describe role expectations and skill development.]
|
| [Estimated Timeframe]
|
[Senior-Level Role] (Supervisory or highly skilled position.)
Responsibilities: [Management, leadership, or high-stakes tasks.]
|
| [Estimated Timeframe]
|
[Top-Level Role / Specialization] (Highest level in the career or business ownership.)
Responsibilities: [Entrepreneurial, executive, or niche-specialized roles.]

Note: Career progression can vary depending on [insert factors relevant to the career, such as licensing, specialization, industry growth, legislation in the country, freelance vs. corporate paths, etc.]"
            ],
            [
                'title' => 'Education',
                'getDescription' => function ($data) {
                    return self::getEducationFormatted($data['education']);
                }
            ],
            [
                "prompt" => "Generate a structured explanation of the different routes into the career of {{career_title}}. The response should always prioritize alternative pathways first if the career allows entry without a formal degree. However, if a degree is mandatory (e.g., medical or legal careers), focus on the traditional educational route.

The structure should follow this format:
1. Alternative Pathways (If Applicable)
	•	Clearly state if a degree is not required and emphasize that hands-on experience, certifications, or training can sometimes be equally valuable.
	•	Provide examples of alternative ways to enter the field, such as:
	•	Obtaining Certifications
	•	Participating in Bootcamps & learning from Online Courses
	•	Freelance & Self-Taught Learning
	•	Internships & Apprenticeships (e.g., shadowing professionals or hands-on training in real-world environments).
2️⃣ Formal Degree Paths
	•	If a degree is preferred but not mandatory, mention that many professionals succeed without one but that formal education can still be an advantage.
	•	If a degree is required by law or licensing, emphasize that there are no alternative pathways and outline the mandatory education structure.
	•	List no more than 5 common degrees relevant for the career, including:
	•	Degree Name 1 (Brief explanation of focus).
	•	Degree Name 2 (Brief explanation of focus).
	•	Degree Name 3 (Brief explanation of focus)."
            ],
            [
                "title" => "Industries or specializations in {{career_title}} career",
                "prompt" => "Provide a list of up to 10 industries or specializations within the career of {{career_title}}. Help the user understand how the same job title can have distinct nuances depending on the field, offering them a broad view of potential career paths within the profession. Ensure the list is diverse and provides insights that reflect the range of opportunities in this career.
Highlight how the responsibilities, tasks, or day-to-day activities may vary across different industries and specializations.
For each industry or specialization, provide a brief description of how the role might differ (e.g., focus areas, dynamism, day-to-day tasks, work environment, unique challenges)."
            ],
            [
                'title' => "Industries or specializations where you might excel",
                'prompt' => "Based on the user's RIASEC scores: {{ppmScores}} and personality test results {{personality_profile}}, recommend industries or specializations within the {{career_title}} career where their unique profile would excel.
Select 3 industries or specializations from the general list above that best align with the user's strengths, interests, and personality traits.
For each recommended industry or specialization, explain why it suits the user's profile. Highlight which traits or skills make them a good fit for the work environment or responsibilities.
Inspire the user by showing how their unique qualities can lead to success and fulfillment in these areas."
            ],
            [
                'title' => "Related careers",
                'prompt' => "Enlist all Related Occupations from ONET database {{related_occupations}} associated with the {{career_title}}.
	1.	Data Extraction:
	•	Access the ONET database for the given career {{career_title}}.
	•	Extract the list of related careers, including the Career Title (e.g., Art Directors).
	2.	Formatting for the Report:
	•	Present the information in a clean, organized format, exactly as it is presented in the ONET database with a bullet list that includes the Career Title.
	•	3.	User-Friendly Explanation:
	•	Provide a brief introduction to this section:
\"The following careers are closely related to {{career_title}}. They share overlapping skills, tasks, or industries, making them logical alternatives or pivot opportunities for individuals exploring options in this field.\""
            ],
            [
                'title' => "Freelancing and Entrepreneurship",
                'prompt' => "Generate a relevant and practical response based on the {{career_title}} career's feasibility for self-employment or business ownership. Follow these structured guidelines:

1. Determine Feasibility
	•	Use industry insights to assess whether freelancing or entrepreneurship is a viable career path.
	•	If freelancing or running a business is uncommon or not feasible (e.g., for surgeons or airline pilots), state this clearly and concisely and do not include further details.
	•	If freelancing is a possibility, proceed with the next sections.
2. Freelancing Opportunities (If Applicable)
	•	Explain how freelancing is typically pursued in this field (e.g., contract work, project-based gigs, or running an independent business).
	•	Mention common freelancing platforms, marketplaces, or networks where professionals find clients (only if applicable to this career).
3. Entrepreneurship Possibilities (If Applicable)
	•	If relevant, describe ways professionals in this field can start their own business (e.g., opening an independent design agency, starting a consultancy, launching a product/service).
	•	If entrepreneurship is rare but possible with advanced experience, mention that professionals typically gain experience first before transitioning into business ownership.
4. Practical Advice for Freelancing or Entrepreneurship
	•	Provide two to three actionable tips for getting started. Examples include:
	•	Building a client base – Using online platforms, networking, or word-of-mouth referrals.
	•	Creating a strong portfolio – Showcasing work through websites, social media, or professional platforms.
	•	Learning business skills – Managing finances, pricing services, and negotiating contracts.
	•	If industry experience is recommended first, mention that it helps professionals build confidence, credibility, and a strong portfolio before going solo.

Final instructions
	•	Heading: \"Freelancing & Entrepreneurship\"
	•	If applicable: Provide a short introduction about the potential for freelancing or business ownership.
	•	Use bullet points for key tips and advice.
	•	If not applicable: Clearly state that freelancing or business ownership is not a common path in this career and avoid unnecessary elaboration."
            ],
            [
                'title' => "First steps to start your career as a {{career_title}}",
                'prompt' => "Generate a structured and practical guide that provides the most relevant learning resources, certifications, communities, and opportunities tailored to this career {{career_title}}. Ensure that the recommendations align with industry standards and best practices.

Follow this structure:
Online Courses
	•	Identify 2–3 highly rated courses from platforms such as Coursera, Udemy, LinkedIn Learning, edX, or other reputable sites.
	•	If certification is useful in this field, mention courses that lead to recognized credentials.

Certifications
	•	List industry-recognized certifications that can improve job prospects.
	•	Explain the relevance of each certification and where to obtain it.
	•	If no certifications are required, omit this section.

Books & Articles
	•	Recommend 2–3 must-read books that provide foundational knowledge, technical skills, or industry insights.
	•	Suggest online articles, blogs, or industry publications where professionals can stay updated.

Podcasts & Videos
	•	List 2–3 career-relevant podcasts, YouTube channels, or video series for continuous learning.

Technology & Tools
	•	Provide a list of essential software, tools, or platforms commonly used in this profession.
	•	For technical careers: List coding languages, engineering tools, or industry-standard platforms.
	•	For creative careers: Include design software, video editing tools, or industry-specific apps.
	•	For business-related roles: Mention CRM systems, project management tools, or financial software.

Communities & Networking Groups
	•	List 2–3 professional communities, LinkedIn groups, or forums where beginners can network and learn from experts.
	•	Mention professional associations that offer events, mentorship, and job boards.

Opportunities to Gain Experience
	•	Provide entry points into the career, such as:
	•	Internships & Volunteering: Where to look for hands-on experience.
	•	Competitions: If applicable, mention industry-specific contests that help build experience.
	•	Freelancing & Side Projects: If relevant, suggest beginner-friendly platforms or project ideas.
	•	Hackathons or Bootcamps: If useful for skill-building, highlight such opportunities.

Personal Projects
	•	Suggest ways beginners can practice their skills through self-initiated projects.
	•	Provide ideas for portfolio-building projects, such as mock assignments or real-world applications.
	•	Encourage documentation of progress for resume and interview preparation.
	•	If no personal projects can be done in this career, omit this section.

Final Instructions
- Ensure all recommendations are career-specific and up to date.
- Prioritize the most impactful resources to avoid overwhelming the user.
- If a section is irrelevant for a career, omit it entirely instead of adding generic content.
- Keep explanations concise yet practical, ensuring each resource has clear value.
- Format using clear bullet points and bold key terms for readability."
            ],
            [
                'title' => "What it takes to be successful as a {{career_title}}",
                'prompt' => "Generate a structured and well-formatted response that outlines the essential Skills {{skills}}, Abilities {{abilities}}, Work Styles {{work_styles}}, and Knowledge {{knowledge}} required for success in the {{career_title}}. Use data directly from the O*NET database, ensuring accuracy and relevance. Follow the exact format and structure provided below.

1. Formatting & Structure Requirements
Ensure the output follows this exact structure for readability and consistency:
Skills
	•	Introduction: Explain that skills are learned abilities that can be developed over time through training, education, and practice.
	•	List five most important skills from O*NET's \"Skills\" section.
	•	Display the skills in bold, followed by a short definition in clear and simple language.
Abilities
	•	Introduction: Define abilities as inherent traits that influence how effectively someone performs in a role. Mention that these qualities are typically natural and not acquired through training.
	•	List five most important abilities from O*NET's \"Abilities\" section.
	•	Display each ability in bold, followed by a short definition.
Work styles
	•	Introduction: Explain that work styles describe personal characteristics and behaviors that affect how well someone adapts to the job. Mention that these traits influence motivation, work habits, and job performance.
	•	List five most important work styles from O*NET's \"Work Styles\" section.
	•	Display each work style in bold, followed by a short definition.
Knowledge
	•	Introduction: Define knowledge as an organized set of principles and facts necessary for performing well in this career.
	•	List five most important knowledge areas from O*NET's \"Knowledge\" section.
	•	Display each knowledge area in bold, followed by a short definition.

2. Content Selection & Prioritization Rules
	•	Select only the five most critical attributes from each category, ensuring relevance to the career.
	•	If there are more than five relevant options, prioritize based on:
	•	Core job functions (Does this directly impact job performance?)
	•	Industry demand (Are employers actively seeking this attribute?)
	•	Career progression (Is this skill/trait necessary for long-term success?)
•	User's career search (What was the career searched by the user?)
	•	Any remaining attributes will only be shown upon expansion by the user.

3. Formatting Guidelines for AI
- Headings must follow this exact structure:
	•	Skills
	•	Abilities
	•	Work Styles
	•	Knowledge
- Introduction for each section should be concise, informative, and set clear expectations.
- Bullet point format should be:
	•	[Attribute Name] — [Short explanation]
Example:
	•	Critical Thinking — Using logic and reasoning to identify the strengths and weaknesses of alternative solutions, conclusions, or approaches to problems.
- No unnecessary elaboration – Keep definitions simple and easy to understand and take them exactly as they are phrased from ONET database.
- Maintain clarity and engagement – Ensure the final output is structured, informative, and easy to read."
            ],
            [
                'title' => "Famous people with this career",
                'prompt' => "Identify 5 well-known individuals who have excelled in this profession of {{career_title}}.
Include:
	1.	Prominent Names: List several famous people associated with this profession and mention their achievements or contributions.
	2.	Diverse Examples: Ensure a mix of historical and modern figures, if applicable, to showcase the profession's evolution and relevance.
	3.	Inspiration for the User: Provide a brief description of why these individuals are notable, highlighting their unique impact or accomplishments in the field.
Ensure the response is motivational and gives the user role models to look up to within this career"
            ]
        ];
    }

}
