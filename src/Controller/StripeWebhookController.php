<?php

namespace App\Controller;

use Stripe\Event;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
    #[Route('/webhook/stripe', name: 'app_stripe_webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): JsonResponse
    {
        Stripe::setApiKey($this->getParameter('app.stripe_secret_key'));

        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        $webhookSecret = $this->getParameter('app.stripe_webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException) {
            return new JsonResponse(['error' => 'Payload invalide'], Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException) {
            return new JsonResponse(['error' => 'Signature invalide'], Response::HTTP_BAD_REQUEST);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                // Paiement accepté — la commande est déjà créée dans PaymentController::success()
                break;

            case 'payment_intent.payment_failed':
                // Paiement échoué — possibilité d'envoyer un email ou logger ici
                break;
        }

        return new JsonResponse(['status' => 'success']);
    }
}
