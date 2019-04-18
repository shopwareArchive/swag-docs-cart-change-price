<?php declare(strict_types=1);

namespace Swag\CartChangePrice\Cart\Checkout\OverwrittenPrice;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(OverwrittenPriceEntity $entity)
 * @method void              set(string $key, OverwrittenPriceEntity $entity)
 * @method OverwrittenPriceEntity[]    getIterator()
 * @method OverwrittenPriceEntity[]    getElements()
 * @method OverwrittenPriceEntity|null get(string $key)
 * @method OverwrittenPriceEntity|null first()
 * @method OverwrittenPriceEntity|null last()
 */
class OverwrittenPriceCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OverwrittenPriceEntity::class;
    }
}