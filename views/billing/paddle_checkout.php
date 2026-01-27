<section class="page-section">
    <h1>Paddle checkout</h1>
    <p>Opening the hosted Paddle checkout...</p>
    <button type="button" class="button" id="paddle-open">Open checkout</button>
</section>

<script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
<script>
  (function () {
    const clientToken = <?= json_encode($clientToken ?? '') ?>;
    const environment = <?= json_encode($environment ?? 'live') ?>;
    const priceId = <?= json_encode($priceId ?? '') ?>;
    const subscriptionId = <?= json_encode((string)($subscriptionId ?? 0)) ?>;
    const changeId = <?= json_encode((string)($changeId ?? 0)) ?>;
    const planId = <?= json_encode((string)($planId ?? 0)) ?>;
    const successUrl = <?= json_encode($successUrl ?? '') ?>;
    const cancelUrl = <?= json_encode($cancelUrl ?? '') ?>;
    const customerEmail = <?= json_encode($customerEmail ?? '') ?>;

    if (!window.Paddle || !clientToken || !priceId) {
      return;
    }

    const openCheckout = () => {
      window.Paddle.Checkout.open({
        items: [{ priceId: priceId, quantity: 1 }],
        customer: customerEmail ? { email: customerEmail } : undefined,
        customData: {
          subscription_id: subscriptionId,
          change_id: changeId,
          plan_id: planId
        },
        settings: {
          successUrl: successUrl,
          cancelUrl: cancelUrl
        }
      });
    };

    window.Paddle.Environment.set(environment === 'sandbox' ? 'sandbox' : 'production');
    window.Paddle.Initialize({ token: clientToken });

    const button = document.getElementById('paddle-open');
    if (button) {
      button.addEventListener('click', openCheckout);
    }

    openCheckout();
  })();
</script>
