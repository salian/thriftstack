# API Authentication

This API supports Bearer tokens scoped per workspace. Tokens are created from the Settings → API Tokens page.

## Bearer Token

Send the token in the `Authorization` header:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://example.com/api/credits/balance
```

## Scopes

- `credits.read` — required for balance queries.
- `credits.consume` — required to consume credits.

## Examples

### Create a token (UI)

1. Go to `/settings/api-tokens`
2. Add a name and scopes (e.g., `credits.read, credits.consume`)
3. Copy the token shown after creation

### Consume credits

```bash
curl -X POST https://example.com/api/credits/consume \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d "credits=5" \
  -d "usage_type=ai.chat" \
  -d "metadata={\"model\":\"gpt-4o-mini\"}"
```

Response:

```json
{
  "ok": true,
  "balance_after": 120
}
```

### Fetch balance

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://example.com/api/credits/balance
```

Response:

```json
{
  "ok": true,
  "balance": 120
}
```

## Errors

- `401` with `{ "ok": false, "error": "Missing API token." }` if no Bearer token is provided.
- `401` with `{ "ok": false, "error": "Invalid API token." }` for invalid tokens.
- `401` with `{ "ok": false, "error": "API token expired." }` for expired tokens.
- `403` with `{ "ok": false, "error": { "code": "forbidden", "message": "Missing credits.read scope." } }` when scope is missing.
