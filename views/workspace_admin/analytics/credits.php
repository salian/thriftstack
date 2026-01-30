<section class="page-section">
    <h1>Workspace Admin</h1>
    <?php require __DIR__ . '/../../admin/nav.php'; ?>

    <h2>Credits analytics</h2>
    <p class="muted">Track credit consumption trends and category usage.</p>

    <div class="card">
        <div class="table-header">
            <div>
                <h2>Credits overview</h2>
                <p class="muted">Usage trends for the selected range.</p>
            </div>
            <a class="button button-ghost" href="/workspace-admin/analytics/credits/export?start=<?= e($start ?? '') ?>&end=<?= e($end ?? '') ?>">Export CSV</a>
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
            <button type="submit" class="button button-ghost">Filter</button>
        </form>
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

        <div class="comparison-grid">
            <div class="comparison-card">
                <div class="comparison-title">User segments</div>
                <div class="comparison-values">
                    <span>High: <?= e((string)($segments['high']['count'] ?? 0)) ?></span>
                    <span>Medium: <?= e((string)($segments['medium']['count'] ?? 0)) ?></span>
                    <span>Low: <?= e((string)($segments['low']['count'] ?? 0)) ?></span>
                </div>
                <div class="muted">Segmented by total credits (top 20%, middle 50%, bottom 30%).</div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="card-muted">
                <h3>Usage type trends</h3>
                <canvas id="usageTypeChart" height="200"></canvas>
            </div>
            <div class="card-muted">
                <h3>Time-of-day heatmap</h3>
                <div class="heatmap">
                    <div class="heatmap-grid">
                        <div class="heatmap-header"></div>
                        <?php for ($hour = 0; $hour < 24; $hour++) : ?>
                            <div class="heatmap-header"><?= e(str_pad((string)$hour, 2, '0', STR_PAD_LEFT)) ?></div>
                        <?php endfor; ?>
                        <?php
                        $heatmapGrid = $heatmap['grid'] ?? [];
                        $heatmapMax = (int)($heatmap['max'] ?? 0);
                        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        foreach ($days as $dayIndex => $label) :
                        ?>
                            <div class="heatmap-day"><?= e($label) ?></div>
                            <?php for ($hour = 0; $hour < 24; $hour++) : ?>
                                <?php
                                $value = (int)($heatmapGrid[$dayIndex][$hour] ?? 0);
                                $alpha = $heatmapMax > 0 ? max(0.08, $value / $heatmapMax) : 0.08;
                                ?>
                                <div class="heatmap-cell" style="background-color: rgba(43, 90, 149, <?= e((string)round($alpha, 2)) ?>);">
                                    <span><?= e((string)$value) ?></span>
                                </div>
                            <?php endfor; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-muted">
            <h3>Anomaly detection</h3>
            <p class="muted">Anomalies are flagged when daily usage exceeds 2x the rolling 7-day average and is above the 30-day mean + 2σ.</p>
            <?php if (empty($anomalies ?? [])) : ?>
                <p class="muted">No anomalies detected in the last 30 days.</p>
            <?php else : ?>
                <div class="anomaly-list">
                    <?php foreach ($anomalies as $anomaly) : ?>
                        <div class="anomaly-card">
                            <div>
                                <strong><?= e($anomaly['date']) ?></strong>
                                <p class="muted">Credits: <?= e((string)$anomaly['credits']) ?> · Rolling avg: <?= e((string)$anomaly['rolling_avg']) ?></p>
                            </div>
                            <span class="badge badge-warning">Spike</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card-muted">
            <h3>Drill-down consumption</h3>
            <form method="get" class="table-toolbar form-inline">
                <label class="field-inline">
                    <span>Start</span>
                    <input type="date" name="drill_start" value="<?= e($drilldown['filters']['start'] ?? '') ?>">
                </label>
                <label class="field-inline">
                    <span>End</span>
                    <input type="date" name="drill_end" value="<?= e($drilldown['filters']['end'] ?? '') ?>">
                </label>
                <label class="field-inline">
                    <span>Usage type</span>
                    <select name="drill_usage_type">
                        <option value="">All</option>
                        <?php foreach (($drilldown['usage_types'] ?? []) as $usageType) : ?>
                            <option value="<?= e($usageType) ?>" <?= $usageType === ($drilldown['filters']['usage_type'] ?? '') ? 'selected' : '' ?>>
                                <?= e($usageType) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field-inline">
                    <span>User</span>
                    <input type="text" name="drill_user" value="<?= e($drilldown['filters']['user'] ?? '') ?>" placeholder="Name or email">
                </label>
                <button type="submit" class="button button-ghost">Filter</button>
            </form>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Usage type</th>
                        <th>Credits</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($drilldown['rows'] ?? []) as $row) : ?>
                        <tr>
                            <td><?= e($row['created_at'] ?? '') ?></td>
                            <td><?= e($row['user_name'] ?? 'API') ?></td>
                            <td><?= e($row['usage_type'] ?? 'unknown') ?></td>
                            <td><?= e((string)abs((int)($row['credits'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="table-pagination">
                <?php
                $filters = $drilldown['filters'] ?? [];
                $page = (int)($filters['page'] ?? 1);
                $totalPages = (int)($drilldown['total_pages'] ?? 1);
                $baseParams = $filters;
                unset($baseParams['page']);
                ?>
                <span>Page <?= e((string)$page) ?> of <?= e((string)$totalPages) ?> (<?= e((string)($drilldown['total'] ?? 0)) ?> total)</span>
                <div class="table-pagination-links">
                    <?php if ($page > 1) : ?>
                        <?php $prev = http_build_query(array_merge($baseParams, ['drill_page' => $page - 1])); ?>
                        <a class="pagination-link" href="/workspace-admin/analytics/credits?<?= e($prev) ?>">Prev</a>
                    <?php else : ?>
                        <button class="pagination-link" type="button" disabled>Prev</button>
                    <?php endif; ?>
                    <?php if ($page < $totalPages) : ?>
                        <?php $next = http_build_query(array_merge($baseParams, ['drill_page' => $page + 1])); ?>
                        <a class="pagination-link" href="/workspace-admin/analytics/credits?<?= e($next) ?>">Next</a>
                    <?php else : ?>
                        <button class="pagination-link" type="button" disabled>Next</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>Spending velocity alerts</h2>
        <p class="muted">Notify workspace owners and admins when weekly credit usage spikes beyond the threshold.</p>
        <form method="post" action="/workspace-admin/analytics/credits/alerts" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label class="checkbox">
                <input type="checkbox" name="velocity_alert_enabled" value="1" <?= !empty($alertSettings['enabled']) ? 'checked' : '' ?>>
                <span>Enable velocity alerts</span>
            </label>
            <label>
                <span>Spike threshold (%)</span>
                <input type="number" name="velocity_threshold_percent" min="10" max="500" value="<?= e((string)($alertSettings['threshold_percent'] ?? 50)) ?>">
            </label>
            <button type="submit" class="button">Save velocity alert settings</button>
        </form>
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

const usageTrend = <?= json_encode($usageTypeTrend ?? [], JSON_UNESCAPED_SLASHES) ?>;
const usageLabels = [...new Set(usageTrend.map(row => row.date))];
const usageTypes = [...new Set(usageTrend.map(row => row.usage_type || 'unknown'))];
const usageDatasets = usageTypes.map((type, index) => ({
  label: type,
  data: usageLabels.map(label => {
    const row = usageTrend.find(item => item.date === label && (item.usage_type || 'unknown') === type);
    return row ? Number(row.credits) : 0;
  }),
  borderColor: `hsl(${(index * 55) % 360} 60% 45%)`,
  backgroundColor: `hsla(${(index * 55) % 360} 60% 45% / 0.2)`,
  tension: 0.3
}));

const usageTypeCtx = document.getElementById('usageTypeChart');
if (usageTypeCtx) {
  new Chart(usageTypeCtx, {
    type: 'line',
    data: { labels: usageLabels, datasets: usageDatasets },
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
