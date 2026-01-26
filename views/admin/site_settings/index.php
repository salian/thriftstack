<section class="page-section">
    <h1>Application Super Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>Site Settings</h2>

    <div class="card">
        <h2>Access control</h2>
        <div class="tabs" data-tabs>
            <button type="button" class="tab-button is-active" data-tab-button="user-roles"
                aria-selected="true" aria-controls="tab-user-roles">User Roles</button>
            <button type="button" class="tab-button" data-tab-button="permissions"
                aria-selected="false" aria-controls="tab-permissions">Permissions</button>
            <button type="button" class="tab-button" data-tab-button="roles"
                aria-selected="false" aria-controls="tab-roles">Roles</button>
        </div>

        <div class="tab-panels">
            <div class="tab-panel is-active" data-tab-panel="user-roles" id="tab-user-roles" role="tabpanel">
                <form method="get" class="table-toolbar form-inline" data-auto-search-form>
                    <input type="hidden" name="tab" value="user-roles">
                    <input type="search" name="search" value="<?= e($search ?? '') ?>" placeholder="Search by name or email" data-auto-search>
                    <select name="role_id" data-auto-submit>
                        <option value="all" <?= ($selectedRole ?? 'all') === 'all' ? 'selected' : '' ?>>All roles</option>
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
                    'tab' => 'user-roles',
                    'search' => $search ?? '',
                    'role_id' => $selectedRole ?? 'all',
                ];
                ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Assign</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $actorId = (int)(Auth::user()['id'] ?? 0);
                        $superAdminCount = (int)($superAdminCount ?? 0);
                        $superAdminHint = 'At least one Super Admin is required.';
                        foreach ($users as $user) :
                        ?>
                            <?php
                            $status = (string)($user['status'] ?? 'active');
                            $redirectQuery = http_build_query(array_merge($queryBase, ['page' => $page]));
                            $redirectPath = '/super-admin/settings?' . $redirectQuery;
                            $isSelf = $actorId > 0 && (int)$user['id'] === $actorId;
                            $isLastSuper = $superAdminCount <= 1 && ($user['role_name'] ?? '') === 'Super Admin';
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
                                            <option value="">Select role</option>
                                            <?php foreach ($roles as $role) : ?>
                                                <?php
                                                $isSuperRole = $role['name'] === 'Super Admin';
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
                            <a class="pagination-link" href="/super-admin/settings?<?= e($prev) ?>">Prev</a>
                        <?php else : ?>
                            <button class="pagination-link" type="button" disabled>Prev</button>
                        <?php endif; ?>
                        <?php if ($page < $totalPages) : ?>
                            <?php $next = http_build_query(array_merge($queryBase, ['page' => $page + 1])); ?>
                            <a class="pagination-link" href="/super-admin/settings?<?= e($next) ?>">Next</a>
                        <?php else : ?>
                            <button class="pagination-link" type="button" disabled>Next</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tab-panel" data-tab-panel="permissions" id="tab-permissions" role="tabpanel" hidden>
                <div class="table-header">
                    <h3>Existing permissions</h3>
                    <button type="button" class="button" data-modal-open="permission-create">Create New Permission</button>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($permissions as $permission) : ?>
                            <tr>
                                <td><?= e($permission['name']) ?></td>
                                <td><?= e($permission['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="tab-panel" data-tab-panel="roles" id="tab-roles" role="tabpanel" hidden>
                <div class="table-header">
                    <h3>Existing roles</h3>
                    <button type="button" class="button" data-modal-open="role-create">New Role</button>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Description</th>
                            <th>Permissions</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role) : ?>
                            <tr>
                                <td><?= e($role['name']) ?></td>
                                <td><?= e($role['description'] ?? '') ?></td>
                                <td>
                                    <?php $current = $permissionsByRole[(int)$role['id']] ?? []; ?>
                                    <form method="post" action="/super-admin/roles/permissions" class="form-inline">
                                        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                        <input type="hidden" name="role_id" value="<?= e((string)$role['id']) ?>">
                                        <div class="checkbox-grid">
                                            <?php foreach ($permissions as $permission) : ?>
                                                <label class="checkbox">
                                                    <input
                                                        type="checkbox"
                                                        name="permission_ids[]"
                                                        value="<?= e((string)$permission['id']) ?>"
                                                        <?= in_array($permission['name'], $current, true) ? 'checked' : '' ?>
                                                    >
                                                    <span><?= e($permission['name']) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="submit" class="button button-ghost">Save</button>
                                    </form>
                                </td>
                                <td></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <dialog class="modal" id="permission-create">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Create permission</h2>
                    <p class="modal-subtitle">Add permissions to control access.</p>
                </div>
                <button type="button" class="icon-button" data-modal-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="/super-admin/permissions" class="form">
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <label>
                    <span>Permission name</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>Description</span>
                    <input type="text" name="description">
                </label>
                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="button">Create permission</button>
                </div>
            </form>
        </div>
    </dialog>

    <dialog class="modal" id="role-create">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Create role</h2>
                    <p class="modal-subtitle">Add roles to group permissions.</p>
                </div>
                <button type="button" class="icon-button" data-modal-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="/super-admin/roles" class="form">
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <label>
                    <span>Role name</span>
                    <input type="text" name="name" required>
                </label>
                <label>
                    <span>Description</span>
                    <input type="text" name="description">
                </label>
                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="button">Create role</button>
                </div>
            </form>
        </div>
    </dialog>

    <div class="card">
        <h2>Feature flags</h2>
        <p>Global enable/disable for sections like Tasks, Reports, and more (coming soon).</p>
    </div>
</section>
