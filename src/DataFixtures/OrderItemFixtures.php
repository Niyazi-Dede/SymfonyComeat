<?php

namespace App\DataFixtures;

use App\Entity\OrderItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderItemFixtures extends Fixture implements DependentFixtureInterface
{
    private const PRODUCT_COUNT = 21;

    public function load(ObjectManager $manager): void
    {
        for ($orderIndex = 1; $orderIndex <= 10; $orderIndex++) {
            /** @var \App\Entity\Order $order */
            $order = $this->getReference('order_' . $orderIndex, \App\Entity\Order::class);

            $itemCount = mt_rand(2, 4);
            $usedProducts = [];

            for ($j = 0; $j < $itemCount; $j++) {
                // Pick a product not already in this order
                do {
                    $productIndex = mt_rand(0, self::PRODUCT_COUNT - 1);
                } while (in_array($productIndex, $usedProducts));

                $usedProducts[] = $productIndex;

                /** @var \App\Entity\Product $product */
                $product = $this->getReference('product_' . $productIndex, \App\Entity\Product::class);

                $item = new OrderItem();
                $item->setQuantity(mt_rand(1, 3));
                $item->setPrice($product->getPrice());
                $item->setCreatedAt(new \DateTimeImmutable());
                $item->setUpdatedAt(new \DateTimeImmutable());
                $item->setOrderName($order);
                $item->setOrderItem($product);

                $manager->persist($item);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [OrderFixtures::class, ProductFixtures::class];
    }
}
