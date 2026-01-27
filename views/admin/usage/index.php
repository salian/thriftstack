<section class="page-section">
    <h1>System Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>App Usage</h2>
    <p>Workspace, user, and product usage across the platform.</p>

    <div class="card">
        <h2>Users</h2>
        <form method="get" class="table-toolbar form-inline" data-auto-search-form>
            <input type="search" name="search" value="<?= e($search ?? '') ?>" placeholder="Search by name or email" data-auto-search>
            <select name="system_access" data-auto-submit>
                <option value="all" <?= ($selectedAccess ?? 'all') === 'all' ? 'selected' : '' ?>>All access</option>
                <option value="admin" <?= ($selectedAccess ?? '') === 'admin' ? 'selected' : '' ?>>System Admin</option>
                <option value="staff" <?= ($selectedAccess ?? '') === 'staff' ? 'selected' : '' ?>>System Staff</option>
                <option value="standard" <?= ($selectedAccess ?? '') === 'standard' ? 'selected' : '' ?>>Standard</option>
            </select>
            <span class="table-search-status" data-search-status aria-live="polite"></span>
        </form>
        <?php
        $totalPages = (int)($totalPages ?? 1);
        $page = (int)($page ?? 1);
        $queryBase = [
            'search' => $search ?? '',
            'system_access' => $selectedAccess ?? 'all',
        ];
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>System access</th>
                    <th>Status</th>
                    <th>Update</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $actor = Auth::user();
                $actorId = (int)($actor['id'] ?? 0);
                $actorIsAdmin = (int)($actor['is_system_admin'] ?? 0) === 1;
                $superAdminCount = (int)($superAdminCount ?? 0);
                $superAdminHint = 'At least one System Admin is required.';
                foreach ($users as $user) :
                ?>
                    <?php
                    $status = (string)($user['status'] ?? 'active');
                    $redirectQuery = http_build_query(array_merge($queryBase, ['page' => $page]));
                    $redirectPath = '/super-admin/usage?' . $redirectQuery;
                    $isSelf = $actorId > 0 && (int)$user['id'] === $actorId;
                    $isLastSuper = $superAdminCount <= 1 && (int)($user['is_system_admin'] ?? 0) === 1;
                    $accessValue = 'standard';
                    $targetIsAdmin = (int)($user['is_system_admin'] ?? 0) === 1;
                    $targetIsStaff = (int)($user['is_system_staff'] ?? 0) === 1;
                    if ($targetIsAdmin) {
                        $accessValue = 'admin';
                    } elseif ($targetIsStaff) {
                        $accessValue = 'staff';
                    }
                    $targetIsPrivileged = $targetIsAdmin || $targetIsStaff;
                    $canManageAccess = $actorIsAdmin;
                    $canChangeStatus = $actorIsAdmin || !$targetIsPrivileged;
                    ?>
                    <tr>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($accessValue === 'admin' ? 'System Admin' : ($accessValue === 'staff' ? 'System Staff' : 'Standard')) ?></td>
                        <td><?= e(ucfirst($status)) ?></td>
                        <td>
                            <?php if ($canManageAccess) : ?>
                                <form method="post" action="/super-admin/user-roles" class="form-inline">
                                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                    <input type="hidden" name="user_id" value="<?= e((string)$user['id']) ?>">
                                    <select name="system_access" required>
                                        <option value="standard" <?= $accessValue === 'standard' ? 'selected' : '' ?> <?= $isLastSuper ? 'disabled' : '' ?>>Standard</option>
                                        <option value="staff" <?= $accessValue === 'staff' ? 'selected' : '' ?> <?= $isLastSuper ? 'disabled' : '' ?>>System Staff</option>
                                        <option value="admin" <?= $accessValue === 'admin' ? 'selected' : '' ?>>
                                            System Admin
                                        </option>
                                    </select>
                                    <button type="submit" class="button button-ghost" <?= $isLastSuper ? 'title="' . e($superAdminHint) . '"' : '' ?>>Update</button>
                                </form>
                            <?php else : ?>
                                <span class="muted">Admin only</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($canChangeStatus) : ?>
                                <form method="post" action="/super-admin/users/status" class="form-inline">
                                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                    <input type="hidden" name="user_id" value="<?= e((string)$user['id']) ?>">
                                    <input type="hidden" name="status" value="<?= e($status === 'active' ? 'inactive' : 'active') ?>">
                                    <input type="hidden" name="redirect" value="<?= e($redirectPath) ?>">
                                    <button type="submit" class="button button-ghost" <?= $isSelf ? 'disabled' : '' ?>>
                                        <?= $status === 'active' ? 'Deactivate' : 'Reactivate' ?>
                                    </button>
                                </form>
                            <?php else : ?>
                                <span class="muted">Admin only</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="table-pagination">
            <span>Page <?= e((string)$page) ?> of <?= e((string)max(1, $totalPages)) ?> (<?= e((string)($total ?? 0)) ?> total)</span>
            <div class="table-pagination-links">
                <?php if ($page > 1) : ?>
                    <?php $prev = http_build_query(array_merge($queryBase, ['page' => $page - 1])); ?>
                    <a class="pagination-link" href="/super-admin/usage?<?= e($prev) ?>">Prev</a>
                <?php else : ?>
                    <button class="pagination-link" type="button" disabled>Prev</button>
                <?php endif; ?>
                <?php if ($page < $totalPages) : ?>
                    <?php $next = http_build_query(array_merge($queryBase, ['page' => $page + 1])); ?>
                    <a class="pagination-link" href="/super-admin/usage?<?= e($next) ?>">Next</a>
                <?php else : ?>
                    <button class="pagination-link" type="button" disabled>Next</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
