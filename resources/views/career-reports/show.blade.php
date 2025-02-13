@extends('layout')
@section('title', 'Career Report')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    @if(isset($content['haiku']))
        <div class="mb-12 bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-bold mb-6">{{ $content['haiku']['title'] }}</h2>
            <div class="prose">
                @if(empty($content['haiku']['response']))
                    <div class="flex justify-center items-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                        <span class="ml-2 text-gray-600">Generating content...</span>
                    </div>
                @else
                    @foreach($content['haiku']['response']['lyrics'] as $line)
                        <p class="text-lg italic text-gray-700">{{ $line }}</p>
                    @endforeach
                @endif
            </div>
        </div>
    @endif

    @if(isset($content['insights']))
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-bold mb-6">{{ $content['insights']['title'] }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if(empty($content['insights']['response']))
                    <div class="col-span-2 flex justify-center items-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                        <span class="ml-2 text-gray-600">Generating insights...</span>
                    </div>
                @else
                    @foreach($content['insights']['response'] as $insight)
                        <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start space-x-4">
                                <span class="text-2xl">{{ $insight['emoji'] }}</span>
                                <div>
                                    <h3 class="font-semibold text-lg mb-2">{{ $insight['insight_title'] }}</h3>
                                    <p class="text-gray-600">{{ $insight['insight_description'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endif

    @if(isset($content['long_description']))
        <div class="bg-white rounded-lg shadow-sm p-8">
            <h2 class="text-2xl font-bold mb-6">{{ $content['insights']['title'] }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if(empty($content['long_description']['response']))
                    <div class="col-span-2 flex justify-center items-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                        <span class="ml-2 text-gray-600">Generating insights...</span>
                    </div>
                @else
                    <article class="prose lg:prose-xl">
                        {!! nl2br(e($content['long_description']['response']['essay'])) !!}
                    </article>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
