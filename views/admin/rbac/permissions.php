<section class="page-section">
    <h1>Permissions</h1>
    <p>Define permissions for your modules.</p>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <div class="card">
        <div class="table-header">
            <h2>Existing permissions</h2>
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
</section>
