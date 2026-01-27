<section class="page-section">
    <h1>System Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>App Analytics</h2>
    <p>Usage, revenue, and churn summaries across all workspaces.</p>
    <?php if (!(bool)($showRevenue ?? false)) : ?>
        <p class="muted">Revenue metrics are visible to System Admins only.</p>
    <?php endif; ?>

    <div class="analytics-kpis">
        <?php foreach (($kpis ?? []) as $kpi) : ?>
            <div class="card kpi-card">
                <p class="kpi-label"><?= e($kpi['label']) ?></p>
                <p class="kpi-value"><?= e($kpi['value']) ?></p>
                <p class="kpi-meta"><?= e($kpi['delta']) ?> vs last period</p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="analytics-charts">
        <?php foreach (($charts ?? []) as $chart) : ?>
            <div class="card chart-card">
                <h3><?= e($chart['title']) ?></h3>
                <p class="chart-subtitle"><?= e($chart['description']) ?></p>
                <div class="chart-placeholder">
                    <span>Chart placeholder</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Future data sources</h2>
        <p>These placeholders will connect to:</p>
        <ul>
            <?php foreach (($futureSources ?? []) as $source) : ?>
                <li><?= e($source) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
