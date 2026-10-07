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

The client accepts relative endpoint paths and Symfony HttpClient request options, and returns a `ResponseInterface`. It logs in lazily, reuses the access token for the current configuration within the service instance, and logs in again and retries once on HTTP 401. Changing the URL, credentials or client ID discards the previous token. Tokens are not persisted. Requests use the `X-Client-Id` header, a 30-second timeout and no redirects.

Missing or invalid configuration and missing login tokens throw `RuntimeException`; invalid endpoint paths throw `InvalidArgumentException`. Symfony HttpClient exceptions propagate for transport failures and when reading unsuccessful responses with `toArray()` or `getContent()`. For responses without a body (e.g. HTTP 204), use `getStatusCode()`. Requests are not retried for transport errors or server errors.

See the [Warexo REST API documentation](https://docs.warexo.de/Daten-Im-Export/api.html) for available endpoints and payloads.
