#!/usr/bin/env bash

set -euo pipefail

CONTAINER="${DOCKWARE_CONTAINER:-}"
ACCESS_KEY="${SHOPWARE_ACCESS_KEY:-}"
BASE_URL="${SHOPWARE_BASE_URL:-http://127.0.0.1}"
PROMOTION_ID="${PROMOTION_ID:-c0dec0dec0dec0dec0dec0dec0de1101}"
TIER_PRODUCT_ID="${TIER_PRODUCT_ID:-c0dec0dec0dec0dec0dec0dec0de5001}"
DECIMAL_PRODUCT_ID="${DECIMAL_PRODUCT_ID:-c0dec0dec0dec0dec0dec0dec0de4003}"
CUSTOM_FORM_ID="${CUSTOM_FORM_ID:-019f41cda01d71188864657fdda6b480}"

if [[ -z "$CONTAINER" ]]; then
    CONTAINER="$(docker ps --filter 'name=shopware' --format '{{.ID}}' | head -n 1)"
fi

if [[ -z "$CONTAINER" ]]; then
    echo 'No running Dockware container found. Set DOCKWARE_CONTAINER.' >&2
    exit 1
fi

if [[ -z "$ACCESS_KEY" ]]; then
    ACCESS_KEY="$(docker exec -u root "$CONTAINER" mysql -uroot -proot shopware -N -e \
        "SELECT access_key FROM sales_channel WHERE active = 1 AND access_key IS NOT NULL LIMIT 1" 2>/dev/null)"
fi

initial_active="$(docker exec -u root "$CONTAINER" mysql -uroot -proot shopware -N -e \
    "SELECT active FROM promotion WHERE id = UNHEX('$PROMOTION_ID')" 2>/dev/null)"

if [[ -z "$initial_active" ]]; then
    echo "Promotion fixture $PROMOTION_ID is missing." >&2
    exit 1
fi

set_promotion_active() {
    local active="$1"

    docker exec -u root "$CONTAINER" mysql -uroot -proot shopware -e \
        "UPDATE promotion SET active = $active, updated_at = NOW(3) WHERE id = UNHEX('$PROMOTION_ID')" 2>/dev/null
    docker exec "$CONTAINER" php bin/console cache:clear --no-warmup >/dev/null
}

restore_promotion() {
    set_promotion_active "$initial_active"
}
trap restore_promotion EXIT

assert_number() {
    local json="$1"
    local filter="$2"
    local expected="$3"
    local label="$4"

    if ! jq -e --argjson expected "$expected" \
        "(($filter) - \$expected) | if . < 0 then -. else . end < 0.00001" \
        >/dev/null <<<"$json"; then
        echo "FAIL: $label (expected $expected, got $(jq -r "$filter" <<<"$json"))" >&2
        return 1
    fi
}

request_cart() {
    local token="$1"
    local payload="$2"

    docker exec "$CONTAINER" curl -fsS -X POST "$BASE_URL/store-api/checkout/cart/line-item" \
        -H "sw-access-key: $ACCESS_KEY" \
        -H "sw-context-token: $token" \
        -H 'Content-Type: application/json' \
        --data "$payload"
}

assert_promoted_cart() {
    local label="$1"
    local payload="$2"
    local expected_discount="$3"
    local expected_total="$4"
    local expected_tax="$5"
    local expected_net="$6"
    local token="promotion-tier-${label}-$(date +%s%N)"
    local cart

    cart="$(request_cart "$token" "$payload")"
    assert_number "$cart" '[.lineItems[] | select(.type == "promotion") | .price.totalPrice] | add' "$expected_discount" "$label discount"
    assert_number "$cart" '.price.totalPrice' "$expected_total" "$label cart total"
    assert_number "$cart" '[.price.calculatedTaxes[].tax] | add' "$expected_tax" "$label cart tax"
    assert_number "$cart" '.price.netPrice' "$expected_net" "$label cart net"

    cart="$(docker exec "$CONTAINER" curl -fsS "$BASE_URL/store-api/checkout/cart" \
        -H "sw-access-key: $ACCESS_KEY" -H "sw-context-token: $token")"
    assert_number "$cart" '[.lineItems[] | select(.type == "promotion") | .price.totalPrice] | add' "$expected_discount" "$label recalculated discount"
    assert_number "$cart" '.price.totalPrice' "$expected_total" "$label recalculated cart total"
}

set_promotion_active 1

assert_promoted_cart decimal \
    "{\"items\":[{\"id\":\"decimal\",\"referencedId\":\"$DECIMAL_PRODUCT_ID\",\"type\":\"product\",\"quantity\":1000}]}" \
    -10 89.99 14.36 75.63
assert_promoted_cart tier \
    "{\"items\":[{\"id\":\"tier\",\"referencedId\":\"$TIER_PRODUCT_ID\",\"type\":\"product\",\"quantity\":10000}]}" \
    -0.79 7.11 1.13 5.98
assert_promoted_cart mixed \
    "{\"items\":[{\"id\":\"decimal\",\"referencedId\":\"$DECIMAL_PRODUCT_ID\",\"type\":\"product\",\"quantity\":1000},{\"id\":\"tier\",\"referencedId\":\"$TIER_PRODUCT_ID\",\"type\":\"product\",\"quantity\":10000}]}" \
    -10.79 97.1 15.51 81.59

if docker exec "$CONTAINER" php bin/console plugin:list --format=json \
    | jq -e '.[] | select(.name == "AggroCmsExtrasPlugin" and .active == true)' >/dev/null; then
    assert_promoted_cart cms-custom-form \
        "{\"items\":[{\"id\":\"tier-custom\",\"referencedId\":\"$TIER_PRODUCT_ID\",\"type\":\"product\",\"quantity\":10000,\"payload\":{\"customFormId\":\"$CUSTOM_FORM_ID\",\"customFormFields\":{\"customFinish\":{\"value\":\"Premium (+10.00)\"}}}}]}" \
        -10.79 97.11 15.51 81.6
fi

echo 'Promotion tier matrix passed.'
