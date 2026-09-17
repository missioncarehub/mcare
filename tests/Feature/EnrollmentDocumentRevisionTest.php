<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\User;
use App\Notifications\EnrollmentDocumentsReviseRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EnrollmentDocumentRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_revision_email_opens_the_document_revision_page(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->revisionApplication($applicant, [
            'birth-certificate' => ['status' => 'replace', 'note' => 'Upload a clearer complete copy.'],
            'education-document' => ['status' => 'accepted', 'note' => null],
            'good-moral-certificate' => ['status' => 'accepted', 'note' => null],
            'id-photo' => ['status' => 'accepted', 'note' => null],
            'signature' => ['status' => 'accepted', 'note' => null],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.enrollments.documents.request-revisions', $application), [
                'remark' => 'Please replace the birth certificate.',
            ])
            ->assertRedirect(route('admin.enrollments.document-review', $application))
            ->assertSessionHas('saved', fn (string $message): bool => str_contains($message, 'document revision page'));

        Notification::assertSentTo(
            $applicant,
            EnrollmentDocumentsReviseRequestedNotification::class,
            function (EnrollmentDocumentsReviseRequestedNotification $notification) use ($applicant, $application): bool {
                $mail = $notification->toMail($applicant);
                $actionUrl = $mail->viewData['actionUrl'];

                $this->assertSame('Open document revision page', $mail->viewData['actionLabel']);
                $this->assertStringContainsString('/enrollment/'.$application->id.'/documents/revise', $actionUrl);
                $this->assertSame(
                    route('enrollment.documents.revise', $application),
                    $notification->toDatabase($applicant)['url'],
                );

                return true;
            },
        );
    }

    public function test_signed_revision_link_shows_only_documents_marked_for_replacement(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->revisionApplication($applicant, [
            'birth-certificate' => ['status' => 'replace', 'note' => 'Upload a clearer complete copy.'],
            'education-document' => ['status' => 'accepted', 'note' => null],
            'good-moral-certificate' => ['status' => 'accepted', 'note' => null],
            'id-photo' => ['status' => 'accepted', 'note' => null],
            'signature' => ['status' => 'accepted', 'note' => null],
        ]);

        $this->get($this->signedShowUrl($application))
            ->assertOk()
            ->assertSee('Replace documents that need replacement')
            ->assertSee('Birth Certificate')
            ->assertSee('Needs replacement')
            ->assertSee('Upload a clearer complete copy.')
            ->assertSee('name="birth_certificate"', false)
            ->assertDontSee('name="education_document"', false)
            ->assertDontSee('name="id_photo"', false)
            ->assertDontSee('name="signature_upload"', false);
    }

    public function test_guest_cannot_open_the_revision_page_without_a_valid_signature(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->revisionApplication($applicant);

        $this->get(route('enrollment.documents.revise', $application))
            ->assertForbidden();
    }

    public function test_owner_can_open_the_revision_page_without_a_signature(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->revisionApplication($applicant);

        $this->actingAs($applicant)
            ->get(route('enrollment.documents.revise', $application))
            ->assertOk()
            ->assertSee('Birth Certificate')
            ->assertDontSee('name="education_document"', false);
    }

    public function test_another_user_cannot_open_the_revision_page_without_a_signature(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        $stranger = User::factory()->create(['role' => 'applicant']);
        $application = $this->revisionApplication($applicant);

        $this->actingAs($stranger)
            ->get(route('enrollment.documents.revise', $application))
            ->assertForbidden();
    }

    public function test_expired_revision_link_is_rejected(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->revisionApplication($applicant);
        $url = $this->signedShowUrl($application);

        $this->travel(15)->days();

        $this->get($url)->assertForbidden();
    }

    public function test_applicant_can_replace_only_flagged_documents_from_the_signed_page(): void
    {
        Storage::fake('local');
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->revisionApplication($applicant);
        Storage::disk('local')->put($application->birth_certificate_path, '%PDF-1.4 old-birth');
        Storage::disk('local')->put($application->education_document_path, '%PDF-1.4 old-education');

        $this->post($this->signedUpdateUrl($application), [
            'birth_certificate' => UploadedFile::fake()->create('birth-replacement.pdf', 120, 'application/pdf'),
            'education_document' => UploadedFile::fake()->create('diploma-should-be-ignored.pdf', 120, 'application/pdf'),
        ])
            ->assertRedirect()
            ->assertSessionHas('saved', 'Replacement files submitted. MCARE administration will review them.');

        $application->refresh();
        $this->assertSame('unreviewed', $application->document_review['birth-certificate']['status']);
        $this->assertSame('Replacement uploaded; awaiting admin review.', $application->document_review['birth-certificate']['note']);
        $this->assertSame('accepted', $application->document_review['education-document']['status']);
        $this->assertSame('enrollment-documents/test/education-document.pdf', $application->education_document_path);
        $this->assertNotSame('enrollment-documents/test/birth-certificate.pdf', $application->birth_certificate_path);
        Storage::disk('local')->assertExists($application->birth_certificate_path);
        Storage::disk('local')->assertMissing('enrollment-documents/test/birth-certificate.pdf');
        Storage::disk('local')->assertExists('enrollment-documents/test/education-document.pdf');
    }

    public function test_owner_can_submit_replacements_without_a_signature(): void
    {
        Storage::fake('local');
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->revisionApplication($applicant);

        $this->actingAs($applicant)
            ->post(route('enrollment.documents.revise.update', $application), [
                'birth_certificate' => UploadedFile::fake()->create('birth-replacement.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect(route('enrollment.documents.revise', $application))
            ->assertSessionHas('saved');

        $application->refresh();
        $this->assertSame('unreviewed', $application->document_review['birth-certificate']['status']);
    }

    /**
     * @param  array<string, array{status: string, note: ?string}>  $review
     */
    private function revisionApplication(User $user, ?array $review = null): EnrollmentApplication
    {
        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Document',
            'last_name' => 'Applicant',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Street',
            'barangay' => 'Barangay',
            'city' => 'City',
            'province' => 'Province',
            'zip_code' => '1000',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE College',
            'year_graduated' => 2022,
            'birth_certificate_path' => 'enrollment-documents/test/birth-certificate.pdf',
            'education_document_path' => 'enrollment-documents/test/education-document.pdf',
            'good_moral_certificate_path' => 'enrollment-documents/test/good-moral-certificate.pdf',
            'id_photo_path' => 'enrollment-documents/test/id-photo.jpg',
            'signature_path' => 'enrollment-documents/test/signature.png',
            'document_review' => $review ?? [
                'birth-certificate' => ['status' => 'replace', 'note' => 'Upload a clearer complete copy.'],
                'education-document' => ['status' => 'accepted', 'note' => null],
                'good-moral-certificate' => ['status' => 'accepted', 'note' => null],
                'id-photo' => ['status' => 'accepted', 'note' => null],
                'signature' => ['status' => 'accepted', 'note' => null],
            ],
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'review_released_at' => now(),
        ]);
    }

    private function signedShowUrl(EnrollmentApplication $application): string
    {
        return URL::temporarySignedRoute(
            'enrollment.documents.revise',
            now()->addDays(14),
            ['enrollmentApplication' => $application],
        );
    }

    private function signedUpdateUrl(EnrollmentApplication $application): string
    {
        return URL::temporarySignedRoute(
            'enrollment.documents.revise.update',
            now()->addDays(14),
            ['enrollmentApplication' => $application],
        );
    }
}
