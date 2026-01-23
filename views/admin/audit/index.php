<section class="page-section">
    <h1>Audit Log</h1>
    <p>Recent security and admin events.</p>

    <?php require __DIR__ . '/../nav.php'; ?>

    <div class="card">
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
    </div>
</section>
