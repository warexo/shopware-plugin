function loadOverride() {
    const override = jest.spyOn(Shopware.Component, 'override');

    jest.isolateModules(() => {
        require('./index');
    });

    const registration = override.mock.calls.find(([name]) => name === 'sw-product-deliverability-form');
    override.mockRestore();

    return registration?.[1];
}

describe('sw-product-deliverability-form-override', () => {
    let component;

    beforeAll(() => {
        component = loadOverride();
    });

    it('loads the decimal stock plugin configuration', async () => {
        const context = {
            warexoDecimalStockEnabled: false,
            systemConfigApiService: {
                getValues: jest.fn().mockResolvedValue({
                    'AggroWarexoPlugin.config.decimalstock': true,
                }),
            },
        };

        await component.methods.loadWarexoDecimalStockConfig.call(context);

        expect(context.systemConfigApiService.getValues)
            .toHaveBeenCalledWith('AggroWarexoPlugin.config');
        expect(context.warexoDecimalStockEnabled).toBe(true);
    });

    it('creates and attaches a default Warexo extension', () => {
        const extension = {};
        const product = {
            id: 'product-id',
            addExtension: jest.fn(),
        };
        const context = {
            product,
            warexoExtension: null,
            warexoProductExtensionRepository: {
                create: jest.fn(() => extension),
            },
        };

        component.methods.ensureWarexoExtension.call(context);

        expect(context.warexoProductExtensionRepository.create)
            .toHaveBeenCalledWith(Shopware.Context.api);
        expect(extension).toEqual({
            productId: 'product-id',
            position: 0,
            stock: null,
            minPurchase: null,
            maxPurchase: null,
            purchaseSteps: null,
        });
        expect(product.addExtension).toHaveBeenCalledWith('warexoExtension', extension);
    });

    it('keeps an existing extension and fills a missing product id', () => {
        const extension = { productId: null, position: 5 };
        const repository = { create: jest.fn() };

        component.methods.ensureWarexoExtension.call({
            product: { id: 'product-id' },
            warexoExtension: extension,
            warexoProductExtensionRepository: repository,
        });

        expect(extension).toEqual({ productId: 'product-id', position: 5 });
        expect(repository.create).not.toHaveBeenCalled();
    });

    it('supports plain product objects without extension methods', () => {
        const extension = {};
        const product = { id: 'product-id' };

        component.methods.ensureWarexoExtension.call({
            product,
            warexoExtension: null,
            warexoProductExtensionRepository: {
                create: jest.fn(() => extension),
            },
        });

        expect(product.extensions.warexoExtension).toBe(extension);
    });
});
