<section class="hero">
    <h1>Dashboard</h1>
    <p>Protected area placeholder for authenticated users.</p>
    <div class="card">
        <p>Authenticated as <?= e(Auth::user()['email'] ?? 'unknown') ?>.</p>
    </div>

    <div class="card">
        <div class="table-header">
            <h2>Tasks</h2>
            <span class="muted">Placeholder for task lists.</span>
        </div>
        <div class="tabs" data-tabs>
            <button type="button" class="tab-button is-active" data-tab-button="my-tasks"
                aria-selected="true" aria-controls="tab-my-tasks">My Tasks</button>
            <button type="button" class="tab-button" data-tab-button="all-tasks"
                aria-selected="false" aria-controls="tab-all-tasks">All Tasks</button>
        </div>
        <div class="tab-panels">
            <div class="tab-panel is-active" data-tab-panel="my-tasks" id="tab-my-tasks" role="tabpanel">
                <div class="card-muted">
                    <p class="muted">No tasks yet. Your assigned tasks will appear here.</p>
                </div>
            </div>
            <div class="tab-panel" data-tab-panel="all-tasks" id="tab-all-tasks" role="tabpanel" hidden>
                <div class="card-muted">
                    <p class="muted">No tasks yet. Workspace tasks will appear here.</p>
                </div>
            </div>
        </div>
    </div>
</section>
