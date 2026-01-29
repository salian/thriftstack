<section class="page-section">
    <h1>Workspace Admin</h1>
    <?php require __DIR__ . '/../../admin/nav.php'; ?>

    <h2>Credits analytics</h2>
    <p class="muted">Track credit consumption trends and category usage.</p>

    <div class="card">
        <div class="table-header">
            <div>
                <h2>Credits overview</h2>
                <p class="muted">Usage trends for the last 30 days.</p>
            </div>
            <a class="button button-ghost" href="/workspace-admin/analytics/credits/export">Export CSV</a>
        </div>
        <div class="kpi-grid">
            <div class="card-muted">
                <h3>Total consumed</h3>
                <p class="plan-price"><?= e((string)$totalConsumed) ?></p>
            </div>
            <div class="card-muted">
                <h3>Current balance</h3>
                <p class="plan-price"><?= e((string)$currentBalance) ?></p>
            </div>
            <div class="card-muted">
                <h3>Projected depletion</h3>
                <p class="plan-price"><?= e($depletionDate ?? '—') ?></p>
            </div>
        </div>

        <div class="chart-grid">
            <div class="card-muted">
                <h3>Consumption trends</h3>
                <canvas id="creditsTrendChart" height="200"></canvas>
            </div>
            <div class="card-muted">
                <h3>Usage by category</h3>
                <canvas id="creditsUsageChart" height="200"></canvas>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const trendData = <?= json_encode($trend ?? [], JSON_UNESCAPED_SLASHES) ?>;
const usageTotals = <?= json_encode($usageTotals ?? [], JSON_UNESCAPED_SLASHES) ?>;

const labels = [...new Set(trendData.map(row => row.date))];
const types = [...new Set(trendData.map(row => row.usage_type || 'unknown'))];
const datasets = types.map((type, index) => {
  const data = labels.map(label => {
    const match = trendData.find(row => row.date === label && (row.usage_type || 'unknown') === type);
    return match ? Number(match.credits) : 0;
  });
  return {
    label: type,
    data,
    borderColor: `hsl(${(index * 60) % 360} 60% 45%)`,
    backgroundColor: `hsla(${(index * 60) % 360} 60% 45% / 0.2)`,
    tension: 0.3
  };
});

const trendCtx = document.getElementById('creditsTrendChart');
if (trendCtx) {
  new Chart(trendCtx, {
    type: 'line',
    data: { labels, datasets },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'bottom' } }
    }
  });
}

const usageCtx = document.getElementById('creditsUsageChart');
if (usageCtx) {
  new Chart(usageCtx, {
    type: 'pie',
    data: {
      labels: usageTotals.map(row => row.usage_type || 'unknown'),
      datasets: [{
        data: usageTotals.map(row => Number(row.credits)),
        backgroundColor: usageTotals.map((_, index) => `hsl(${(index * 60) % 360} 60% 55%)`)
      }]
    },
    options: {
      plugins: { legend: { position: 'bottom' } }
    }
  });
}
</script>
