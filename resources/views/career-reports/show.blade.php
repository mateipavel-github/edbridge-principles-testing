@extends('career-reports.layout')

@section('title', 'Career Report: Actor')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header with Compatibility Score --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <h1 class="text-3xl font-bold mb-4">Career Report: Actor</h1>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg ml-4 text-center min-w-[200px]">
                <h2 class="text-xl font-semibold text-blue-800">Compatibility Score</h2>
                <div class="text-4xl font-bold text-blue-600 mt-2">60%</div>
            </div>
        </div>
        <p class="text-gray-600 mt-4">{{ $content['compatibility_score']['response']['content'] }}</p>
        
        {{-- Overview Section --}}
        <div class="mt-6 pt-6 border-t border-gray-200">
            <h2 class="text-xl font-semibold mb-3">Overview</h2>
            <p class="text-gray-600">{{ $content['overview']['response']['content'] }}</p>
        </div>
    </div>

    {{-- Tasks and Responsibilities --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['tasks_and_responsibilities']['title'] }}</h2>
        <ul class="space-y-3">
            @foreach($content['tasks_and_responsibilities']['response']['responsibilities'] as $task)
                <li class="flex items-start">
                    <span class="text-blue-500 mr-2">•</span>
                    <span>{{ $task }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Salary Information --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['average_salary']['title'] }}</h2>
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg text-center">
                <div class="text-sm text-gray-600">Average Hourly</div>
                <div class="text-2xl font-bold text-gray-800">{{ $content['average_salary']['response']['average salary'][0]['hourly'] }}</div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg text-center">
                <div class="text-sm text-gray-600">Average Annual</div>
                <div class="text-2xl font-bold text-gray-800">{{ $content['average_salary']['response']['average salary'][0]['annual'] }}</div>
            </div>
        </div>
        <div class="space-y-4">
            @foreach($content['average_salary']['response']['description'] as $factor)
                <div class="border-l-4 border-blue-500 pl-4">
                    <h3 class="font-semibold">{{ $factor['Factor'] }}</h3>
                    <p class="text-gray-600">{{ $factor['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Safety Score and Industry Outlook --}}
    <div class="grid md:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-4">Career Safety</h2>
            <div class="mb-4">
                <span class="inline-block px-3 py-1 rounded-full text-white bg-yellow-500">{{ $content['safety_score']['response']['rating'] }}</span>
            </div>
            <p class="mb-4">{{ $content['safety_score']['response']['explanation'] }}</p>
            @foreach($content['safety_score']['response']['factors'] as $factor)
                <div class="mb-4">
                    <h3 class="font-semibold">{{ $factor['title'] }}</h3>
                    <p class="text-gray-600">{{ $factor['description'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-4">Work-Life Balance</h2>
            <div class="mb-4">
                <span class="inline-block px-3 py-1 rounded-full text-white bg-red-500">{{ $content['worklife_balance']['response']['rating'] }}</span>
            </div>
            @foreach($content['worklife_balance']['response']['items'] as $item)
                <div class="mb-4">
                    <h3 class="font-semibold">{{ $item['title'] }}</h3>
                    <p class="text-gray-600">{{ $item['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Work Setting and Flexibility --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <div class="grid md:grid-cols-2 gap-8">
            <div>
                <h2 class="text-2xl font-bold mb-4">Work Setting</h2>
                <div class="space-y-4">
                    <div class="flex items-center">
                        <span class="font-semibold mr-2">Type:</span>
                        <span>{{ $content['work_setting']['response']['typical_setting'] }}</span>
                    </div>
                    <div>
                        <span class="font-semibold">Industries:</span>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($content['work_setting']['response']['industries'] as $industry)
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">{{ $industry }}</span>
                            @endforeach
                        </div>
                    </div>
                    <p>{{ $content['work_setting']['response']['summary'] }}</p>
                </div>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-4">Flexibility</h2>
                <div class="mb-4">
                    <span class="inline-block px-3 py-1 rounded-full text-white bg-green-500">{{ $content['flexibility']['response']['rating'] }}</span>
                </div>
                <div class="space-y-4">
                    <p><span class="font-semibold">Freelancing:</span> {{ $content['flexibility']['response']['freelancing'] }}</p>
                    <p><span class="font-semibold">Schedule:</span> {{ $content['flexibility']['response']['schedule'] }}</p>
                    <p><span class="font-semibold">Deadlines:</span> {{ $content['flexibility']['response']['deadlines'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Paycheck Time and Pivot Potential --}}
    <div class="grid md:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-4">Time to First Paycheck</h2>
            <div class="mb-4">
                <span class="inline-block px-3 py-1 rounded-full text-white bg-yellow-500">{{ $content['paycheck_time']['response']['rating'] }}</span>
            </div>
            @foreach($content['paycheck_time']['response']['items'] as $item)
                <div class="mb-4">
                    <h3 class="font-semibold">{{ $item['title'] }}</h3>
                    <p class="text-gray-600">{{ $item['description'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-4">Pivot Potential</h2>
            <div class="mb-4">
                <span class="inline-block px-3 py-1 rounded-full text-white bg-yellow-500">{{ $content['pivot_potential']['response']['rating'] }}</span>
            </div>
            @foreach($content['pivot_potential']['response']['items'] as $item)
                <div class="mb-4">
                    <h3 class="font-semibold">{{ $item['title'] }}</h3>
                    <p class="text-gray-600">{{ $item['description'] }}</p>
                </div>
            @endforeach
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600">{{ $content['pivot_potential']['response']['explanation'] }}</p>
            </div>
        </div>
    </div>

    {{-- Personal Fit Analysis --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['you_as_a']['title'] }}</h2>
        <div class="prose max-w-none">
            {!! nl2br(e($content['you_as_a']['response']['content'])) !!}
        </div>
    </div>

    {{-- Compatibility Table --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['compatibility_table']['title'] }}</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attribute</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Importance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Score</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($content['compatibility_table']['response']['attributes'] as $attribute)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $attribute['name'] }}</div>
                                <div class="text-sm text-gray-500">{{ $attribute['explanation'] }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $attribute['importance'] === 'High' ? 'bg-green-100 text-green-800' : 
                                       ($attribute['importance'] === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 
                                       'bg-gray-100 text-gray-800') }}">
                                    {{ $attribute['importance'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 rounded-full h-2" style="width: {{ ($attribute['your_score'] / 6) * 100 }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ number_format($attribute['your_score'], 1) }}/6</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Things You Might Enjoy/Dislike --}}
    <div class="grid md:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-4">{{  $content['top_enjoy']['title'] }}</h2>
            @foreach($content['top_enjoy']['response']['items'] as $item)
                <div class="mb-6">
                    <h3 class="font-semibold text-lg text-blue-800">{{ $item['title'] }}</h3>
                    <p class="text-gray-600 mt-2">{{ $item['description'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold mb-4">{{ $content['top_dislike']['title'] }}</h2>
            @foreach($content['top_dislike']['response']['items'] as $item)
                <div class="mb-6">
                    <h3 class="font-semibold text-lg text-red-800">{{ $item['title'] }}</h3>
                    <p class="text-gray-600 mt-2">{{ $item['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- A Day in the Life --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['oneday_life']['title'] }}</h2>
        <div class="prose max-w-none text-gray-600">
            {!! nl2br(e($content['oneday_life']['response']['content'])) !!}
        </div>
    </div>

    {{-- Daily Activities --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['daily_activities']['title'] }}</h2>
        <div class="space-y-6">
            @foreach($content['daily_activities']['response']['activities'] as $activity)
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-blue-500 mr-4"></div>
                    <div>
                        <h3 class="font-semibold text-lg">{{ $activity['title'] }}</h3>
                        <p class="text-gray-600 mt-1">{{ $activity['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Professionals Survey (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['professionals_survey']['title'] }}</h2>
        @if($content['professionals_survey']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Survey data not available</p>
        @endif
    </div>

    {{-- Perks and Challenges --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['perks_and_challenges']['title'] }}</h2>
        <div class="grid md:grid-cols-2 gap-6">
            @foreach($content['perks_and_challenges']['response']['content'] as $item)
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold text-lg text-blue-800 mb-2">{{ $item['title'] }}</h3>
                    <p class="text-gray-600">{{ $item['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Work People --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Who You'll Work With</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($content['work_people']['response']['content'] as $person)
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-lg mb-2">{{ $person['title'] }}</h3>
                    <p class="text-gray-600 text-sm">{{ $person['description'] }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <p class="text-blue-800">{{ $content['work_people']['response']['closingStatement'] }}</p>
        </div>
    </div>

    {{-- Career Path --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['career_path']['title'] }}</h2>
        <div class="space-y-6">
            @foreach($content['career_path']['response']['path'] as $step)
                <div class="relative pl-8 pb-6 border-l-2 border-blue-200 last:border-l-0">
                    <div class="absolute left-0 top-0 w-4 h-4 rounded-full bg-blue-500 -translate-x-[9px]"></div>
                    <div class="mb-2">
                        <span class="inline-block px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 mb-2">{{ $step['timeframe'] }}</span>
                        <h3 class="text-lg font-semibold">{{ $step['job_title'] }}</h3>
                        <div class="text-sm text-gray-500">{{ $step['career_level'] }}</div>
                    </div>
                    <p class="text-gray-600">{{ $step['responsibilities'] }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">{{ $content['career_path']['response']['growth_note'] }}</p>
        </div>
    </div>

    {{-- Education (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['education']['title'] }}</h2>
        @if($content['education']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Education data not available</p>
        @endif
    </div>

    {{-- Educational Path --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Educational Pathways</h2>
        <div class="grid md:grid-cols-2 gap-8">
            {{-- Alternative Pathways --}}
            <div>
                <h3 class="text-xl font-semibold mb-4">Alternative Pathways</h3>
                <div class="space-y-4">
                    @foreach($content['educational_path']['response']['alternative_pathways'] as $pathway)
                        <div class="border-l-4 border-blue-500 pl-4">
                            <h4 class="font-semibold">{{ $pathway['title'] }}</h4>
                            <p class="text-gray-600">{{ $pathway['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Formal Degree Paths --}}
            <div>
                <h3 class="text-xl font-semibold mb-4">Formal Education</h3>
                <div class="space-y-4">
                    @foreach($content['educational_path']['response']['formal_degree_paths'] as $path)
                        <div class="border-l-4 border-green-500 pl-4">
                            <h4 class="font-semibold">{{ $path['degree'] }}</h4>
                            <p class="text-gray-600">{{ $path['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Industries (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Industries</h2>
        @if($content['industries']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Industry data not available</p>
        @endif
    </div>

    {{-- User Industries (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">User Industries</h2>
        @if($content['user_industries']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">User industry data not available</p>
        @endif
    </div>

    {{-- Job Titles (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Job Titles</h2>
        @if($content['job_titles']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Job titles data not available</p>
        @endif
    </div>

    {{-- Related Careers (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Related Careers</h2>
        @if($content['related_careers']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Related careers data not available</p>
        @endif
    </div>

    {{-- Entrepreneurship --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Entrepreneurship Opportunities</h2>
        <p class="text-gray-600 mb-6">{{ $content['entrepreneurship']['response']['overview'] }}</p>

        <div class="grid md:grid-cols-2 gap-8">
            {{-- Freelancing Opportunities --}}
            <div>
                <h3 class="text-xl font-semibold mb-4">Freelancing Opportunities</h3>
                <div class="space-y-4">
                    @foreach($content['entrepreneurship']['response']['freelancing_opportunities'] as $opportunity)
                        <div class="border-l-4 border-blue-500 pl-4">
                            <h4 class="font-semibold">{{ $opportunity['title'] }}</h4>
                            <p class="text-gray-600">{{ $opportunity['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Entrepreneurship Opportunities --}}
            <div>
                <h3 class="text-xl font-semibold mb-4">Business Opportunities</h3>
                <div class="space-y-4">
                    @foreach($content['entrepreneurship']['response']['entrepreneurship_opportunitites'] as $opportunity)
                        <div class="border-l-4 border-green-500 pl-4">
                            <h4 class="font-semibold">{{ $opportunity['title'] }}</h4>
                            <p class="text-gray-600">{{ $opportunity['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- How to Succeed --}}
        <div class="mt-8">
            <h3 class="text-xl font-semibold mb-4">How to Succeed</h3>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach($content['entrepreneurship']['response']['how to succeed'] as $tip)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-800">{{ $tip['title'] }}</h4>
                        <p class="text-gray-600 mt-2">{{ $tip['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- First Steps --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['first_steps']['title'] }}</h2>
        
        {{-- Courses --}}
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Recommended Courses</h3>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach($content['first_steps']['response']['courses'] as $course)
                    <div class="border rounded-lg p-4">
                        <span class="text-sm font-medium text-blue-600">{{ $course['platform'] }}</span>
                        <h4 class="font-semibold mt-1">{{ $course['title'] }}</h4>
                        <p class="text-sm text-gray-600 mt-2">{{ $course['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Books and Articles --}}
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4">Reading Material</h3>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach($content['first_steps']['response']['books and articles'] as $book)
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold">{{ $book['title'] }}</h4>
                        <p class="text-sm text-gray-500">by {{ $book['author'] }}</p>
                        <p class="text-sm text-gray-600 mt-2">{{ $book['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Resources Grid --}}
        <div class="grid md:grid-cols-2 gap-8">
            {{-- Left Column --}}
            <div class="space-y-6">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Media Resources</h3>
                    @foreach($content['first_steps']['response']['podcasts and videos'] as $media)
                        <div class="mb-4">
                            <h4 class="font-semibold">{{ $media['title'] }}</h4>
                            <p class="text-gray-600">{{ $media['description'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div>
                    <h3 class="text-xl font-semibold mb-4">Tools & Technology</h3>
                    @foreach($content['first_steps']['response']['technology and tools'] as $tool)
                        <div class="mb-4">
                            <h4 class="font-semibold">{{ $tool['title'] }}</h4>
                            <p class="text-gray-600">{{ $tool['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Communities & Groups</h3>
                    @foreach($content['first_steps']['response']['communities and groups'] as $community)
                        <div class="mb-4">
                            <h4 class="font-semibold">{{ $community['title'] }}</h4>
                            <p class="text-gray-600">{{ $community['description'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div>
                    <h3 class="text-xl font-semibold mb-4">Opportunities</h3>
                    @foreach($content['first_steps']['response']['opportunities'] as $opportunity)
                        <div class="mb-4">
                            <h4 class="font-semibold">{{ $opportunity['title'] }}</h4>
                            <p class="text-gray-600">{{ $opportunity['description'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div>
                    <h3 class="text-xl font-semibold mb-4">Personal Projects</h3>
                    @foreach($content['first_steps']['response']['personal projects'] as $project)
                        <div class="mb-4">
                            <h4 class="font-semibold">{{ $project['title'] }}</h4>
                            <p class="text-gray-600">{{ $project['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Successful Requirements (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['successful_requirements']['title'] }}</h2>
        @if($content['successful_requirements']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Requirements data not available</p>
        @endif
    </div>

    {{-- Skills (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Skills Required</h2>
        @if($content['skills']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Skills data not available</p>
            <p class="text-sm text-gray-600 mt-2">{{ $content['skills']['description'] }}</p>
        @endif
    </div>

    {{-- Abilities (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Required Abilities</h2>
        @if($content['abilities']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Abilities data not available</p>
            <p class="text-sm text-gray-600 mt-2">{{ $content['abilities']['description'] }}</p>
        @endif
    </div>

    {{-- Work Styles (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Work Styles</h2>
        @if($content['work_styles']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Work styles data not available</p>
            <p class="text-sm text-gray-600 mt-2">{{ $content['work_styles']['description'] }}</p>
        @endif
    </div>

    {{-- Knowledge (empty but keeping structure) --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">Required Knowledge</h2>
        @if($content['knowledge']['response'])
            {{-- Content would go here if response wasn't null --}}
        @else
            <p class="text-gray-500 italic">Knowledge data not available</p>
            <p class="text-sm text-gray-600 mt-2">{{ $content['knowledge']['description'] }}</p>
        @endif
    </div>

    {{-- Famous People --}}
    <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold mb-4">{{ $content['famous_people']['title'] }}</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($content['famous_people']['response']['items'] as $person)
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-xl font-semibold text-blue-800 mb-3">{{ $person['name'] }}</h3>
                    <p class="text-gray-600">{{ $person['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .rating-badge {
        @apply px-3 py-1 rounded-full text-white text-sm font-medium;
    }
    .rating-high {
        @apply bg-green-500;
    }
    .rating-moderate {
        @apply bg-yellow-500;
    }
    .rating-low {
        @apply bg-red-500;
    }
</style>
@endpush
