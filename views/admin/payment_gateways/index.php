<section class="page-section">
    <h1>App Super Admin</h1>

    <?php require __DIR__ . '/../super_nav.php'; ?>

    <h2>Payment Gateways</h2>
    <p>Configure webhook secrets and provider connections.</p>

    <?php
    $settings = $settings ?? [];
    $stripe = $settings['stripe'] ?? [];
    $razorpay = $settings['razorpay'] ?? [];
    $paypal = $settings['paypal'] ?? [];
    $lemonsqueezy = $settings['lemonsqueezy'] ?? [];
    $stripeEnabled = ($stripe['enabled'] ?? '1') !== '0';
    $razorpayEnabled = ($razorpay['enabled'] ?? '1') !== '0';
    $paypalEnabled = ($paypal['enabled'] ?? '1') !== '0';
    $lemonsqueezyEnabled = ($lemonsqueezy['enabled'] ?? '1') !== '0';
    $appUrl = rtrim((string)config('app.url', ''), '/');
    ?>
    <div class="card">
        <h2>Gateway settings</h2>
        <div class="tabs" data-tabs>
            <button type="button" class="tab-button is-active" data-tab-button="stripe"
                aria-selected="true" aria-controls="tab-stripe">Stripe</button>
            <button type="button" class="tab-button" data-tab-button="razorpay"
                aria-selected="false" aria-controls="tab-razorpay">Razorpay</button>
            <button type="button" class="tab-button" data-tab-button="paypal"
                aria-selected="false" aria-controls="tab-paypal">PayPal</button>
            <button type="button" class="tab-button" data-tab-button="lemonsqueezy"
                aria-selected="false" aria-controls="tab-lemonsqueezy">Lemon Squeezy</button>
        </div>

        <div class="tab-panels">
            <div class="tab-panel is-active" data-tab-panel="stripe" id="tab-stripe" role="tabpanel">
                <div class="table-header">
                    <div>
                        <h3>Stripe</h3>
                <p class="muted">Store webhook and API credentials for Stripe.</p>
                    </div>
                    <?php if ($stripeEnabled) : ?>
                        <form method="post" action="/super-admin/payment-gateways/stripe" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="0">
                            <button type="submit" class="button button-ghost">Disable</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (!$stripeEnabled) : ?>
                    <div class="banner banner-warning">
                        <span>Stripe is currently disabled.</span>
                        <form method="post" action="/super-admin/payment-gateways/stripe" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="1">
                            <button type="submit" class="button">Enable Stripe</button>
                        </form>
                    </div>
                <?php endif; ?>
                <form method="post" action="/super-admin/payment-gateways/stripe" class="form">
                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                    <label>
                        <span>Publishable key</span>
                        <input type="text" name="publishable_key" value="<?= e($stripe['publishable_key'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Secret key</span>
                        <input type="text" name="secret_key" value="<?= e($stripe['secret_key'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Webhook secret</span>
                        <input type="text" name="webhook_secret" value="<?= e($stripe['webhook_secret'] ?? '') ?>">
                    </label>
                    <div class="modal-actions">
                        <button type="submit" class="button">Save Stripe settings</button>
                    </div>
                </form>
                <details class="helper">
                    <summary>How to set up Stripe</summary>
                    <div class="helper-body">
                        <p>Get credentials from the Stripe Dashboard:</p>
                        <ul>
                            <li><strong>Publishable key</strong>: Developers → API keys → Publishable key.</li>
                            <li><strong>Secret key</strong>: Developers → API keys → Secret key.</li>
                            <li><strong>Webhook secret</strong>: Developers → Webhooks → select endpoint → Signing secret.</li>
                        </ul>
                        <p>Webhook URL:</p>
                        <div class="helper-copy">
                            <span><?= e($appUrl) ?>/webhooks/stripe</span>
                            <button type="button" class="icon-button" data-copy-text="<?= e($appUrl) ?>/webhooks/stripe" aria-label="Copy webhook URL">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p>Use your live keys for production. Create a webhook endpoint pointing to the URL above.</p>
                    </div>
                </details>
            </div>
            <div class="tab-panel" data-tab-panel="razorpay" id="tab-razorpay" role="tabpanel" hidden>
                <div class="table-header">
                    <div>
                        <h3>Razorpay</h3>
                <p class="muted">Store webhook and API credentials for Razorpay.</p>
                    </div>
                    <?php if ($razorpayEnabled) : ?>
                        <form method="post" action="/super-admin/payment-gateways/razorpay" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="0">
                            <button type="submit" class="button button-ghost">Disable</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (!$razorpayEnabled) : ?>
                    <div class="banner banner-warning">
                        <span>Razorpay is currently disabled.</span>
                        <form method="post" action="/super-admin/payment-gateways/razorpay" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="1">
                            <button type="submit" class="button">Enable Razorpay</button>
                        </form>
                    </div>
                <?php endif; ?>
                <form method="post" action="/super-admin/payment-gateways/razorpay" class="form">
                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                    <label>
                        <span>Key ID</span>
                        <input type="text" name="key_id" value="<?= e($razorpay['key_id'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Key secret</span>
                        <input type="text" name="key_secret" value="<?= e($razorpay['key_secret'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Webhook secret</span>
                        <input type="text" name="webhook_secret" value="<?= e($razorpay['webhook_secret'] ?? '') ?>">
                    </label>
                    <div class="modal-actions">
                        <button type="submit" class="button">Save Razorpay settings</button>
                    </div>
                </form>
                <details class="helper">
                    <summary>How to set up Razorpay</summary>
                    <div class="helper-body">
                        <p>Get credentials from Razorpay Dashboard:</p>
                        <ul>
                            <li><strong>Key ID</strong> and <strong>Key secret</strong>: Settings → API Keys.</li>
                            <li><strong>Webhook secret</strong>: Settings → Webhooks → add endpoint → Secret.</li>
                        </ul>
                        <p>Webhook URL:</p>
                        <div class="helper-copy">
                            <span><?= e($appUrl) ?>/webhooks/razorpay</span>
                            <button type="button" class="icon-button" data-copy-text="<?= e($appUrl) ?>/webhooks/razorpay" aria-label="Copy webhook URL">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p>Create a webhook endpoint pointing to the URL above and copy the secret.</p>
                    </div>
                </details>
            </div>
            <div class="tab-panel" data-tab-panel="paypal" id="tab-paypal" role="tabpanel" hidden>
                <div class="table-header">
                    <div>
                        <h3>PayPal</h3>
                <p class="muted">Store webhook and API credentials for PayPal.</p>
                    </div>
                    <?php if ($paypalEnabled) : ?>
                        <form method="post" action="/super-admin/payment-gateways/paypal" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="0">
                            <button type="submit" class="button button-ghost">Disable</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (!$paypalEnabled) : ?>
                    <div class="banner banner-warning">
                        <span>PayPal is currently disabled.</span>
                        <form method="post" action="/super-admin/payment-gateways/paypal" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="1">
                            <button type="submit" class="button">Enable PayPal</button>
                        </form>
                    </div>
                <?php endif; ?>
                <form method="post" action="/super-admin/payment-gateways/paypal" class="form">
                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                    <label>
                        <span>Client ID</span>
                        <input type="text" name="client_id" value="<?= e($paypal['client_id'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Client secret</span>
                        <input type="text" name="client_secret" value="<?= e($paypal['client_secret'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Webhook ID</span>
                        <input type="text" name="webhook_id" value="<?= e($paypal['webhook_id'] ?? '') ?>">
                    </label>
                    <div class="modal-actions">
                        <button type="submit" class="button">Save PayPal settings</button>
                    </div>
                </form>
                <details class="helper">
                    <summary>How to set up PayPal</summary>
                    <div class="helper-body">
                        <p>Get credentials from PayPal Developer Dashboard:</p>
                        <ul>
                            <li><strong>Client ID</strong> and <strong>Client secret</strong>: My Apps & Credentials.</li>
                            <li><strong>Webhook ID</strong>: Webhooks → add endpoint → copy Webhook ID.</li>
                        </ul>
                        <p>Webhook URL:</p>
                        <div class="helper-copy">
                            <span><?= e($appUrl) ?>/webhooks/paypal</span>
                            <button type="button" class="icon-button" data-copy-text="<?= e($appUrl) ?>/webhooks/paypal" aria-label="Copy webhook URL">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p>Create a webhook endpoint pointing to the URL above for live or sandbox.</p>
                    </div>
                </details>
            </div>
            <div class="tab-panel" data-tab-panel="lemonsqueezy" id="tab-lemonsqueezy" role="tabpanel" hidden>
                <div class="table-header">
                    <div>
                        <h3>Lemon Squeezy</h3>
                <p class="muted">Store webhook and API credentials for Lemon Squeezy.</p>
                    </div>
                    <?php if ($lemonsqueezyEnabled) : ?>
                        <form method="post" action="/super-admin/payment-gateways/lemonsqueezy" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="0">
                            <button type="submit" class="button button-ghost">Disable</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (!$lemonsqueezyEnabled) : ?>
                    <div class="banner banner-warning">
                        <span>Lemon Squeezy is currently disabled.</span>
                        <form method="post" action="/super-admin/payment-gateways/lemonsqueezy" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="1">
                            <button type="submit" class="button">Enable Lemon Squeezy</button>
                        </form>
                    </div>
                <?php endif; ?>
                <form method="post" action="/super-admin/payment-gateways/lemonsqueezy" class="form">
                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                    <label>
                        <span>API key</span>
                        <input type="text" name="api_key" value="<?= e($lemonsqueezy['api_key'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Store ID</span>
                        <input type="text" name="store_id" value="<?= e($lemonsqueezy['store_id'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Webhook secret</span>
                        <input type="text" name="webhook_secret" value="<?= e($lemonsqueezy['webhook_secret'] ?? '') ?>">
                    </label>
                    <div class="modal-actions">
                        <button type="submit" class="button">Save Lemon Squeezy settings</button>
                    </div>
                </form>
                <details class="helper">
                    <summary>How to set up Lemon Squeezy</summary>
                    <div class="helper-body">
                        <p>Get credentials from Lemon Squeezy:</p>
                        <ul>
                            <li><strong>API key</strong>: Settings → API → Create key.</li>
                            <li><strong>Store ID</strong>: Settings → Stores → copy ID.</li>
                            <li><strong>Webhook secret</strong>: Webhooks → add endpoint → Signing secret.</li>
                        </ul>
                        <p>Webhook URL:</p>
                        <div class="helper-copy">
                            <span><?= e($appUrl) ?>/webhooks/lemonsqueezy</span>
                            <button type="button" class="icon-button" data-copy-text="<?= e($appUrl) ?>/webhooks/lemonsqueezy" aria-label="Copy webhook URL">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p>Create a webhook endpoint pointing to the URL above.</p>
                    </div>
                </details>
            </div>
        </div>
    </div>
</section>
