<?php $__env->startSection('content'); ?>
<?php
    $careerCreateErrors = $errors->getBag('careerCreate');
?>

<section class="space-y-6">
    <style>
        .career-form-dialog {
            width: min(96vw, 72rem);
            max-width: 72rem;
            max-height: 92vh;
            overflow: hidden;
        }
        .career-form-dialog[open],
        .career-review-dialog[open] {
            display: flex;
            flex-direction: column;
        }
        .career-review-dialog {
            width: min(96vw, 36rem);
            max-height: 90vh;
            overflow: hidden;
        }
        .career-form-dialog > form {
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .career-form-dialog .career-form-layout {
            display: grid;
            grid-template-columns: minmax(22rem, 1.15fr) minmax(20rem, 0.85fr);
            align-items: stretch;
            gap: 1.25rem;
        }
        @media (max-width: 860px) {
            .career-form-dialog .career-form-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <header class="flex flex-wrap justify-end gap-2">
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="career-opportunity-dialog" class="primary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'plus','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'plus','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                Add career
            </button>
            <a href="<?php echo e(route('admin.learning.alumni-jobs.preview')); ?>" class="secondary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'briefcase','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'briefcase','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                Preview alumni board
            </a>
        </div>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Alumni accounts</p><p class="dashboard-stat-value"><?php echo e($alumniAccounts); ?></p><p class="dashboard-stat-help">Dedicated Career Hub access</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'users','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'users','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Available for Duty</p><p class="dashboard-stat-value"><?php echo e($availableAlumni); ?></p><p class="dashboard-stat-help">Alumni accepting placement</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'circle-check','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'circle-check','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Published careers</p><p class="dashboard-stat-value"><?php echo e($publishedJobs); ?></p><p class="dashboard-stat-help">Visible on the alumni board</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'briefcase','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'briefcase','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Draft careers</p><p class="dashboard-stat-value"><?php echo e($draftJobs); ?></p><p class="dashboard-stat-help">Awaiting privacy review</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'pencil','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'pencil','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Pending applications</p><p class="dashboard-stat-value"><?php echo e($pendingInquiryCount); ?></p><p class="dashboard-stat-help">Career applications to review</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-700 ring-1 ring-violet-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'message-circle','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'message-circle','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span></article>
    </div>

    <dialog id="career-opportunity-dialog" data-dashboard-dialog data-auto-open="<?php echo e($careerCreateErrors->any() ? 'true' : 'false'); ?>" class="career-form-dialog m-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-opportunity-form-title">
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <h2 id="career-opportunity-form-title" class="font-display text-xl font-bold text-slate-900">Add a career</h2>
                <p class="mt-1 text-xs text-slate-500">Use only the client-approved care summary.</p>
            </div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close career form" title="Close"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button>
        </div>

        <form method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.store')); ?>" class="p-6" data-dashboard-dialog-form data-career-sms-form data-submit-label="Saving career...">
            <?php echo csrf_field(); ?>
            <div class="career-form-layout">
                <div class="space-y-4">
                    <div><label for="career-title" class="form-label">Career title</label><input id="career-title" name="title" type="text" maxlength="160" value="<?php echo e(old('title')); ?>" required class="form-field" placeholder="Example: Live-in caregiver, Iriga City" autofocus data-career-sms-field="title"><?php $__errorArgs = ['title', 'careerCreate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="career-salary" class="form-label">Estimated salary</label><input id="career-salary" name="estimated_salary" type="text" maxlength="80" value="<?php echo e(old('estimated_salary')); ?>" required class="form-field" placeholder="Example: ₱18,000 / month" data-career-sms-field="salary"><?php $__errorArgs = ['estimated_salary', 'careerCreate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                        <div><label for="career-start-date" class="form-label">Estimated start date</label><input id="career-start-date" name="estimated_start_date" type="date" min="<?php echo e(now()->toDateString()); ?>" value="<?php echo e(old('estimated_start_date')); ?>" required class="form-field" data-career-sms-field="start"><?php $__errorArgs = ['estimated_start_date', 'careerCreate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div><label for="career-patient-age" class="form-label">Patient age <span class="font-normal text-slate-400">(optional)</span></label><input id="career-patient-age" name="patient_age" type="number" min="0" max="120" value="<?php echo e(old('patient_age')); ?>" class="form-field" placeholder="Example: 72" data-career-sms-field="age"><?php $__errorArgs = ['patient_age', 'careerCreate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                        <div><label for="career-patient-gender" class="form-label">Patient gender</label><select id="career-patient-gender" name="patient_gender" required class="form-field" data-career-sms-field="gender"><option value="">Select gender</option><?php $__currentLoopData = $patientGenders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(old('patient_gender') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><?php $__errorArgs = ['patient_gender', 'careerCreate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                        <div><label for="career-mobility" class="form-label">Mobility status</label><select id="career-mobility" name="mobility_status" required class="form-field" data-career-sms-field="mobility"><option value="">Select mobility</option><?php $__currentLoopData = $mobilityStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(old('mobility_status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><?php $__errorArgs = ['mobility_status', 'careerCreate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                    </div>
                    <div><label for="career-contraptions" class="form-label">Specific contraptions <span class="font-normal text-slate-400">(optional)</span></label><input id="career-contraptions" name="specific_contraptions" value="<?php echo e(old('specific_contraptions')); ?>" maxlength="255" class="form-field" placeholder="Example: wheelchair, oxygen concentrator"><?php $__errorArgs = ['specific_contraptions', 'careerCreate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                    <div><label for="career-condition" class="form-label">Condition summary <span class="font-normal text-slate-400">(optional)</span></label><textarea id="career-condition" name="condition_summary" rows="3" maxlength="500" class="form-field" placeholder="Short care-relevant context only"><?php echo e(old('condition_summary')); ?></textarea><?php $__errorArgs = ['condition_summary', 'careerCreate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900"><span class="font-bold">Privacy boundary:</span> Do not enter patient names, exact addresses, contact details, identification numbers, medical histories, or upload patient documents.</div>
                </div>
                <div class="space-y-4">
                    <label class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4 text-sm text-slate-700"><input type="checkbox" name="is_published" value="1" <?php if(old('is_published')): echo 'checked'; endif; ?> class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Publish now</span><span class="mt-1 block leading-5">Alumni receive an in-app notification when this career becomes visible.</span></span></label>
                    <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-900">Graduate SMS</p>
                        <p class="text-xs leading-5 text-slate-500">Send immediately reaches senior alumni only. A scheduled text reaches both junior and senior alumni. Only alumni with a valid contact number are included.</p>
                        <?php echo $__env->make('admin.learning.partials.career-sms-preview', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <label class="flex items-start gap-3 text-sm text-slate-700"><input type="checkbox" name="sms_send_immediately" value="1" <?php if(old('sms_send_immediately')): echo 'checked'; endif; ?> class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Send SMS immediately</span><span class="mt-1 block leading-5">Text senior alumni as soon as this career is published, using Semaphore’s priority queue so the message is not held for several minutes. This uses 2 credits per text. Leave this unchecked and set a schedule to text junior and senior alumni on the regular queue.</span></span></label>
                        <div><label for="career-sms-scheduled-at" class="form-label">Or schedule SMS date and time</label><input id="career-sms-scheduled-at" name="sms_scheduled_at" type="datetime-local" min="<?php echo e(now()->format('Y-m-d\\TH:i')); ?>" value="<?php echo e(old('sms_scheduled_at')); ?>" class="form-field"><?php $__errorArgs = ['sms_scheduled_at', 'careerCreate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                    </div>
                </div>
            </div>
            <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action inline-flex items-center justify-center gap-2"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'briefcase','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'briefcase','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Save career</button></div>
        </form>
    </dialog>

    <section class="dashboard-panel" aria-labelledby="career-opportunities-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="dashboard-section-kicker">Centralized postings</p><h2 id="career-opportunities-title" class="dashboard-section-title text-xl">Career opportunities</h2></div>
            <span class="text-sm text-slate-500"><?php echo e($jobs->total()); ?> total records</span>
        </div>

        <div class="mt-5 divide-y divide-slate-100">
            <?php $__empty_1 = true; $__currentLoopData = $jobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article class="flex flex-col gap-4 py-5 first:pt-0 last:pb-0 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-slate-950"><?php echo e($job->listingTitle()); ?></h3>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold <?php echo e($job->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>"><?php echo e($job->is_published ? 'Published' : 'Draft'); ?></span>
                            <?php if(! $job->estimated_start_date): ?><span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700">Privacy review required</span><?php endif; ?>
                        </div>
                        <p class="mt-2 text-sm text-slate-600"><?php echo e($job->estimated_salary ?: 'Salary not set'); ?><span class="mx-2 text-slate-300">|</span><?php echo e($job->estimated_start_date?->format('M d, Y') ?? 'Start date not set'); ?><span class="mx-2 text-slate-300">|</span><?php echo e($job->patientGenderLabel()); ?><span class="mx-2 text-slate-300">|</span><?php echo e($job->mobilityStatusLabel()); ?></p>
                        <p class="mt-1 text-xs font-semibold <?php echo e(filled($job->sms_last_error) && ! $job->sms_sent_at ? 'text-rose-600' : 'text-slate-500'); ?>"><?php echo e($job->smsStatusLabel()); ?><?php if($job->inquiries_count): ?> · <?php echo e($job->inquiries_count); ?> <?php echo e(str('inquiry')->plural($job->inquiries_count)); ?><?php endif; ?></p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-dashboard-dialog-open="career-edit-<?php echo e($job->id); ?>" class="secondary-action inline-flex items-center gap-2"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'pencil','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'pencil','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Edit</button>
                        <form method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.destroy', $job)); ?>" data-confirm="Remove this career?"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button type="submit" class="secondary-action border-red-200 text-red-700 hover:bg-red-50">Remove</button></form>
                    </div>
                </article>

                <dialog id="career-edit-<?php echo e($job->id); ?>" data-dashboard-dialog class="career-form-dialog m-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-edit-title-<?php echo e($job->id); ?>">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4"><div><h2 id="career-edit-title-<?php echo e($job->id); ?>" class="font-display text-xl font-bold text-slate-900">Edit career</h2><p class="mt-1 text-xs text-slate-500">Only privacy-approved care details are retained.</p></div><button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close edit form"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button></div>
                    <form method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.update', $job)); ?>" class="p-6" data-dashboard-dialog-form data-career-sms-form data-submit-label="Updating career...">
                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <div class="career-form-layout">
                            <div class="space-y-4">
                                <div><label for="job-title-<?php echo e($job->id); ?>" class="form-label">Career title</label><input id="job-title-<?php echo e($job->id); ?>" name="title" type="text" maxlength="160" value="<?php echo e($job->title); ?>" required class="form-field" data-career-sms-field="title"></div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div><label for="job-salary-<?php echo e($job->id); ?>" class="form-label">Estimated salary</label><input id="job-salary-<?php echo e($job->id); ?>" name="estimated_salary" type="text" maxlength="80" value="<?php echo e($job->estimated_salary); ?>" required class="form-field" data-career-sms-field="salary"></div>
                                    <div><label for="job-start-<?php echo e($job->id); ?>" class="form-label">Estimated start date</label><input id="job-start-<?php echo e($job->id); ?>" name="estimated_start_date" type="date" min="<?php echo e(now()->toDateString()); ?>" value="<?php echo e($job->estimated_start_date?->toDateString()); ?>" required class="form-field" data-career-sms-field="start"></div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div><label for="job-age-<?php echo e($job->id); ?>" class="form-label">Patient age <span class="font-normal text-slate-400">(optional)</span></label><input id="job-age-<?php echo e($job->id); ?>" name="patient_age" type="number" min="0" max="120" value="<?php echo e($job->patient_age); ?>" class="form-field" data-career-sms-field="age"></div>
                                    <div><label for="job-gender-<?php echo e($job->id); ?>" class="form-label">Patient gender</label><select id="job-gender-<?php echo e($job->id); ?>" name="patient_gender" required class="form-field" data-career-sms-field="gender"><option value="">Select gender</option><?php $__currentLoopData = $patientGenders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if($job->patient_gender === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                                    <div><label for="job-mobility-<?php echo e($job->id); ?>" class="form-label">Mobility status</label><select id="job-mobility-<?php echo e($job->id); ?>" name="mobility_status" required class="form-field" data-career-sms-field="mobility"><option value="">Select mobility</option><?php $__currentLoopData = $mobilityStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if($job->mobility_status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                                </div>
                                <div><label for="job-contraptions-<?php echo e($job->id); ?>" class="form-label">Specific contraptions <span class="font-normal text-slate-400">(optional)</span></label><input id="job-contraptions-<?php echo e($job->id); ?>" name="specific_contraptions" value="<?php echo e($job->specific_contraptions); ?>" maxlength="255" class="form-field"></div>
                                <div><label for="job-condition-<?php echo e($job->id); ?>" class="form-label">Condition summary <span class="font-normal text-slate-400">(optional)</span></label><textarea id="job-condition-<?php echo e($job->id); ?>" name="condition_summary" rows="3" maxlength="500" class="form-field"><?php echo e($job->condition_summary); ?></textarea></div>
                            </div>
                            <div class="space-y-4">
                                <label class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4 text-sm text-slate-700"><input type="checkbox" name="is_published" value="1" <?php if($job->is_published): echo 'checked'; endif; ?> class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Published for alumni</span><span class="mt-1 block leading-5">Uncheck to return this career to draft review.</span></span></label>
                                <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-bold text-slate-900">Graduate SMS</p>
                                    <p class="text-xs leading-5 <?php echo e(filled($job->sms_last_error) && ! $job->sms_sent_at ? 'font-semibold text-rose-600' : 'text-slate-500'); ?>"><?php echo e($job->smsStatusLabel()); ?></p>
                                    <?php echo $__env->make('admin.learning.partials.career-sms-preview', ['opportunity' => $job], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    <?php if (! ($job->sms_sent_at)): ?>
                                        <label class="flex items-start gap-3 text-sm text-slate-700"><input type="checkbox" name="sms_send_immediately" value="1" <?php if($job->sms_mode === \App\Models\CareerOpportunity::SMS_IMMEDIATE): echo 'checked'; endif; ?> class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span class="font-bold text-slate-900">Send SMS immediately</span></label>
                                        <div><label for="job-sms-<?php echo e($job->id); ?>" class="form-label">Or schedule SMS date and time</label><input id="job-sms-<?php echo e($job->id); ?>" name="sms_scheduled_at" type="datetime-local" min="<?php echo e(now()->format('Y-m-d\\TH:i')); ?>" value="<?php echo e($job->sms_scheduled_at?->format('Y-m-d\\TH:i')); ?>" class="form-field"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" class="primary-action">Save changes</button></div>
                    </form>
                </dialog>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="py-10 text-center"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'briefcase','class' => 'mx-auto h-8 w-8 text-slate-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'briefcase','class' => 'mx-auto h-8 w-8 text-slate-300']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?><p class="mt-3 font-bold text-slate-800">No careers yet</p><p class="mt-1 text-sm text-slate-500">Add the first privacy-reviewed career to start the board.</p></div>
            <?php endif; ?>
        </div>

        <?php if($jobs->hasPages()): ?><div class="mt-6 border-t border-slate-100 pt-5"><?php echo e($jobs->links()); ?></div><?php endif; ?>
    </section>

    <section class="dashboard-panel" aria-labelledby="career-applications-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="dashboard-section-kicker">Alumni applications</p><h2 id="career-applications-title" class="dashboard-section-title text-xl">Career applications</h2></div>
            <span class="text-sm text-slate-500"><?php echo e($inquiries->total()); ?> total records</span>
        </div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-3 font-bold">Applicant</th>
                        <th class="px-3 py-3 font-bold">Career</th>
                        <th class="px-3 py-3 font-bold">Contact</th>
                        <th class="px-3 py-3 font-bold">Credentials</th>
                        <th class="px-3 py-3 font-bold">Certificate</th>
                        <th class="px-3 py-3 font-bold">Status</th>
                        <th class="px-3 py-3 font-bold">Submitted</th>
                        <th class="px-3 py-3 font-bold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $inquiries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inquiry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-4">
                                <p class="font-bold text-slate-900"><?php echo e($inquiry->name); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($inquiry->email); ?></p>
                            </td>
                            <td class="px-3 py-4 font-semibold text-slate-800"><?php echo e($inquiry->opportunity?->listingTitle() ?? 'Removed career'); ?></td>
                            <td class="px-3 py-4 text-slate-600"><?php echo e($inquiry->contact_number); ?></td>
                            <td class="px-3 py-4">
                                <?php if(is_array($inquiry->credential_paths) && count($inquiry->credential_paths) > 0): ?>
                                    <span class="text-xs font-semibold text-purple-700"><?php echo e(count($inquiry->credential_paths)); ?> <?php echo e(str('file')->plural(count($inquiry->credential_paths))); ?></span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-4">
                                <?php if($inquiry->isApproved() && $inquiry->certificate_status): ?>
                                    <span class="text-xs font-semibold
                                        <?php echo e($inquiry->isCareerAwarded() ? 'text-emerald-700' :
                                           ($inquiry->certificateIsPendingReview() ? 'text-amber-700' :
                                           ($inquiry->certificate_status === \App\Models\CareerInquiry::CERT_REJECTED ? 'text-red-700' : 'text-sky-700'))); ?>">
                                        <?php echo e($inquiry->certificateStatusLabel()); ?>

                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold
                                    <?php echo e($inquiry->isPending() ? 'bg-amber-50 text-amber-700' :
                                       ($inquiry->isApproved() ? 'bg-emerald-50 text-emerald-700' :
                                       ($inquiry->isRejected() ? 'bg-red-50 text-red-700' :
                                       ($inquiry->status === \App\Models\CareerInquiry::STATUS_CLOSED ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700')))); ?>">
                                    <?php echo e($inquiry->statusLabel()); ?>

                                </span>
                            </td>
                            <td class="px-3 py-4 text-slate-500"><?php echo e($inquiry->created_at?->format('M d, Y g:i A')); ?></td>
                            <td class="px-3 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <?php if($inquiry->certificateIsPendingReview()): ?>
                                        <form method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.inquiries.certificate.update', $inquiry)); ?>" data-confirm="Approve this placement certificate and award the career in alumni history?"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><input type="hidden" name="certificate_status" value="approved"><button type="submit" class="secondary-action border-emerald-200 text-emerald-700 hover:bg-emerald-50 inline-flex items-center gap-1"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'award','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'award','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Award career</button></form>
                                        <form method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.inquiries.certificate.update', $inquiry)); ?>" data-confirm="Return this certificate to the alumni for resubmission?"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><input type="hidden" name="certificate_status" value="rejected"><button type="submit" class="secondary-action border-red-200 text-red-700 hover:bg-red-50 inline-flex items-center gap-1"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Return cert</button></form>
                                    <?php endif; ?>
                                    <?php if($inquiry->isPending()): ?>
                                        <form method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.inquiries.update', $inquiry)); ?>" data-confirm="Approve this application? This fills the opening, closes the posting, and marks every other pending applicant as not selected."><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><input type="hidden" name="status" value="approved"><button type="submit" class="secondary-action border-emerald-200 text-emerald-700 hover:bg-emerald-50 inline-flex items-center gap-1"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'circle-check','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'circle-check','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Approve</button></form>
                                        <form method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.inquiries.update', $inquiry)); ?>" data-confirm="Reject this application?"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><input type="hidden" name="status" value="rejected"><button type="submit" class="secondary-action border-red-200 text-red-700 hover:bg-red-50 inline-flex items-center gap-1"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Reject</button></form>
                                    <?php endif; ?>
                                    <button type="button" data-dashboard-dialog-open="career-inquiry-<?php echo e($inquiry->id); ?>" class="secondary-action inline-flex items-center gap-2"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'pencil','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'pencil','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Review</button>
                                    <form method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.inquiries.destroy', $inquiry)); ?>" data-confirm="Remove this application?"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button type="submit" class="secondary-action border-red-200 text-red-700 hover:bg-red-50">Remove</button></form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="8" class="px-3 py-10 text-center text-slate-500">No career applications yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php $__currentLoopData = $inquiries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inquiry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <dialog id="career-inquiry-<?php echo e($inquiry->id); ?>" data-dashboard-dialog class="career-review-dialog m-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-inquiry-title-<?php echo e($inquiry->id); ?>">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
                    <div>
                        <h2 id="career-inquiry-title-<?php echo e($inquiry->id); ?>" class="font-display text-xl font-bold text-slate-900">Review application</h2>
                        <p class="mt-1 text-xs text-slate-500"><?php echo e($inquiry->opportunity?->listingTitle() ?? 'Removed career'); ?></p>
                    </div>
                    <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close application review"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button>
                </div>
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                        <p><span class="font-bold text-slate-900">Applicant:</span> <?php echo e($inquiry->name); ?></p>
                        <p class="mt-1"><span class="font-bold text-slate-900">Email:</span> <?php echo e($inquiry->email); ?></p>
                        <p class="mt-1"><span class="font-bold text-slate-900">Contact number:</span> <?php echo e($inquiry->contact_number); ?></p>
                        <p class="mt-3 whitespace-pre-line"><?php echo e($inquiry->message); ?></p>
                    </div>
                    <?php if(is_array($inquiry->credential_paths) && count($inquiry->credential_paths) > 0): ?>
                        <div class="rounded-lg border border-purple-100 bg-purple-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Attached credentials</p>
                            <ul class="mt-2 space-y-1.5">
                                <?php $__currentLoopData = $inquiry->credential_paths; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $credential): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="flex items-center gap-2 text-sm">
                                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-text','class' => 'h-4 w-4 shrink-0 text-purple-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-text','class' => 'h-4 w-4 shrink-0 text-purple-600']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                                        <a href="<?php echo e(route('admin.learning.alumni-jobs.inquiries.credential', [$inquiry, $idx])); ?>" target="_blank" class="truncate font-semibold text-purple-700 underline decoration-purple-200 hover:text-purple-900"><?php echo e(data_get($credential, 'name', 'Credential '.($idx + 1))); ?></a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if($inquiry->isApproved()): ?>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Placement certificate</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900"><?php echo e($inquiry->certificateStatusLabel() ?? 'Not started'); ?></p>
                            <?php if($inquiry->placement_certificate): ?>
                                <a href="<?php echo e(route('admin.learning.alumni-jobs.inquiries.placement-certificate', $inquiry)); ?>" target="_blank" class="mt-2 inline-flex items-center gap-1 text-sm font-bold text-purple-700 underline decoration-purple-200 hover:text-purple-900">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-text','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-text','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                                    View placement certificate
                                </a>
                            <?php endif; ?>
                            <?php if($inquiry->certificate_admin_notes): ?>
                                <p class="mt-3 text-sm leading-6 text-slate-700"><span class="font-bold text-slate-900">Certificate remarks:</span> <?php echo e($inquiry->certificate_admin_notes); ?></p>
                            <?php endif; ?>
                            <?php if($inquiry->certificateIsPendingReview()): ?>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button type="submit" form="inquiry-award-<?php echo e($inquiry->id); ?>" class="secondary-action border-emerald-200 text-emerald-700 hover:bg-emerald-50 inline-flex items-center gap-1"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'award','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'award','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Award career</button>
                                    <button type="submit" form="inquiry-return-cert-<?php echo e($inquiry->id); ?>" class="secondary-action border-red-200 text-red-700 hover:bg-red-50 inline-flex items-center gap-1"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Return certificate</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.inquiries.update', $inquiry)); ?>" class="shrink-0 space-y-4 border-t border-slate-200 bg-white px-6 py-5" data-dashboard-dialog-form data-submit-label="Saving decision...">
                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <div>
                        <label for="inquiry-status-<?php echo e($inquiry->id); ?>" class="form-label">Decision</label>
                        <select id="inquiry-status-<?php echo e($inquiry->id); ?>" name="status" required class="form-field">
                            <?php $__currentLoopData = $inquiryStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('status', $inquiry->status) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Approving fills this opening, closes the posting, and marks other pending applicants as not selected.</p>
                    </div>
                    <div>
                        <label for="inquiry-notes-<?php echo e($inquiry->id); ?>" class="form-label">Admin notes <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="inquiry-notes-<?php echo e($inquiry->id); ?>" name="admin_notes" rows="3" maxlength="1000" class="form-field"><?php echo e(old('admin_notes', $inquiry->admin_notes)); ?></textarea>
                        <?php $__errorArgs = ['admin_notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                        <button type="submit" data-action-button class="primary-action">Save decision</button>
                    </div>
                </form>
                <?php if($inquiry->certificateIsPendingReview()): ?>
                    <form id="inquiry-award-<?php echo e($inquiry->id); ?>" method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.inquiries.certificate.update', $inquiry)); ?>" class="hidden" data-confirm="Approve this placement certificate and award the career?">
                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <input type="hidden" name="certificate_status" value="approved">
                    </form>
                    <form id="inquiry-return-cert-<?php echo e($inquiry->id); ?>" method="POST" action="<?php echo e(route('admin.learning.alumni-jobs.inquiries.certificate.update', $inquiry)); ?>" class="hidden" data-confirm="Return this certificate for resubmission?">
                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <input type="hidden" name="certificate_status" value="rejected">
                    </form>
                <?php endif; ?>
            </dialog>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php if($inquiries->hasPages()): ?><div class="mt-5 border-t border-slate-100 pt-5"><?php echo e($inquiries->links()); ?></div><?php endif; ?>
    </section>

    <section class="dashboard-panel" aria-labelledby="alumni-availability-title">
        <div class="border-b border-slate-200 pb-5"><p class="dashboard-section-kicker">Caregiver connectivity</p><h2 id="alumni-availability-title" class="dashboard-section-title text-xl">Alumni availability</h2></div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3 font-bold">Alumni</th><th class="px-3 py-3 font-bold">Account</th><th class="px-3 py-3 font-bold">Duty status</th><th class="px-3 py-3 font-bold">Updated</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $alumniRoster; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alumni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php ($profile = $alumni->alumniProfile); ?>
                        <tr><td class="px-3 py-4 font-bold text-slate-900"><?php echo e($alumni->name); ?></td><td class="px-3 py-4 text-slate-600"><?php echo e($alumni->email); ?></td><td class="px-3 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold <?php echo e($profile?->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>"><?php echo e($profile?->is_available_for_duty ? 'Available for Duty' : 'Unavailable'); ?></span></td><td class="px-3 py-4 text-slate-500"><?php echo e($profile?->availability_updated_at?->diffForHumans() ?? 'Not updated'); ?></td></tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="px-3 py-10 text-center text-slate-500">No alumni accounts yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($alumniRoster->hasPages()): ?><div class="mt-5 border-t border-slate-100 pt-5"><?php echo e($alumniRoster->links()); ?></div><?php endif; ?>
    </section>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Alumni Job Board | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\alumni-jobs.blade.php ENDPATH**/ ?>