<?php $__env->startSection('content'); ?>
<div class="w-full space-y-6">
    <header class="border-b border-stone-200 pb-5">
        <p class="dashboard-section-kicker">Trainer search</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">
            <?php if($query === ''): ?>
                Search the trainer portal
            <?php else: ?>
                Results for “<?php echo e($query); ?>”
            <?php endif; ?>
        </h1>
        <p class="mt-2 text-sm text-stone-600">
            <?php if($query === ''): ?>
                Find pages, people, modules, quizzes, classes, and stream posts from this trainer workspace.
            <?php elseif(mb_strlen($query) < 2): ?>
                Type at least two characters to search.
            <?php elseif($resultCount === 0): ?>
                No matching pages or records were found in your assigned class.
            <?php else: ?>
                <?php echo e($resultCount); ?> <?php echo e(\Illuminate\Support\Str::plural('match', $resultCount)); ?> across your trainer pages and class data.
            <?php endif; ?>
        </p>
    </header>

    <?php $__empty_1 = true; $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <section class="dashboard-table-wrap" aria-labelledby="search-<?php echo e($group['key']); ?>">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 id="search-<?php echo e($group['key']); ?>" class="text-sm font-bold uppercase tracking-[0.12em] text-slate-500"><?php echo e($group['label']); ?></h2>
            </div>
            <ul class="divide-y divide-slate-100">
                <?php $__currentLoopData = $group['results']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li>
                        <a href="<?php echo e($result['href']); ?>" class="flex flex-col gap-0.5 px-4 py-3 hover:bg-violet-50">
                            <span class="font-semibold text-slate-950"><?php echo e($result['title']); ?></span>
                            <?php if($result['subtitle'] !== ''): ?>
                                <span class="text-sm text-slate-500"><?php echo e($result['subtitle']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </section>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <?php if($query !== '' && mb_strlen($query) >= 2): ?>
            <div class="dashboard-panel">
                <p class="text-sm text-slate-600">Try a page name such as People or Classwork, a trainee name, or a module code.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Search | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\search.blade.php ENDPATH**/ ?>