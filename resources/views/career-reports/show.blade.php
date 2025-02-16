@extends('layout')
@section('title', 'Career Report')
@section('content')
    <div class="max-w-4xl mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold mb-6">Career Report</h1>

        @foreach($content as $sectionId => $section)
            <div class="mb-8">
                <h2 class="text-2xl font-semibold mb-4">{{ $section['title'] }}</h2>

                @if(!empty($section['description']))
                    <p class="mb-4 text-gray-600">{{ $section['description'] }}</p>
                @endif

                @if(!empty($section['response']))
                    @if($sectionId === 'overview' && isset($section['response']['content']))
                        <p class="text-gray-800">{{ $section['response']['content'] }}</p>
                    @endif

                    @if($sectionId === 'tasks_and_responsibilities' && isset($section['response']['responsibilities']))
                        <ul class="list-disc list-inside space-y-2">
                            @foreach($section['response']['responsibilities'] as $responsibility)
                                <li class="text-gray-800">{{ $responsibility }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if($sectionId === 'compatibility_score' && isset($section['response']['content']))
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <p class="mt-2 text-gray-800">{{ $section['response']['content'] }}</p>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach

    </div>
@endsection
