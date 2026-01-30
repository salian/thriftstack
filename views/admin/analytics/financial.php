<section class="page-section">
    <h1>System Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>Financial analytics</h2>
    <p class="muted">MRR, revenue, churn, and LTV insights.</p>

    <div class="card">
        <div class="table-header">
            <div>
                <h2>Overview</h2>
                <p class="muted">Select a month range to update the charts.</p>
            </div>
            <a class="button button-ghost" href="/super-admin/analytics/financial/export?start=<?= e($start) ?>&end=<?= e($end) ?>">Export CSV</a>
        </div>
        <form method="get" class="table-toolbar form-inline">
            <div class="date-presets" data-date-presets>
                <button type="button" class="button button-ghost" data-preset="last-7">Last 7 days</button>
                <button type="button" class="button button-ghost" data-preset="last-30">Last 30 days</button>
                <button type="button" class="button button-ghost" data-preset="last-90">Last 90 days</button>
                <button type="button" class="button button-ghost" data-preset="this-month">This month</button>
                <button type="button" class="button button-ghost" data-preset="last-month">Last month</button>
                <button type="button" class="button button-ghost" data-preset="custom">Custom</button>
            </div>
            <label class="field-inline">
                <span>Start date</span>
                <input type="date" name="start" value="<?= e($start ?? '') ?>">
            </label>
            <label class="field-inline">
                <span>End date</span>
                <input type="date" name="end" value="<?= e($end ?? '') ?>">
            </label>
            <label class="checkbox">
                <input type="checkbox" name="compare" value="1" <?= ($compareEnabled ?? false) ? 'checked' : '' ?>>
                <span>Compare to previous period</span>
            </label>
            <button type="submit" class="button button-ghost">Filter</button>
        </form>
        <?php if (($compareEnabled ?? false) && !empty($comparison)) : ?>
            <div class="comparison-grid">
                <div class="comparison-card">
                    <div class="comparison-title">MRR</div>
                    <div class="comparison-values">
                        <span>$<?= number_format(($comparison['mrr']['current'] ?? 0) / 100, 2) ?></span>
                        <span class="muted">vs $<?= number_format(($comparison['mrr']['previous'] ?? 0) / 100, 2) ?></span>
                    </div>
                    <div class="comparison-delta <?= e($comparison['mrr']['trend'] ?? '') ?>">
                        <?= ($comparison['mrr']['trend'] ?? '') === 'up' ? '▲' : ((($comparison['mrr']['trend'] ?? '') === 'down') ? '▼' : '•') ?>
                        <?= number_format(($comparison['mrr']['delta'] ?? 0) / 100, 2) ?>
                        <?= $comparison['mrr']['pct'] !== null ? '(' . e((string)$comparison['mrr']['pct']) . '%)' : '' ?>
                    </div>
                </div>
                <div class="comparison-card">
                    <div class="comparison-title">Churn rate</div>
                    <div class="comparison-values">
                        <span><?= e((string)($comparison['churn']['current'] ?? 0)) ?>%</span>
                        <span class="muted">vs <?= e((string)($comparison['churn']['previous'] ?? 0)) ?>%</span>
                    </div>
                    <div class="comparison-delta <?= e($comparison['churn']['trend'] ?? '') ?>">
                        <?= ($comparison['churn']['trend'] ?? '') === 'up' ? '▲' : ((($comparison['churn']['trend'] ?? '') === 'down') ? '▼' : '•') ?>
                        <?= e((string)($comparison['churn']['delta'] ?? 0)) ?>%
                        <?= $comparison['churn']['pct'] !== null ? '(' . e((string)$comparison['churn']['pct']) . '%)' : '' ?>
                    </div>
                </div>
                <div class="comparison-card">
                    <div class="comparison-title">LTV</div>
                    <div class="comparison-values">
                        <span>$<?= number_format(($comparison['ltv']['current'] ?? 0) / 100, 2) ?></span>
                        <span class="muted">vs $<?= number_format(($comparison['ltv']['previous'] ?? 0) / 100, 2) ?></span>
                    </div>
                    <div class="comparison-delta <?= e($comparison['ltv']['trend'] ?? '') ?>">
                        <?= ($comparison['ltv']['trend'] ?? '') === 'up' ? '▲' : ((($comparison['ltv']['trend'] ?? '') === 'down') ? '▼' : '•') ?>
                        <?= number_format(($comparison['ltv']['delta'] ?? 0) / 100, 2) ?>
                        <?= $comparison['ltv']['pct'] !== null ? '(' . e((string)$comparison['ltv']['pct']) . '%)' : '' ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="kpi-grid">
            <div class="card-muted">
                <h3>MRR</h3>
                <p class="plan-price">$<?= number_format($currentMrr / 100, 2) ?></p>
            </div>
            <div class="card-muted">
                <h3>Top-up revenue</h3>
                <p class="plan-price">$<?= number_format($currentTopup / 100, 2) ?></p>
            </div>
            <div class="card-muted">
                <h3>Churn rate</h3>
                <p class="plan-price"><?= e((string)$churnRate) ?>%</p>
            </div>
            <div class="card-muted">
                <h3>LTV</h3>
                <p class="plan-price">$<?= number_format($ltv / 100, 2) ?></p>
            </div>
        </div>

        <div class="chart-grid">
            <div class="card-muted">
                <h3>MRR trend</h3>
                <canvas id="mrrTrendChart" height="200"></canvas>
            </div>
            <div class="card-muted">
                <h3>Top-up revenue</h3>
                <canvas id="topupRevenueChart" height="200"></canvas>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const mrrTrend = <?= json_encode($mrrTrend ?? [], JSON_UNESCAPED_SLASHES) ?>;
