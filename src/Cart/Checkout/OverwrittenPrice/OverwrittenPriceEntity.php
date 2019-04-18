<?php declare(strict_types=1);

namespace Swag\CartChangePrice\Cart\Checkout\OverwrittenPrice;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class OverwrittenPriceEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var ProductEntity
     */
    protected $product;

    /**
     * @var string
     */
    protected $productId;

    /**
     * @var float
     */
    protected $price;

    public function getProduct(): ProductEntity
    {
        return $this->product;
    }

    public function setProduct(ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }
}