<?php declare(strict_types=1);

namespace Swag\CartChangePrice\Cart\Checkout;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Struct\StructCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CartChangePrice\Cart\Checkout\OverwrittenPrice\OverwrittenPriceEntity;

class OverwrittenPriceCollector implements \Shopware\Core\Checkout\Cart\CollectorInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $overwritePriceRepository;

    public function __construct(EntityRepositoryInterface $overwritePriceRepository)
    {
        $this->overwritePriceRepository = $overwritePriceRepository;
    }

    public function prepare(StructCollection $definitions, Cart $cart, SalesChannelContext $context, CartBehavior $behavior): void
    {
        // Simply consider all products in the cart and pass it to the collection
        // We do not want to filter for the products from our custom database table here, since database actions are supposed to be done
        // in the `collect` method
        $definitions->add(new OverwrittenPriceFetchDefinition($cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE)->getKeys()));
    }

    public function collect(StructCollection $fetchDefinitions, StructCollection $data, Cart $cart, SalesChannelContext $context, CartBehavior $behavior): void
    {
        // Fetch the items from the collection again.
        $priceOverwriteFetchDefinitions = $fetchDefinitions->filterInstance(OverwrittenPriceFetchDefinition::class);
        if ($priceOverwriteFetchDefinitions->count() === 0) {
            return;
        }

        $productIds = [[]];
        /** @var OverwrittenPriceFetchDefinition $fetchDefinition */
        foreach ($priceOverwriteFetchDefinitions as $fetchDefinition) {
            // Collect all product IDs from the items in the cart
            $productIds[] = $fetchDefinition->getProductIds();
        }

        // Flatten the array
        $productIds = array_unique(array_merge(...$productIds));

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));
        // Check if any product id, which was collected earlier, matches with the custom table product IDs
        $overwrittenPrices = $this->overwritePriceRepository->search($criteria, $context->getContext());

        // Necessary for the `enrich` method. Contains all product IDs, whose prices need to be overwritten
        $data->set('overwritten_price', $overwrittenPrices);
    }

    public function enrich(StructCollection $data, Cart $cart, SalesChannelContext $context, CartBehavior $behavior): void
    {
        // If no price has to be overwritten, do nothing
        if (!$data->has('overwritten_price')) {
            return;
        }

        $overwrittenPrices = $data->get('overwritten_price');

        // If no price has to be overwritten, do nothing
        if (count($overwrittenPrices) === 0) {
            return;
        }

        /** @var OverwrittenPriceEntity $overwrittenPrice */
        foreach ($overwrittenPrices as $overwrittenPrice) {
            // Fetch the cart item matching to the product ID
            $matchedCartItem = $cart->getLineItems()->get($overwrittenPrice->getProductId());

            if (!$matchedCartItem) {
                continue;
            }

            /** @var QuantityPriceDefinition $oldPriceDefinition */
            $oldPriceDefinition = $matchedCartItem->getPriceDefinition();
            // Overwrite price definition with the new price, hence the `$overwrittenPrice->getPrice()` call
            $matchedCartItem->setPriceDefinition(
                new QuantityPriceDefinition(
                    $overwrittenPrice->getPrice(),
                    $oldPriceDefinition->getTaxRules(),
                    $oldPriceDefinition->getPrecision(),
                    $oldPriceDefinition->getQuantity(),
                    $oldPriceDefinition->isCalculated()
                )
            );
        }
    }
}
