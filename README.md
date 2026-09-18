# Warexo Connector for Shopware 6

The Shopware-side connector for [Warexo](https://www.warexo.de/). It extends Shopware with the data structures, API access and storefront behaviour required for exchanging catalogue and order data with Warexo.

## Features

- Warexo product and category data, including sorting and cross-sales-channel canonical URLs
- Product selection lists with text, dropdown, colour or image choices and optional price surcharges
- Optional decimal stock and purchase quantities throughout the product page, cart and order
- Remaining-value support for individual promotion codes
- CMS blocks for product options and imported GPSR manufacturer information
- Category icons and downloadable product media in the storefront

Warexo initiates the data exchange through a Shopware integration. The plugin does not require Warexo credentials in its own configuration.

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

## Further information

- [Warexo website](https://www.warexo.de/)
- [Warexo documentation](https://docs.warexo.de/)
- [Webshop connector documentation](https://docs.warexo.de/Installation/connector.html)
