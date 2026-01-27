<section class="page-section">
    <h1>Billing</h1>
    <p>Manage subscriptions, trials, and billing history for your workspace.</p>

    <?php if (($_GET['status'] ?? '') === 'success') : ?>
        <div class="banner banner-success" data-clear-status>
            <span>Checkout completed. Your subscription will update shortly.</span>
        </div>
    <?php elseif (($_GET['status'] ?? '') === 'cancelled') : ?>
        <div class="banner banner-warning" data-clear-status>
            <span>Checkout was cancelled. No changes were applied.</span>
        </div>
    <?php endif; ?>

    <?php if (empty($workspace)) : ?>
        <div class="card">
            <p>Create a workspace to manage billing.</p>
        </div>
    <?php else : ?>
        <div class="card">
            <h2>Current subscription</h2>
            <?php if (!empty($subscription)) : ?>
                <p><strong><?= e($subscription['plan_name'] ?? '') ?></strong> — <?= e(ucfirst((string)($subscription['status'] ?? ''))) ?></p>
                <?php if (!empty($subscription['trial_ends_at'])) : ?>
                    <p>Trial ends: <?= e($subscription['trial_ends_at']) ?></p>
                <?php endif; ?>
                <?php if (!empty($subscription['current_period_end'])) : ?>
                    <p>Current period ends: <?= e($subscription['current_period_end']) ?></p>
                <?php endif; ?>
                <?php if (($subscription['status'] ?? '') === 'pending') : ?>
                    <p class="muted">Checkout initiated. Complete payment to activate the plan.</p>
                <?php endif; ?>
            <?php else : ?>
                <p>No subscription yet. Start a trial or choose a plan.</p>
                <?php
                $trialPlan = null;
                foreach (($plans ?? []) as $plan) {
                    if (($plan['code'] ?? '') === 'trial') {
                        $trialPlan = $plan;
                        break;
                    }
                }
                ?>
                <?php if ($trialPlan) : ?>
                    <form method="post" action="/billing/trial">
                        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                        <button type="submit" class="button">Start <?= e((string)$trialDays) ?>-day trial</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($pendingChanges)) : ?>
            <div class="card">
                <h2>Pending changes</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Change</th>
                            <th>New plan</th>
                            <th>Effective</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingChanges as $change) : ?>
                            <?php
                            $plan = $planIndex[(int)($change['to_plan_id'] ?? 0)] ?? null;
                            $planName = $plan['name'] ?? 'Unknown';
                            ?>
                            <tr>
                                <td><?= e(ucfirst((string)($change['change_type'] ?? ''))) ?></td>
                                <td><?= e($planName) ?></td>
                                <td><?= e((string)($change['effective_at'] ?? '')) ?></td>
                                <td><?= e(ucfirst((string)($change['status'] ?? 'pending'))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="muted">Downgrades and cancellations are applied at the end of the billing period.</p>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Plans</h2>
            <?php if (empty($plans)) : ?>
                <p>No plans configured yet.</p>
            <?php else : ?>
                <div class="plan-grid">
                    <?php foreach ($plans as $plan) : ?>
                        <?php $isCurrent = !empty($subscription) && (int)$subscription['plan_id'] === (int)$plan['id']; ?>
                        <div class="plan-card">
                            <h3><?= e($plan['name']) ?></h3>
                            <p class="plan-price">
                                <?= e($plan['currency'] ?? 'USD') ?>
                                <?= number_format(((int)$plan['price_cents']) / 100, 2) ?>
                            </p>
                            <p class="plan-interval"><?= e($plan['duration']) ?></p>
                            <?php if ($isCurrent) : ?>
                                <span class="badge badge-primary">Current plan</span>
                                <?php if ((int)($plan['is_active'] ?? 1) !== 1 && (int)($plan['is_grandfathered'] ?? 0) === 1) : ?>
                                    <span class="badge badge-muted">Grandfathered</span>
                                <?php endif; ?>
                            <?php else : ?>
                                <form method="post" action="/billing/subscribe">
                                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                    <input type="hidden" name="plan_id" value="<?= e((string)$plan['id']) ?>">
                                    <button type="submit" class="button button-ghost">Continue with this plan</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="muted">Paid plans redirect to hosted checkout based on your active payment gateways.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Invoices</h2>
            <?php if (empty($invoices)) : ?>
                <p>No invoices yet.</p>
            <?php else : ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Provider</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice) : ?>
                            <tr>
                                <td><?= e($invoice['created_at'] ?? '') ?></td>
                                <td><?= e($invoice['provider'] ?? '') ?></td>
                                <td>$<?= number_format(((int)($invoice['amount_cents'] ?? 0)) / 100, 2) ?></td>
                                <td><?= e($invoice['status'] ?? '') ?></td>
                                <td><?= e($invoice['external_id'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</section>
