<?php $__env->startSection('content'); ?>
    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Alumni directory</p>
            <h1 class="dashboard-section-title mt-2 text-2xl">Alumni list</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">All MCARE graduates with Career Hub access, including duty availability, alumni standing, and career activity.</p>
        </header>

        <div class="grid gap-3 sm:grid-cols-3">
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Total alumni</p>
                    <p class="dashboard-stat-value"><?php echo e($stats['total']); ?></p>
                    <p class="dashboard-stat-help">Graduates in the system</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?></span>
            </article>
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Available for duty</p>
                    <p class="dashboard-stat-value"><?php echo e($stats['available']); ?></p>
                    <p class="dashboard-stat-help">Marked available in Career Hub</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?></span>
            </article>
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Senior alumni</p>
                    <p class="dashboard-stat-value"><?php echo e($stats['senior']); ?></p>
                    <p class="dashboard-stat-help">Promoted senior standing</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-700 ring-1 ring-violet-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'award','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'award','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span>
            </article>
        </div>

        <div class="dashboard-panel">
            <form method="GET" action="<?php echo e(route('admin.alumni.index')); ?>" data-auto-filter class="grid gap-4 md:grid-cols-[minmax(0,1fr)_12rem_auto] md:items-end">
                <div>
                    <label for="alumni-search" class="form-label">Search</label>
                    <input id="alumni-search" name="search" type="search" value="<?php echo e($search); ?>" placeholder="Name, email, or contact number" class="form-field">
                </div>
                <div>
                    <label for="alumni-availability" class="form-label">Duty status</label>
                    <select id="alumni-availability" name="availability" class="form-field">
                        <option value="">All alumni</option>
                        <option value="available" <?php if($availability === 'available'): echo 'selected'; endif; ?>>Available for duty</option>
                        <option value="unavailable" <?php if($availability === 'unavailable'): echo 'selected'; endif; ?>>Unavailable</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="primary-action">Filter</button>
                    <?php if($search !== '' || $availability !== ''): ?>
                        <a href="<?php echo e(route('admin.alumni.index')); ?>" class="secondary-action">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Alumni</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Standing</th>
                        <th class="px-4 py-3">Duty status</th>
                        <th class="px-4 py-3">Applications</th>
                        <th class="px-4 py-3">Awarded careers</th>
                        <th class="px-4 py-3">Updated</th>
                        <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $alumni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $person): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $profile = $person->alumniProfile;
                            $contactNumber = $person->contact_number ?: $person->enrollmentApplication?->contact_number;
                        ?>
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-900"><?php echo e($person->name); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($person->email); ?></p>
                            </td>
                            <td class="px-4 py-4 text-slate-700"><?php echo e($contactNumber ?: '—'); ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 <?php echo e(($profile?->rank ?? \App\Models\AlumniProfile::RANK_JUNIOR) === \App\Models\AlumniProfile::RANK_SENIOR ? 'bg-purple-50 text-purple-800 ring-purple-100' : 'bg-slate-50 text-slate-700 ring-slate-200'); ?>">
                                    <?php echo e(\App\Models\AlumniProfile::ranks()[$profile?->rank ?? \App\Models\AlumniProfile::RANK_JUNIOR]); ?>

                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo e($profile?->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>">
                                    <?php echo e($profile?->is_available_for_duty ? 'Available for Duty' : 'Unavailable'); ?>

                                </span>
                            </td>
                            <td class="px-4 py-4 font-semibold text-slate-800"><?php echo e($person->applications_count); ?></td>
                            <td class="px-4 py-4 font-semibold text-slate-800"><?php echo e($person->awarded_careers_count); ?></td>
                            <td class="px-4 py-4 text-slate-500"><?php echo e($profile?->availability_updated_at?->diffForHumans() ?? 'Not updated'); ?></td>
                            <td class="px-4 py-4">
                                <a href="<?php echo e(route('admin.alumni.show', $person)); ?>" class="secondary-action inline-flex h-9 items-center gap-2 text-xs">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'eye','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'eye','class' => 'h-4 w-4']); ?>
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
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-500">No alumni match your filters.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if($alumni->hasPages()): ?>
                <div class="border-t border-slate-100 px-4 py-4"><?php echo e($alumni->links()); ?></div>
            <?php endif; ?>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Alumni List | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\alumni\index.blade.php ENDPATH**/ ?>