<?php

namespace App\Http\Controllers;

use App\Exceptions\PrinciplesApiException;
use App\Models\Student;
use App\Services\PrinciplesService;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonalityTestController extends Controller
{
    protected PrinciplesService $principlesService;

    public function __construct(PrinciplesService $principlesService)
    {
        $this->principlesService = $principlesService;
    }

    /**
     * Display the testing page for the personality test, depends on the progress of the student.
     *
     * @param Request $request
     * @param string $studentUid
     * @param bool $showQuestions
     * @return View|Application|Factory|\Illuminate\Contracts\Foundation\Application
     * @throws PrinciplesApiException
     */
    public function index(Request $request, string $studentUid, bool $showQuestions = false): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application
    {
        $student = Student::where('uid', $studentUid)->firstOrFail();
        $nextQuestions = $this->principlesService->getNextQuestions($student->principles_account_uid);
        $fractionComplete = $nextQuestions['assessmentProgress']['fractionComplete'];
        $assessmentComplete = $nextQuestions['assessmentProgress']['assessmentComplete'];

        if( $assessmentComplete ) {
            try {
                $ppmOccupations = $this->principlesService->getPpmOccupations($student->principles_account_uid);
            } catch (PrinciplesApiException $exception) {
                $ppmOccupations = ['ppmOccupations' => []];
            }
            // usort($ppmOccupations['occupations'], function($a, $b) {
            //     return $a['errorMargin'] <=> $b['errorMargin'];
            // });
            $top10Occupations = array_slice($ppmOccupations['occupations'], 0, 10);

            $top10Occupations = array_map(function($occupation) {
                $occupation['onet_url'] = "https://www.onetonline.org/link/summary/" . $occupation['socCode'];
                return $occupation;
            }, $top10Occupations);

            try {
                $ppmScores = $this->principlesService->getPpmScores($student->principles_account_uid);
            } catch (PrinciplesApiException $exception) {
                echo 'PPM scores not found '.$exception->getMessage();
            }

            return view('personality-test.complete', [
                'student' => $student,
                'occupations' => $top10Occupations,
                'ppmScores' => $ppmScores['ppmScore']
            ]);
        }

        if( $fractionComplete === 0 && !$showQuestions ) {
            return view('personality-test.start', [
                'student' => $student,
            ]);
        }

        return view('personality-test.questions', [
            'student' => $student,
            'fractionComplete' => $fractionComplete,
            'questions' => $nextQuestions['questions'],
            'answers' => PrinciplesService::QUESTION_POSSIBLE_ANSWERS,
        ]);
    }

    /**
     * Store the answers to the personality test.
     *
     * @param Request $request
     * @param string $studentUid
     * @return RedirectResponse
     * @throws PrinciplesApiException
     */
    public function store(Request $request, string $studentUid): \Illuminate\Http\RedirectResponse
    {
        $student = Student::where('uid', $studentUid)->firstOrFail();

        // Transform the given params from the radio inputs into a validatable structure.
        try {
            $request->merge([
                'answers' => collect($request->toArray())
                    ->filter(fn($value, $key) => is_numeric($key) && is_numeric($value))
                    ->map(function ($answer, $question) {
                        return [
                            'questionNumber' => (int) $question,
                            'answerNumber' => (int) $answer,
                        ];
                    })->toArray()
            ]);
        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Invalid answers provided.');
        }


        $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.questionNumber' => ['required', 'integer', 'min:1'],
            'answers.*.answerNumber' => ['required', 'integer', Rule::in(
                array_values(array_map(fn ($a) => $a['value'],PrinciplesService::QUESTION_POSSIBLE_ANSWERS))
            )],
        ]);


        $this->principlesService->storeAnswers($student->principles_account_uid, array_values($request->get('answers')));

        return redirect()->route('personality-test.index', [
            'studentUid' => $studentUid,
            'showQuestions' => true,
        ]);
    }

    /**
     * Show the PDF of the personality test results.
     *
     * @param Request $request
     * @param string $studentUid
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws PrinciplesApiException
     */
    public function showPdf(Request $request, string $studentUid): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $student = Student::where('uid', $studentUid)->firstOrFail();

        $pdfResults = $this->principlesService->getPdfResults($student->principles_account_uid);

        return response()->streamDownload(function() use ($pdfResults) {
            echo $pdfResults;
        }, 'personality-test-results.pdf');
    }
}
