<?php

namespace App\EventSubscriber;

use App\Entity\CartItem;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CartSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private TokenStorageInterface  $tokenStorage,
        private CartItemRepository     $cartItemRepo,
        private ProductRepository      $productRepo,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -10],
        ];
    }

    /**
     * Au premier appel authentifié de la session :
     * 1. Transfert du panier session (invité) vers la table cart_item.
     * 2. Chargement du panier DB vers la session (pour le badge).
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();

        // Une seule fois par session
        if ($session->get('_cart_synced', false)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            return;
        }

        /** @var User $user */
        $user = $token->getUser();
        if (!$user->getId()) {
            return;
        }

        $session->set('_cart_synced', true);

        // Transférer le panier session (invité) vers la DB
        $sessionCart = $session->get('cart', []);
        if (!empty($sessionCart)) {
            foreach ($sessionCart as $productId => $qty) {
                $product = $this->productRepo->find($productId);
                if (!$product || $qty <= 0) {
                    continue;
                }
                $existing = $this->cartItemRepo->findOneByUserAndProduct($user, $product);
                if ($existing) {
                    // Le panier session a priorité (plus récent)
                    $existing->setQuantity($qty);
                } else {
                    $item = (new CartItem())
                        ->setUser($user)
                        ->setProduct($product)
                        ->setQuantity($qty);
                    $this->em->persist($item);
                }
            }
            $this->em->flush();
        }

        // Charger le panier DB complet vers la session (pour le badge et les templates)
        $session->set('cart', $this->cartItemRepo->getCartArrayForUser($user));
    }
}
