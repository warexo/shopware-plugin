<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Promotion\Gateway;

use Shopware\Core\Checkout\Promotion\Gateway\PromotionGatewayInterface;
use Shopware\Core\Checkout\Promotion\Gateway\Template\PermittedIndividualCodePromotions;
use Shopware\Core\Checkout\Promotion\PromotionCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RestValuePromotionGateway implements PromotionGatewayInterface
{
    private const PAYLOAD_FIELD = 'promotion.individualCodes.payload';

    public function __construct(private readonly PromotionGatewayInterface $inner)
    {
    }

    public function get(Criteria $criteria, SalesChannelContext $context): PromotionCollection
    {
        $filters = $criteria->getFilters();

        if ($this->containsIndividualCodeFilter($filters)) {
            $criteria->resetFilters();

            foreach ($filters as $filter) {
                $criteria->addFilter($this->allowPositiveRestValue($filter));
            }
        }

        return $this->inner->get($criteria, $context);
    }

    /**
     * @param array<array-key, Filter> $filters
     */
    private function containsIndividualCodeFilter(array $filters): bool
    {
        foreach ($filters as $filter) {
            if ($filter instanceof PermittedIndividualCodePromotions) {
                return true;
            }
        }

        return false;
    }

    private function allowPositiveRestValue(Filter $filter): Filter
    {
        if (!$filter instanceof PermittedIndividualCodePromotions) {
            return $filter;
        }

        $queries = [];

        foreach ($filter->getQueries() as $query) {
            if ($query instanceof EqualsFilter && $query->getField() === self::PAYLOAD_FIELD && $query->getValue() === null) {
                $queries[] = new MultiFilter(MultiFilter::CONNECTION_OR, [
                    $query,
                    new RangeFilter(self::PAYLOAD_FIELD . '.restValue', [RangeFilter::GT => 0]),
                ]);

                continue;
            }

            $queries[] = $query;
        }

        return new MultiFilter($filter->getOperator(), $queries);
    }
}
