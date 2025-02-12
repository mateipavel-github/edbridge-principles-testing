@extends('layout')

@section('title', 'Personality Test - Questions')

@section('content')
<div class="max-w-xs mt-16 text-center flex flex-col items-center pb-6">
    <span class="font-bold">Congratulations!</span>

    <p class="mt-2">
        Thank you for completing the test. We will be in touch with you shortly.
    </p>

    <a class="mt-10 py-4 px-4 border border-black rounded-xl"
        href="{{ route('personality-test.show-pdf', ['studentUid' => $student->uid]) }}">
        Download personality profile
    </a>

    <div class="w-full mt-12">
        <span class="font-bold block mb-8">PPM score (formerly RIASEC)</span>
        @foreach($ppmScores as $topic => $score)
            <div class="flex items-center text-left justify-between mt-3 w-full">
                <span>{{ ucfirst($topic) }}</span>
                <span class="ml-4">{{ round($score['rawScore'], 2) }} ({{ round($score['rawScore']/7*100, 2) }}%)</span>
            </div>
        @endforeach
    </div>

    <div class="w-full mt-12">
        <span class="font-bold block mb-8">Career Matches</span>

        <div class="mb-4">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex" aria-label="Tabs">
                    <button class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm cursor-pointer tab-button active" data-tab="best-matches">
                        Top 50 occupations
                    </button>
                    <button class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm cursor-pointer tab-button" data-tab="lowest-matches">
                        Bottom 50 occupations
                    </button>
                </nav>
            </div>
        </div>

        <div id="best-matches" class="tab-content">
            <table class="text-left w-full">
                @foreach($top50occupations as $occupation)
                    <tr class="border-b">
                        <td class="py-4">
                            <div class="flex flex-col">
                                <div class="flex justify-between items-start">
                                    <a class="text-base text-purple-600 hover:text-purple-800 font-medium" href="{{$occupation['onet_url']}}">
                                        {{ $occupation['occupation'] }}
                                    </a>
                                    <span class="ml-4 text-sm">
                                        {{ round($occupation['errorMargin'] * 100, 1) }}%
                                        @if($occupation['fit'])
                                            <br><span class="text-xs text-gray-500">{{ $occupation['fit'] }}</span>
                                        @endif
                                    </span>
                                </div>
                                @if(!empty($occupation['description']))
                                    <div class="mt-2 text-sm text-gray-600">
                                        {{ $occupation['description'] }}
                                    </div>
                                @endif
                                @if(!empty($occupation['jobTitles']))
                                    <div class="mt-2 text-sm text-gray-600">
                                        <div class="job-titles-container">
                                            <strong>Job Titles:</strong> 
                                            <ul class="list-disc list-inside mt-1">
                                                @foreach(array_slice($occupation['jobTitles'], 0, 5) as $title)
                                                    <li class="job-titles-preview ml-2">{{ $title }}</li>
                                                @endforeach
                                                @if(count($occupation['jobTitles']) > 5)
                                                    <div class="job-titles-full hidden">
                                                        @foreach(array_slice($occupation['jobTitles'], 5) as $title)
                                                            <li class="ml-2">{{ $title }}</li>
                                                        @endforeach
                                                    </div>
                                                    <button class="text-purple-600 hover:text-purple-800 mt-1 toggle-job-titles">
                                                        <span class="more-text">Show more titles</span>
                                                        <span class="less-text hidden">Show less</span>
                                                    </button>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div id="lowest-matches" class="tab-content hidden">
            <table class="text-left w-full">
                @foreach($bottom50occupations as $occupation)
                    <tr class="border-b">
                        <td class="py-4">
                            <div class="flex flex-col">
                                <div class="flex justify-between items-start">
                                    <a class="text-base text-purple-600 hover:text-purple-800 font-medium" href="{{$occupation['onet_url']}}">
                                        {{ $occupation['occupation'] }}
                                    </a>
                                    <span class="ml-4 text-sm">
                                        {{ round($occupation['errorMargin'] * 100, 1) }}%
                                        @if($occupation['fit'])
                                            <br><span class="text-xs text-gray-500">{{ $occupation['fit'] }}</span>
                                        @endif
                                    </span>
                                </div>
                                @if(!empty($occupation['description']))
                                    <div class="mt-2 text-sm text-gray-600">
                                        {{ $occupation['description'] }}
                                    </div>
                                @endif
                                @if(!empty($occupation['jobTitles']))
                                    <div class="mt-2 text-sm text-gray-600">
                                        <div class="job-titles-container">
                                            <strong>Job Titles:</strong> 
                                            <ul class="list-disc list-inside mt-1">
                                                @foreach(array_slice($occupation['jobTitles'], 0, 5) as $title)
                                                    <li class="job-titles-preview ml-2">{{ $title }}</li>
                                                @endforeach
                                                @if(count($occupation['jobTitles']) > 5)
                                                    <div class="job-titles-full hidden">
                                                        @foreach(array_slice($occupation['jobTitles'], 5) as $title)
                                                            <li class="ml-2">{{ $title }}</li>
                                                        @endforeach
                                                    </div>
                                                    <button class="text-purple-600 hover:text-purple-800 mt-1 toggle-job-titles">
                                                        <span class="more-text">Show more titles</span>
                                                        <span class="less-text hidden">Show less</span>
                                                    </button>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.tab-button');
                const contents = document.querySelectorAll('.tab-content');

                tabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        // Remove active class from all tabs
                        tabs.forEach(t => t.classList.remove('active', 'border-purple-500', 'text-purple-600'));
                        tab.classList.add('active', 'border-purple-500', 'text-purple-600');

                        // Hide all contents
                        contents.forEach(content => content.classList.add('hidden'));
                        
                        // Show selected content
                        document.getElementById(tab.dataset.tab).classList.remove('hidden');
                    });
                });

                // Set initial active state
                document.querySelector('.tab-button.active').classList.add('border-purple-500', 'text-purple-600');

                // Add job titles toggle functionality
                document.querySelectorAll('.toggle-job-titles').forEach(button => {
                    button.addEventListener('click', function() {
                        const container = this.closest('.job-titles-container');
                        const preview = container.querySelector('.job-titles-preview');
                        const full = container.querySelector('.job-titles-full');
                        const moreText = this.querySelector('.more-text');
                        const lessText = this.querySelector('.less-text');

                        preview.classList.toggle('hidden');
                        full.classList.toggle('hidden');
                        moreText.classList.toggle('hidden');
                        lessText.classList.toggle('hidden');
                    });
                });
            });
        </script>
    </div>
</div>

<style>
    .job-titles-container {
        display: inline;
    }
</style>
@endsection