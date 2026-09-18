# Dockware compatibility checks

`tier-tax-matrix.sh` exercises the real Store API cart pipeline. It covers the
decimal tier boundary, a tier-only cart, a mixed normal/decimal cart, and a
second calculation pass to detect processor-order drift.

The default deterministic fixture IDs are:

- `c0dec0dec0dec0dec0dec0dec0de5001`: decimal product, 19% VAT, EUR 0.99 base,
  EUR 0.89 below quantity 10, and EUR 0.79 from quantity 10. The distinct base
  makes an ignored tier immediately visible.
- `c0dec0dec0dec0dec0dec0dec0de4001`: normal product, 19% VAT, EUR 99.99
- `c0dec0dec0dec0dec0dec0dec0de5005`: standard integer product without a
  Warexo extension, with the same EUR 0.99/EUR 0.89/EUR 0.79 price structure
- `c0dec0dec0dec0dec0dec0dec0de5010`: decimal product with a 0.001 purchase
  step and adjacent ranges at EUR 0.99 for 1-4.999, EUR 0.85 from 5, EUR 0.69
  from 10, and EUR 0.59 from 45. This catches tier selectors that confuse
  Shopware's calculated range upper bounds with tier start quantities.
- `019f41cda01d71188864657fdda6b480`: CMS section containing a custom form
  option with a EUR 10.00 unit surcharge

Run against the current Dockware container:

```bash
DOCKWARE_CONTAINER=<container-id> tests/Compatibility/tier-tax-matrix.sh
```

To run the same assertions with every on/off combination of CMS Extras,
Promotion Surcharges, and Digital Delivery Costs, install all three plugins and
run:

```bash
DOCKWARE_CONTAINER=<container-id> tests/Compatibility/plugin-combination-matrix.sh
```

The combination runner restores the original activation state on exit.

The access key is discovered from Dockware's database. It can be overridden
with `SHOPWARE_ACCESS_KEY`; fixture IDs and `SHOPWARE_BASE_URL` are also
configurable through environment variables of the same names. When CMS Extras
is active, the script additionally verifies its AJAX price endpoint and the
same custom-form surcharge in the persisted cart pipeline. AJAX assertions
cover fractional totals on both sides of the tier boundary and compare the
currency-rounded gross, net, and tax values with the cart.

`promotion-tier-matrix.sh` activates the deterministic automatic 10% promotion
fixture, verifies the historical EUR 99.99 decimal case, then verifies the low
tier alone and in a mixed cart. It restores the promotion's original state.
