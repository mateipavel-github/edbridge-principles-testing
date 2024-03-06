@extends('layout')

@section('title', 'Personality Test - Questions')

@section('content')
    <div class="max-w-md" x-data="{
        requesting: false,

        submitForm() {
            if (!this.requesting) {
                this.requesting = true;
                this.$refs.form.submit();
            }
        }
    }">
        <form method="POST" action="{{ route('personality-test.store', ['studentUid' => $student->uid]) }}" x-ref="form" @submit.prevent="submitForm()">
            @csrf


            <div class="w-full flex flex-col items-center mt-8">
                <span class="font-bold text-primary">{{ $fractionComplete * 100  }} %</span>

                <div class="w-full mt-2 bg-gray-100 rounded-full">
                    <div style="width: {{ $fractionComplete * 100 }}%; height: 10px;" class="bg-primary rounded-full"></div>
                </div>
            </div>

            <div class="w-full flex flex-col items-center pb-6">
                <div class="w-full h-px bg-gray-200 mb-4 mt-6"></div>

                @foreach( $questions as $question )
                    <div class="w-full">
                        <span class="font-bold">{{ $question['text'] }}</span>

                        <div class="w-full flex justify-between items-start pt-6">
                            @foreach( $answers as $answer )
                                <div class="w-1/5 flex flex-col items-center justify-center text-center">
                                    <input type="radio" required
                                           id="{{ $question['number'] . '-' . $answer['value'] }}"
                                           name="{{ $question['number'] }}"
                                           value="{{ $answer['value'] }}"
                                           class="mb-1 h-6 w-6 color-primary">

                                    <label for="{{ $question['number'] . '-' . $answer['value'] }}"
                                           class="text-xs">
                                        {{ $answer['label'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        @error("answers.{$question['number']}.answerNumber")
                        <span class="text-red-500 text-xs mt-1">
                            {{ $message }}
                        </span>
                        @enderror

                        <div class="w-full h-px bg-gray-200 my-4"></div>
                    </div>
                @endforeach

                <div class="max-w-xs mt-8 flex items-center justify-center">
                    <button :disabled="requesting" type="submit" class="bg-primary uppercase px-12 py-4 rounded-full font-bold text-gray-primary flex items-center justify-center">
                        CONTINUE

                        <img x-show="requesting" src="{{ Vite::asset('resources/img/spinner.svg') }}" alt="spinner" class="ml-2" />
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
