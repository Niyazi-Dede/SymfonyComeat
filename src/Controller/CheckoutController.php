<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CheckoutController extends AbstractController
{
    #[Route('/commande', name: 'app_checkout')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $cart = $request->getSession()->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        $cartData = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
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

        return $this->render('checkout/index.html.twig', [
            'cart' => $cartData,
            'total' => $total,
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/commande/confirmer', name: 'app_checkout_confirm', methods: ['POST'])]
    public function confirm(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('checkout_confirm', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_checkout');
        }

        if ($request->request->get('save_profile') === '1') {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $user->setAddress($request->request->get('address') ?: null);
            $user->setCity($request->request->get('city') ?: null);
            $user->setPostalCode($request->request->get('postalCode') ?: null);
            $user->setPhone($request->request->get('phone') ?: null);
            $em->flush();
            $this->addFlash('success', 'Vos informations de livraison ont été sauvegardées sur votre profil.');
        }

        // TODO: Redirection vers Stripe (étape 14)
        $this->addFlash('info', 'Le paiement par carte sera disponible très prochainement !');
        return $this->redirectToRoute('app_cart');
    }
}
