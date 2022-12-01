<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ProductOptionCartCollector implements CartDataCollectorInterface
{
    private EntityRepositoryInterface $productRepository;
    private EntityRepositoryInterface $productOptionRepository;
    private EntityRepositoryInterface $productOptionValueRepository;

    public function __construct(EntityRepositoryInterface $productRepository, EntityRepositoryInterface $productOptionRepository, EntityRepositoryInterface $productOptionValueRepository)
    {
        $this->productRepository = $productRepository;
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueRepository = $productOptionValueRepository;
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $selections = [];
        foreach ($original->getLineItems() as $lineItem) {
            $product = $this->getLineItemProduct($lineItem, $context);
            $options = $product->getExtension('warexoProductOptions');

            if ($options && count($options))
            {
                $lineSelection = [];
                $selectedOptions = $lineItem->getPayloadValue('warexoProductOptions');
                foreach($options as $option)
                {
                    if (isset($selectedOptions[$option->getId()]))
                    {
                        $value = $this->productOptionValueRepository->search(new Criteria([$selectedOptions[$option->getId()]]), $context->getContext())->first();
                        $lineSelection[] = [
                            'option' => $option->getName(),
                            'value' => $value->getName(),
                            'surcharge' => $value->getSurcharge()
                        ];
                    }else {
                        $criteria = new Criteria();
                        $criteria->addFilter(new EqualsFilter('productOptionId', $option->getId()));
                        $criteria->addSorting(new FieldSorting('position'));
                        $value = $this->productOptionValueRepository->search($criteria, $context->getContext())->first();
                        $lineSelection[] = [
                            'option' => $option->getName(),
                            'value' => $value->getName(),
                            'surcharge' => $value->getSurcharge()
                        ];
                    }
                }
                $selections[$lineItem->getId()] = $lineSelection;
            }
        }
        $data->set('optionValueSelections', $selections);
    }

    private function getLineItemProduct(LineItem $lineItem, SalesChannelContext $context) : ProductEntity
    {
        $criteria = new Criteria([$lineItem->getReferencedId()]);
        $criteria->addAssociation('warexoProductOptions');
        $product = $this->productRepository->search($criteria, $context->getContext())->first();
        if ($product instanceof ProductEntity) {
            return $product;
        }
        throw new \RuntimeException('Line item has no product');
    }
}