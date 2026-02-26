<?php

namespace App\Repository;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /**
     * @return CartItem[]
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'ASC']);
    }

    public function findOneByUserAndProduct(User $user, Product $product): ?CartItem
    {
        return $this->findOneBy(['user' => $user, 'product' => $product]);
    }

    public function clearByUser(User $user): void
    {
        $this->createQueryBuilder('c')
            ->delete()
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Retourne le panier sous forme de tableau associatif [productId => quantity].
     */
    public function getCartArrayForUser(User $user): array
    {
        $items = $this->findByUser($user);
        $cart = [];
        foreach ($items as $item) {
            $cart[$item->getProduct()->getId()] = $item->getQuantity();
        }
        return $cart;
    }
}
