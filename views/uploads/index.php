<section class="page-section">
    <h1>Uploads</h1>
    <p>Manage profile images and attachments.</p>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

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
        <h2>Attachment</h2>
        <form method="post" action="/uploads/attachment" enctype="multipart/form-data" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Choose file (PDF, image, txt)</span>
                <input type="file" name="attachment" accept="application/pdf,image/jpeg,image/png,image/webp,text/plain" required>
            </label>
            <button type="submit" class="button">Upload attachment</button>
        </form>
    </div>
</section>
