<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'user' => null,
    'name' => null,
    'src' => null,
    'application' => null,
    'useEnrollmentPhoto' => false,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'user' => null,
    'name' => null,
    'src' => null,
    'application' => null,
    'useEnrollmentPhoto' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
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
?>

<span <?php echo e($attributes->class(['user-avatar'])->merge(['title' => $displayName])); ?>>
    <span class="user-avatar-fallback" aria-hidden="true"><?php echo e($initial ?: 'M'); ?></span>
    <?php if($avatarUrl): ?>
        <img
            src="<?php echo e($avatarUrl); ?>"
            alt=""
            class="user-avatar-image"
            loading="lazy"
            decoding="async"
            referrerpolicy="no-referrer"
            data-user-avatar-image
        >
    <?php endif; ?>
</span>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views/components/user-avatar.blade.php ENDPATH**/ ?>