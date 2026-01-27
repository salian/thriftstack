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
    $dodo = $settings['dodo'] ?? [];
    $paddle = $settings['paddle'] ?? [];
    $stripeEnabled = ($stripe['enabled'] ?? '1') !== '0';
    $razorpayEnabled = ($razorpay['enabled'] ?? '1') !== '0';
    $paypalEnabled = ($paypal['enabled'] ?? '1') !== '0';
    $lemonsqueezyEnabled = ($lemonsqueezy['enabled'] ?? '1') !== '0';
    $dodoEnabled = ($dodo['enabled'] ?? '1') !== '0';
    $paddleEnabled = ($paddle['enabled'] ?? '1') !== '0';
    $appUrl = rtrim((string)config('app.url', ''), '/');
    $gatewayRule = $gatewayRule ?? 'priority';
    $gatewayPriority = $gatewayPriority ?? ['stripe', 'razorpay', 'paypal', 'lemonsqueezy', 'dodo', 'paddle'];
    $providers = [
        'stripe' => 'Stripe',
        'razorpay' => 'Razorpay',
        'paypal' => 'PayPal',
        'lemonsqueezy' => 'Lemon Squeezy',
        'dodo' => 'Dodo Payments',
        'paddle' => 'Paddle',
    ];
    ?>
    <div class="card">
        <h2>Gateway settings</h2>
        <div class="card-muted">
            <h3>Gateway selection</h3>
            <form method="post" action="/super-admin/payment-gateways/rules" class="form">
                <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                <label>
                    <span>Selection rule</span>
                    <div class="checkbox-grid">
                        <label class="checkbox">
                            <input type="radio" name="gateway_rule" value="priority" <?= $gatewayRule === 'priority' ? 'checked' : '' ?>>
                            <span>Use priority order</span>
                        </label>
                        <label class="checkbox">
                            <input type="radio" name="gateway_rule" value="random" <?= $gatewayRule === 'random' ? 'checked' : '' ?>>
                            <span>Random among active gateways</span>
                        </label>
                        <label class="checkbox">
                            <input type="radio" name="gateway_rule" value="failure_rate" <?= $gatewayRule === 'failure_rate' ? 'checked' : '' ?>>
                            <span>Lowest failure rate</span>
                        </label>
                        <label class="checkbox">
                            <input type="radio" name="gateway_rule" value="geo" disabled>
                            <span>Geo-based routing (coming soon)</span>
                        </label>
                    </div>
                </label>
                <label>
                    <span>Priority order</span>
                    <div class="checkbox-grid">
                        <?php
                        $priorityList = array_values($gatewayPriority);
                        $priorityCount = count($providers);
                        for ($i = 0; $i < $priorityCount; $i++) :
                            $selected = $priorityList[$i] ?? array_keys($providers)[$i];
                        ?>
                            <label class="checkbox">
                                <span>Priority <?= $i + 1 ?></span>
                                <select name="gateway_priority[]">
                                    <?php foreach ($providers as $key => $label) : ?>
                                        <option value="<?= e($key) ?>" <?= $selected === $key ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endfor; ?>
                    </div>
                </label>
                <div class="modal-actions">
                    <button type="submit" class="button">Save selection rules</button>
                </div>
            </form>
        </div>
        <div class="tabs" data-tabs>
            <button type="button" class="tab-button is-active" data-tab-button="stripe"
                aria-selected="true" aria-controls="tab-stripe">Stripe</button>
            <button type="button" class="tab-button" data-tab-button="razorpay"
                aria-selected="false" aria-controls="tab-razorpay">Razorpay</button>
            <button type="button" class="tab-button" data-tab-button="paypal"
                aria-selected="false" aria-controls="tab-paypal">PayPal</button>
            <button type="button" class="tab-button" data-tab-button="lemonsqueezy"
                aria-selected="false" aria-controls="tab-lemonsqueezy">Lemon Squeezy</button>
            <button type="button" class="tab-button" data-tab-button="dodo"
                aria-selected="false" aria-controls="tab-dodo">Dodo Payments</button>
            <button type="button" class="tab-button" data-tab-button="paddle"
                aria-selected="false" aria-controls="tab-paddle">Paddle</button>
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
                        <span>Mode</span>
                        <select name="mode">
                            <option value="live" <?= ($paypal['mode'] ?? 'live') === 'live' ? 'selected' : '' ?>>Live</option>
                            <option value="sandbox" <?= ($paypal['mode'] ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                        </select>
                    </label>
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
            <div class="tab-panel" data-tab-panel="dodo" id="tab-dodo" role="tabpanel" hidden>
                <div class="table-header">
                    <div>
                        <h3>Dodo Payments</h3>
                        <p class="muted">Store webhook and API credentials for Dodo Payments.</p>
                    </div>
                    <?php if ($dodoEnabled) : ?>
                        <form method="post" action="/super-admin/payment-gateways/dodo" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="0">
                            <button type="submit" class="button button-ghost">Disable</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (!$dodoEnabled) : ?>
                    <div class="banner banner-warning">
                        <span>Dodo Payments is currently disabled.</span>
                        <form method="post" action="/super-admin/payment-gateways/dodo" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="1">
                            <button type="submit" class="button">Enable Dodo</button>
                        </form>
                    </div>
                <?php endif; ?>
                <form method="post" action="/super-admin/payment-gateways/dodo" class="form">
                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                    <label>
                        <span>Environment</span>
                        <select name="environment">
                            <option value="live_mode" <?= ($dodo['environment'] ?? 'live_mode') === 'live_mode' ? 'selected' : '' ?>>Live</option>
                            <option value="test_mode" <?= ($dodo['environment'] ?? '') === 'test_mode' ? 'selected' : '' ?>>Test</option>
                        </select>
                    </label>
                    <label>
                        <span>API key</span>
                        <input type="text" name="api_key" value="<?= e($dodo['api_key'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Webhook secret</span>
                        <input type="text" name="webhook_secret" value="<?= e($dodo['webhook_secret'] ?? '') ?>">
                    </label>
                    <div class="modal-actions">
                        <button type="submit" class="button">Save Dodo settings</button>
                    </div>
                </form>
                <details class="helper">
                    <summary>How to set up Dodo Payments</summary>
                    <div class="helper-body">
                        <p>Create API keys and webhooks in the Dodo Payments dashboard:</p>
                        <ul>
                            <li><strong>API key</strong>: Settings → API keys.</li>
                            <li><strong>Webhook secret</strong>: Webhooks → create endpoint.</li>
                        </ul>
                        <p>Webhook URL:</p>
                        <div class="helper-copy">
                            <span><?= e($appUrl) ?>/webhooks/dodo</span>
                            <button type="button" class="icon-button" data-copy-text="<?= e($appUrl) ?>/webhooks/dodo" aria-label="Copy webhook URL">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </details>
            </div>
            <div class="tab-panel" data-tab-panel="paddle" id="tab-paddle" role="tabpanel" hidden>
                <div class="table-header">
                    <div>
                        <h3>Paddle</h3>
                        <p class="muted">Store API credentials and Paddle.js client token.</p>
                    </div>
                    <?php if ($paddleEnabled) : ?>
                        <form method="post" action="/super-admin/payment-gateways/paddle" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="0">
                            <button type="submit" class="button button-ghost">Disable</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php if (!$paddleEnabled) : ?>
                    <div class="banner banner-warning">
                        <span>Paddle is currently disabled.</span>
                        <form method="post" action="/super-admin/payment-gateways/paddle" class="form-inline">
                            <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                            <input type="hidden" name="enabled" value="1">
                            <button type="submit" class="button">Enable Paddle</button>
                        </form>
                    </div>
                <?php endif; ?>
                <form method="post" action="/super-admin/payment-gateways/paddle" class="form">
                    <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
                    <label>
                        <span>Environment</span>
                        <select name="environment">
                            <option value="live" <?= ($paddle['environment'] ?? 'live') === 'live' ? 'selected' : '' ?>>Live</option>
                            <option value="sandbox" <?= ($paddle['environment'] ?? '') === 'sandbox' ? 'selected' : '' ?>>Sandbox</option>
                        </select>
                    </label>
                    <label>
                        <span>API key</span>
                        <input type="text" name="api_key" value="<?= e($paddle['api_key'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Paddle.js client token</span>
                        <input type="text" name="client_token" value="<?= e($paddle['client_token'] ?? '') ?>">
                    </label>
                    <label>
                        <span>Endpoint secret</span>
                        <input type="text" name="endpoint_secret" value="<?= e($paddle['endpoint_secret'] ?? '') ?>">
                    </label>
                    <div class="modal-actions">
                        <button type="submit" class="button">Save Paddle settings</button>
                    </div>
                </form>
                <details class="helper">
                    <summary>How to set up Paddle</summary>
                    <div class="helper-body">
                        <p>Collect credentials from Paddle Billing:</p>
                        <ul>
                            <li><strong>API key</strong>: Developer Tools → API Keys.</li>
                            <li><strong>Client token</strong>: Paddle.js settings.</li>
                            <li><strong>Endpoint secret</strong>: Webhooks → endpoint secret key.</li>
                        </ul>
                        <p>Webhook URL:</p>
                        <div class="helper-copy">
                            <span><?= e($appUrl) ?>/webhooks/paddle</span>
                            <button type="button" class="icon-button" data-copy-text="<?= e($appUrl) ?>/webhooks/paddle" aria-label="Copy webhook URL">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </div>
</section>
