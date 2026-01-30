<section class="page-section">
    <h1>Report preferences</h1>
    <p class="muted">Configure weekly or monthly workspace digest emails.</p>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Digest schedule</h2>
        <form method="post" action="/settings/reports" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Frequency</span>
                <select name="digest_frequency">
                    <option value="off" <?= ($settings['digest_frequency'] ?? '') === 'off' ? 'selected' : '' ?>>Off</option>
                    <option value="weekly" <?= ($settings['digest_frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                    <option value="monthly" <?= ($settings['digest_frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                </select>
            </label>

            <div>
                <p class="form-label">Include in report</p>
                <label class="checkbox">
                    <input type="checkbox" name="include_metrics[]" value="credit_usage_summary"
                        <?= in_array('credit_usage_summary', (array)($settings['include_metrics'] ?? []), true) ? 'checked' : '' ?>>
                    <span>Credit usage summary</span>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="include_metrics[]" value="depletion_forecast"
                        <?= in_array('depletion_forecast', (array)($settings['include_metrics'] ?? []), true) ? 'checked' : '' ?>>
                    <span>Depletion forecast</span>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="include_metrics[]" value="top_categories"
                        <?= in_array('top_categories', (array)($settings['include_metrics'] ?? []), true) ? 'checked' : '' ?>>
                    <span>Top usage categories</span>
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="include_metrics[]" value="cost_breakdown"
                        <?= in_array('cost_breakdown', (array)($settings['include_metrics'] ?? []), true) ? 'checked' : '' ?>>
                    <span>Cost breakdown</span>
                </label>
            </div>

            <label>
                <span>Recipients</span>
                <textarea name="recipients" rows="3" placeholder="owner@example.com, finance@example.com"><?= e(implode(', ', (array)($settings['recipients'] ?? []))) ?></textarea>
                <span class="muted">Leave blank to email workspace owners.</span>
            </label>

            <button type="submit" class="button">Save report preferences</button>
        </form>
    </div>
</section>
