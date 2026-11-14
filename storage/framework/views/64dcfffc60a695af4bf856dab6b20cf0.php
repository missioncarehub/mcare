<?php
    $amount = 'PHP '.number_format((float) ($application->downpayment_amount ?: $application->payment_amount ?: 0), 2);
    $batch = $application->batch;
    $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed by admin';
    $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room to be announced';
    $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
    $paymentMethodLabels = [
        'gcash' => 'GCash',
        'card' => 'Card',
        'qrph' => 'QR Ph',
        'grab_pay' => 'GrabPay',
        'paymaya' => 'Maya',
        'maya' => 'Maya',
    ];
    $paymentCleared = $application->hasEnrollmentPaymentClearance();
    $onsiteLocked = $application->payment_status === $application::PAYMENT_ONSITE_PENDING;
    $canChooseOnline = $paymongoReady && ! $paymongoModeConflict && ! $paymentCleared && ! $onsiteLocked;
    $canContinue = ! $paymentCleared && ! $onsiteLocked;
?>

<?php if($paymentCleared): ?>
    <p class="enrollment-notice enrollment-notice-ok">Payment is already recorded. No payment method needs to be chosen.</p>
<?php else: ?>
    <form
        method="POST"
        action="<?php echo e(route('payment.select')); ?>"
        data-single-action
        data-payment-choice-form
        data-payment-amount="<?php echo e($amount); ?>"
        class="space-y-6"
    >
        <?php echo csrf_field(); ?>

        <p class="enrollment-lede" style="margin-top: 0;">Choose one payment method, then continue. A confirmation appears before MCARE starts PayMongo or creates a cashier receipt.</p>

        <div class="enrollment-payment-grid">
            <label class="enrollment-payment-card enrollment-payment-choice is-paymongo<?php echo e($canChooseOnline ? '' : ' is-disabled'); ?>">
                <input
                    type="radio"
                    name="payment_method"
                    value="online"
                    required
                    <?php if(! $canChooseOnline): echo 'disabled'; endif; ?>
                >
                <div class="enrollment-section-heading">
                    <p>Pay online</p>
                    <h2>PayMongo checkout</h2>
                    <p>Pay <?php echo e($amount); ?> with <?php echo e(collect($paymongoMethods)->map(fn ($method) => $paymentMethodLabels[$method] ?? str($method)->replace('_', ' ')->title())->join(', ') ?: 'the configured methods'); ?> after you confirm.</p>
                </div>

                <ul class="enrollment-payment-methods">
                    <?php $__currentLoopData = $paymongoMethods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($paymentMethodLabels[$method] ?? str($method)->replace('_', ' ')->title()); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>

                <dl class="enrollment-payment-facts">
                    <div>
                        <dt>Checkout</dt>
                        <dd>Starts only after you confirm this method</dd>
                    </div>
                    <div>
                        <dt>Deadline</dt>
                        <dd><?php echo e($deadline?->format('M d, Y g:i A') ?? 'Set by the batch schedule'); ?></dd>
                    </div>
                </dl>

                <?php if(! $paymongoConfigured): ?>
                    <p class="enrollment-notice enrollment-notice-amber">Online checkout is not configured yet. Choose Pay on site.</p>
                <?php elseif($paymongoModeConflict): ?>
                    <p class="enrollment-notice enrollment-notice-amber">A checkout from a different PayMongo mode is still active. Contact MCARE or choose Pay on site.</p>
                <?php elseif(! $paymongoLiveMode): ?>
                    <p class="enrollment-notice enrollment-notice-info">Test mode is active. Use PayMongo test payment details only.</p>
                <?php endif; ?>
            </label>

            <label class="enrollment-payment-card enrollment-payment-choice<?php echo e($onsiteLocked ? ' is-disabled' : ''); ?>">
                <input
                    type="radio"
                    name="payment_method"
                    value="onsite"
                    required
                    <?php if($onsiteLocked): echo 'checked'; endif; ?>
                    <?php if($onsiteLocked): echo 'disabled'; endif; ?>
                >
                <div class="enrollment-section-heading">
                    <p>Pay on site</p>
                    <h2>Cashier receipt</h2>
                    <p>Generate a unique reference and bring it to MCARE. The cashier verifies the payment and issues the official receipt.</p>
                </div>

                <dl class="enrollment-payment-facts">
                    <div>
                        <dt>Reference number</dt>
                        <dd><?php echo e($application->payment_reference ?: 'Created when you confirm Pay on site'); ?></dd>
                    </div>
                    <div>
                        <dt>Class</dt>
                        <dd><?php echo e($application->schedule_preference); ?> · <?php echo e($scheduleLabel); ?> · <?php echo e($roomLabel); ?></dd>
                    </div>
                </dl>
            </label>
        </div>

        <?php if($onsiteLocked): ?>
            <p class="enrollment-notice enrollment-notice-ok">Pay on site is already selected. Use the receipt when you pay at MCARE. The continue button stays closed so this method is not started again.</p>
        <?php endif; ?>

        <div class="enrollment-payment-continue">
            <button
                type="submit"
                data-action-button
                data-payment-continue
                <?php if(! $canContinue): echo 'disabled'; endif; ?>
                class="primary-action cursor-pointer"
            >
                Continue with selected method
            </button>
            <?php if($application->payment_reference || $application->payment_receipt_number): ?>
                <a href="<?php echo e(route('payment.receipt')); ?>" class="secondary-action">View receipt</a>
            <?php endif; ?>
        </div>
    </form>

    <dialog class="enrollment-payment-dialog" data-payment-confirm aria-labelledby="payment-confirm-title">
        <div class="enrollment-payment-dialog-card">
            <p data-payment-confirm-kicker>Confirm payment method</p>
            <h2 id="payment-confirm-title" data-payment-confirm-title>Confirm your payment method</h2>
            <p data-payment-confirm-body>Please confirm the method you selected before MCARE continues.</p>
            <div class="enrollment-payment-dialog-actions">
                <button type="button" class="secondary-action cursor-pointer" data-payment-confirm-cancel>Cancel</button>
                <button type="button" class="primary-action cursor-pointer" data-payment-confirm-accept>Confirm</button>
            </div>
        </div>
    </dialog>

    <script>
        (() => {
            const confirmDialog = document.querySelector('[data-payment-confirm]');
            const confirmKicker = confirmDialog?.querySelector('[data-payment-confirm-kicker]');
            const confirmTitle = confirmDialog?.querySelector('[data-payment-confirm-title]');
            const confirmBody = confirmDialog?.querySelector('[data-payment-confirm-body]');
            const confirmAccept = confirmDialog?.querySelector('[data-payment-confirm-accept]');
            let pendingPaymentForm = null;

            function paymentConfirmCopy(method, amount) {
                if (method === 'online') {
                    return {
                        kicker: 'PayMongo checkout',
                        title: 'Confirm PayMongo payment',
                        body: `You selected Pay online. After you confirm, MCARE opens PayMongo so you can pay ${amount}. No payment is recorded until PayMongo marks the checkout paid.`,
                    };
                }

                return {
                    kicker: 'Pay on site',
                    title: 'Confirm pay on site',
                    body: `You selected Pay on site. After you confirm, MCARE creates a cashier receipt for ${amount}. Bring that receipt to MCARE. The continue button will then stay closed.`,
                };
            }

            function closePaymentConfirm() {
                if (confirmDialog?.open) {
                    confirmDialog.close();
                }
                pendingPaymentForm = null;
                confirmDialog?.classList.remove('is-paymongo');
            }

            document.querySelectorAll('[data-payment-choice-form]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (form.dataset.confirmed === 'true') {
                        if (form.dataset.submitted === 'true') {
                            event.preventDefault();
                            window.showActionToast?.('Too many actions. Please wait for the current request to finish.');
                            return;
                        }

                        form.dataset.submitted = 'true';
                        form.querySelectorAll('[data-action-button]').forEach((actionButton) => {
                            actionButton.disabled = true;
                            actionButton.classList.add('cursor-not-allowed', 'opacity-70');
                            actionButton.textContent = 'Continuing…';
                        });
                        return;
                    }

                    event.preventDefault();

                    if (form.querySelector('[data-payment-continue]')?.disabled) {
                        window.showActionToast?.('Pay on site is already selected. Open the receipt instead.');
                        return;
                    }

                    const selected = form.querySelector('input[name="payment_method"]:checked:not(:disabled)');
                    if (!selected) {
                        window.showActionToast?.('Choose a payment method first.');
                        return;
                    }

                    const copy = paymentConfirmCopy(selected.value, form.dataset.paymentAmount || 'the required downpayment');
                    pendingPaymentForm = form;
                    if (confirmKicker) confirmKicker.textContent = copy.kicker;
                    if (confirmTitle) confirmTitle.textContent = copy.title;
                    if (confirmBody) confirmBody.textContent = copy.body;
                    confirmDialog?.classList.toggle('is-paymongo', selected.value === 'online');

                    if (confirmDialog && typeof confirmDialog.showModal === 'function') {
                        confirmDialog.showModal();
                        return;
                    }

                    if (window.confirm(`${copy.title}. ${copy.body}`)) {
                        form.dataset.confirmed = 'true';
                        form.requestSubmit();
                    }
                });
            });

            confirmDialog?.querySelector('[data-payment-confirm-cancel]')?.addEventListener('click', () => {
                closePaymentConfirm();
            });

            confirmDialog?.addEventListener('click', (event) => {
                if (event.target === confirmDialog) {
                    closePaymentConfirm();
                }
            });

            confirmDialog?.addEventListener('close', () => {
                pendingPaymentForm = null;
                confirmDialog.classList.remove('is-paymongo');
            });

            confirmAccept?.addEventListener('click', () => {
                const form = pendingPaymentForm;
                closePaymentConfirm();
                if (!form) return;
                form.dataset.confirmed = 'true';
                form.requestSubmit();
            });
        })();
    </script>
<?php endif; ?>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\enrollment\partials\payment-methods.blade.php ENDPATH**/ ?>