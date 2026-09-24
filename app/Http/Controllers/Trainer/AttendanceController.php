<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\TraineeAttendance;
use App\Models\TrainingBatch;
use App\Services\AttendanceService;
use App\Services\AttendanceWorkbookExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $trainer = $request->user();

        $batches = TrainingBatch::query()
            ->when($trainer && ! $trainer->hasRole('admin'), fn ($q) => $q->where('trainer_id', $trainer->id))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        if ($batches->isEmpty()) {
            $batches = TrainingBatch::orderByDesc('is_active')->orderBy('name')->get();
        }

        $selectedBatchId = (int) $request->input('batch_id', $batches->first()?->id);
        $selectedBatch = $batches->firstWhere('id', $selectedBatchId) ?? $batches->first();

        $dateString = $request->input('date', now()->toDateString());
        try {
            $selectedDate = Carbon::parse($dateString);
        } catch (\Exception) {
            $selectedDate = now();
        }

        $activeTab = $request->input('tab', 'sheet');

        $trainees = collect();
        $existingAttendances = collect();
        $summary = null;
        $isFutureDate = $this->attendanceService->isFutureAttendanceDate($selectedDate);

        if ($selectedBatch) {
            $trainees = $this->attendanceService->rosterForDate($selectedBatch, $selectedDate);

            $existingAttendances = TraineeAttendance::where('training_batch_id', $selectedBatch->id)
                ->whereDate('attendance_date', $selectedDate->toDateString())
                ->whereNull('quiz_id')
                ->get()
                ->keyBy('enrollment_application_id');

            if ($activeTab === 'summary') {
                $summary = $this->attendanceService->getBatchSummary($selectedBatch);
            }
        }

        return view('trainer.attendance.index', [
            'batches' => $batches,
            'selectedBatch' => $selectedBatch,
            'selectedDate' => $selectedDate,
            'activeTab' => $activeTab,
            'trainees' => $trainees,
            'existingAttendances' => $existingAttendances,
            'summary' => $summary,
            'statuses' => TraineeAttendance::statuses(),
            'isFutureDate' => $isFutureDate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'date' => ['required', 'date'],
            'records' => ['required', 'array'],
            'records.*.status' => ['nullable', 'string', 'in:present,late,absent,excused'],
            'records.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $batch = TrainingBatch::findOrFail($validated['batch_id']);
        $date = Carbon::parse($validated['date']);

        if ($this->attendanceService->isFutureAttendanceDate($date)) {
            return redirect()
                ->route('trainer.attendance.index', [
                    'batch_id' => $batch->id,
                    'date' => $date->toDateString(),
                    'tab' => 'sheet',
                ])
                ->with('error', 'Attendance status can only be recorded on or after the session date.');
        }

        $savedCount = $this->attendanceService->saveDailyAttendance(
            $batch,
            $date,
            $validated['records'],
            $request->user()
        );

        if ($savedCount === 0) {
            return redirect()
                ->route('trainer.attendance.index', [
                    'batch_id' => $batch->id,
                    'date' => $date->toDateString(),
                    'tab' => 'sheet',
                ])
                ->with('error', 'Mark a status for at least one trainee before saving attendance.');
        }

        return redirect()
            ->route('trainer.attendance.index', [
                'batch_id' => $batch->id,
                'date' => $date->toDateString(),
                'tab' => 'sheet',
            ])
            ->with('status', "Attendance recorded for {$savedCount} trainee(s) on {$date->format('M d, Y')}.");
    }

    public function export(
        Request $request,
        TrainingBatch $batch,
        AttendanceWorkbookExporter $exporter
    ): Response {
        if ($request->query('format') === 'csv') {
            return $this->attendanceService->exportCsv($batch);
        }

        $export = $exporter->build($batch, $request->query('schedule'));

        return response()->download($export['path'], $export['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }
}
