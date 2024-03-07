@extends('layout')

@section('title', 'Personality Test - Start')

@section('content')
    <div class="max-w-xs mt-8">
        <span class="font-bold">Hello, {{ $student->first_name . ' ' . $student->last_name }}</span>

        <p class="mt-2">
            This test will take approximately 40-45 minutes. Upon completion, you will receive a detailed report, a RIASEC score and a list of potentially interesting careers that might match your personality profile.
        </p>
    </div>

    <div class="max-w-xs mt-8">
        <span class="font-bold">How to complete the test?</span>

        <p class="mt-2">
            Mark each statement on a scale from “Disagree strongly” to “Agree strongly”.
        </p>
    </div>

    <div class="max-w-xs mt-8 flex items-center justify-center">
        <button class="bg-primary uppercase px-12 py-4 rounded-full font-bold">
            <a href="{{ route('personality-test.index', ['studentUid' => $student->uid, 'showQuestions' => true]) }}"
               class="text-gray-primary">
                START TEST
            </a>
        </button>
    </div>
@endsection
