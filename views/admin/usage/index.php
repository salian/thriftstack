<section class="page-section">
    <h1>App Super Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>App Usage</h2>
    <p>Workspace, user, and product usage across the platform.</p>

    <div class="card">
        <h2>Users</h2>
        <form method="get" class="table-toolbar form-inline" data-auto-search-form>
            <input type="search" name="search" value="<?= e($search ?? '') ?>" placeholder="Search by name or email" data-auto-search>
            <select name="role_id" data-auto-submit>
                <option value="all" <?= ($selectedRole ?? 'all') === 'all' ? 'selected' : '' ?>>All app roles</option>
                <option value="unassigned" <?= ($selectedRole ?? '') === 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                <?php foreach ($roles as $role) : ?>
                    <option value="<?= e((string)$role['id']) ?>" <?= ($selectedRole ?? '') == $role['id'] ? 'selected' : '' ?>>
                        <?= e($role['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="table-search-status" data-search-status aria-live="polite"></span>
        </form>
        <?php
        $totalPages = (int)($totalPages ?? 1);
        $page = (int)($page ?? 1);
        $queryBase = [
            'search' => $search ?? '',
            'role_id' => $selectedRole ?? 'all',
        ];
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>App role</th>
                    <th>Status</th>
                    <th>Assign</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $actorId = (int)(Auth::user()['id'] ?? 0);
                $superAdminCount = (int)($superAdminCount ?? 0);
                $superAdminHint = 'At least one App Super Admin is required.';
                foreach ($users as $user) :
                ?>
                    <?php
                    $status = (string)($user['status'] ?? 'active');
                    $redirectQuery = http_build_query(array_merge($queryBase, ['page' => $page]));
                    $redirectPath = '/super-admin/usage?' . $redirectQuery;
                    $isSelf = $actorId > 0 && (int)$user['id'] === $actorId;
                    $isLastSuper = $superAdminCount <= 1 && ($user['role_name'] ?? '') === 'App Super Admin';
                    ?>
                    <tr>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['role_name'] ?? 'Unassigned') ?></td>
                        <td><?= e(ucfirst($status)) ?></td>
                        <td>
                            <form method="post" action="/super-admin/user-roles" class="form-inline">
                                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                <input type="hidden" name="user_id" value="<?= e((string)$user['id']) ?>">
                                <select name="role_id" required>
                                    <option value="">Select app role</option>
                                    <?php foreach ($roles as $role) : ?>
                                        <?php
                                        $isSuperRole = $role['name'] === 'App Super Admin';
                                        $disableRole = $isLastSuper && !$isSuperRole;
                                        ?>
                                        <option
                                            value="<?= e((string)$role['id']) ?>"
                                            <?= ($user['role_name'] ?? '') === $role['name'] ? 'selected' : '' ?>
                                            <?= $disableRole ? 'disabled' : '' ?>
                                            <?= $disableRole ? 'title="' . e($superAdminHint) . '"' : '' ?>
                                        >
                                            <?= e($role['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="button button-ghost">Assign</button>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="/super-admin/users/status" class="form-inline">
                                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                <input type="hidden" name="user_id" value="<?= e((string)$user['id']) ?>">
                                <input type="hidden" name="status" value="<?= e($status === 'active' ? 'inactive' : 'active') ?>">
                                <input type="hidden" name="redirect" value="<?= e($redirectPath) ?>">
                                <button type="submit" class="button button-ghost" <?= $isSelf ? 'disabled' : '' ?>>
                                    <?= $status === 'active' ? 'Deactivate' : 'Reactivate' ?>
                                </button>
                            </form>
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
