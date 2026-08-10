#!/usr/bin/env bash

set -euo pipefail

CONTAINER="${DOCKWARE_CONTAINER:-}"

if [[ -z "$CONTAINER" ]]; then
    CONTAINER="$(docker ps --filter 'name=shopware' --format '{{.ID}}' | head -n 1)"
fi

if [[ -z "$CONTAINER" ]]; then
    echo 'No running Dockware container found. Set DOCKWARE_CONTAINER.' >&2
    exit 1
fi

plugins=(
    AggroCmsExtrasPlugin
    AggroPromotionSurchargesPlugin
    AggroDigitalDeliveryCostsPlugin
)

plugin_state() {
    local plugin="$1"

    docker exec "$CONTAINER" php bin/console plugin:list --format=json \
        | jq -r --arg plugin "$plugin" '.[] | select(.name == $plugin) | .active'
}

set_plugin_state() {
    local plugin="$1"
    local active="$2"
    local current

    current="$(plugin_state "$plugin")"
    if [[ -z "$current" ]]; then
        echo "Plugin $plugin is not installed; the combination matrix cannot be complete." >&2
        exit 1
    fi

    if [[ "$active" == '1' && "$current" != 'true' ]]; then
        docker exec "$CONTAINER" php bin/console plugin:activate "$plugin" --no-interaction >/dev/null
    elif [[ "$active" == '0' && "$current" == 'true' ]]; then
        docker exec "$CONTAINER" php bin/console plugin:deactivate "$plugin" --no-interaction >/dev/null
    fi
}

initial_states=()
for plugin in "${plugins[@]}"; do
    initial_states+=("$(plugin_state "$plugin")")
done

restore_states() {
    local index

    for index in "${!plugins[@]}"; do
        if [[ "${initial_states[$index]}" == 'true' ]]; then
            set_plugin_state "${plugins[$index]}" 1
        else
            set_plugin_state "${plugins[$index]}" 0
        fi
    done

    docker exec "$CONTAINER" php bin/console cache:clear --no-warmup >/dev/null
}
trap restore_states EXIT

for cms in 0 1; do
    for promotion in 0 1; do
        for digital in 0 1; do
            set_plugin_state AggroCmsExtrasPlugin "$cms"
            set_plugin_state AggroPromotionSurchargesPlugin "$promotion"
            set_plugin_state AggroDigitalDeliveryCostsPlugin "$digital"
            docker exec "$CONTAINER" php bin/console cache:clear --no-warmup >/dev/null

            echo "Running CMS=$cms promotion-surcharges=$promotion digital-delivery=$digital"
            DOCKWARE_CONTAINER="$CONTAINER" "$(dirname "$0")/tier-tax-matrix.sh"
            if [[ "$promotion" == '1' ]]; then
                DOCKWARE_CONTAINER="$CONTAINER" "$(dirname "$0")/promotion-tier-matrix.sh"
            fi
        done
    done
done

echo 'All optional-plugin combinations passed.'
