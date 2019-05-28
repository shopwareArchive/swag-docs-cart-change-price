<?php declare(strict_types=1);

namespace Swag\CartChangePrice\Cart\Checkout\OverwrittenPrice;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\{CreatedAtField,
    Flag\CascadeDelete,
    Flag\Inherited,
    Flag\PrimaryKey,
    Flag\Required,
    FloatField,
    IdField,
    OneToOneAssociationField,
    ReferenceVersionField,
    UpdatedAtField,
    FkField};
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OverwrittenPriceEntityDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'overwritten_price';
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Inherited(), new Required()),

            new FloatField('price', 'price'),
            (new OneToOneAssociationField('product', 'product_id', 'id', ProductDefinition::class, false))->addFlags(new CascadeDelete()),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }

    public function getCollectionClass(): string
    {
        return OverwrittenPriceCollection::class;
    }

    public function getEntityClass(): string
    {
        return OverwrittenPriceEntity::class;
    }
}