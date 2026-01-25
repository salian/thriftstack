<section class="page-section">
    <h1>Workspaces</h1>
    <p>Manage your workspaces, members, and invites.</p>

    <?php if (!empty($message)) : ?>
        <div class="alert alert-success"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($inviteLink)) : ?>
        <div class="alert">
            Share this invite link:
            <a href="<?= e($inviteLink) ?>"><?= e($inviteLink) ?></a>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-header">
            <h2>Your workspaces</h2>
            <button type="button" class="button" data-modal-open="workspace-create">Add a workspace</button>
        </div>
        <?php if (empty($workspaces)) : ?>
            <p>No workspaces yet. Create one to get started.</p>
        <?php else : ?>
            <?php $returnTo = $_SERVER['REQUEST_URI'] ?? '/workspaces'; ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Members</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workspaces as $workspace) : ?>
                        <?php $isCurrent = ($currentWorkspace['id'] ?? null) == $workspace['id']; ?>
                        <?php $canEditWorkspace = in_array(($workspace['role'] ?? ''), ['Owner', 'Admin'], true); ?>
                        <tr>
                            <td>
                                <div class="workspace-name-cell" data-workspace-row="<?= e((string)$workspace['id']) ?>">
                                    <span class="workspace-name-text"><?= e($workspace['name'] ?? '') ?></span>
                                    <?php if ($canEditWorkspace) : ?>
                                        <button type="button" class="icon-button workspace-edit"
                                            data-workspace-edit="<?= e((string)$workspace['id']) ?>"
                                            aria-label="Edit workspace name">
                                            <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($canEditWorkspace) : ?>
                                    <form method="post" action="/workspaces/update" class="workspace-edit-form"
                                        data-workspace-form="<?= e((string)$workspace['id']) ?>">
                                        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                        <input type="hidden" name="workspace_id" value="<?= e((string)$workspace['id']) ?>">
                                        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                                        <input type="text" name="name" class="workspace-name-input"
                                            data-workspace-input="<?= e((string)$workspace['id']) ?>"
                                            value="<?= e($workspace['name'] ?? '') ?>" required>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string)($workspace['member_count'] ?? 0)) ?></td>
                            <td><?= e($workspace['role'] ?? '') ?></td>
                            <td>
                                <?php if ($isCurrent) : ?>
                                    <span class="badge badge-primary">Current</span>
                                <?php else : ?>
                                    <form method="post" action="/workspaces/switch">
                                        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                        <input type="hidden" name="workspace_id" value="<?= e((string)$workspace['id']) ?>">
                                        <button type="submit" class="button">Switch</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <dialog class="modal" id="workspace-create">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2>Add workspace</h2>
                    <p class="modal-subtitle">Workspaces keep teams, data, and settings separated.</p>
                </div>
                <button type="button" class="icon-button" data-modal-close>
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <form method="post" action="/workspaces" class="form">
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <label>
                    <span>Workspace name</span>
                    <input type="text" name="name" required>
                </label>
                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                    <button type="submit" class="button">Create workspace</button>
                </div>
            </form>
        </div>
    </dialog>

    <?php if (!empty($currentWorkspace)) : ?>
        <div class="card">
            <h2>Members</h2>
            <?php if (empty($members)) : ?>
                <p>No members yet.</p>
            <?php else : ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <?php if (!empty($canManage)) : ?>
                                <th>Update</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member) : ?>
                            <tr>
                                <td><?= e($member['name'] ?? '') ?></td>
                                <td><?= e($member['email'] ?? '') ?></td>
                                <td><?= e($member['role'] ?? '') ?></td>
                                <?php if (!empty($canManage)) : ?>
                                    <td>
                                        <form method="post" action="/workspaces/members/role" class="form-inline">
                                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                                            <input type="hidden" name="member_id" value="<?= e((string)$member['id']) ?>">
                                            <select name="role">
                                                <?php foreach ($roles as $roleOption) : ?>
                                                    <option value="<?= e($roleOption) ?>" <?= ($roleOption === $member['role']) ? 'selected' : '' ?>>
                                                        <?= e($roleOption) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="button">Update</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Invites</h2>
            <?php if (!empty($canManage)) : ?>
                <form method="post" action="/workspaces/invites" class="form">
                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" required>
                    </label>
                    <label>
                        <span>Role</span>
                        <select name="role">
                            <?php foreach ($roles as $roleOption) : ?>
                                <option value="<?= e($roleOption) ?>"><?= e($roleOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit" class="button">Send invite</button>
                </form>
            <?php endif; ?>

            <?php if (empty($invites)) : ?>
                <p>No invites sent yet.</p>
            <?php else : ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invites as $invite) : ?>
                            <tr>
                                <td><?= e($invite['email'] ?? '') ?></td>
                                <td><?= e($invite['role'] ?? '') ?></td>
                                <td><?= !empty($invite['accepted_at']) ? 'Accepted' : 'Pending' ?></td>
                                <td><?= e($invite['expires_at'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
