<section class="page-section">
    <h1>User Roles</h1>
    <p>Assign roles to users.</p>

    <?php require __DIR__ . '/../nav.php'; ?>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Assign</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($rolesByUser[(int)$user['id']] ?? 'Unassigned') ?></td>
                        <td>
                            <form method="post" action="/admin/user-roles" class="form-inline">
                                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                <input type="hidden" name="user_id" value="<?= e((string)$user['id']) ?>">
                                <select name="role_id" required>
                                    <option value="">Select role</option>
                                    <?php foreach ($roles as $role) : ?>
                                        <option value="<?= e((string)$role['id']) ?>">
                                            <?= e($role['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="button button-ghost">Assign</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
