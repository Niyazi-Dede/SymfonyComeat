<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartItemRepository;
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
final class PaymentController extends AbstractController
{
    #[Route('/paiement/checkout', name: 'app_payment_checkout', methods: ['POST'])]
    public function checkout(Request $request, ProductRepository $productRepository): Response
    {
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
                        'product_data' => [
                            'name' => $product->getName(),
                        ],
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
            'success_url' => $this->generateUrl(
                'app_payment_success',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl(
                'app_payment_cancel',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'customer_email' => $this->getUser()->getEmail(),
        ]);

        return $this->redirect($stripeSession->url);
    }

    #[Route('/paiement/succes', name: 'app_payment_success')]
    public function success(
        Request $request,
        ProductRepository $productRepository,
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();
        $cart = $session->get('cart', []);

        if (!empty($cart)) {
            $order = new Order();
            $order->setUser($this->getUser());
            $order->setOrderNumber('CMD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)));
            $order->setStatus('paid');
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setUpdatedAt(new \DateTimeImmutable());

            $total = 0;
            foreach ($cart as $productId => $quantity) {
                $product = $productRepository->find($productId);
                if ($product) {
                    $orderItem = new OrderItem();
                    $orderItem->setOrderName($order);
                    $orderItem->setOrderItem($product);
                    $orderItem->setQuantity($quantity);
                    $orderItem->setPrice($product->getPrice());
                    $orderItem->setCreatedAt(new \DateTimeImmutable());
                    $em->persist($orderItem);

                    $total += (float) $product->getPrice() * $quantity;
                }
            }

            $order->setTotal((string) round($total, 2));
            $em->persist($order);
            $em->flush();

            // Vider le panier session
            $session->remove('cart');

            // Vider le panier base de données
            $cartItemRepository->clearByUser($this->getUser());
        }

        return $this->render('payment/success.html.twig');
    }

    #[Route('/paiement/annulation', name: 'app_payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}
