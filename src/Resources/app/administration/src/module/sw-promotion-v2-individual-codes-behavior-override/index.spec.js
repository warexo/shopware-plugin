function loadOverride() {
    const override = jest.spyOn(Shopware.Component, 'override');

    jest.isolateModules(() => {
        require('./index');
    });

    const registration = override.mock.calls.find(
        ([name]) => name === 'sw-promotion-v2-individual-codes-behavior',
    );
    override.mockRestore();

    return registration?.[1];
}

function createMethodContext(component, additional = {}) {
    const context = { ...additional };

    Object.entries(component.methods).forEach(([name, method]) => {
        context[name] = (...args) => method.call(context, ...args);
    });

    return context;
}

describe('module/sw-promotion-v2-individual-codes-behavior-override', () => {
    let component;

    beforeAll(() => {
        component = loadOverride();
    });

    it('adds the rest value column before the creation date', () => {
        const columns = component.computed.codeColumns.call({
            $super: jest.fn(() => [
                { property: 'code' },
                { property: 'createdAt' },
            ]),
            $t: (key) => key,
        });

        expect(columns.map(({ property }) => property)).toEqual([
            'code',
            'payload.restValue',
            'createdAt',
        ]);
        expect(columns[1]).toEqual({
            property: 'payload.restValue',
            label: 'warexo.promotion.individualCodes.restValue',
            sortable: false,
        });
    });

    it.each([
        [0, true],
        ['12.50', true],
        [null, false],
        ['', false],
        [-1, false],
        ['invalid', false],
    ])('validates rest value %p', (restValue, expected) => {
        expect(component.methods.warexoHasRestValue({ payload: { restValue } })).toBe(expected);
    });

    it('updates the rest value without discarding other payload data', () => {
        const item = { payload: { orderId: 'order-id', restValue: 10 } };

        component.methods.warexoUpdateRestValue(item, '25.50');

        expect(item.payload).toEqual({ orderId: 'order-id', restValue: 25.5 });
    });

    it('removes an invalid rest value and clears an empty payload', () => {
        const item = { payload: { restValue: 10 } };

        component.methods.warexoUpdateRestValue(item, -1);

        expect(item.payload).toBeNull();
    });

    it('saves the edited rest value and closes the modal', async () => {
        const item = { payload: { restValue: 10 } };
        const save = jest.fn().mockResolvedValue(undefined);
        const context = createMethodContext(component, {
            warexoRestValueEditItem: item,
            warexoRestValueDraft: 20,
            warexoRestValueSaving: false,
            $refs: { individualCodesGrid: { save } },
        });

        await component.methods.warexoSaveRestValue.call(context);

        expect(save).toHaveBeenCalledWith(item);
        expect(item.payload.restValue).toBe(20);
        expect(context.warexoRestValueEditItem).toBeNull();
        expect(context.warexoRestValueSaving).toBe(false);
    });

    it('reverts the grid and reports a failed save', async () => {
        const item = { payload: { restValue: 10 } };
        const revert = jest.fn().mockResolvedValue(undefined);
        const createNotificationError = jest.fn();
        const context = createMethodContext(component, {
            warexoRestValueEditItem: item,
            warexoRestValueDraft: 20,
            warexoRestValueSaving: false,
            $refs: {
                individualCodesGrid: {
                    save: jest.fn().mockRejectedValue(new Error('save failed')),
                    revert,
                },
            },
            $t: (key) => key,
            createNotificationError,
        });

        await component.methods.warexoSaveRestValue.call(context);

        expect(revert).toHaveBeenCalledTimes(1);
        expect(createNotificationError).toHaveBeenCalledWith({
            message: 'warexo.promotion.individualCodes.saveError',
        });
        expect(context.warexoRestValueEditItem).toBeNull();
        expect(context.warexoRestValueSaving).toBe(false);
    });
});
