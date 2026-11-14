<?php $__env->startSection('content'); ?>
<section class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">Training records</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Certificate templates</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">These are the templates used for the next COTC, transcript, and Certificate of Registration. Manage a template only when you want to change its wording, signatories, or design.</p>
        </div>
        <a href="<?php echo e(route('admin.learning.certificates')); ?>" class="secondary-action">Back to Training Records</a>
    </header>

    <?php if(session('saved')): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status"><?php echo e(session('saved')); ?></div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-3">
    <?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $active = $section['active'];
            $defaults = $section['defaults'];
            $label = \App\Models\CertificateTemplate::label($type);
            $heading = match ($label) {
                'COTC' => 'Certificate of Training Completion',
                'COR' => 'Certificate of Registration',
                default => 'Transcript of Records',
            };
            $usingOld = old('type') === $type;
            $field = function (string $key) use ($usingOld, $active, $defaults) {
                if ($usingOld) {
                    return old($key);
                }

                if ($active) {
                    $stored = $active->{$key};

                    return ($stored === null || $stored === '') ? ($defaults[$key] ?? '') : $stored;
                }

                return $defaults[$key] ?? '';
            };
            $artwork = $usingOld ? (bool) old('use_official_artwork') : (bool) ($active->use_official_artwork ?? $defaults['use_official_artwork']);
            $layout = $active?->resolvedFieldLayout() ?? \App\Models\CertificateTemplate::defaultFieldLayout($type, (string) ($field('orientation') ?: 'portrait'));
            if ($usingOld && is_string(old('field_layout'))) {
                $postedLayout = json_decode(old('field_layout'), true);
                if (is_array($postedLayout)) {
                    $layout = array_replace($layout, $postedLayout);
                }
            }
            $backgroundIsPdf = is_string($active?->background_path) && str_ends_with(strtolower($active->background_path), '.pdf');
            $savedBackground = (! $artwork && $active && ! $backgroundIsPdf && $active->absoluteAsset($active->background_path))
                ? route('admin.learning.certificate-templates.asset', [$active, 'background'])
                : '';
            $importFields = \App\Models\CertificateTemplate::importFields($type);
            $previewLandscape = $artwork || $field('orientation') === 'landscape';
            $organization = config('official_documents.organization', []);
            $sampleValues = [
                'trainee_name' => 'Sample Graduate',
                'program' => 'Caregiving NC II',
                'batch' => 'Batch 1 2026',
                'schedule' => 'MWF | 8:00 AM - 12:00 PM',
                'payment_status' => 'Fully paid',
                'completion_date' => now()->format('F j, Y'),
                'generation_date' => now()->format('F j, Y'),
                'certificate_no' => 'COR-SAMPLE',
                'enrollment_id' => 'MCE-2026-SAMPLE',
                'organization' => (string) ($organization['name'] ?? 'Mission Care Training Center'),
            ];
            $previewTitle = \App\Models\CertificateTemplate::replacePlaceholders((string) $field('title'), $sampleValues);
            $previewBody = \App\Models\CertificateTemplate::replacePlaceholders((string) $field('body'), $sampleValues);
            $usesImport = $savedBackground !== '' || $backgroundIsPdf;
            $templateState = ! $active
                ? 'Default official template'
                : ($usesImport ? 'Imported template · Version '.$active->version : 'Active template · Version '.$active->version);
        ?>
        <article class="dashboard-panel flex flex-col">
            <p class="dashboard-section-kicker"><?php echo e($label); ?></p>
            <h2 class="mt-1 text-xl font-bold text-slate-950"><?php echo e($heading); ?></h2>
            <p class="mt-1 text-sm text-slate-600"><?php echo e($templateState); ?></p>
            <div class="relative mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white" <?php if($previewLandscape): ?> style="aspect-ratio: 11 / 8.5" <?php else: ?> style="aspect-ratio: 210 / 297" <?php endif; ?>>
                <?php if($savedBackground !== ''): ?>
                    <img src="<?php echo e($savedBackground); ?>" alt="Active <?php echo e($label); ?> template" class="h-full w-full object-contain">
                <?php elseif($backgroundIsPdf): ?>
                    <div class="grid h-full place-items-center px-6 text-center text-sm font-semibold text-slate-600">Imported one-page PDF</div>
                <?php elseif($label === 'COTC'): ?>
                    <img src="<?php echo e(asset('assets/cotc-official-template.png')); ?>" alt="Default Certificate of Training Completion" class="h-full w-full object-contain">
                <?php elseif($label === 'TOR'): ?>
                    <?php echo $__env->make('admin.learning.partials.tor-default-preview', [
                        'organization' => $organization,
                        'torTitle' => $previewTitle,
                        'torTrainee' => 'NAME OF THE STUDENT',
                        'torTrainer' => $field('second_signatory_name'),
                        'torTrainerRole' => $field('second_signatory_role') ?: 'Lead Trainer',
                        'torRegistrar' => $field('signatory_name'),
                        'torRegistrarRole' => $field('signatory_role') ?: 'Registrar',
                        'torLive' => false,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php else: ?>
                    <div class="absolute inset-0 flex flex-col overflow-hidden bg-white px-4 py-3 text-center text-slate-950">
                        <img src="<?php echo e(asset('assets/official-logo.png')); ?>" alt="" class="mx-auto h-8 w-8 object-contain">
                        <p class="mt-1 text-[10px] font-bold leading-tight"><?php echo e($organization['name'] ?? 'Mission Care Training Center'); ?></p>
                        <p class="text-[8px] text-slate-600"><?php echo e($organization['address'] ?? ''); ?></p>
                        <p class="mt-3 font-serif text-sm font-bold leading-tight"><?php echo e($previewTitle); ?></p>
                        <p class="mt-2 line-clamp-3 text-[9px] leading-4 text-slate-700"><?php echo e($previewBody); ?></p>
                        <div class="mt-3 space-y-1 text-left text-[9px]">
                            <p><span class="font-bold">Trainee</span> Name of the Student</p>
                            <p><span class="font-bold">Program</span> Caregiving NC II</p>
                            <p><span class="font-bold">Payment status</span> Fully paid</p>
                        </div>
                        <p class="mt-auto pt-3 text-[9px] font-bold"><?php echo e($field('signatory_name') ?: 'Registrar'); ?></p>
                        <p class="text-[8px]"><?php echo e($field('signatory_role') ?: 'Registrar'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php $pendingImport = $section['history']->first(fn ($version) => filled($version->background_path) && ! $version->is_active); ?>
            <div class="mt-4 flex flex-col gap-2">
                <a href="<?php echo e(route('admin.learning.certificate-templates.edit', $type)); ?>" class="primary-action">Manage template</a>
                <?php if($pendingImport): ?>
                    <form method="POST" action="<?php echo e(route('admin.learning.certificate-templates.activate', $pendingImport)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <button type="submit" class="secondary-action w-full">Make active template</button>
                    </form>
                <?php endif; ?>
                <?php if($active): ?>
                    <form method="POST" action="<?php echo e(route('admin.learning.certificate-templates.destroy', $active)); ?>" onsubmit="return confirm('Delete this saved template? The official layout is used again when no saved version remains.');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="secondary-action w-full border-red-200 text-red-700 hover:bg-red-50">Delete saved template</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Certificate templates | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\certificate-templates.blade.php ENDPATH**/ ?>