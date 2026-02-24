<?php

namespace App\DataFixtures;

use App\Entity\Order;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $statuses = ['pending', 'paid', 'shipped', 'delivered'];

        for ($i = 1; $i <= 10; $i++) {
            $order = new Order();
            $order->setOrderNumber('CMD-2026-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT));
            $order->setTotal((string) round(mt_rand(800, 8000) / 100, 2));
            $order->setCreatedAt(new \DateTimeImmutable('-' . mt_rand(1, 60) . ' days'));
            $order->setUpdatedAt(new \DateTimeImmutable());

            // Distribute orders across users 1–5
            $userIndex = (($i - 1) % 5) + 1;
            /** @var \App\Entity\User $user */
            $user = $this->getReference('user_' . $userIndex, \App\Entity\User::class);
            $order->setUser($user);

            $manager->persist($order);
            $this->addReference('order_' . $i, $order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
