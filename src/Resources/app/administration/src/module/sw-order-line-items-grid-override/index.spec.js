function loadOverride() {
    const override = jest.spyOn(Shopware.Component, 'override');

    jest.isolateModules(() => {
        require('./index');
    });

    const registration = override.mock.calls.find(([name]) => name === 'sw-order-line-items-grid');
    override.mockRestore();

    return registration?.[1];
}

describe('module/sw-order-line-items-grid-override', () => {
    let component;

    beforeAll(() => {
        component = loadOverride();
    });

    it('registers the Shopware component override', () => {
        expect(component).toBeDefined();
    });

    it.each([
        [{ payload: { warexoIsDecimalQuantity: true } }, true],
        [{ payload: { warexoIsDecimalQuantity: false } }, false],
        [{}, false],
        [null, false],
    ])('detects decimal line items', (item, expected) => {
        expect(component.methods.warexoIsDecimalQuantityItem(item)).toBe(expected);
    });

    it('formats the decimal payload using the Administration locale', () => {
        const context = {
            $i18n: { locale: 'de-DE' },
            warexoIsDecimalQuantityItem: component.methods.warexoIsDecimalQuantityItem,
        };

        expect(component.methods.warexoGetDisplayQuantity.call(context, {
            quantity: 1250,
            payload: {
                warexoIsDecimalQuantity: true,
                warexoDecimalQuantity: 1.25,
            },
        })).toBe('1,25');
    });

    it('falls back to the core quantity for invalid or regular line items', () => {
        const context = {
            $i18n: { locale: 'en-GB' },
            warexoIsDecimalQuantityItem: component.methods.warexoIsDecimalQuantityItem,
        };

        expect(component.methods.warexoGetDisplayQuantity.call(context, {
            quantity: 4,
            payload: {},
        })).toBe('4');
        expect(component.methods.warexoGetDisplayQuantity.call(context, {
            quantity: 1250,
            payload: {
                warexoIsDecimalQuantity: true,
                warexoDecimalQuantity: 'invalid',
            },
        })).toBe('1250');
    });
});
