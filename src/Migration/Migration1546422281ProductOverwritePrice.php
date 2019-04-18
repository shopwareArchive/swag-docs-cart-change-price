<?php declare(strict_types=1);

namespace Swag\CartChangePrice\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1546422281ProductOverwritePrice extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1546422281;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE `overwritten_price` (
    `id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NOT NULL,
    `product_version_id` BINARY(16) NOT NULL,
    `price` DOUBLE NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk.overwritten_price.product_id` (`product_id`, `product_version_id`),
    CONSTRAINT `fk.overwritten_price.product_id` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $connection->executeQuery($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}