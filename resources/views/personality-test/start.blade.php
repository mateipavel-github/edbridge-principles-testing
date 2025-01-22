@extends('layout')

@section('title', 'Personality Test - Start')

@section('content')
    <div class="max-w-xs">
        <div class="mt-8">
            <span class="font-bold">Hello, {{ $student->first_name . ' ' . $student->last_name }}</span>

            <p class="mt-2">
                This test will take approximately 40-45 minutes. Upon completion, you will receive a detailed personality report, a RIASEC score and a list of potentially interesting careers that match your personality profile.
            </p>
        </div>

        <div class="mt-8">
            <span class="font-bold">How to complete the test?</span>

            <p class="mt-2">
                Mark each statement on a scale from “Disagree strongly” to “Agree strongly”. <b>Important: answer truthfully based on what you think about yourself in the present, don't reply based on what you want to be true or what you think is expected of you.</b>
            </p>
        </div>

        <div class="mt-8 flex items-center justify-center">
            <a class="bg-primary uppercase px-12 py-4 rounded-full font-bold text-gray-primary"
               href="{{ route('personality-test.index', ['studentUid' => $student->uid, 'showQuestions' => true]) }}">
                START TEST
            </a>
        </div>
    </div>
@endsection
