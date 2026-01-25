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
        <h2>Create workspace</h2>
        <form method="post" action="/workspaces" class="form">
            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
            <label>
                <span>Workspace name</span>
                <input type="text" name="name" required>
            </label>
            <button type="submit" class="button">Create workspace</button>
        </form>
    </div>

    <div class="card">
        <h2>Your workspaces</h2>
        <?php if (empty($workspaces)) : ?>
            <p>No workspaces yet. Create one to get started.</p>
        <?php else : ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workspaces as $workspace) : ?>
                        <?php $isCurrent = ($currentWorkspace['id'] ?? null) == $workspace['id']; ?>
                        <tr>
                            <td><?= e($workspace['name'] ?? '') ?></td>
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

    <?php if (!empty($currentWorkspace)) : ?>
        <div class="card">
            <h2>Current workspace</h2>
            <p>
                <?= e($currentWorkspace['name'] ?? '') ?>
                <span class="badge"><?= e($currentRole ?? '') ?></span>
            </p>
        </div>

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
