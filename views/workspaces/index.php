<section class="page-section">
    <h1>Teams</h1>
    <p>Manage your teams, members, and invites.</p>

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

    <div class="tabs" data-tabs>
        <button type="button" class="tab-button is-active" data-tab-button="team"
            aria-selected="true" aria-controls="tab-team">Team Members</button>
        <button type="button" class="tab-button" data-tab-button="workspaces"
            aria-selected="false" aria-controls="tab-workspaces">Workspaces</button>
    </div>

    <div class="tab-panels">
        <div class="tab-panel" data-tab-panel="workspaces" id="tab-workspaces" role="tabpanel" hidden>
            <div class="card">
                <div class="table-header">
                    <h2>Your workspaces</h2>
                    <button type="button" class="button" data-modal-open="workspace-create">Add a workspace</button>
                </div>
                <?php if (empty($workspaces)) : ?>
                    <p>No workspaces yet. Create one to get started.</p>
                <?php else : ?>
                    <?php $returnTo = $_SERVER['REQUEST_URI'] ?? '/teams'; ?>
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
                                            <form method="post" action="/teams/update" class="workspace-edit-form"
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
                                            <form method="post" action="/teams/switch">
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
        </div>

        <div class="tab-panel is-active" data-tab-panel="team" id="tab-team" role="tabpanel">
            <?php if (!empty($currentWorkspace)) : ?>
                <div class="card">
                    <div class="table-header">
                        <h2>Team Members</h2>
                        <?php if (!empty($canManage)) : ?>
                            <button type="button" class="button" data-modal-open="team-invite">Add a Team Member</button>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($members)) : ?>
                        <p>No members yet.</p>
                    <?php else : ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
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
                                        <td>Active</td>
                                        <?php if (!empty($canManage)) : ?>
                                            <td>
                                                <form method="post" action="/teams/members/role" class="form-inline">
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
                                <?php foreach ($invites as $invite) : ?>
                                    <?php if (!empty($invite['accepted_at'])) : ?>
                                        <?php continue; ?>
                                    <?php endif; ?>
                                    <tr class="is-muted">
                                        <td><span class="badge">Invited</span></td>
                                        <td><?= e($invite['email'] ?? '') ?></td>
                                        <td><?= e($invite['role'] ?? '') ?></td>
                                        <td>Invited</td>
                                        <?php if (!empty($canManage)) : ?>
                                            <td></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <div class="card">
                    <p>Create a workspace to manage team members.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($currentWorkspace) && !empty($canManage)) : ?>
        <dialog class="modal" id="team-invite">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2>Add a team member</h2>
                        <p class="modal-subtitle">Invite someone to join this workspace.</p>
                    </div>
                    <button type="button" class="icon-button" data-modal-close>
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
                <form method="post" action="/teams/invites" class="form">
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
                    <div class="modal-actions">
                        <button type="button" class="button button-ghost" data-modal-close>Cancel</button>
                        <button type="submit" class="button">Send invite</button>
                    </div>
                </form>
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
        </dialog>
    <?php endif; ?>

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
            <form method="post" action="/teams" class="form">
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

</section>
