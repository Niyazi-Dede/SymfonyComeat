<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
class CartController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CartItemRepository $cartItemRepo,
        private ProductRepository $productRepo,
    ) {}

    /**
     * Synchronise la session avec les CartItem en base (pour le badge).
     */
    private function syncSessionFromDb(Request $request, User $user): void
    {
        $request->getSession()->set('cart', $this->cartItemRepo->getCartArrayForUser($user));
    }

    #[Route('', name: 'app_cart', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $cartData = [];
        $total = 0;

        if ($user instanceof User) {
            // Utilisateur connecté → lecture depuis la base
            $items = $this->cartItemRepo->findByUser($user);
            foreach ($items as $item) {
                $subtotal = (float) $item->getProduct()->getPrice() * $item->getQuantity();
                $cartData[] = [
                    'product' => $item->getProduct(),
                    'quantity' => $item->getQuantity(),
                    'subtotal' => $subtotal,
                ];
                $total += $subtotal;
            }
            $this->syncSessionFromDb($request, $user);
        } else {
            // Anonyme → lecture depuis la session
            $cart = $request->getSession()->get('cart', []);
            foreach ($cart as $productId => $quantity) {
                $product = $this->productRepo->find($productId);
                if ($product) {
                    $subtotal = (float) $product->getPrice() * $quantity;
                    $cartData[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                    ];
                    $total += $subtotal;
                }
            }
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cartData,
            'total' => $total,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_add_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_cart');
        }

        $product = $this->productRepo->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $quantity = $request->request->getInt('quantity', 1);
        $user = $this->getUser();

        if ($user instanceof User) {
            // ── DB ──
            $cartItem = $this->cartItemRepo->findOneByUserAndProduct($user, $product);
            if (!$cartItem) {
                $cartItem = (new CartItem())->setUser($user)->setProduct($product)->setQuantity(0);
                $this->em->persist($cartItem);
            }
            $newQty = $cartItem->getQuantity() + $quantity;
            if ($newQty > $product->getStock()) {
                $newQty = $product->getStock();
                $message = 'Quantité ajustée au stock disponible (' . $product->getStock() . ').';
                $type = 'warning';
            } else {
                $message = '"' . $product->getName() . '" ajouté au panier !';
                $type = 'success';
            }
            $cartItem->setQuantity($newQty);
            $this->em->flush();
            $this->syncSessionFromDb($request, $user);
            $cartCount = count($this->cartItemRepo->findByUser($user));
        } else {
            // ── Session ──
            $session = $request->getSession();
            $cart = $session->get('cart', []);
            $currentQty = $cart[$id] ?? 0;
            $newQty = $currentQty + $quantity;

            if ($newQty > $product->getStock()) {
                $newQty = $product->getStock();
                $message = 'Quantité ajustée au stock disponible (' . $product->getStock() . ').';
                $type = 'warning';
            } else {
                $message = '"' . $product->getName() . '" ajouté au panier !';
                $type = 'success';
            }
            if ($newQty > 0) {
                $cart[$id] = $newQty;
            }
            $session->set('cart', $cart);
            $cartCount = count($cart);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'cartCount' => $cartCount,
                'message'   => $message,
                'type'      => $type,
            ]);
        }

        $this->addFlash($type, $message);
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_cart');
    }

    #[Route('/supprimer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_remove_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_cart');
        }

        $user = $this->getUser();

        if ($user instanceof User) {
            $product = $this->productRepo->find($id);
            if ($product) {
                $cartItem = $this->cartItemRepo->findOneByUserAndProduct($user, $product);
                if ($cartItem) {
                    $this->em->remove($cartItem);
                    $this->em->flush();
                }
            }
            $this->syncSessionFromDb($request, $user);
        } else {
            $session = $request->getSession();
            $cart = $session->get('cart', []);
            unset($cart[$id]);
            $session->set('cart', $cart);
        }

        $this->addFlash('success', 'Produit retiré du panier.');
        return $this->redirectToRoute('app_cart');
    }

    #[Route('/modifier/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_update_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_cart');
        }

        $quantity = $request->request->getInt('quantity', 1);
        $isAjax = $request->isXmlHttpRequest();
        $user = $this->getUser();

        if ($user instanceof User) {
            $product = $this->productRepo->find($id);
            if ($product) {
                $cartItem = $this->cartItemRepo->findOneByUserAndProduct($user, $product);
                if ($cartItem) {
                    if ($quantity <= 0) {
                        $this->em->remove($cartItem);
                        if (!$isAjax) $this->addFlash('success', 'Produit retiré du panier.');
                    } else {
                        if ($quantity > $product->getStock()) {
                            $quantity = $product->getStock();
                            if (!$isAjax) $this->addFlash('warning', 'Quantité ajustée au stock disponible (' . $product->getStock() . ').');
                        }
                        $cartItem->setQuantity($quantity);
                    }
                    $this->em->flush();
                }
            }
            $this->syncSessionFromDb($request, $user);
        } else {
            $session = $request->getSession();
            $cart = $session->get('cart', []);
            if ($quantity <= 0) {
                unset($cart[$id]);
                if (!$isAjax) $this->addFlash('success', 'Produit retiré du panier.');
            } else {
                $product = $this->productRepo->find($id);
                if ($product && $quantity > $product->getStock()) {
                    $quantity = $product->getStock();
                    if (!$isAjax) $this->addFlash('warning', 'Quantité ajustée au stock disponible (' . $product->getStock() . ').');
                }
                $cart[$id] = $quantity;
            }
            $session->set('cart', $cart);
        }

        if ($isAjax) {
            return $this->json(['success' => true]);
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_clear', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_cart');
        }

        $user = $this->getUser();

        if ($user instanceof User) {
            $this->cartItemRepo->clearByUser($user);
            $this->syncSessionFromDb($request, $user);
        } else {
            $request->getSession()->remove('cart');
        }

        $this->addFlash('success', 'Panier vidé.');
        return $this->redirectToRoute('app_cart');
    }
}
