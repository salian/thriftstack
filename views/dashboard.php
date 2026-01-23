<section class="hero">
    <h1>Dashboard</h1>
    <p>Protected area placeholder for authenticated users.</p>
    <div class="card">
        <p>Authenticated as <?= e(Auth::user()['email'] ?? 'unknown') ?>.</p>
    </div>
</section>
