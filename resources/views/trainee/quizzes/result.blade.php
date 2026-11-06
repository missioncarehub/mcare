@extends('trainee.layouts.app', ['title' => 'Quiz Result | MCARE Trainee'])

@section('content')
@php
    $answers = collect($attempt->answers ?? []);
    $answerReviewAvailable = $answerReviewAvailable ?? false;
    $moduleReturnUrl = $quiz->training_module_id
        ? route('trainee.modules.show', $quiz->training_module_id).'#assessments'
        : route('trainee.modules.index');
@endphp

<div class="lms-page" data-quiz-result>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Attempt {{ $attempt->attempt_number }} result</p>
            <h1>{{ $quiz->title }}</h1>
            <p>Submitted {{ $attempt->submitted_at?->format('M d, Y g:i A') }}</p>
        </div>
        <a href="{{ $moduleReturnUrl }}" class="secondary-action">Back to module</a>
    </header>

    <section class="lms-result-hero {{ $attempt->passed ? 'is-passed' : 'is-review' }}">
        <div class="lms-result-score">
            <span>Your score</span>
            <strong>{{ number_format((float) $attempt->score_percent, 1) }}%</strong>
            <small>{{ number_format((float) $attempt->earned_points, 1) }} of {{ number_format((float) $attempt->total_points, 1) }} points · Attempt {{ $attempt->attempt_number }} of {{ $gradedAttemptCount ?? $attempt->attempt_number }}</small>
        </div>
        <div>
            <p class="lms-eyebrow">{{ $attempt->passed ? 'Passed' : 'Keep learning' }}</p>
            <h2>{{ $attempt->passed ? 'Great work - you reached the passing score.' : 'Your score has been recorded.' }}</h2>
            <p>The passing score for this quiz is {{ number_format((float) $quiz->passing_score_percent) }}%.</p>
        </div>
    </section>

    <section class="lms-review-list" aria-labelledby="answer-review-title">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Answer review</p>
                <h2 id="answer-review-title">{{ $answerReviewAvailable ? 'Questions and answers' : 'Your submitted answers' }}</h2>
                @unless($answerReviewAvailable)
                    <p>Correctness and answer keys unlock after the deadline or your final allowed attempt.</p>
                @endunless
            </div>
        </div>

        @foreach($quiz->questions as $questionIndex => $question)
            @php
                $rawAnswer = $answers->get((string) $question->id, $answers->get($question->id));
                $isFile = is_array($rawAnswer) && ($rawAnswer['type'] ?? null) === 'file';
                $isText = is_array($rawAnswer) && ($rawAnswer['type'] ?? null) === 'text';
                $selectedIndex = is_numeric($rawAnswer) ? (int) $rawAnswer : null;
                $selectedOption = $selectedIndex !== null ? data_get($question->options, $selectedIndex) : null;
                $correctOption = $answerReviewAvailable && $question->requiresOptions()
                    ? data_get($question->options, (int) $question->correct_option)
                    : null;
                $isCorrect = $answerReviewAvailable
                    ? ($question->requiresOptions() ? ($selectedIndex !== null && $selectedIndex === (int) $question->correct_option) : ($isFile || $isText))
                    : false;
            @endphp
            <article class="lms-review-card {{ $answerReviewAvailable ? ($isCorrect ? 'is-correct' : 'is-incorrect') : '' }}">
                <header>
                    <span>Question {{ $questionIndex + 1 }}</span>
                    @if($answerReviewAvailable)
                        <span class="lms-status-chip {{ $isCorrect ? 'is-green' : 'is-red' }}">{{ $isCorrect ? 'Completed' : 'Incorrect' }}</span>
                    @else
                        <span class="lms-status-chip is-neutral">Recorded</span>
                    @endif
                </header>
                <h3>{{ $question->prompt }}</h3>
                <dl>
                    <div>
                        <dt>Your answer</dt>
                        <dd>
                            @if($isFile)
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="font-medium text-slate-800">{{ $rawAnswer['original_name'] ?? 'Submitted Document' }}</span>
                                    <a href="{{ route('trainee.quiz-attempts.download', ['attempt' => $attempt, 'question' => $question->id]) }}" class="inline-flex items-center gap-1 rounded-md bg-purple-100 px-2.5 py-1 text-xs font-bold text-purple-800 hover:bg-purple-200">
                                        Download File
                                    </a>
                                </div>
                            @elseif($isText)
                                <p class="whitespace-pre-line text-slate-800">{{ $rawAnswer['content'] }}</p>
                            @else
                                {{ $selectedOption ?? 'No answer' }}
                            @endif
                        </dd>
                    </div>
                    @if($answerReviewAvailable && ! $isCorrect && $correctOption)
                        <div><dt>Correct answer</dt><dd>{{ $correctOption }}</dd></div>
                    @endif
                </dl>
            </article>
        @endforeach
    </section>
</div>
@endsection
