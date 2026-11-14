<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replace enrollment documents | MCARE</title>
    <?php if (isset($component)) { $__componentOriginald9e77967a5438b63fd29d241808e49d9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald9e77967a5438b63fd29d241808e49d9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-favicon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-favicon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald9e77967a5438b63fd29d241808e49d9)): ?>
<?php $attributes = $__attributesOriginald9e77967a5438b63fd29d241808e49d9; ?>
<?php unset($__attributesOriginald9e77967a5438b63fd29d241808e49d9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald9e77967a5438b63fd29d241808e49d9)): ?>
<?php $component = $__componentOriginald9e77967a5438b63fd29d241808e49d9; ?>
<?php unset($__componentOriginald9e77967a5438b63fd29d241808e49d9); ?>
<?php endif; ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="enrollment-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <?php if (isset($component)) { $__componentOriginald5c36e5eb93f3b111375117a4289e4f9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5c36e5eb93f3b111375117a4289e4f9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-header','data' => ['mastheadAside' => 'Caregiving NC II · Document revision','navLabel' => 'Document revision','secondaryHref' => route('landing'),'secondaryLabel' => 'Public site','primaryHref' => route('login'),'primaryLabel' => 'Sign in']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['masthead-aside' => 'Caregiving NC II · Document revision','nav-label' => 'Document revision','secondary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('landing')),'secondary-label' => 'Public site','primary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('login')),'primary-label' => 'Sign in']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald5c36e5eb93f3b111375117a4289e4f9)): ?>
<?php $attributes = $__attributesOriginald5c36e5eb93f3b111375117a4289e4f9; ?>
<?php unset($__attributesOriginald5c36e5eb93f3b111375117a4289e4f9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald5c36e5eb93f3b111375117a4289e4f9)): ?>
<?php $component = $__componentOriginald5c36e5eb93f3b111375117a4289e4f9; ?>
<?php unset($__componentOriginald5c36e5eb93f3b111375117a4289e4f9); ?>
<?php endif; ?>

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Official TESDA NC II document revision</p>
                <h1>Replace documents that need replacement</h1>
                <p class="enrollment-lede">
                    MCARE administration asked you to re-upload only the files listed below. Accepted documents are not shown and cannot be changed from this page.
                </p>
                <dl class="enrollment-status-row">
                    <div>
                        <dt>Applicant</dt>
                        <dd><?php echo e(trim($application->first_name.' '.$application->last_name) ?: 'Enrollment applicant'); ?></dd>
                    </div>
                    <div>
                        <dt>Enrollment number</dt>
                        <dd><?php echo e($application->enrollment_number); ?></dd>
                    </div>
                    <div>
                        <dt>Files to replace</dt>
                        <dd><?php echo e(count($documents)); ?> <?php echo e(\Illuminate\Support\Str::plural('document', count($documents))); ?></dd>
                    </div>
                </dl>
            </header>

            <section class="enrollment-form-body">
                <?php if(session('saved')): ?>
                    <div class="mb-6 border border-emerald-200 bg-emerald-50 p-4" role="status">
                        <p class="text-sm font-semibold leading-6 text-emerald-900"><?php echo e(session('saved')); ?></p>
                    </div>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <div class="mb-6 border border-red-200 bg-red-50 p-4" role="alert">
                        <p class="text-sm font-bold text-red-900">Please upload a replacement for every file listed below.</p>
                    </div>
                <?php endif; ?>

                <?php if($documents === []): ?>
                    <?php if (! (session('saved'))): ?>
                        <div class="border border-slate-200 bg-slate-50 p-5">
                            <p class="text-sm font-semibold leading-6 text-slate-700">There are no documents currently marked for replacement. If you already re-uploaded the requested files, MCARE administration will review them.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="POST" action="<?php echo e($formAction); ?>" enctype="multipart/form-data" class="space-y-6">
                        <?php echo csrf_field(); ?>

                        <div class="enrollment-fields grid grid-cols-1 gap-5 md:grid-cols-2">
                            <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="enrollment-upload-card">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <label for="<?php echo e($document['input']); ?>" class="block text-sm font-bold text-slate-900"><?php echo e($document['label']); ?></label>
                                        <span class="text-[11px] font-black uppercase tracking-wide text-amber-800">
                                            <?php echo e($document['status'] === 'missing' ? 'Missing' : 'Needs replacement'); ?>

                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs leading-5 text-slate-500"><?php echo e($document['description']); ?></p>
                                    <?php if($document['note']): ?>
                                        <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold leading-5 text-amber-950"><?php echo e($document['note']); ?></p>
                                    <?php endif; ?>
                                    <div data-upload-zone class="enrollment-upload-zone relative mt-4 rounded-2xl border-2 border-dashed border-purple-200 bg-white px-5 py-7 text-center transition hover:border-purple-400 hover:bg-purple-50/50">
                                        <input id="<?php echo e($document['input']); ?>" name="<?php echo e($document['input']); ?>" type="file" accept="<?php echo e($document['accept']); ?>" required class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                                        <p class="text-sm font-bold text-purple-700">Click to upload or drag and drop</p>
                                        <p data-upload-name class="mt-1 text-xs font-semibold text-slate-500">No file selected</p>
                                    </div>
                                    <?php $__errorArgs = [$document['input']];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-purple-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-purple-800">
                                Submit replacement files
                            </button>
                            <p class="text-xs leading-5 text-slate-500">PDF, JPG, or PNG. ID photo and signature accept JPG or PNG only. Maximum 5MB per file.</p>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </article>
    </main>

    <script>
        document.querySelectorAll('[data-upload-zone] input[type="file"]').forEach((input) => {
            input.addEventListener('change', () => {
                const name = input.closest('[data-upload-zone]')?.querySelector('[data-upload-name]');
                if (name) {
                    name.textContent = input.files?.[0]?.name || 'No file selected';
                }
            });
        });
    </script>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\enrollment\revise-documents.blade.php ENDPATH**/ ?>