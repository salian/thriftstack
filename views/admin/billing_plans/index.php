<section class="page-section">
    <h1>App Super Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>Billing Plans</h2>
    <p>Manage plan tiers, pricing, and trial defaults.</p>

    <div class="card">
        <div class="table-header">
            <div>
                <h2>Manage plans</h2>
                <p class="muted">Edit pricing and availability for subscription plans.</p>
            </div>
            <button type="button" class="button" data-billing-open="create">Create plan</button>
        </div>
        <?php if (!empty($plans)) : ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Price (cents)</th>
                        <th>Interval</th>
                        <th>Active</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan) : ?>
                        <tr>
                            <td><?= e($plan['code'] ?? '') ?></td>
                            <td><?= e($plan['name'] ?? '') ?></td>
                            <td><?= e((string)($plan['price_cents'] ?? 0)) ?></td>
                            <td><?= e($plan['interval'] ?? '') ?></td>
                            <td><?= (int)($plan['is_active'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                            <td>
                                <button
                                    type="button"
                                    class="button button-ghost"
                                    data-billing-open="edit"
                                    data-plan-id="<?= e((string)($plan['id'] ?? '')) ?>"
                                    data-plan-code="<?= e($plan['code'] ?? '') ?>"
                                    data-plan-name="<?= e($plan['name'] ?? '') ?>"
                                    data-plan-price="<?= e((string)($plan['price_cents'] ?? 0)) ?>"
                                    data-plan-interval="<?= e($plan['interval'] ?? '') ?>"
                                    data-plan-active="<?= (int)($plan['is_active'] ?? 0) === 1 ? '1' : '0' ?>"
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
                    <span>Name</span>
                    <input type="text" name="name" required data-billing-name>
                </label>
                <label>
                    <span>Price (cents)</span>
                    <input type="number" name="price_cents" min="0" step="1" value="0" data-billing-price>
                </label>
                <label>
                    <span>Interval</span>
                    <input type="text" name="interval" value="monthly" required data-billing-interval>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="is_active" value="1" data-billing-active>
                    <span>Active</span>
                </label>
                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="button" data-billing-submit>Create plan</button>
                </div>
            </form>
        </div>
    </dialog>
</section>
