<?php $__env->startSection('content'); ?>
    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Career applications</p>
            <h1 class="text-2xl font-black text-slate-950 sm:text-3xl">My Applications</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Track the status of your career applications. Once MCARE approves your application, it will also appear in your Career History.</p>
        </header>

        <?php
            $pending = $applications->where('status', \App\Models\CareerInquiry::STATUS_PENDING)->count();
            $approved = $applications->where('status', \App\Models\CareerInquiry::STATUS_APPROVED)->count();
            $rejected = $applications->where('status', \App\Models\CareerInquiry::STATUS_REJECTED)->count();
        ?>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="dashboard-panel flex items-center gap-4 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'clock','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clock','class' => 'h-5 w-5']); ?>
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
                <div>
                    <p class="text-2xl font-black text-slate-950"><?php echo e($pending); ?></p>
                    <p class="text-xs font-semibold text-slate-500">Pending</p>
                </div>
            </div>
            <div class="dashboard-panel flex items-center gap-4 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?>
                </span>
                <div>
                    <p class="text-2xl font-black text-slate-950"><?php echo e($approved); ?></p>
                    <p class="text-xs font-semibold text-slate-500">Approved</p>
                </div>
            </div>
            <div class="dashboard-panel flex items-center gap-4 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-700">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-5 w-5']); ?>
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
                <div>
                    <p class="text-2xl font-black text-slate-950"><?php echo e($rejected); ?></p>
                    <p class="text-xs font-semibold text-slate-500">Not approved</p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <?php $__empty_1 = true; $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $application): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php $job = $application->opportunity; ?>
                <article class="dashboard-panel">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Career application</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950"><?php echo e($job?->listingTitle() ?? 'Career opportunity'); ?></h2>
                            <p class="mt-1 text-sm font-bold text-purple-700"><?php echo e($job?->listingEmployer() ?? 'MCARE-Coordinated Placement'); ?></p>
                        </div>
                        <?php if($application->isApproved()): ?>
                            <span class="dashboard-pill bg-emerald-50 text-emerald-800 ring-emerald-200">Approved</span>
                        <?php elseif($application->isRejected()): ?>
                            <span class="dashboard-pill bg-red-50 text-red-800 ring-red-200">Not approved</span>
                        <?php elseif($application->isPending()): ?>
                            <span class="dashboard-pill bg-amber-50 text-amber-800 ring-amber-200">Pending</span>
                        <?php else: ?>
                            <span class="dashboard-pill bg-slate-50 text-slate-800 ring-slate-200"><?php echo e($application->statusLabel()); ?></span>
                        <?php endif; ?>
                    </div>

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <?php if($job?->estimated_salary): ?>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Salary</dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($job->estimated_salary); ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if($job?->estimated_start_date): ?>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Start</dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($job->estimated_start_date->format('M d, Y')); ?></dd>
                        </div>
                        <?php endif; ?>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Applied</dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($application->created_at?->format('M d, Y')); ?></dd>
                        </div>
                        <?php if($application->reviewed_at): ?>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Reviewed</dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($application->reviewed_at->format('M d, Y')); ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>

                    <?php if($application->admin_notes): ?>
                        <div class="mt-4 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Admin remarks</p>
                            <p class="mt-1 text-sm leading-6 text-slate-700"><?php echo e($application->admin_notes); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if(is_array($application->credential_paths) && count($application->credential_paths) > 0): ?>
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Attached credentials</p>
                            <ul class="mt-2 space-y-1.5">
                                <?php $__currentLoopData = $application->credential_paths; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $credential): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                                        <a href="<?php echo e(route('trainee.applications.credential', [$application, $idx])); ?>" target="_blank" class="truncate font-semibold text-purple-700 underline decoration-purple-200 hover:text-purple-900"><?php echo e(data_get($credential, 'name', 'Credential '.($idx + 1))); ?></a>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if($job?->postingSummary()): ?>
                        <p class="mt-4 text-sm leading-6 text-slate-600"><?php echo e($job->postingSummary()); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="dashboard-panel py-16 text-center lg:col-span-2">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'clipboard-list','class' => 'mx-auto h-9 w-9 text-slate-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-list','class' => 'mx-auto h-9 w-9 text-slate-300']); ?>
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
                    <h2 class="mt-4 text-lg font-bold text-slate-900">No applications yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Apply for a career from the Career Hub and your application will appear here so you can track its status.</p>
                    <a href="<?php echo e(route('trainee.career-hub')); ?>" class="primary-action mt-5 inline-flex">Open Career Hub</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if($applications->hasPages()): ?>
            <div><?php echo e($applications->links()); ?></div>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => 'My Applications | MCARE Graduate'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\applications.blade.php ENDPATH**/ ?>