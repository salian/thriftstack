<section class="page-section">
    <h1>Users</h1>
    <p>View registered users.</p>

    <?php require __DIR__ . '/../nav.php'; ?>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['role'] ?? 'Unassigned') ?></td>
                        <td><?= e($user['status'] ?? '') ?></td>
                        <td><?= e($user['created_at'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
