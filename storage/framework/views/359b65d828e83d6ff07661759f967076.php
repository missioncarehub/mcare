<?php $__env->startSection('content'); ?>
<?php
    $previewKind = $module->previewKind();
    $viewerUrl = route('trainee.modules.content', $module);
    $downloadUrl = route('trainee.modules.download', $module);
    $traineeName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
    $watermarkImageUrl = \App\Support\WatermarkedFpdi::publicImageUrl();
    $supplementaryList = $module->supplementaryList();
    $isCompetent = $progress?->competency_outcome === 'competent';
    $assessmentAverage = $assessmentSummary['average_score'] ?? null;
    $unpassedQuizzes = $quizzes->reject(function ($q) use ($quizAttempts) {
        $attempts = $quizAttempts->get($q->id);
        return $attempts && $attempts->contains('passed', true);
    });
    $submoduleAccess = $submoduleAccess ?? collect();
?>

<div class="lms-page" data-lms-classwork data-lms-role="trainee" data-protected-module-viewer data-security-event-url="<?php echo e(route('trainee.modules.security-event', $module)); ?>">
    <a href="<?php echo e(route('trainee.modules.index')); ?>" class="lms-module-back">
        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-left','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-left','class' => 'h-4 w-4']); ?>
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
        Back to Classwork
    </a>

    <article class="lms-module-workspace">
        <header class="lms-module-workspace-header">
            <div class="lms-module-workspace-meta">
                <p class="lms-eyebrow">Lesson</p>
                <?php if($module->module_code): ?>
                    <span class="lms-status-chip is-purple font-mono"><?php echo e($module->module_code); ?></span>
                <?php endif; ?>
                <span class="lms-status-chip"><?php echo e($module->categoryLabel()); ?></span>
                <?php if($module->estimated_hours): ?>
                    <span class="lms-status-chip"><?php echo e($module->estimated_hours); ?> hours</span>
                <?php endif; ?>
                <span class="lms-status-chip <?php echo e($isCompetent ? 'is-green' : ($progress?->status === 'awaiting_evaluation' ? 'is-purple' : ($progress?->status === 'needs_remediation' ? 'is-amber' : ''))); ?>">
                    <?php echo e($progress->workflowStatusLabel()); ?>

                </span>
            </div>

            <div class="lms-module-workspace-title">
                <div>
                    <h1><?php echo e($module->title); ?></h1>
                    <?php if($module->topic): ?>
                        <p class="lms-module-topic"><?php echo e($module->topic); ?></p>
                    <?php endif; ?>
                    <p class="lms-module-byline">
                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $module->trainer,'name' => $module->trainer?->name ?? 'MCARE Trainer','class' => 'lms-module-trainer-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module->trainer),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module->trainer?->name ?? 'MCARE Trainer'),'class' => 'lms-module-trainer-avatar']); ?>
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
                        Trainer: <strong><?php echo e($module->trainer?->name ?? 'MCARE Trainer'); ?></strong>
                        <?php if($module->due_at): ?>
                            <span>· Due <?php echo e($module->due_at->format('M d, Y g:i A')); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if(!$module->requiresEvaluation()): ?>
                    <p class="lms-module-guide">Learning material only</p>
                <?php else: ?>
                    <a href="#submodules" class="lms-module-guide is-link">
                        <?php echo e($progress->isTrainerValidated() ? 'Outcomes completed' : ($progress->needsRemediation() ? 'Remediate outcomes below' : 'Outcomes below')); ?>

                    </a>
                <?php endif; ?>
            </div>
        </header>

        <?php if($errors->any() || session('error') || session('alert')): ?>
            <div class="lms-module-banner is-danger" role="alert">
                <strong>Module Submission Notice.</strong>
                <?php echo e($errors->first('action') ?: ($errors->first() ?: (session('error') ?: session('alert')))); ?>

                <?php if(isset($unpassedQuizzes) && $unpassedQuizzes->isNotEmpty()): ?>
                    <a href="#assessments">Jump to required <?php echo e(\Illuminate\Support\Str::plural('quiz', $unpassedQuizzes->count())); ?> below</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if($progress->isTrainerValidated()): ?>
            <div class="lms-module-banner is-success">
                <strong>Completed.</strong>
                Validated by <?php echo e($progress->evaluator?->name ?? 'MCARE Trainer'); ?>

                on <?php echo e($progress->evaluated_at?->format('M d, Y') ?? $progress->completed_at?->format('M d, Y') ?? 'recorded date'); ?>.
            </div>
        <?php elseif($progress->status === \App\Models\ModuleProgress::STATUS_AWAITING_EVALUATION): ?>
            <div class="lms-module-banner is-review">
                <strong>Submitted.</strong>
                Your trainer is reviewing the outcomes.
            </div>
        <?php elseif($progress->needsRemediation()): ?>
            <div class="lms-module-banner is-danger" role="status">
                <strong>Not yet competent.</strong>
                Remediate the marked outcomes to unlock the next module.
            </div>
        <?php else: ?>
            <div class="lms-module-banner is-notice">
                <strong>Protected content.</strong>
                Watermarked for <?php echo e($traineeName); ?>.
            </div>
        <?php endif; ?>

        
        <?php
            $canMarkAsDone = $module->requiresEvaluation()
                && ! $progress->isTrainerValidated()
                && $progress->status !== \App\Models\ModuleProgress::STATUS_AWAITING_EVALUATION
                && ! $module->submodules()->where('is_required', true)->exists();
        ?>
        <?php if($canMarkAsDone): ?>
            <form method="POST" action="<?php echo e(route('trainee.modules.progress', $module)); ?>" class="lms-module-mark-done" data-confirm="Mark this module as done and send it to your trainer for evaluation? You will not be able to edit it after this.">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <input type="hidden" name="action" value="submit">
                <button type="submit" class="primary-action">Mark this module as done</button>
                <p class="mt-1 text-xs text-slate-500">Your trainer sees this submission on their Trainees page and Competency Records.</p>
            </form>
        <?php endif; ?>

        <?php $lessonDocumentOpen = ! $progress->isTrainerValidated(); ?>
        <div data-lesson-document data-lesson-document-open="<?php echo e($lessonDocumentOpen ? 'true' : 'false'); ?>">
            <div class="lms-lesson-document-toolbar">
                <div>
                    <p class="lms-eyebrow">Protected content</p>
                    <p class="lms-lesson-document-copy">Watermarked for <?php echo e($traineeName); ?></p>
                </div>
                <button type="button" class="lms-lesson-document-toggle" data-lesson-document-toggle aria-controls="lesson-document-panel" aria-expanded="<?php echo e($lessonDocumentOpen ? 'true' : 'false'); ?>">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'book-open','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'book-open','class' => 'h-4 w-4']); ?>
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
                    <span data-lesson-document-toggle-label><?php echo e($lessonDocumentOpen ? 'Hide lesson document' : 'Show lesson document'); ?></span>
                </button>
            </div>

            <div id="lesson-document-panel" data-lesson-document-panel <?php if (! ($lessonDocumentOpen)): ?> hidden <?php endif; ?>>
                <section class="lms-module-viewer protected-module-content" data-protected-module-viewer>
                    <?php if(in_array($previewKind, ['video', 'audio'], true)): ?>
                        <div class="lms-module-watermark" aria-hidden="true">
                            <?php if (isset($component)) { $__componentOriginal287a2bc1a235dfa6685a724cfb5d4204 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal287a2bc1a235dfa6685a724cfb5d4204 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.learning-watermark','data' => ['src' => $watermarkImageUrl]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('learning-watermark'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($watermarkImageUrl)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal287a2bc1a235dfa6685a724cfb5d4204)): ?>
<?php $attributes = $__attributesOriginal287a2bc1a235dfa6685a724cfb5d4204; ?>
<?php unset($__attributesOriginal287a2bc1a235dfa6685a724cfb5d4204); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal287a2bc1a235dfa6685a724cfb5d4204)): ?>
<?php $component = $__componentOriginal287a2bc1a235dfa6685a724cfb5d4204; ?>
<?php unset($__componentOriginal287a2bc1a235dfa6685a724cfb5d4204); ?>
<?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($previewKind === 'video'): ?>
                        <video class="lms-module-media" controls controlsList="nodownload noremoteplayback" disablePictureInPicture preload="metadata">
                            <source src="<?php echo e($viewerUrl); ?>" type="<?php echo e($module->mime_type); ?>">
                            Your browser cannot play this video.
                        </video>
                    <?php elseif($previewKind === 'audio'): ?>
                        <div class="lms-module-fallback">
                            <p><?php echo e($module->original_file_name); ?></p>
                            <audio controls preload="metadata"><source src="<?php echo e($viewerUrl); ?>" type="<?php echo e($module->mime_type); ?>"></audio>
                        </div>
                    <?php elseif($previewKind === 'image'): ?>
                        <div class="lms-module-image">
                            <img src="<?php echo e($viewerUrl); ?>" alt="<?php echo e($module->title); ?>" draggable="false">
                        </div>
                    <?php elseif($previewKind === 'pdf'): ?>
                        <div class="lms-module-pdf" data-pdf-canvas-viewer data-pdf-url="<?php echo e($viewerUrl); ?>">
                            <div class="lms-module-pdf-toolbar" aria-label="Document controls">
                                <div>
                                    <button type="button" data-pdf-prev disabled title="Previous Page">&larr; Prev</button>
                                    <span>Page <span data-pdf-current-page>1</span> of <span data-pdf-total-pages>-</span></span>
                                    <button type="button" data-pdf-next disabled title="Next Page">Next &rarr;</button>
                                </div>
                                <div>
                                    <button type="button" data-pdf-zoom-out title="Zoom Out">&minus;</button>
                                    <span data-pdf-zoom-level>125%</span>
                                    <button type="button" data-pdf-zoom-in title="Zoom In">+</button>
                                    <button type="button" data-pdf-fit-width title="Fit Width">Fit Width</button>
                                </div>
                            </div>
                            <div class="lms-module-pdf-stage" data-pdf-canvas-container>
                                <div data-pdf-scroll-sizer>
                                    <div data-pdf-page-wrapper>
                                        <canvas class="block bg-white" data-pdf-canvas></canvas>
                                    </div>
                                </div>
                                <div class="lms-module-pdf-loading" data-pdf-loading>
                                    Rendering lesson document...
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="lms-module-fallback">
                            <p><?php echo e($module->fileTypeLabel()); ?></p>
                            <p>This document format is available for download and review.</p>
                            <a href="<?php echo e($downloadUrl); ?>" class="primary-action">Download Lesson Document</a>
                        </div>
                    <?php endif; ?>
                </section>

                <?php if(count($supplementaryList) > 0): ?>
                    <section class="lms-module-section">
                        <h2>Supplementary handouts</h2>
                        <ul class="lms-module-file-list">
                            <?php $__currentLoopData = $supplementaryList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $supp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li>
                                    <div>
                                        <strong><?php echo e($supp['original_name']); ?></strong>
                                        <span><?php echo e($supp['human_size'] ?? ''); ?></span>
                                    </div>
                                    <a href="<?php echo e(route('trainee.modules.supplementary.download', [$module, $idx])); ?>">Download</a>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </div>
        </div>

        <?php if($module->requiresEvaluation()): ?>
            <section id="submodules" class="lms-module-section">
                <div class="lms-module-section-heading">
                    <div>
                        <p class="lms-eyebrow">Outcomes</p>
                        <h2>Submodules</h2>
                        <p>Trainer records Competent or Not yet competent.</p>
                    </div>
                    <span class="lms-status-chip is-purple">
                        <?php echo e($submoduleProgressById->filter(fn ($item) => $item->isTrainerValidated())->count()); ?> / <?php echo e($submodules->where('is_required', true)->count()); ?> competent
                    </span>
                </div>

                <div class="lms-module-outcome-list">
                    <?php $__empty_1 = true; $__currentLoopData = $submodules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submodule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $childProgress = $submoduleProgressById->get($submodule->id);
                            $childSummary = $submoduleAssessmentSummaries->get($submodule->id, []);
                            $childQuizzes = $childSummary['quizzes'] ?? collect();
                            $childHasClasswork = ($childSummary['required_count'] ?? 0) > 0;
                            $childCompleted = $childProgress?->isTrainerValidated() ?? false;
                            $childAwaiting = $childProgress?->status === \App\Models\TrainingSubmoduleProgress::STATUS_AWAITING_EVALUATION;
                            $childNyc = $childProgress?->needsRemediation() ?? false;
                            $childAccess = $submoduleAccess[$submodule->id] ?? ['can_work' => true, 'blocker' => null];
                            $childLocked = ! ($childAccess['can_work'] ?? true);
                            $childBlocker = $childAccess['blocker'] ?? null;
                        ?>
                        <article class="lms-module-outcome <?php echo e($childCompleted ? 'is-complete' : ($childNyc ? 'is-remediation' : ($childLocked ? 'is-locked' : ''))); ?>">
                            <div class="lms-module-outcome-top">
                                <div>
                                    <p class="lms-eyebrow">Submodule <?php echo e($submodule->position); ?></p>
                                    <h3><?php echo e($submodule->title); ?></h3>
                                </div>
                                <span class="lms-status-chip <?php echo e($childCompleted ? 'is-green' : ($childAwaiting ? 'is-purple' : ($childNyc ? 'is-amber' : ($childLocked ? 'is-red' : '')))); ?>">
                                    <?php echo e($childLocked ? 'Locked' : ($childProgress?->workflowStatusLabel() ?? 'Ready to start')); ?>

                                </span>
                            </div>

                            <?php if($childLocked): ?>
                                <p class="lms-module-outcome-copy">
                                    Locked until <?php echo e($childBlocker?->title ?? 'the previous outcome'); ?> is Competent.
                                </p>
                            <?php elseif($childNyc): ?>
                                <p class="lms-module-outcome-copy">
                                    Not yet competent. Needs class reassessment.
                                </p>
                            <?php elseif(! $childCompleted): ?>
                                <p class="lms-module-outcome-copy">
                                    Awaiting trainer evaluation.
                                    <?php if($childHasClasswork): ?>
                                        Optional classwork: <?php echo e($childSummary['passed_count'] ?? 0); ?>/<?php echo e($childSummary['required_count'] ?? 0); ?> passed
                                        <?php if(($childSummary['average_score'] ?? null) !== null): ?>
                                            · <?php echo e(number_format((float) $childSummary['average_score'], 1)); ?>%
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <?php if($childQuizzes->isNotEmpty()): ?>
                                <div class="lms-module-inline-actions">
                                    <?php $__currentLoopData = $childQuizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $childQuiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <a href="<?php echo e(route('trainee.quizzes.show', $childQuiz)); ?>" class="secondary-action px-3 py-1.5 text-[11px]"><?php echo e($childQuiz->title); ?></a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>

                            <?php if($childProgress?->evaluation_remarks): ?>
                                <p class="lms-module-outcome-copy"><strong>Trainer feedback:</strong> <?php echo e($childProgress->evaluation_remarks); ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="lms-module-banner is-notice">No competency outcomes are attached to this module yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <section id="assessments" class="lms-module-section">
            <h2>Assessments</h2>

            <div class="lms-module-stat-row">
                <div>
                    <span>Quiz average</span>
                    <strong><?php echo e($assessmentAverage !== null ? number_format((float) $assessmentAverage, 1).'%' : 'No score yet'); ?></strong>
                </div>
                <div>
                    <span>Practical demo</span>
                    <strong><?php echo e($progress?->practicalRatingLabel() ?? 'Pending demo'); ?></strong>
                </div>
                <div>
                    <span>Competency outcome</span>
                    <strong><?php echo e($isCompetent ? 'Competent (Passed)' : ($progress?->competency_outcome === 'not_yet_competent' ? 'For Remediation' : 'In Progress')); ?></strong>
                </div>
            </div>

            <?php if($progress?->evaluation_remarks): ?>
                <p class="lms-module-outcome-copy"><strong>Trainer Feedback:</strong> "<?php echo e($progress->evaluation_remarks); ?>"
                    <?php if($progress->evaluator): ?> · <?php echo e($progress->evaluator->name); ?> <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if($quizzes->isNotEmpty()): ?>
                <h3 class="lms-module-subheading">Online assessments (<?php echo e($quizzes->count()); ?>)</h3>
                <ul class="lms-module-quiz-list">
                    <?php $__currentLoopData = $quizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $attempts = $quizAttempts->get($quiz->id) ?? collect();
                            $bestAttempt = $attempts->sortByDesc('score_percent')->first();
                            $hasPassed = $bestAttempt && $bestAttempt->score_percent >= $quiz->passing_score_percent;
                        ?>
                        <li>
                            <div>
                                <strong><?php echo e($quiz->title); ?></strong>
                                <span><?php echo e($quiz->questions->count()); ?> questions · <?php echo e(number_format($quiz->passing_score_percent, 0)); ?>% to pass<?php echo e($quiz->time_limit_minutes ? ' · '.$quiz->time_limit_minutes.' min' : ''); ?></span>
                                <?php if($bestAttempt): ?>
                                    <span class="<?php echo e($hasPassed ? 'is-pass' : 'is-retry'); ?>">Best Score: <?php echo e(number_format($bestAttempt->score_percent, 1)); ?>% (<?php echo e($hasPassed ? 'Passed' : 'Needs Retake'); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <div class="lms-module-inline-actions">
                                <?php if($bestAttempt): ?>
                                    <a href="<?php echo e(route('trainee.quiz-attempts.result', $bestAttempt)); ?>" class="secondary-action text-xs py-1.5 px-3">View Score</a>
                                <?php endif; ?>
                                <?php if(!$progress->isTrainerValidated() && $progress->status !== \App\Models\ModuleProgress::STATUS_AWAITING_EVALUATION): ?>
                                    <a href="<?php echo e(route('trainee.quizzes.show', $quiz)); ?>" class="primary-action text-xs py-1.5 px-3.5">
                                        <?php echo e($bestAttempt ? 'Retake Quiz' : 'Take Quiz'); ?>

                                    </a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if (isset($component)) { $__componentOriginalad64fd8082a658cbdd3ae55129ff9480 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad64fd8082a658cbdd3ae55129ff9480 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.classroom-comments','data' => ['commentable' => $module,'comments' => $classroomComments,'privateRecipients' => $privateCommentRecipients]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('classroom-comments'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['commentable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module),'comments' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($classroomComments),'private-recipients' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($privateCommentRecipients)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalad64fd8082a658cbdd3ae55129ff9480)): ?>
<?php $attributes = $__attributesOriginalad64fd8082a658cbdd3ae55129ff9480; ?>
<?php unset($__attributesOriginalad64fd8082a658cbdd3ae55129ff9480); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalad64fd8082a658cbdd3ae55129ff9480)): ?>
<?php $component = $__componentOriginalad64fd8082a658cbdd3ae55129ff9480; ?>
<?php unset($__componentOriginalad64fd8082a658cbdd3ae55129ff9480); ?>
<?php endif; ?>
    </article>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => $module->title.' | MCARE Learning'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\modules\show.blade.php ENDPATH**/ ?>