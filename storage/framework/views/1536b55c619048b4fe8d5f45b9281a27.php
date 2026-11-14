<?php $__env->startSection('content'); ?>
<?php
    $trainerErrors = $errors->getBag('trainer');
    $traineeErrors = $errors->getBag('trainee');
    $currentRole = $roleFilter ?? 'all';
    $searchQuery = $search ?? '';
?>

<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <p class="max-w-3xl text-sm leading-6 text-slate-600">
            Manage verified staff access, assisted onsite trainees, and applicants released for review.
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="trainer-account-dialog" class="secondary-action inline-flex items-center justify-center gap-2">
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
<?php endif; ?>Add trainer
            </button>
            <button type="button" data-dashboard-dialog-open="trainee-account-dialog" class="secondary-action inline-flex items-center justify-center gap-2">
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
<?php endif; ?>Assisted trainee intake
            </button>
            <a href="<?php echo e(route('admin.historical-alumni.index')); ?>" class="primary-action inline-flex items-center justify-center gap-2">
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'user-check','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user-check','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Alumni claims
            </a>
        </div>
    </header>

    <!-- Role Filter Tabs & Search Bar -->
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-1.5" role="tablist" aria-label="Filter accounts by role">
            <a href="<?php echo e(route('admin.accounts.index', ['role' => 'all', 'search' => $searchQuery])); ?>"
               class="rounded-xl px-4 py-2 text-xs font-bold transition <?php echo e($currentRole === 'all' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'); ?>">
                All Accounts (<?php echo e($counts['all'] ?? 0); ?>)
            </a>
            <a href="<?php echo e(route('admin.accounts.index', ['role' => 'trainer', 'search' => $searchQuery])); ?>"
               class="rounded-xl px-4 py-2 text-xs font-bold transition <?php echo e($currentRole === 'trainer' ? 'bg-indigo-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'); ?>">
                Trainers (<?php echo e($counts['trainer'] ?? 0); ?>)
            </a>
            <a href="<?php echo e(route('admin.accounts.index', ['role' => 'trainee', 'search' => $searchQuery])); ?>"
               class="rounded-xl px-4 py-2 text-xs font-bold transition <?php echo e($currentRole === 'trainee' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'); ?>">
                Trainees (<?php echo e($counts['trainee'] ?? 0); ?>)
            </a>
            <a href="<?php echo e(route('admin.accounts.index', ['role' => 'alumni', 'search' => $searchQuery])); ?>"
               class="rounded-xl px-4 py-2 text-xs font-bold transition <?php echo e($currentRole === 'alumni' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'); ?>">
                Alumni (<?php echo e($counts['alumni'] ?? 0); ?>)
            </a>
            <a href="<?php echo e(route('admin.accounts.index', ['role' => 'applicant', 'search' => $searchQuery])); ?>"
               class="rounded-xl px-4 py-2 text-xs font-bold transition <?php echo e($currentRole === 'applicant' ? 'bg-amber-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'); ?>">
                Applicants (<?php echo e($counts['applicant'] ?? 0); ?>)
            </a>
        </div>

        <form method="GET" action="<?php echo e(route('admin.accounts.index')); ?>" class="flex items-center gap-2">
            <input type="hidden" name="role" value="<?php echo e($currentRole); ?>">
            <div class="relative">
                <input type="text" name="search" value="<?php echo e($searchQuery); ?>" placeholder="Search name, email..."
                       class="form-field w-56 text-xs pl-8 pr-3 py-1.5">
                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'search','class' => 'h-3.5 w-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','class' => 'h-3.5 w-3.5']); ?>
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
                </span>
            </div>
            <button type="submit" class="secondary-action text-xs py-1.5 px-3">Search</button>
            <?php if(filled($searchQuery)): ?>
                <a href="<?php echo e(route('admin.accounts.index', ['role' => $currentRole])); ?>" class="text-xs text-slate-500 hover:text-slate-900 underline">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Accounts Table -->
    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table w-full min-w-[66rem]">
            <thead>
                <tr>
                    <th>Account / Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Batch / Progress Status</th>
                    <th>Verification</th>
                    <th>Created</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $app = $account->enrollmentApplication;
                        $roleBadge = match($account->role) {
                            'trainer' => 'bg-indigo-50 text-indigo-700 ring-indigo-100',
                            'trainee' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            'applicant' => 'bg-amber-50 text-amber-800 ring-amber-100',
                            default => 'bg-purple-50 text-purple-700 ring-purple-100',
                        };
                        $isAlumni = $account->isGraduate();
                        if ($isAlumni) $roleBadge = 'bg-purple-50 text-purple-700 ring-purple-100';
                        $deleteTitle = $app?->accountDeletionTitle() ?: 'Delete this account?';
                        $deleteMessage = $app?->accountDeletionMessage() ?: "Permanently delete account for '".($account->name ?: $account->email)."' (".str($account->role)->headline().')? All related enrollment applications, payment records, uploaded documents, and learning history will be permanently deleted, allowing them to re-enroll if needed.';
                        $deleteDetail = $app?->accountDeletionDetail();
                        $deleteAction = $app?->accountDeletionAction() ?: 'Delete account';
                    ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $account,'application' => $app,'useEnrollmentPhoto' => true,'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($account),'application' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($app),'use-enrollment-photo' => true,'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-800']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $attributes = $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $component = $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900"><?php echo e($account->name ?: ($app ? $app->first_name.' '.$app->last_name : 'No name set')); ?></div>
                                    <?php if(filled($account->google_id)): ?>
                                        <span class="inline-flex items-center gap-1 text-[10px] text-slate-500 font-medium">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-blue-500"></span> Google Sign-in
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="font-mono text-xs text-slate-700"><?php echo e($account->contact_email ?: $account->email); ?></div>
                            <?php if($account->contact_number || $app?->contact_number): ?>
                                <div class="text-[11px] text-slate-500"><?php echo e($account->contact_number ?: $app?->contact_number); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="dashboard-pill <?php echo e($roleBadge); ?>">
                                <?php echo e($isAlumni ? 'Alumni' : str($account->role)->headline()); ?>

                            </span>
                            <?php if($account->role === 'trainee' || $account->trainee_status): ?>
                                <div class="mt-1 text-[11px] font-semibold text-slate-500"><?php echo e($account->traineeStatusLabel()); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($app): ?>
                                <div class="text-xs font-semibold text-slate-900">
                                    <?php echo e($app->is_historical_record ? 'Historical verified record' : ($app->batch ? $app->batch->name.' '.$app->batch->year : 'No batch assigned')); ?>

                                </div>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-bold <?php echo e($app->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($app->status === 'denied' ? 'bg-red-100 text-red-800' : 'bg-stone-100 text-stone-700')); ?>">
                                        <?php echo e($app->statusLabel()); ?>

                                    </span>
                                    <span class="text-[10px] text-slate-500">· <?php echo e($app->paymentStatusLabel()); ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">Direct staff account</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="dashboard-pill <?php echo e($account->hasVerifiedEmail() ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100'); ?>">
                                <?php echo e($account->hasVerifiedEmail() ? 'Verified' : 'Pending'); ?>

                            </span>
                        </td>
                        <td class="text-xs text-slate-600">
                            <?php echo e($account->created_at?->format('M d, Y g:i A')); ?>

                        </td>
                        <td class="text-right">
                            <form method="POST" action="<?php echo e(route('admin.accounts.destroy', $account)); ?>"
                                  data-confirm-title="<?php echo e($deleteTitle); ?>"
                                  data-confirm="<?php echo e($deleteMessage); ?>"
                                  <?php if($deleteDetail): ?> data-confirm-detail="<?php echo e($deleteDetail); ?>" <?php endif; ?>
                                  data-confirm-action="<?php echo e($deleteAction); ?>">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-700 hover:bg-red-50 hover:border-red-300 transition"
                                        aria-label="Delete <?php echo e($account->name ?: $account->email); ?>" title="<?php echo e($deleteAction); ?>">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'trash-2','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash-2','class' => 'h-4 w-4']); ?>
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
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500">
                            No accounts found matching the selected filter.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($accounts->hasPages()): ?>
            <div class="border-t border-slate-200 px-5 py-4">
                <?php echo e($accounts->links()); ?>

            </div>
        <?php endif; ?>
    </div>

    <!-- DIALOG: ADD TRAINER -->
    <dialog id="trainer-account-dialog" data-dashboard-dialog data-auto-open="<?php echo e($trainerErrors->any() ? 'true' : 'false'); ?>" class="m-auto max-h-[90vh] w-[min(94vw,42rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="trainer-account-dialog-title">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div><h2 id="trainer-account-dialog-title" class="font-display text-xl font-bold text-slate-900">Add trainer</h2><p class="mt-1 text-xs text-slate-500">Enter the trainer’s name and email. A temporary password will be generated and sent to that address.</p></div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close trainer form" title="Close"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
        <form method="POST" action="<?php echo e(route('admin.accounts.trainers.store')); ?>" class="space-y-4 p-6" data-dashboard-dialog-form data-submit-label="Creating trainer...">
            <?php echo csrf_field(); ?>
            <div><label for="trainer-name" class="form-label">Full name</label><input id="trainer-name" name="name" value="<?php echo e(old('name')); ?>" class="form-field" required autofocus><?php $__errorArgs = ['name', 'trainer'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <div><label for="trainer-email" class="form-label">Email</label><input id="trainer-email" name="email" type="email" value="<?php echo e(old('email')); ?>" class="form-field" required><?php $__errorArgs = ['email', 'trainer'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <p class="rounded-lg border border-purple-100 bg-purple-50 px-3 py-2 text-xs leading-5 text-slate-600">MCARE will generate a unique temporary password and email it to this address. The trainer must verify the email before signing in.</p>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action">Create trainer</button></div>
        </form>
    </dialog>

    <!-- DIALOG: ADD TRAINEE -->
    <dialog id="trainee-account-dialog" data-dashboard-dialog data-auto-open="<?php echo e($traineeErrors->any() ? 'true' : 'false'); ?>" class="m-auto max-h-[90vh] w-[min(96vw,64rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="trainee-account-dialog-title">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div><h2 id="trainee-account-dialog-title" class="font-display text-xl font-bold text-slate-900">Assisted trainee intake</h2><p class="mt-1 text-xs text-slate-500">For walk-ins whose original requirements and onsite payment are verified by an administrator. A temporary password is generated and emailed after intake.</p></div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close trainee form" title="Close"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
        <form method="POST" action="<?php echo e(route('admin.accounts.trainees.store')); ?>" class="space-y-5 p-6" data-dashboard-dialog-form data-submit-label="Creating trainee...">
            <?php echo csrf_field(); ?>
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="trainee-first-name" class="form-label">First name</label><input id="trainee-first-name" name="first_name" value="<?php echo e(old('first_name')); ?>" class="form-field" required autofocus><?php $__errorArgs = ['first_name', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-middle-name" class="form-label">Middle name</label><input id="trainee-middle-name" name="middle_name" value="<?php echo e(old('middle_name')); ?>" class="form-field"><?php $__errorArgs = ['middle_name', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-last-name" class="form-label">Last name</label><input id="trainee-last-name" name="last_name" value="<?php echo e(old('last_name')); ?>" class="form-field" required><?php $__errorArgs = ['last_name', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="trainee-email" class="form-label">Email</label><input id="trainee-email" name="email" type="email" value="<?php echo e(old('email')); ?>" class="form-field" required><?php $__errorArgs = ['email', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-contact-number" class="form-label">Contact number</label><input id="trainee-contact-number" name="contact_number" value="<?php echo e(old('contact_number')); ?>" class="form-field" required><?php $__errorArgs = ['contact_number', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="trainee-birth-date" class="form-label">Birth date</label><input id="trainee-birth-date" name="birth_date" type="date" value="<?php echo e(old('birth_date')); ?>" class="form-field" required><?php $__errorArgs = ['birth_date', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-gender" class="form-label">Gender</label><select id="trainee-gender" name="gender" class="form-field" required><option value="">Select</option><option <?php if(old('gender') === 'Male'): echo 'selected'; endif; ?>>Male</option><option <?php if(old('gender') === 'Female'): echo 'selected'; endif; ?>>Female</option></select><?php $__errorArgs = ['gender', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-schedule" class="form-label">Schedule</label><select id="trainee-schedule" name="schedule_preference" class="form-field" required><option value="AM" <?php if(old('schedule_preference') === 'AM'): echo 'selected'; endif; ?>>AM</option><option value="PM" <?php if(old('schedule_preference') === 'PM'): echo 'selected'; endif; ?>>PM</option></select><?php $__errorArgs = ['schedule_preference', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            </div>
            <?php
                $selectedIntakeBatch = $batches->firstWhere('id', (int) old('training_batch_id'));
                $selectedIntakeProgram = $selectedIntakeBatch?->program ?? $defaultProgram;
            ?>
            <div><label for="trainee-batch" class="form-label">Program and batch</label><select id="trainee-batch" name="training_batch_id" class="form-field" required><option value="">Select batch</option><?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php ($batchProgram = $batch->program ?? $defaultProgram); ?><option value="<?php echo e($batch->id); ?>" data-program="<?php echo e($batchProgram?->name ?? 'Program not assigned'); ?>" data-downpayment="<?php echo e($batchProgram?->downpayment_amount ?? 0); ?>" data-total-fee="<?php echo e($batchProgram?->total_program_fee ?? 0); ?>" <?php if((int)old('training_batch_id') === $batch->id): echo 'selected'; endif; ?>><?php echo e($batchProgram?->name ?? 'Program not assigned'); ?> — <?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><?php $__errorArgs = ['training_batch_id', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="trainee-street" class="form-label">Number and street</label><input id="trainee-street" name="street" value="<?php echo e(old('street')); ?>" class="form-field" required><?php $__errorArgs = ['street', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-barangay" class="form-label">Barangay</label><input id="trainee-barangay" name="barangay" value="<?php echo e(old('barangay')); ?>" class="form-field" required><?php $__errorArgs = ['barangay', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-city" class="form-label">City</label><input id="trainee-city" name="city" value="<?php echo e(old('city')); ?>" class="form-field" required><?php $__errorArgs = ['city', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-province" class="form-label">Province</label><input id="trainee-province" name="province" value="<?php echo e(old('province')); ?>" class="form-field" required><?php $__errorArgs = ['province', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-zip-code" class="form-label">ZIP code</label><input id="trainee-zip-code" name="zip_code" value="<?php echo e(old('zip_code')); ?>" class="form-field" required><?php $__errorArgs = ['zip_code', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="trainee-education" class="form-label">Educational attainment</label><input id="trainee-education" name="educational_attainment" value="<?php echo e(old('educational_attainment')); ?>" class="form-field" required><?php $__errorArgs = ['educational_attainment', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-school" class="form-label">School</label><input id="trainee-school" name="school_name" value="<?php echo e(old('school_name')); ?>" class="form-field" required><?php $__errorArgs = ['school_name', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-year-graduated" class="form-label">Year graduated</label><input id="trainee-year-graduated" name="year_graduated" type="number" min="1950" max="<?php echo e(now()->year); ?>" value="<?php echo e(old('year_graduated')); ?>" class="form-field" required><?php $__errorArgs = ['year_graduated', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            </div>
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                <p class="text-xs font-black uppercase tracking-wide text-purple-700">Onsite requirements and consent</p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <?php $__currentLoopData = ['birth_certificate_onsite' => 'Original birth certificate inspected', 'education_document_onsite' => 'Original Form 137/138 or diploma inspected', 'good_moral_onsite' => 'Original good moral certificate inspected', 'id_photo_onsite' => 'ID photo received and identity checked', 'signature_onsite' => 'Applicant signature received', 'privacy_consent_onsite' => 'Applicant signed the privacy consent']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="flex items-start gap-3 rounded-lg border border-purple-100 bg-white p-3 text-xs leading-5 text-slate-700"><input type="checkbox" name="<?php echo e($field); ?>" value="1" <?php if(old($field)): echo 'checked'; endif; ?> class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-600" required><span><?php echo e($label); ?></span></label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="trainee-onsite-payment" class="form-label">Verified onsite payment</label><input id="trainee-onsite-payment" name="onsite_payment_amount" type="number" min="<?php echo e($selectedIntakeProgram?->downpayment_amount ?? '0.01'); ?>" max="<?php echo e($selectedIntakeProgram?->total_program_fee ?? '1000000'); ?>" step="0.01" value="<?php echo e(old('onsite_payment_amount', $selectedIntakeProgram?->downpayment_amount)); ?>" class="form-field" required><p id="trainee-payment-range" class="mt-1 text-xs text-slate-500">Select a batch to load its required downpayment and total fee.</p><?php $__errorArgs = ['onsite_payment_amount', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
                <div><label for="trainee-or-number" class="form-label">Official receipt number</label><input id="trainee-or-number" name="onsite_or_number" value="<?php echo e(old('onsite_or_number')); ?>" class="form-field" required><?php $__errorArgs = ['onsite_or_number', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            </div>
            <label class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900"><input type="checkbox" name="onsite_payment_received" value="1" <?php if(old('onsite_payment_received')): echo 'checked'; endif; ?> class="mt-1 h-4 w-4 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-600" required><span><strong class="block">Payment and receipt verified</strong>I confirm the amount was received onsite and the official receipt number above is accurate.</span></label>
            <div><label for="trainee-onsite-notes" class="form-label">Verification notes</label><textarea id="trainee-onsite-notes" name="onsite_verification_notes" rows="3" class="form-field" required placeholder="Record who presented the originals, relevant document references, and payment context."><?php echo e(old('onsite_verification_notes')); ?></textarea><?php $__errorArgs = ['onsite_verification_notes', 'trainee'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?></div>
            <p class="rounded-lg border border-purple-100 bg-purple-50 px-3 py-2 text-xs leading-5 text-slate-600">MCARE will generate a unique temporary password and email it to the trainee. They must verify the email before signing in.</p>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action">Verify intake and create trainee</button></div>
        </form>
    </dialog>
    <script>
        (() => {
            const batch = document.getElementById('trainee-batch');
            const amount = document.getElementById('trainee-onsite-payment');
            const hint = document.getElementById('trainee-payment-range');
            if (!batch || !amount || !hint) return;

            const money = (value) => Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const syncProgramFees = (replaceAmount = false) => {
                const option = batch.selectedOptions[0];
                const downpayment = Number(option?.dataset.downpayment || 0);
                const totalFee = Number(option?.dataset.totalFee || 0);
                if (!option?.value || downpayment <= 0 || totalFee <= 0) {
                    hint.textContent = 'Select a batch to load its required downpayment and total fee.';
                    return;
                }

                amount.min = String(downpayment);
                amount.max = String(totalFee);
                if (replaceAmount || !amount.value) amount.value = downpayment.toFixed(2);
                hint.textContent = `${option.dataset.program}: PHP ${money(downpayment)} minimum, PHP ${money(totalFee)} total fee.`;
            };

            batch.addEventListener('change', () => syncProgramFees(true));
            syncProgramFees(false);
        })();
    </script>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'User Accounts | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\accounts.blade.php ENDPATH**/ ?>