<section class="page-section">
    <h1>System Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>Site Settings</h2>

    <div class="card">
        <h2>Access control</h2>
        <div class="tabs" data-tabs>
            <button type="button" class="tab-button is-active" data-tab-button="workspace-roles"
                aria-selected="true" aria-controls="tab-workspace-roles">Workspace Roles</button>
            <button type="button" class="tab-button" data-tab-button="workspace-permissions"
                aria-selected="false" aria-controls="tab-workspace-permissions">Workspace Permissions</button>
        </div>

        <div class="tab-panels">
            <div class="tab-panel is-active" data-tab-panel="workspace-roles" id="tab-workspace-roles" role="tabpanel">
                <div class="table-header">
                    <h3>Workspace roles</h3>
                    <span class="muted">Manage workspace role permissions.</span>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Workspace role</th>
                            <th>Permissions</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($workspaceRoles ?? []) as $workspaceRole) : ?>
                            <tr>
                                <td><?= e($workspaceRole) ?></td>
                                <td>
                                    <?php $current = $workspacePermissionsByRole[$workspaceRole] ?? []; ?>
                                    <form method="post" action="/super-admin/workspace-roles/permissions" class="form-inline">
                                        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                        <input type="hidden" name="workspace_role" value="<?= e($workspaceRole) ?>">
                                        <div class="checkbox-grid">
                                            <?php foreach (($workspacePermissions ?? []) as $permission) : ?>
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

            <div class="tab-panel" data-tab-panel="workspace-permissions" id="tab-workspace-permissions" role="tabpanel" hidden>
                <div class="table-header">
                    <h3>Existing workspace permissions</h3>
                    <button type="button" class="button" data-modal-open="workspace-permission-create">Create New Permission</button>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($workspacePermissions ?? []) as $permission) : ?>
                            <tr>
                                <td><?= e($permission['name']) ?></td>
                                <td><?= e($permission['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <dialog class="modal" id="workspace-permission-create">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Create workspace permission</h2>
                    <p class="modal-subtitle">Add workspace permissions to control access.</p>
                </div>
                <button type="button" class="icon-button" data-modal-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="/super-admin/workspace-permissions" class="form">
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <label>
                    <span>Workspace permission name</span>
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

    <div class="card">
        <h2>Invoice setup</h2>
        <p class="muted">Configure seller details for invoices and receipts.</p>
        <form method="post" action="/super-admin/invoice-setup" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Company name</span>
                <input type="text" name="invoice_company_name" value="<?= e((string)$appSettings->get('invoice.company_name', '')) ?>">
            </label>
            <label>
                <span>Company address</span>
                <input type="text" name="invoice_company_address" value="<?= e((string)$appSettings->get('invoice.company_address', '')) ?>">
            </label>
            <label>
                <span>City</span>
                <input type="text" name="invoice_company_city" value="<?= e((string)$appSettings->get('invoice.company_city', '')) ?>">
            </label>
            <label>
                <span>State / region</span>
                <input type="text" name="invoice_company_state" value="<?= e((string)$appSettings->get('invoice.company_state', '')) ?>">
            </label>
            <label>
                <span>Postal code</span>
                <input type="text" name="invoice_company_postal_code" value="<?= e((string)$appSettings->get('invoice.company_postal_code', '')) ?>">
            </label>
            <label>
                <span>Country</span>
                <input type="text" name="invoice_company_country" value="<?= e((string)$appSettings->get('invoice.company_country', '')) ?>">
            </label>
            <label>
                <span>Tax ID / VAT</span>
                <input type="text" name="invoice_tax_id" value="<?= e((string)$appSettings->get('invoice.tax_id', '')) ?>">
            </label>
            <label>
                <span>Support email</span>
                <input type="email" name="invoice_support_email" value="<?= e((string)$appSettings->get('invoice.support_email', '')) ?>">
            </label>
            <button type="submit" class="button">Save invoice setup</button>
        </form>
    </div>

    <div class="card">
        <h2>Profile images</h2>
        <p class="muted">Allow users to upload profile images and display them in the header.</p>
        <form method="post" action="/super-admin/profile-images" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label class="checkbox">
                <input type="checkbox" name="profile_images_enabled" value="1"
                    <?= $appSettings->get('profile.images.enabled', '0') === '1' ? 'checked' : '' ?>>
                <span>Enable profile images</span>
            </label>
            <button type="submit" class="button">Save profile image setting</button>
        </form>
    </div>

    <div class="card">
        <h2>Feature flags</h2>
        <p>App-wide enable/disable for sections like Tasks, Reports, and more (coming soon).</p>
    </div>
</section>
