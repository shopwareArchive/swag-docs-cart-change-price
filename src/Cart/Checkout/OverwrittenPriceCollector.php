<?php declare(strict_types=1);

namespace Swag\CartChangePrice\Cart\Checkout;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CartChangePrice\Cart\Checkout\OverwrittenPrice\OverwrittenPriceEntity;

class OverwrittenPriceCollector implements CartDataCollectorInterface, CartProcessorInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $overwritePriceRepository;

    /**
     * @var QuantityPriceCalculator
     */
    private $calculator;

    public function __construct(
        EntityRepositoryInterface $overwritePriceRepository,
        QuantityPriceCalculator $calculator
    ) {
        $this->overwritePriceRepository = $overwritePriceRepository;
        $this->calculator = $calculator;
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        // get all product ids of current cart
        $productIds = $original->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE)->getReferenceIds();

        // remove all product ids which are already fetched from the database
        $filtered = $this->filterAlreadyFetchedPrices($productIds, $data);

        if (empty($filtered)) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productId', $filtered));

        // fetch prices from database
        $prices = $this->overwritePriceRepository->search($criteria, $context->getContext());;

        foreach ($filtered as $id) {
            $key = $this->buildKey($id);

            $price = null;
            // find price for the current product id
            foreach ($prices as $current) {
                if ($current->getProductId() === $id) {
                    $price = $current;
                    break;
                }
            }

            // we have to set a value for each product id to prevent duplicate queries in next calculation
            $data->set($key, $price);
        }
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        // get all product line items
        $products = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($products as $product) {
            $key = $this->buildKey($product->getReferencedId());

            // no overwritten price? continue with next product
            if (!$data->has($key) || $data->get($key) === null) {
                continue;
            }

            /** @var OverwrittenPriceEntity $price */
            $price = $data->get($key);

            // build new price definition
            $definition = new QuantityPriceDefinition(
                $price->getPrice(),
                $product->getPrice()->getTaxRules(),
                $context->getCurrency()->getDecimalPrecision(),
                $product->getPrice()->getQuantity(),
                true
            );

            // build CalculatedPrice over calculator class for overwitten price
            $calculated = $this->calculator->calculate($definition, $context);

            // set new price into line item
            $product->setPrice($calculated);
            $product->setPriceDefinition($definition);
        }
    }

    private function filterAlreadyFetchedPrices(array $productIds, CartDataCollection $data): array
    {
        $filtered = [];

        foreach ($productIds as $id) {
            $key = $this->buildKey($id);

            // already fetched from database?
            if ($data->has($key)) {
                continue;
            }

            $filtered[] = $id;
        }

        return $filtered;
    }

    private function buildKey(string $id): string
    {
        return 'price-overwrite-'.$id;
    }
}