const topupTrend = <?= json_encode($topupTrend ?? [], JSON_UNESCAPED_SLASHES) ?>;

const labels = mrrTrend.map(row => row.month);
const mrrValues = mrrTrend.map(row => Number(row.mrr_cents) / 100);
const topupValues = topupTrend.map(row => Number(row.topup_cents) / 100);

const mrrCtx = document.getElementById('mrrTrendChart');
if (mrrCtx) {
  new Chart(mrrCtx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'MRR ($)',
        data: mrrValues,
        borderColor: '#2b5a95',
        backgroundColor: 'rgba(43, 90, 149, 0.2)',
        tension: 0.3
      }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });
}

const topupCtx = document.getElementById('topupRevenueChart');
if (topupCtx) {
  new Chart(topupCtx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Top-up revenue ($)',
        data: topupValues,
        backgroundColor: '#6ea3ff'
      }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
  });
}

const presetContainers = document.querySelectorAll('[data-date-presets]');
presetContainers.forEach(container => {
  const form = container.closest('form');
  if (!form) return;
  const startInput = form.querySelector('input[name="start"]');
  const endInput = form.querySelector('input[name="end"]');
  if (!startInput || !endInput) return;

  const formatDate = (date) => date.toISOString().slice(0, 10);
  const setRange = (startDate, endDate) => {
    startInput.value = formatDate(startDate);
    endInput.value = formatDate(endDate);
  };

  container.querySelectorAll('button[data-preset]').forEach(button => {
    button.addEventListener('click', () => {
      const preset = button.getAttribute('data-preset');
      const today = new Date();
      const end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
      if (preset === 'last-7') {
        const start = new Date(end);
        start.setDate(start.getDate() - 6);
        setRange(start, end);
      } else if (preset === 'last-30') {
        const start = new Date(end);
        start.setDate(start.getDate() - 29);
        setRange(start, end);
      } else if (preset === 'last-90') {
        const start = new Date(end);
        start.setDate(start.getDate() - 89);
        setRange(start, end);
      } else if (preset === 'this-month') {
        const start = new Date(end.getFullYear(), end.getMonth(), 1);
        const endOfMonth = new Date(end.getFullYear(), end.getMonth() + 1, 0);
        setRange(start, endOfMonth);
      } else if (preset === 'last-month') {
        const start = new Date(end.getFullYear(), end.getMonth() - 1, 1);
        const endOfMonth = new Date(end.getFullYear(), end.getMonth(), 0);
        setRange(start, endOfMonth);
      } else if (preset === 'custom') {
        startInput.focus();
        return;
      }
      form.submit();
    });
  });
});
</script>
