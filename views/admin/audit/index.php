<section class="page-section">
    <h1>Workspace Admin</h1>

    <?php require __DIR__ . '/../nav.php'; ?>

    <h2>Workspace Audit Log</h2>

    <?php
    $pagination = $pagination ?? ['page' => 1, 'totalPages' => 1, 'total' => 0];
    $currentPage = (int)($pagination['page'] ?? 1);
    $totalPages = (int)($pagination['totalPages'] ?? 1);
    $totalRows = (int)($pagination['total'] ?? 0);
    $filters = $filters ?? [];
    $queryBase = $filters;
    $queryBase['page'] = null;
    $baseQuery = http_build_query(array_filter($queryBase, static fn($value) => $value !== null && $value !== ''));
    $baseQuery = $baseQuery !== '' ? $baseQuery . '&' : '';
    ?>

    <div class="card">
        <form method="get" class="table-toolbar form-inline">
            <label class="field-inline">
                <span>Start date</span>
                <input type="date" name="start" value="<?= e($filters['start'] ?? '') ?>">
            </label>
            <label class="field-inline">
                <span>End date</span>
                <input type="date" name="end" value="<?= e($filters['end'] ?? '') ?>">
            </label>
            <select name="action" aria-label="Filter by action">
                <option value="">All actions</option>
                <?php foreach ($actions as $action) : ?>
                    <option value="<?= e($action) ?>" <?= ($action === ($filters['action'] ?? '')) ? 'selected' : '' ?>>
                        <?= e($action) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-ghost">Filter</button>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Metadata</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td><?= e($log['created_at'] ?? '') ?></td>
                        <td><?= e($log['email'] ?? 'System') ?></td>
                        <td><?= e($log['action'] ?? '') ?></td>
                        <td><?= e($log['metadata'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="table-pagination">
            <span>Page <?= e((string)$currentPage) ?> of <?= e((string)$totalPages) ?> (<?= e((string)$totalRows) ?> total)</span>
            <div class="table-pagination-links">
                <a class="pagination-link <?= $currentPage <= 1 ? 'is-disabled' : '' ?>"
                    href="<?= $currentPage <= 1 ? '#' : '?' . $baseQuery . 'page=' . ($currentPage - 1) ?>"
                    <?= $currentPage <= 1 ? 'aria-disabled="true"' : '' ?>>Prev</a>
                <a class="pagination-link <?= $currentPage >= $totalPages ? 'is-disabled' : '' ?>"
                    href="<?= $currentPage >= $totalPages ? '#' : '?' . $baseQuery . 'page=' . ($currentPage + 1) ?>"
                    <?= $currentPage >= $totalPages ? 'aria-disabled="true"' : '' ?>>Next</a>
            </div>
        </div>
    </div>
</section>
