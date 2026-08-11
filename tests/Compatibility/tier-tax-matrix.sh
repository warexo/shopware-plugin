#!/usr/bin/env bash

set -euo pipefail

CONTAINER="${DOCKWARE_CONTAINER:-}"
ACCESS_KEY="${SHOPWARE_ACCESS_KEY:-}"
BASE_URL="${SHOPWARE_BASE_URL:-http://127.0.0.1}"
TIER_PRODUCT_ID="${TIER_PRODUCT_ID:-c0dec0dec0dec0dec0dec0dec0de5001}"
NORMAL_PRODUCT_ID="${NORMAL_PRODUCT_ID:-c0dec0dec0dec0dec0dec0dec0de4001}"
STANDARD_TIER_PRODUCT_ID="${STANDARD_TIER_PRODUCT_ID:-c0dec0dec0dec0dec0dec0dec0de5005}"
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

if [[ -z "$ACCESS_KEY" ]]; then
    echo 'No active Store API access key found. Set SHOPWARE_ACCESS_KEY.' >&2
    exit 1
fi

request_cart() {
    local token="$1"
    local payload="$2"

    docker exec "$CONTAINER" curl -fsS -X POST "$BASE_URL/store-api/checkout/cart/line-item" \
        -H "sw-access-key: $ACCESS_KEY" \
        -H "sw-context-token: $token" \
        -H 'Content-Type: application/json' \
        --data "$payload"
}

recalculate_cart() {
    local token="$1"

    docker exec "$CONTAINER" curl -fsS "$BASE_URL/store-api/checkout/cart" \
        -H "sw-access-key: $ACCESS_KEY" \
        -H "sw-context-token: $token"
}

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

assert_cart() {
    local quantity="$1"
    local expected_unit="$2"
    local expected_line_total="$3"
    local expected_cart_total="$4"
    local expected_cart_tax="$5"
    local expected_cart_net="$6"
    local token="tier-tax-${quantity}-$(date +%s%N)"
    local payload
    local cart

    payload="{\"items\":[{\"id\":\"tier\",\"referencedId\":\"$TIER_PRODUCT_ID\",\"type\":\"product\",\"quantity\":$quantity}]}"
    cart="$(request_cart "$token" "$payload")"

    assert_number "$cart" '.lineItems[0].price.unitPrice' "$expected_unit" "quantity $quantity unit price"
    assert_number "$cart" '.lineItems[0].price.totalPrice' "$expected_line_total" "quantity $quantity line total"
    assert_number "$cart" '.price.totalPrice' "$expected_cart_total" "quantity $quantity cart total"
    assert_number "$cart" '[.price.calculatedTaxes[].tax] | add' "$expected_cart_tax" "quantity $quantity tax"
    assert_number "$cart" '.price.netPrice' "$expected_cart_net" "quantity $quantity net total"

    cart="$(recalculate_cart "$token")"
    assert_number "$cart" '.lineItems[0].price.unitPrice' "$expected_unit" "quantity $quantity recalculated unit price"
    assert_number "$cart" '.price.totalPrice' "$expected_cart_total" "quantity $quantity recalculated cart total"
    assert_number "$cart" '[.price.calculatedTaxes[].tax] | add' "$expected_cart_tax" "quantity $quantity recalculated tax"
}

assert_mixed_cart() {
    local token="tier-tax-mixed-$(date +%s%N)"
    local payload
    local cart

    payload="{\"items\":[{\"id\":\"tier\",\"referencedId\":\"$TIER_PRODUCT_ID\",\"type\":\"product\",\"quantity\":10000},{\"id\":\"normal\",\"referencedId\":\"$NORMAL_PRODUCT_ID\",\"type\":\"product\",\"quantity\":1}]}"
    cart="$(request_cart "$token" "$payload")"

    assert_number "$cart" '.price.totalPrice' 107.89 'mixed cart total'
    assert_number "$cart" '[.price.calculatedTaxes[].tax] | add' 17.22 'mixed cart tax'
    assert_number "$cart" '.price.netPrice' 90.67 'mixed cart net total'

    cart="$(recalculate_cart "$token")"
    assert_number "$cart" '.price.totalPrice' 107.89 'recalculated mixed cart total'
    assert_number "$cart" '[.price.calculatedTaxes[].tax] | add' 17.22 'recalculated mixed cart tax'
    assert_number "$cart" '.price.netPrice' 90.67 'recalculated mixed cart net total'
}

