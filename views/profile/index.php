<section class="page-section">
    <h1>Profile</h1>
    <p>Manage your profile image and uploads.</p>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Profile details</h2>
        <form method="post" action="/profile/update" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" value="<?= e($user['email'] ?? '') ?>" disabled>
            </label>
            <button type="submit" class="button">Save profile</button>
        </form>
    </div>

    <div class="card">
        <h2>Profile image</h2>
        <form method="post" action="/uploads/profile" enctype="multipart/form-data" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Choose image (JPG, PNG, WebP)</span>
                <input type="file" name="profile" accept="image/jpeg,image/png,image/webp" required>
            </label>
            <button type="submit" class="button">Upload profile image</button>
        </form>
    </div>

    <div class="card">
        <h2>My Uploads</h2>
        <form method="post" action="/uploads/attachment" enctype="multipart/form-data" class="form">
            <label>
                <span>Choose file (PDF, image, txt)</span>
                <input type="file" name="attachment" accept="application/pdf,image/jpeg,image/png,image/webp,text/plain" required>
            </label>
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <button type="submit" class="button">Upload file</button>
        </form>

        <?php if (!empty($uploads)) : ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Type</th>
                        <th>Uploaded</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploads as $upload) : ?>
                        <tr>
                            <td><?= e($upload['original_name'] ?? 'Unknown') ?></td>
                            <td><?= e(ucfirst($upload['type'] ?? '')) ?></td>
                            <td><?= e($upload['created_at'] ?? '') ?></td>
                            <td>
                                <?php if (($upload['type'] ?? '') === 'attachment') : ?>
                                    <a href="/uploads/attachment/<?= e((string)$upload['id']) ?>">Download</a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No uploads yet.</p>
        <?php endif; ?>
    </div>
</section>
