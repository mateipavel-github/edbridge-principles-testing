@extends('layout')

@section('title', 'Personality Test - Questions')

@section('content')
    <div class="max-w-xs mt-16 text-center flex flex-col items-center pb-6">
        <span class="font-bold">Congratulations!</span>

        <p class="mt-2">
            Thank you for completing the test. Your Edbridge Academy teaching assistant will be in touch to discuss your results.
        </p>

        <a class="mt-10 py-4 px-4 border border-black rounded-xl" href="{{ route('personality-test.show-pdf', ['studentUid' => $student->uid]) }}">
            Download personality profile
        </a>

        <div class="w-full mt-12">
            <span class="font-bold block mb-8">RIASEC scores</span>

            @foreach( $riasecScores as $topic => $score )
                <div class="flex items-center text-left justify-between mt-3 w-full">
                    <span>{{ $topic }}</span>
                    <span class="ml-4">{{ round($score, 2) }}</span>
                </div>
            @endforeach
        </div>

        <div class="w-full mt-12">
            <span class="font-bold block mb-8">Careers you might find interesting</span>

            @foreach( $occupations as $occupation )
                <div class="flex items-center text-left justify-between mt-3 w-full">
                    <span>{{ $occupation['occupation'] }}</span>
                    <span class="ml-4">{{ $occupation['errorMargin'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endsection
