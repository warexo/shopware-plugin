# Dockware compatibility checks

`tier-tax-matrix.sh` exercises the real Store API cart pipeline. It covers the
decimal tier boundary, a tier-only cart, a mixed normal/decimal cart, and a
second calculation pass to detect processor-order drift.

The default deterministic fixture IDs are:

- `c0dec0dec0dec0dec0dec0dec0de5001`: decimal product, 19% VAT, EUR 0.89 below
  quantity 10 and EUR 0.79 from quantity 10
- `c0dec0dec0dec0dec0dec0dec0de4001`: normal product, 19% VAT, EUR 99.99
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
same custom-form surcharge in the persisted cart pipeline.

`promotion-tier-matrix.sh` activates the deterministic automatic 10% promotion
fixture, verifies the historical EUR 99.99 decimal case, then verifies the low
tier alone and in a mixed cart. It restores the promotion's original state.
