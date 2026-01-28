<section class="page-section">
    <h1>System Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>Billing Plans</h2>
    <p>Manage plan tiers, pricing, and trial defaults.</p>

    <div class="card">
        <div class="table-header">
            <div>
                <h2>Manage plans</h2>
                <p class="muted">Edit pricing and availability for subscription and top-up plans.</p>
            </div>
            <button type="button" class="button" data-billing-open="create">Create plan</button>
        </div>
        <?php if (!empty($plans)) : ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Group</th>
                        <th>Name</th>
                        <th>Price (cents)</th>
                        <th>Duration</th>
                        <th>Type</th>
                        <th>AI credits</th>
                        <th>Trial</th>
                        <th>Trial days</th>
                        <th>Active</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan) : ?>
                        <tr class="<?= (int)($plan['is_active'] ?? 0) === 1 ? '' : 'is-inactive' ?>">
                            <td><?= e($plan['code'] ?? '') ?></td>
                            <td><?= e($plan['plan_group'] ?? '') ?></td>
                            <td><?= e($plan['name'] ?? '') ?></td>
                            <td><?= e((string)($plan['price_cents'] ?? 0)) ?></td>
                            <td><?= e($plan['duration'] ?? '') ?></td>
                            <td><?= e($plan['plan_type'] ?? 'subscription') ?></td>
                            <td><?= e((string)($plan['ai_credits'] ?? 0)) ?></td>
                            <td><?= (int)($plan['trial_enabled'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                            <td><?= e((string)($plan['trial_days'] ?? 0)) ?></td>
                            <td><?= (int)($plan['is_active'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                            <td>
                                <button
                                    type="button"
                                    class="button button-ghost"
                                    data-billing-open="edit"
                                    data-plan-id="<?= e((string)($plan['id'] ?? '')) ?>"
                                    data-plan-code="<?= e($plan['code'] ?? '') ?>"
                                    data-plan-group="<?= e($plan['plan_group'] ?? '') ?>"
                                    data-plan-name="<?= e($plan['name'] ?? '') ?>"
                                    data-plan-price="<?= e((string)($plan['price_cents'] ?? 0)) ?>"
                                    data-plan-duration="<?= e($plan['duration'] ?? '') ?>"
                                    data-plan-type="<?= e($plan['plan_type'] ?? 'subscription') ?>"
                                    data-plan-credits="<?= e((string)($plan['ai_credits'] ?? 0)) ?>"
                                    data-plan-trial-enabled="<?= (int)($plan['trial_enabled'] ?? 0) === 1 ? '1' : '0' ?>"
                                    data-plan-trial-days="<?= e((string)($plan['trial_days'] ?? 0)) ?>"
                                    data-plan-stripe="<?= e($plan['stripe_price_id'] ?? '') ?>"
                                    data-plan-razorpay="<?= e($plan['razorpay_plan_id'] ?? '') ?>"
                                    data-plan-paypal="<?= e($plan['paypal_plan_id'] ?? '') ?>"
                                    data-plan-lemonsqueezy="<?= e($plan['lemonsqueezy_variant_id'] ?? '') ?>"
                                    data-plan-dodo="<?= e($plan['dodo_price_id'] ?? '') ?>"
                                    data-plan-paddle="<?= e($plan['paddle_price_id'] ?? '') ?>"
                                    data-plan-active="<?= (int)($plan['is_active'] ?? 0) === 1 ? '1' : '0' ?>"
                                    data-plan-grandfathered="<?= (int)($plan['is_grandfathered'] ?? 0) === 1 ? '1' : '0' ?>"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No plans configured yet.</p>
        <?php endif; ?>
    </div>

    <dialog class="modal" id="billing-plan-modal">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 data-billing-title>Create plan</h2>
                    <p class="modal-subtitle" data-billing-subtitle>Define pricing and availability for a plan.</p>
                </div>
                <button type="button" class="icon-button" data-modal-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="/billing/plans" class="form" data-billing-form>
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <input type="hidden" name="plan_id" value="" data-billing-id>
                <label data-billing-code>
                    <span>Code</span>
                    <input type="text" name="code" required data-billing-code-input>
                </label>
                <label>
                    <span>Plan group</span>
                    <input type="text" name="plan_group" data-billing-group placeholder="e.g. pro">
                </label>
                <label>
                    <span>Name</span>
                    <input type="text" name="name" required data-billing-name>
                </label>
                <label>
                    <span>Price (cents)</span>
                    <input type="number" name="price_cents" min="0" step="1" value="0" data-billing-price>
                </label>
                <label>
                    <span>Duration</span>
                    <input type="text" name="duration" value="monthly" required data-billing-duration>
                </label>
                <label>
                    <span>Plan type</span>
                    <select name="plan_type" data-billing-type>
                        <option value="subscription">Subscription</option>
                        <option value="topup">AI credit top-up</option>
                    </select>
                </label>
                <label>
                    <span>AI credits</span>
                    <input type="number" name="ai_credits" min="0" step="1" value="0" data-billing-credits>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="trial_enabled" value="1" data-billing-trial-enabled>
                    <span>Enable free trial</span>
                </label>
                <label>
                    <span>Trial duration (days)</span>
                    <input type="number" name="trial_days" min="0" step="1" value="0" data-billing-trial-days>
                </label>
                <label>
                    <span>Stripe price ID</span>
                    <input type="text" name="stripe_price_id" value="" data-billing-stripe>
                </label>
                <label>
                    <span>Razorpay plan ID</span>
                    <input type="text" name="razorpay_plan_id" value="" data-billing-razorpay>
                </label>
                <label>
                    <span>PayPal plan ID</span>
                    <input type="text" name="paypal_plan_id" value="" data-billing-paypal>
                </label>
                <label>
                    <span>Lemon Squeezy variant ID</span>
                    <input type="text" name="lemonsqueezy_variant_id" value="" data-billing-lemonsqueezy>
                </label>
                <label>
                    <span>Dodo price ID</span>
                    <input type="text" name="dodo_price_id" value="" data-billing-dodo>
                </label>
                <label>
                    <span>Paddle price ID</span>
                    <input type="text" name="paddle_price_id" value="" data-billing-paddle>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="is_active" value="1" data-billing-active>
                    <span>Active</span>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="is_grandfathered" value="1" data-billing-grandfathered>
                    <span>Grandfather existing subscribers when disabled</span>
                </label>
                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="button" data-billing-submit>Create plan</button>
                </div>
            </form>
        </div>
    </dialog>
</section>
