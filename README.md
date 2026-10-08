# Warexo Connector for Shopware 6

The Shopware-side connector for [Warexo](https://www.warexo.de/). It extends Shopware with the data structures, API access and storefront behaviour required for exchanging catalogue and order data with Warexo.

## Features

- Warexo product and category data, including sorting and cross-sales-channel canonical URLs
- Product selection lists with text, dropdown, colour or image choices and optional price surcharges
- Optional decimal stock and purchase quantities throughout the product page, cart and order
- Remaining-value support for individual promotion codes
- CMS blocks for product options and imported GPSR manufacturer information
- Category icons and downloadable product media in the storefront
- Reusable API client for plugins communicating with Warexo

Warexo initiates the data exchange through a Shopware integration. Warexo API credentials are optional and are only needed when a plugin uses the API client to call Warexo.

## Compatibility

| Shopware | Plugin version | [Shopware Plugin Core](https://github.com/aggrosoft/shopware-plugin-core) | Git branch |
| --- | --- | --- | --- |
| 6.7 | 4.x | 4.x | `main` |
| 6.6 | 3.x | 3.x | `6.6` |

The connector requires [Aggrosoft Shopware Plugin Core](https://github.com/aggrosoft/shopware-plugin-core); its compatible version is declared in `composer.json`. Use the latest release from the matching major version. Plugin 3.x and 4.x are not interchangeable between these Shopware versions.

## Installation and connection

1. Download the correct ZIP from [GitHub Releases](https://github.com/warexo/shopware-plugin/releases).
2. In Shopware Administration, open **Extensions > My extensions**, upload the ZIP, then install and activate **Warexo Connector**.
3. Under **Settings > System > Integrations**, create an administrator integration for Warexo and copy its access ID and secret access key.
4. Enter the Shopware URL and integration credentials in Warexo under **Systemverwaltung > Schnittstellen > Plugins > Shopware**.

See the [Shopware 6 connector setup](https://docs.warexo.de/Installation/connector.html#shopware-6) for the complete connection, initialisation and import procedure.

## Configuration

Open **Extensions > My extensions > Warexo Connector > Configure**. The setting can be defined globally or for an individual sales channel.

| Option | Default | Effect |
| --- | --- | --- |
| **Activate decimal numbers for stock** | Off | Enables Warexo stock, minimum purchase, maximum purchase and purchase-step values with up to three decimal places. These values are used on product pages and during cart, stock and order processing for products carrying Warexo quantity data. |

Leave this option disabled when the shop sells products only in whole units.

### Stock updates after orders

Placing an order immediately reduces the stock, including while the order is waiting to be imported by Warexo. This also works with Shopware's stock management disabled:

```yaml
shopware:
  stock:
    enable_stock_management: false
```

When `enable_stock_management: false`, the connector uses Shopware's order stock lifecycle to handle deductions, quantity changes and cancellation. When the setting is `true`, Shopware handles these changes itself; the connector does not deduct stock again. The connector keeps the decimal stock in step with these stock changes and immediately invalidates affected product pages and listings in both modes.

Warexo must send the current available stock as an absolute value through the Sync API. For example, stock 5 becomes 3 after an order for 2; a subsequent Warexo update with `stock: 3` leaves the stock at 3. Exported values must account for the relevant orders; an outdated stock snapshot can overwrite newer local deductions.

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

$created = $this->warexoApi->request('POST', 'entity/product', [
    'json' => [
        '__customPrimaryKey' => 'sku',
        'sku' => 'MY-PLUGIN-UNIQUE-SKU',
        'title' => 'Example product',
        'price' => 1.23,
        'active' => false,
    ],
])->toArray();

$response = $this->warexoApi->request('PATCH', 'entity/product/123', [
    'json' => ['title' => 'Updated title'],
]);
```

`__customPrimaryKey` names the field used to identify an existing entity, not the key's value. Repeating the creation request with the same SKU updates that product. Use a unique SKU for a new product. The tenant context is sent in the `X-Client-Id` header; `client` is not a product field.

The client accepts relative endpoint paths and Symfony HttpClient request options, and returns a `ResponseInterface`. It logs in lazily and shares access tokens across requests and workers through the `warexo.api_token.cache` pool. The pool uses Shopware's configured application cache backend in production and a filesystem cache in development so tokens also persist across development requests. JWT tokens expire from the cache 30 seconds before their `exp` time; tokens without a usable expiry are cached for five minutes. Cache keys separate API URLs, credentials, client IDs and sales-channel scopes without exposing credentials.

HTTP 401 invalidates the rejected token, logs in again and retries once. If another worker already refreshed the token, that replacement is reused. A second HTTP 401 removes the replacement from the cache. Changes to any API setting invalidate the affected sales channel's tokens; global changes also invalidate tokens that may inherit those settings. Other plugin settings do not invalidate tokens. Call `invalidateToken($salesChannelId)` to invalidate a sales channel explicitly, or `invalidateToken()` to invalidate all Warexo tokens. Requests use the `X-Client-Id` header, a 30-second timeout and no redirects.

Missing or invalid configuration and missing login tokens throw `RuntimeException`; invalid endpoint paths throw `InvalidArgumentException`. Symfony HttpClient exceptions propagate for transport failures and when reading unsuccessful responses with `toArray()` or `getContent()`. For responses without a body (e.g. HTTP 204), use `getStatusCode()`. Requests are not retried for transport errors or server errors.

See the [Warexo REST API documentation](https://docs.warexo.de/Daten-Im-Export/api.html) for available endpoints and payloads.

## Further information

- [Warexo website](https://www.warexo.de/)
- [Warexo documentation](https://docs.warexo.de/)
- [Webshop connector documentation](https://docs.warexo.de/Installation/connector.html)
