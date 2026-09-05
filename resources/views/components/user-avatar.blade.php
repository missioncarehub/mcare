@props([
    'user' => null,
    'name' => null,
    'src' => null,
    'application' => null,
    'useEnrollmentPhoto' => false,
])

@php
    $resolvedUser = $user ?: $application?->user;
    $displayName = trim((string) ($name ?: $resolvedUser?->name ?: $resolvedUser?->email ?: 'MCARE User'));
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($displayName, 0, 1));
    $staffCanViewPhoto = $useEnrollmentPhoto && auth()->user()?->role === 'admin';
    $profilePhotoPath = (string) ($resolvedUser?->profile_photo_path ?? '');
    $hasLocalProfilePhoto = $profilePhotoPath !== '' && ! str_starts_with($profilePhotoPath, 'https://');
    $hasStoredPhoto = filled($application?->id_photo_path) || $hasLocalProfilePhoto;
    $enrollmentPhotoUrl = '';
    if ($staffCanViewPhoto && $hasStoredPhoto) {
        $enrollmentPhotoUrl = ($application?->id && $application->isReleasedForReview())
            ? route('admin.enrollments.photo', $application, absolute: false)
            : ($resolvedUser ? route('admin.accounts.photo', $resolvedUser, absolute: false) : '');
    }

    // Include a version token so a fresh trainee-uploaded avatar always beats
    // any previously cached copy of the same admin.enrollments.photo URL.
    if ($enrollmentPhotoUrl !== '') {
        $cacheToken = collect([
            $resolvedUser?->updated_at?->getTimestamp(),
            $application?->updated_at?->getTimestamp(),
            $profilePhotoPath !== '' ? substr(sha1($profilePhotoPath), 0, 8) : null,
        ])->filter()->implode('-');

        if ($cacheToken !== '') {
            $enrollmentPhotoUrl .= (str_contains($enrollmentPhotoUrl, '?') ? '&' : '?').'v='.$cacheToken;
        }
    }

    $candidateUrl = trim((string) ($src ?: $enrollmentPhotoUrl ?: $resolvedUser?->profilePhotoUrl() ?? ''));

    $isSafeRelativeUrl = \Illuminate\Support\Str::startsWith($candidateUrl, '/')
        && ! \Illuminate\Support\Str::startsWith($candidateUrl, '//');
    $isSafeRemoteUrl = filter_var($candidateUrl, FILTER_VALIDATE_URL)
        && strtolower((string) parse_url($candidateUrl, PHP_URL_SCHEME)) === 'https';
    $avatarUrl = $isSafeRelativeUrl || $isSafeRemoteUrl ? $candidateUrl : null;
@endphp

<span {{ $attributes->class(['user-avatar'])->merge(['title' => $displayName]) }}>
    <span class="user-avatar-fallback" aria-hidden="true">{{ $initial ?: 'M' }}</span>
    @if ($avatarUrl)
        <img
            src="{{ $avatarUrl }}"
            alt=""
            class="user-avatar-image"
            loading="lazy"
            decoding="async"
            referrerpolicy="no-referrer"
            data-user-avatar-image
        >
    @endif
</span>
