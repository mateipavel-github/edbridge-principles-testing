<?php

namespace App\Observers;

use App\Models\Student;
use App\Services\PrinciplesService;
use Illuminate\Support\Str;

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

        do {
            $uid = Str::random(32);
        } while (Student::where('uid', $uid)->exists());

        $student->uid = $uid;
    }
}
