<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\Quiz;
use App\Models\User;
use App\Services\CompletionEligibilityService;
use Database\Seeders\SampleGraduateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SampleGraduateSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_graduate_passed_every_quiz_and_can_receive_certificates(): void
    {
        Storage::fake('local');
        User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'trainer']);

        $this->seed(SampleGraduateSeeder::class);

        $user = User::query()->where('email', SampleGraduateSeeder::EMAIL)->firstOrFail();
        $application = EnrollmentApplication::query()->where('user_id', $user->id)->firstOrFail();
        $evaluation = app(CompletionEligibilityService::class)->evaluate($application->fresh('batch'));

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame(EnrollmentApplication::LEARNING_GRADUATED, $user->trainee_status);
        $this->assertSame(EnrollmentApplication::LEARNING_GRADUATED, $application->learning_status);
        $this->assertTrue($evaluation['eligible']);
        $this->assertTrue(app(CompletionEligibilityService::class)->canIssueTor($application, $evaluation));
        $this->assertGreaterThan(0, $evaluation['counts']['quizzes']);
        $this->assertSame($evaluation['counts']['quizzes'], $evaluation['counts']['passed_quizzes']);
        $this->assertNotEmpty($evaluation['quiz_summaries']);
        $this->assertTrue(collect($evaluation['quiz_summaries'])->every(fn (array $summary): bool => $summary['result'] === 'Passed'));
        $this->assertDatabaseHas('official_documents', [
            'enrollment_application_id' => $application->id,
            'type' => OfficialDocument::TYPE_COTC,
            'status' => OfficialDocument::STATUS_RELEASED,
        ]);
        $this->assertDatabaseHas('official_documents', [
            'enrollment_application_id' => $application->id,
            'type' => OfficialDocument::TYPE_TOR,
            'status' => OfficialDocument::STATUS_GENERATED,
        ]);
        $this->assertSame(0, Quiz::query()->where('title', 'quiz 1')->count());
    }
}
