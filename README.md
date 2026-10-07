# Warexo API client for Shopware 6.6

Open **Extensions > My extensions > Warexo Connector > Configure**. The API settings are optional and are only needed when a plugin calls Warexo.

The **Warexo API** card provides **API URL** (including `/api/v1`), **Username**, **Password** and **Client ID**. Enter the credentials of a Warexo user and the Warexo tenant's client ID. These settings can also be overridden per sales channel; omitted overrides inherit Shopware's global configuration.

## Using the Warexo API from another plugin

Inject `Warexo\Service\WarexoApiClient` into your service. Declare the Warexo plugin as a dependency of the consuming plugin so its service is available.

```xml
<service id="MyPlugin\Service\WarexoProducts">
    <argument type="service" id="Warexo\Service\WarexoApiClient"/>
</service>
```

```php
use Warexo\Service\WarexoApiClient;

public function __construct(private readonly WarexoApiClient $warexoApi) {}

// Use a sales channel's credentials, or omit salesChannelId for the global settings.
$products = $this->warexoApi->request('GET', 'entity/product', [
    'query' => ['limit' => 10, 'fields' => ['id', 'sku']],
], salesChannelId: $salesChannelId)->toArray();

$response = $this->warexoApi->request('PATCH', 'entity/product/123', [
    'json' => ['title' => 'Updated title'],
]);
```

The client accepts relative endpoint paths and Symfony HttpClient request options, and returns a `ResponseInterface`. It logs in lazily and shares access tokens across requests and workers through the `warexo.api_token.cache` pool. The pool uses Shopware's configured application cache backend in production and a filesystem cache in development so tokens also persist across development requests. JWT tokens expire from the cache 30 seconds before their `exp` time; tokens without a usable expiry are cached for five minutes. Cache keys separate API URLs, credentials, client IDs and sales-channel scopes without exposing credentials.

HTTP 401 invalidates the rejected token, logs in again and retries once. If another worker already refreshed the token, that replacement is reused. A second HTTP 401 removes the replacement from the cache. Changes to any API setting invalidate the affected sales channel's tokens; global changes also invalidate tokens that may inherit those settings. Other plugin settings do not invalidate tokens. Call `invalidateToken($salesChannelId)` to invalidate a sales channel explicitly, or `invalidateToken()` to invalidate all Warexo tokens. Requests use the `X-Client-Id` header, a 30-second timeout and no redirects.

Missing or invalid configuration and missing login tokens throw `RuntimeException`; invalid endpoint paths throw `InvalidArgumentException`. Symfony HttpClient exceptions propagate for transport failures and when reading unsuccessful responses with `toArray()` or `getContent()`. For responses without a body (e.g. HTTP 204), use `getStatusCode()`. Requests are not retried for transport errors or server errors.

See the [Warexo REST API documentation](https://docs.warexo.de/Daten-Im-Export/api.html) for available endpoints and payloads.
