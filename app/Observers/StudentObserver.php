<?php

namespace App\Observers;

use App\Exceptions\PrinciplesApiException;
use App\Models\Student;
use App\Services\PrinciplesService;

class StudentObserver
{
    /**
     * Handle the Student "creating" event.
     *
     * @param \App\Models\Student $student
     * @return void
     * @throws \Exception
     */
    public function creating(Student $student): void
    {
        $principlesService = new PrinciplesService();
        $response = $principlesService->createStudent($student->email, "{$student->first_name} {$student->last_name}");

        $student->principles_account_uid = $response['account_id'];
        $student->principles_person_uid = $response['person_id'];
    }
}
