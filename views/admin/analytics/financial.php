<section class="page-section">
    <h1>System Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>Financial analytics</h2>
    <p class="muted">MRR, revenue, churn, and LTV insights.</p>

    <div class="card">
        <div class="table-header">
            <div>
                <h2>Overview</h2>
                <p class="muted">Last 12 months by default.</p>
            </div>
            <a class="button button-ghost" href="/super-admin/analytics/financial/export?start=<?= e($start) ?>&end=<?= e($end) ?>">Export CSV</a>
        </div>

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
</script>