assert_custom_form_cart_and_ajax() {
    local token="tier-tax-custom-form-$(date +%s%N)"
    local payload
    local cart
    local ajax

    payload="{\"items\":[{\"id\":\"tier-custom\",\"referencedId\":\"$TIER_PRODUCT_ID\",\"type\":\"product\",\"quantity\":10000,\"payload\":{\"customFormId\":\"$CUSTOM_FORM_ID\",\"customFormFields\":{\"customFinish\":{\"value\":\"Premium (+10.00)\"}}}}]}"
    cart="$(request_cart "$token" "$payload")"

    assert_number "$cart" '.lineItems[0].price.unitPrice' 10.79 'custom-form cart unit price'
    assert_number "$cart" '.lineItems[0].price.totalPrice' 107.9 'custom-form cart line total'
    assert_number "$cart" '.price.totalPrice' 107.9 'custom-form cart total'
    assert_number "$cart" '[.price.calculatedTaxes[].tax] | add' 17.23 'custom-form cart tax'
    assert_number "$cart" '.price.netPrice' 90.67 'custom-form cart net total'

    ajax="$(docker exec "$CONTAINER" curl -fsS -X POST "$BASE_URL/checkout/line-item/calculate" \
        -H 'Host: localhost' \
        -H 'X-Requested-With: XMLHttpRequest' \
        --data-urlencode 'lineItems[tier][id]=tier' \
        --data-urlencode "lineItems[tier][referencedId]=$TIER_PRODUCT_ID" \
        --data-urlencode 'lineItems[tier][type]=product' \
        --data-urlencode 'lineItems[tier][quantity]=10' \
        --data-urlencode 'lineItems[tier][stackable]=1' \
        --data-urlencode 'lineItems[tier][removable]=1' \
        --data-urlencode "lineItems[tier][payload][customFormId]=$CUSTOM_FORM_ID" \
        --data-urlencode 'lineItems[tier][payload][customFormFields][customFinish][value]=Premium (+10.00)')"

    if ! jq -e \
        '.totalPrice == "€107.90" and .unitPrice == "€10.79" and .netTotalPrice == "€90.67" and .totalTax == "€17.23" and .totalQuantity == 10' \
        >/dev/null <<<"$ajax"; then
        echo "FAIL: CMS AJAX price response: $ajax" >&2
        return 1
    fi
}

assert_ajax_tier() {
    local quantity="$1"
    local expected_total="$2"
    local expected_unit="$3"
    local expected_net_total="$4"
    local expected_net_unit="$5"
    local expected_tax="$6"
    local expected_unit_tax="$7"
    local ajax

    ajax="$(docker exec "$CONTAINER" curl -fsS -X POST "$BASE_URL/checkout/line-item/calculate" \
        -H 'Host: localhost' \
        -H 'X-Requested-With: XMLHttpRequest' \
        --data-urlencode 'lineItems[tier][id]=tier' \
        --data-urlencode "lineItems[tier][referencedId]=$TIER_PRODUCT_ID" \
        --data-urlencode 'lineItems[tier][type]=product' \
        --data-urlencode "lineItems[tier][quantity]=$quantity" \
        --data-urlencode 'lineItems[tier][stackable]=1' \
        --data-urlencode 'lineItems[tier][removable]=1')"

    if ! jq -e \
        --arg total "$expected_total" \
        --arg unit "$expected_unit" \
        --arg netTotal "$expected_net_total" \
        --arg netUnit "$expected_net_unit" \
        --arg tax "$expected_tax" \
        --arg unitTax "$expected_unit_tax" \
        --argjson quantity "$quantity" \
        '.totalPrice == $total and .unitPrice == $unit and .netTotalPrice == $netTotal and .netUnitPrice == $netUnit and .totalTax == $tax and .unitTax == $unitTax and .totalQuantity == $quantity' \
        >/dev/null <<<"$ajax"; then
        echo "FAIL: CMS AJAX tier response for quantity $quantity: $ajax" >&2
        return 1
    fi
}

assert_ajax_standard_tier() {
    local quantity="$1"
    local expected_total="$2"
    local expected_unit="$3"
    local ajax

    ajax="$(docker exec "$CONTAINER" curl -fsS -X POST "$BASE_URL/checkout/line-item/calculate" \
        -H 'Host: localhost' \
        -H 'X-Requested-With: XMLHttpRequest' \
        --data-urlencode 'lineItems[tier][id]=tier' \
        --data-urlencode "lineItems[tier][referencedId]=$STANDARD_TIER_PRODUCT_ID" \
        --data-urlencode 'lineItems[tier][type]=product' \
        --data-urlencode "lineItems[tier][quantity]=$quantity" \
        --data-urlencode 'lineItems[tier][stackable]=1' \
        --data-urlencode 'lineItems[tier][removable]=1')"

    if ! jq -e --arg total "$expected_total" --arg unit "$expected_unit" --argjson quantity "$quantity" \
        '.totalPrice == $total and .unitPrice == $unit and .totalQuantity == $quantity' \
        >/dev/null <<<"$ajax"; then
        echo "FAIL: CMS AJAX standard tier response for quantity $quantity: $ajax" >&2
        return 1
    fi
}

# Warexo stores business quantities as integer core quantities multiplied by 1000.
# The fixture is gross-priced at EUR 0.89 below 10 and EUR 0.79 from 10, with 19% VAT.
assert_cart 500 0.89 0.445 0.45 0.07 0.38
assert_cart 9000 0.89 8.01 8.01 1.28 6.73
assert_cart 9500 0.89 8.455 8.46 1.35 7.11
assert_cart 10000 0.79 7.9 7.9 1.26 6.64
assert_cart 10500 0.79 8.295 8.3 1.32 6.98
assert_mixed_cart

if docker exec "$CONTAINER" php bin/console plugin:list --format=json \
    | jq -e '.[] | select(.name == "AggroCmsExtrasPlugin" and .active == true)' >/dev/null; then
    assert_ajax_tier 0.5 '€0.45' '€0.89' '€0.38' '€0.75' '€0.07' '€0.14'
    assert_ajax_tier 9.5 '€8.46' '€0.89' '€7.11' '€0.75' '€1.35' '€0.14'
    assert_ajax_tier 10 '€7.90' '€0.79' '€6.64' '€0.66' '€1.26' '€0.13'
    assert_ajax_tier 10.5 '€8.30' '€0.79' '€6.98' '€0.66' '€1.32' '€0.13'
    assert_ajax_standard_tier 9 '€8.01' '€0.89'
    assert_ajax_standard_tier 10 '€7.90' '€0.79'
    assert_custom_form_cart_and_ajax
fi

echo 'Tier-price tax matrix passed.'
