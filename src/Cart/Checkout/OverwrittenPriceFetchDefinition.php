<?php declare(strict_types=1);

namespace Swag\CartChangePrice\Cart\Checkout;

use Shopware\Core\Framework\Struct\Struct;

class OverwrittenPriceFetchDefinition extends Struct
{
    /**
     * @var string[]
     */
    protected $productIds;

    /**
     * @param string[] $productIds
     */
    public function __construct(array $productIds)
    {
        $this->productIds = $productIds;
    }

    /**
     * @return string[]
     */
    public function getProductIds(): array
    {
        return $this->productIds;
    }
}
