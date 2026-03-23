<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    public function confirm(Request $request, EntityManagerInterface $em, ProductRepository $productRepository): Response
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
        }

        $cart = $request->getSession()->get('cart', []);

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart');
        }

        Stripe::setApiKey($this->getParameter('app.stripe_secret_key'));

        $lineItems = [];
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => ['name' => $product->getName()],
                        'unit_amount' => (int) round((float) $product->getPrice() * 100),
                    ],
                    'quantity' => $quantity,
                ];
            }
        }

        $stripeSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('app_payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'customer_email' => $this->getUser()->getEmail(),
        ]);

        return $this->redirect($stripeSession->url);
    }
}
