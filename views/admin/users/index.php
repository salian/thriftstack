<section class="page-section">
    <h1>Workspace Admin</h1>

    <?php require __DIR__ . '/../nav.php'; ?>

    <h2>Workspace Users</h2>

    <div class="card">
        <form method="get" action="/workspace-admin/users" class="form-inline table-toolbar" data-auto-search-form>
            <input type="search" name="search" value="<?= e($search ?? '') ?>" placeholder="Search by name or email" data-auto-search>
            <select name="workspace_id" data-auto-submit>
                <option value="all" <?= ($selectedWorkspace ?? 'all') === 'all' ? 'selected' : '' ?>>All My Workspaces</option>
                <?php foreach ($workspaces as $workspace) : ?>
                    <option value="<?= e((string)$workspace['id']) ?>" <?= ($selectedWorkspace ?? '') == $workspace['id'] ? 'selected' : '' ?>>
                        <?= e($workspace['name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="table-search-status" data-search-status aria-live="polite"></span>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Workspace</th>
                    <th>Workspace Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)) : ?>
                    <tr>
                        <td colspan="5">No users found for this filter.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td><?= e($user['name']) ?></td>
                            <td><?= e($user['workspace_names'] ?? '') ?></td>
                            <td><?= e($user['workspace_roles'] ?? '') ?></td>
                            <td><?= e($user['status'] ?? '') ?></td>
                            <td><?= e($user['created_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        $totalPages = (int)ceil(($total ?? 0) / max(1, (int)($perPage ?? 20)));
        $page = (int)($page ?? 1);
        $queryBase = [
            'workspace_id' => $selectedWorkspace ?? 'all',
            'search' => $search ?? '',
        ];
        ?>
        <div class="table-pagination">
            <span>Page <?= e((string)$page) ?> of <?= e((string)max(1, $totalPages)) ?></span>
            <div class="table-pagination-links">
                <?php if ($page > 1) : ?>
                    <?php $prev = http_build_query(array_merge($queryBase, ['page' => $page - 1])); ?>
                    <a class="pagination-link" href="/workspace-admin/users?<?= e($prev) ?>">Prev</a>
                <?php else : ?>
                    <button class="pagination-link" type="button" disabled>Prev</button>
                <?php endif; ?>
                <?php if ($page < $totalPages) : ?>
                    <?php $next = http_build_query(array_merge($queryBase, ['page' => $page + 1])); ?>
                    <a class="pagination-link" href="/workspace-admin/users?<?= e($next) ?>">Next</a>
                <?php else : ?>
                    <button class="pagination-link" type="button" disabled>Next</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
