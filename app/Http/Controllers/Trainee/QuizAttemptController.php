<?php

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\QuizAttempt;
use App\Services\QuizGradingService;
use App\Support\TrainingModuleFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizAttemptController extends Controller
{
    public function show(
        Request $request,
        QuizAttempt $attempt,
        QuizGradingService $gradingService,
    ): View|RedirectResponse {
        if ($attempt->isGraded()) {
            $this->authorize('view', $attempt);

            return redirect()->route('trainee.quiz-attempts.result', $attempt);
        }

        $this->authorize('update', $attempt);
        $attempt->load(['quiz.questions', 'application']);
        $this->ensureAttemptIsAvailable($attempt);

        if ($attempt->isExpiredAt()) {
            $gradedAttempt = $gradingService->grade($attempt, $attempt->answers ?? []);
            $this->recordSubmission($request, $gradedAttempt, true);

            return redirect()
                ->route('trainee.quiz-attempts.result', $gradedAttempt)
                ->with('saved', 'The quiz time ended and this attempt was finalized.');
        }

        return view('trainee.quizzes.take', [
            'application' => $attempt->application,
            'quiz' => $attempt->quiz,
            'attempt' => $attempt,
            'remainingSeconds' => $attempt->remainingSeconds(),
        ]);
    }

    public function submit(
        Request $request,
        QuizAttempt $attempt,
        QuizGradingService $gradingService,
    ): RedirectResponse {
        if ($attempt->isGraded()) {
            $this->authorize('view', $attempt);

            return redirect()->route('trainee.quiz-attempts.result', $attempt);
        }

        $this->authorize('submit', $attempt);
        $attempt->load(['quiz.questions', 'application']);
        $this->ensureAttemptIsAvailable($attempt);
        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'integer', 'min:0', 'max:10'],
            'text_answers' => ['nullable', 'array'],
            'text_answers.*' => ['nullable', 'string', 'max:5000'],
            'file_answers' => ['nullable', 'array'],
            'file_answers.*' => [
                'nullable',
                'file',
                'max:20480',
                TrainingModuleFiles::mimesRule(),
            ],
        ]);
        $expired = $attempt->isExpiredAt();
        $processedAnswers = $validated['answers'] ?? [];

        $files = $request->file('file_answers', []);
        foreach ($files as $qId => $file) {
            if ($file && $file->isValid()) {
                $path = $file->store("activity-submissions/{$attempt->enrollment_application_id}/{$attempt->quiz_id}", 'local');
                $processedAnswers[(string) $qId] = [
                    'type' => 'file',
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ];
            }
        }

        foreach (($validated['text_answers'] ?? []) as $qId => $text) {
            if (filled($text) && ! isset($processedAnswers[(string) $qId])) {
                $processedAnswers[(string) $qId] = [
                    'type' => 'text',
                    'content' => trim($text),
                ];
            }
        }

        // A short grace window accepts the browser's automatic submission at
        // zero. Later requests cannot use answers chosen after the deadline.
        if ($expired && ! $attempt->acceptsExpirationSubmissionAt()) {
            $processedAnswers = $attempt->answers ?? [];
        }

        $gradedAttempt = $gradingService->grade($attempt, $processedAnswers);
        $this->recordSubmission($request, $gradedAttempt, $expired);

        $response = redirect()->route('trainee.quiz-attempts.result', $gradedAttempt);

        if ($expired) {
            $response->with('saved', 'The quiz time ended and this attempt was finalized.');
        }

        return $response;
    }

    public function downloadSubmission(
        Request $request,
        QuizAttempt $attempt,
        int $questionId,
    ): \Symfony\Component\HttpFoundation\BinaryFileResponse {
        $this->authorize('view', $attempt);

        $answers = $attempt->answers ?? [];
        $answer = $answers[$questionId] ?? $answers[(string) $questionId] ?? null;

        abort_unless(is_array($answer) && ($answer['type'] ?? null) === 'file', 404);
        $path = $answer['file_path'] ?? null;
        abort_unless(is_string($path) && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        $filename = basename($answer['original_name'] ?? 'submission');
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($path), [
            'Content-Type' => ($answer['mime_type'] ?? null) ?: 'application/octet-stream',
            'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition(
                \Symfony\Component\HttpFoundation\HeaderUtils::DISPOSITION_ATTACHMENT,
                $filename,
                $fallbackFilename
            ),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function result(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        $this->authorize('view', $attempt);

        if (! $attempt->isGraded()) {
            return redirect()->route('trainee.quiz-attempts.show', $attempt);
        }

        $attempt->load(['quiz.trainer', 'quiz.batch', 'quiz.questions', 'application']);
        $quiz = $attempt->quiz;
        $gradedAttemptCount = $quiz->attempts()
            ->where('enrollment_application_id', $attempt->enrollment_application_id)
            ->where('status', QuizAttempt::STATUS_GRADED)
            ->count();
        $answerReviewAvailable = (
            $quiz->due_at !== null
            && $quiz->due_at->lte(now())
        ) || $gradedAttemptCount >= $quiz->attempt_limit;

        return view('trainee.quizzes.result', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'answerReviewAvailable' => $answerReviewAvailable,
            'gradedAttemptCount' => $gradedAttemptCount,
        ]);
    }

    private function ensureAttemptIsAvailable(QuizAttempt $attempt): void
    {
        abort_unless(
            $attempt->application->status === EnrollmentApplication::STATUS_APPROVED
                && $attempt->quiz->isReleasedAt()
                && $attempt->quiz->targets($attempt->application),
            404
        );
    }

    private function recordSubmission(
        Request $request,
        QuizAttempt $attempt,
        bool $expired,
    ): void {
        $attempt->loadMissing('quiz');

        AdminActivityLog::record(
            $request->user(),
            $expired ? 'trainee.quiz.expired-finalized' : 'trainee.quiz.submitted',
            $attempt,
            [
                'quiz_id' => $attempt->quiz_id,
                'quiz_title' => $attempt->quiz->title,
                'attempt_number' => $attempt->attempt_number,
                'score_percent' => $attempt->score_percent,
                'passed' => $attempt->passed,
                'expired' => $expired,
            ]
        );
    }
}
