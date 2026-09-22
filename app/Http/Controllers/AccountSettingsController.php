<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\AdminContactMessage;
use App\Models\PublicSiteSetting;
use App\Services\AdminOperationsNotifier;
use App\Services\ProfilePhotoStore;
use App\Support\AccountPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class AccountSettingsController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.settings', $this->accountContext($request));
    }

    public function help(Request $request): View
    {
        $topics = match ($request->user()?->role) {
            'admin' => [
                ['Applications', 'Review applicant details, preview documents, and record missing requirements. Unused approved applications can expire automatically from Settings.'],
                ['Schedules and payments', 'Control enrollment windows, class schedules, and payment verification.'],
                ['Audit reports', 'Use Admin Logs to filter, print, or export system activity.'],
            ],
            'trainer' => [
                ['Teaching schedule', 'Your calendar follows the active batch schedule configured by the administrator.'],
                ['Learning materials', 'Publish PDFs or images to a batch or a specific trainee.'],
                ['Progress', 'Use trainee and report pages to review learner module activity.'],
            ],
            'trainee' => [
                ['Modules', 'Open assigned learning materials and mark lessons complete as you progress.'],
                ['Documents', 'Review admin feedback and replace enrollment documents that need correction.'],
                ['Schedule and payment', 'Your dashboard shows your assigned batch and current payment status.'],
            ],
            'alumni' => [
                ['Career Hub', 'Review current caregiving opportunities shared by the center.'],
                ['Notifications', 'Open the notification center to revisit career and MCARE updates.'],
            ],
            default => [
                ['Enrollment', 'Complete your profile, upload requirements, and monitor the application status.'],
            ],
        };

        $user = $request->user();
        $canContactAdmin = $user?->role !== 'admin';

        return view('account.help', [
            ...$this->accountContext($request),
            'topics' => $topics,
            'canContactAdmin' => $canContactAdmin,
            'contactTopics' => AdminContactMessage::topics(),
            'contactMessages' => $canContactAdmin
                ? AdminContactMessage::query()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->limit(8)
                    ->get()
                : collect(),
        ]);
    }

    public function storeContact(Request $request, AdminOperationsNotifier $adminNotifier): RedirectResponse
    {
        abort_if($request->user()?->role === 'admin', 403);

        $validated = $request->validateWithBag('contactAdmin', [
            'topic' => ['required', Rule::in(array_keys(AdminContactMessage::topics()))],
            'subject' => ['required', 'string', 'max:160', 'not_regex:/[<>]/u'],
            'message' => ['required', 'string', 'max:2000', 'not_regex:/[<>]/u'],
        ], [
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
        ]);

        $user = $request->user();

        $contact = AdminContactMessage::create([
            'user_id' => $user->id,
            'topic' => $validated['topic'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => AdminContactMessage::STATUS_PENDING,
        ]);

        $adminNotifier->notify(
            title: 'Contact admin message',
            message: $user->name.' sent a message about '.$contact->topicLabel().'.',
            url: route('admin.contact-messages.index'),
            icon: 'message-circle',
            event: 'account.contact.received',
            context: [
                'contact_message_id' => $contact->id,
                'user_id' => $user->id,
            ],
        );

        AdminActivityLog::record($user, 'account.contact.created', $contact, [
            'topic' => $contact->topic,
        ]);

        return back()->with([
            'saved' => 'Your message was sent to MCARE administration.',
            'saved_icon' => 'circle-check',
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'max:255', Password::min(10)->mixedCase()->letters()->numbers()],
        ]);

        $request->user()->update(['password' => $validated['password']]);
        AdminActivityLog::record($request->user(), 'account.password.updated', $request->user(), [
            'role' => $request->user()?->role,
        ]);

        return back()->with('saved', 'Your password has been changed successfully.');
    }

    public function updateAvatar(Request $request, ProfilePhotoStore $photos): RedirectResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'avatar.required' => 'Choose a profile photo to upload.',
            'avatar.image' => 'The profile photo must be a JPG, PNG, or WEBP image.',
            'avatar.mimes' => 'The profile photo must be a JPG, PNG, or WEBP image.',
            'avatar.max' => 'The profile photo must not exceed 5MB.',
        ]);

        $user = $request->user();
        $photos->storeUploaded($user, $validated['avatar']);

        AdminActivityLog::record($user, 'account.avatar.updated', $user, [
            'role' => $user->role,
            'profile_photo_path' => $user->profile_photo_path,
        ]);

        return back()->with('saved', 'Your profile photo has been updated.');
    }

    public function updateRegistrar(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $this->validatedRegistrar($request, PublicSiteSetting::current());
        $settings = PublicSiteSetting::instance();
        $previousPath = $settings->registrar_signature_path;
        $signaturePath = $this->storeRegistrarSignature($request, $settings);
        $before = $settings->only(['registrar_name', 'registrar_signature_type']);

        $settings->update([
            'registrar_name' => $validated['registrar_name'],
            'registrar_signature_type' => $validated['registrar_signature_type'],
            'registrar_signature_path' => $signaturePath,
        ]);

        AdminActivityLog::record($request->user(), 'account.registrar.updated', $settings, [
            'before' => $before,
            'after' => $settings->fresh()->only(['registrar_name', 'registrar_signature_type']),
            'signature_replaced' => $signaturePath !== $previousPath,
        ]);

        return redirect()
            ->to(route('account.settings').'#tesda-registrar')
            ->with('saved', 'TESDA form registrar name and signature saved.');
    }

    public function updateUnusedApprovedExpiry(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validateWithBag('unusedApprovedExpiry', [
            'unused_approved_expiry_mode' => ['required', Rule::in(array_keys(PublicSiteSetting::unusedApprovedExpiryModes()))],
            'unused_approved_expiry_amount' => [
                Rule::requiredIf(fn (): bool => in_array($request->input('unused_approved_expiry_mode'), [
                    PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS,
                    PublicSiteSetting::UNUSED_APPROVED_EXPIRY_MONTHS,
                ], true)),
                'nullable',
                'integer',
                'min:1',
                Rule::when($request->input('unused_approved_expiry_mode') === PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS, ['max:365']),
                Rule::when($request->input('unused_approved_expiry_mode') === PublicSiteSetting::UNUSED_APPROVED_EXPIRY_MONTHS, ['max:36']),
            ],
            'unused_approved_expiry_date' => [
                Rule::requiredIf(fn (): bool => $request->input('unused_approved_expiry_mode') === PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DATE),
                'nullable',
                'date',
            ],
        ], [
            'unused_approved_expiry_amount.required' => 'Enter how long unused approved applications should be kept.',
            'unused_approved_expiry_amount.min' => 'Enter at least 1 day or month.',
            'unused_approved_expiry_date.required' => 'Choose the date unused approved applications should be deleted.',
        ]);

        $mode = $validated['unused_approved_expiry_mode'];
        $settings = PublicSiteSetting::instance();
        $before = $settings->only([
            'unused_approved_expiry_mode',
            'unused_approved_expiry_amount',
            'unused_approved_expiry_date',
        ]);

        $settings->update([
            'unused_approved_expiry_mode' => $mode,
            'unused_approved_expiry_amount' => in_array($mode, [
                PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS,
                PublicSiteSetting::UNUSED_APPROVED_EXPIRY_MONTHS,
            ], true) ? (int) $validated['unused_approved_expiry_amount'] : null,
            'unused_approved_expiry_date' => $mode === PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DATE
                ? $validated['unused_approved_expiry_date']
                : null,
        ]);

        AdminActivityLog::record($request->user(), 'account.unused-approved-expiry.updated', $settings, [
            'before' => $before,
            'after' => $settings->fresh()->only(array_keys($before)),
        ]);

        return redirect()
            ->to(route('account.settings').'#unused-approved-expiry')
            ->with('saved', 'Unused approved-application expiry saved.');
    }

    public function registrarSignature(Request $request): BinaryFileResponse
    {
        $this->ensureAdmin($request);

        $settings = PublicSiteSetting::current();
        abort_unless($settings->hasRegistrarSignature(), 404);

        $path = (string) $settings->registrar_signature_path;
        $filename = basename($path);
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();
        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';
        abort_unless(str_starts_with((string) $mime, 'image/'), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename, $fallbackFilename),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Record a small allow-listed set of client-visible abuse signals.
     *
     * This is telemetry, not proof that a user acted maliciously: a modified
     * browser can omit it, so server-side authorization and rate limits remain
     * the real controls.
     */
    public function securityEvent(Request $request): Response
    {
        $validated = $request->validate([
            'event' => ['required', 'in:navigation_spam,rapid_action'],
        ]);

        AdminActivityLog::record($request->user(), 'account.security.client-event', $request->user(), [
            'event' => $validated['event'],
        ]);

        return response()->noContent();
    }

    private function accountContext(Request $request): array
    {
        $portalUrl = match ($request->user()?->role) {
            'admin' => route('admin.dashboard'),
            'trainer' => route('trainer.dashboard'),
            'trainee' => route('trainee.dashboard'),
            'alumni' => route('alumni.dashboard'),
            default => AccountPortal::urlFor($request->user()),
        };

        return [
            'user' => $request->user(),
            'roleLabel' => AccountPortal::roleLabelFor($request->user()),
            'portalUrl' => $portalUrl,
            'siteSettings' => $request->user()?->role === 'admin' ? PublicSiteSetting::current() : null,
        ];
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }

    /** @return array{registrar_name: string, registrar_signature_type: string} */
    private function validatedRegistrar(Request $request, PublicSiteSetting $settings): array
    {
        $request->merge([
            'registrar_name' => trim((string) $request->input('registrar_name')),
        ]);

        $hasSavedSignature = $settings->hasRegistrarSignature();

        $validated = $request->validateWithBag('registrar', [
            'registrar_name' => ['required', 'string', 'max:180', 'not_regex:/[<>"`;{}|\\\\]/u'],
            'registrar_signature_type' => ['required', Rule::in(['draw', 'upload'])],
            'registrar_signature_data' => [
                'exclude_unless:registrar_signature_type,draw',
                $hasSavedSignature ? 'nullable' : 'required',
                'string',
            ],
            'registrar_signature_upload' => [
                'exclude_unless:registrar_signature_type,upload',
                $hasSavedSignature ? 'nullable' : 'required',
                'file',
                'mimes:jpg,jpeg,png',
                'extensions:jpg,jpeg,png',
                'max:5120',
            ],
        ], [
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
            'registrar_signature_data.required' => 'Draw the registrar signature before saving.',
            'registrar_signature_upload.required' => 'Upload a registrar signature image before saving.',
        ]);

        return [
            'registrar_name' => $validated['registrar_name'],
            'registrar_signature_type' => $validated['registrar_signature_type'],
        ];
    }

    private function storeRegistrarSignature(Request $request, PublicSiteSetting $settings): ?string
    {
        $existingPath = $settings->hasRegistrarSignature() ? $settings->registrar_signature_path : null;

        if ($request->input('registrar_signature_type') === 'upload') {
            if (! $request->hasFile('registrar_signature_upload')) {
                return $existingPath;
            }

            $path = $request->file('registrar_signature_upload')->store('organization-assets', 'local');
            $this->forgetRegistrarSignature($existingPath, $path);

            return $path;
        }

        $signatureData = $request->input('registrar_signature_data');

        if (! is_string($signatureData) || $signatureData === '') {
            return $existingPath;
        }

        if (! str_starts_with($signatureData, 'data:image/png;base64,')) {
            throw ValidationException::withMessages([
                'registrar_signature_data' => 'Draw the registrar signature before saving.',
            ])->errorBag('registrar');
        }

        $decodedSignature = base64_decode(substr($signatureData, strlen('data:image/png;base64,')), true);

        if (! $decodedSignature || strlen($decodedSignature) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'registrar_signature_data' => 'The drawn signature could not be saved. Please clear and draw it again.',
            ])->errorBag('registrar');
        }

        $path = 'organization-assets/registrar-signature-'.now()->format('YmdHis').'.png';
        Storage::disk('local')->put($path, $decodedSignature);
        $this->forgetRegistrarSignature($existingPath, $path);

        return $path;
    }

    private function forgetRegistrarSignature(?string $existingPath, string $newPath): void
    {
        if ($existingPath && $existingPath !== $newPath && Storage::disk('local')->exists($existingPath)) {
            Storage::disk('local')->delete($existingPath);
        }
    }
}
