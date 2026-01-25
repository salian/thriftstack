<section class="page-section">
    <h1>Notifications</h1>
    <p>Review your recent notifications.</p>

    <div class="card">
        <?php if (empty($notifications)) : ?>
            <p>No notifications yet.</p>
        <?php else : ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification) : ?>
                        <?php $isRead = !empty($notification['is_read']); ?>
                        <tr class="<?= $isRead ? '' : 'is-muted' ?>">
                            <td><?= e(ucwords(str_replace('_', ' ', (string)($notification['channel'] ?? '')))) ?></td>
                            <td><?= e($notification['subject'] ?? '') ?></td>
                            <td><?= e(ucfirst((string)($notification['status'] ?? ''))) ?></td>
                            <td><?= e($notification['created_at'] ?? '') ?></td>
                            <td>
                                <?php if (!$isRead) : ?>
                                    <form method="post" action="/notifications/read">
                                        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                        <input type="hidden" name="notification_id" value="<?= e((string)($notification['id'] ?? 0)) ?>">
                                        <button type="submit" class="button button-ghost">Mark read</button>
                                    </form>
                                <?php else : ?>
                                    <span class="badge">Read</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
