<section class="page-section">
    <h1>Credit Limits</h1>
    <p>Set daily and monthly limits for AI credit usage.</p>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Limits</h2>
        <form method="post" action="/settings/credit-limits" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Daily limit (credits)</span>
                <input type="number" name="daily_limit" min="0" step="1" value="<?= e((string)($limits['daily_limit'] ?? 0)) ?>">
            </label>
            <label>
                <span>Monthly limit (credits)</span>
                <input type="number" name="monthly_limit" min="0" step="1" value="<?= e((string)($limits['monthly_limit'] ?? 0)) ?>">
            </label>
            <label>
                <span>Alert threshold (%)</span>
                <input type="range" name="alert_threshold_percent" min="1" max="100"
                    value="<?= e((string)($limits['alert_threshold_percent'] ?? 80)) ?>"
                    oninput="this.nextElementSibling.textContent=this.value + '%'">
                <span class="muted"><?= e((string)($limits['alert_threshold_percent'] ?? 80)) ?>%</span>
            </label>
            <button type="submit" class="button">Save limits</button>
        </form>
    </div>
</section>
