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
        <span class="font-bold block mb-8">Occupations you might find interesting</span>
        @if(count($occupations) > 0)
            <table class="text-left">
                @foreach($occupations as $occupation)
                    <tr>
                        <td style="width: 20%; vertical-align: top;">{{ round($occupation['errorMargin'], 2) }}
                                @if($occupation['fit'])
                                    <br /><span style="font-size: 0.7rem;">{{ $occupation['fit'] }}</span>
                                @endif
                        </td>
                        <td style="vertical-align: top;"><a class="onet_link" style="color: #784cca;
                                text-decoration: underline;"
                                href="{{$occupation['onet_url']}}">{{ $occupation['occupation'] }}</a></td>
                    </tr>
                    <tr><td colspan="2" style="height: 15px;">&nbsp;</td></tr>
                @endforeach
            </table>
        @else
            <p style="color: red;">Matching careers failed to load. We are working on fixing this issue and will be in touch
                with you shortly.</p>
        @endif
    </div>
</div>
@endsection