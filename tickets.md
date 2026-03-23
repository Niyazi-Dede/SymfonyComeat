# Tickets — Intégration Stripe (SymfonyComeat)

> Basé sur le guide nouvy.fr/wiki étape 14, adapté au projet Comeat.

---

## TICKET-01 — Installation du SDK et configuration des variables

**Type** : Configuration
**Fichiers** : `.env`, `.env.local`, `config/services.yaml`

### Actions

1. Installer le SDK Stripe :
   ```bash
   composer require stripe/stripe-php
   ```

2. Créer un compte sur [stripe.com](https://stripe.com) et activer le **mode test**.
   Récupérer les clés dans Développeurs > Clés API.

3. Dans `.env`, ajouter (valeurs vides, à ne jamais commiter) :
   ```dotenv
   STRIPE_SECRET_KEY=
   STRIPE_PUBLIC_KEY=
   STRIPE_WEBHOOK_SECRET=
   ```

4. Dans `.env.local` (ignoré par git), renseigner les vraies valeurs :
   ```dotenv
   STRIPE_SECRET_KEY=sk_test_...
   STRIPE_PUBLIC_KEY=pk_test_...
   STRIPE_WEBHOOK_SECRET=whsec_...  # rempli à l'étape TICKET-07
   ```

5. Dans `config/services.yaml`, exposer les paramètres :
   ```yaml
   parameters:
       app.stripe_secret_key: '%env(STRIPE_SECRET_KEY)%'
       app.stripe_public_key: '%env(STRIPE_PUBLIC_KEY)%'
       app.stripe_webhook_secret: '%env(STRIPE_WEBHOOK_SECRET)%'
   ```

---

## TICKET-02 — Création du PaymentController

**Type** : Développement
**Fichier à créer** : `src/Controller/PaymentController.php`

### Routes à créer

| Route | Méthode | Nom | Rôle |
|---|---|---|---|
| `/paiement/checkout` | POST | `app_payment_checkout` | Crée la session Stripe et redirige |
| `/paiement/succes` | GET | `app_payment_success` | Crée la commande, vide le panier |
| `/paiement/annulation` | GET | `app_payment_cancel` | Page d'annulation, panier intact |

### Points spécifiques au projet

- **`checkout()`** : Lit le panier depuis la session (`$session->get('cart', [])`), construit les `line_items` Stripe avec `unit_amount` en centimes (`(int)($product->getPrice() * 100)`), passe `customer_email` avec l'email de l'utilisateur.

- **`success()`** :
  - Crée une entité `Order` avec :
    - Format numéro : `CMD-` + année courante + `-` + 4 derniers chars de `uniqid()` (ex: `CMD-2026-A3F1`)
    - Status : `'paid'`
    - Timestamps : `new \DateTimeImmutable()`
  - Pour chaque produit du panier, crée un `OrderItem` en utilisant les méthodes **telles qu'elles existent dans l'entité** :
    - `$orderItem->setOrderName($order)` ← associe la commande (méthode mal nommée, c'est normal)
    - `$orderItem->setOrderItem($product)` ← associe le produit (idem)
    - `$orderItem->setQuantity($quantity)`
    - `$orderItem->setPrice($product->getPrice())`
  - Vide le panier sur **deux niveaux** :
    - Session : `$session->remove('cart')`
    - Base de données (utilisateurs connectés) : `$cartItemRepository->clearByUser($this->getUser())`

- **`cancel()`** : Ne vide pas le panier.

### Dépendances à injecter

```php
ProductRepository $productRepository
EntityManagerInterface $em
CartItemRepository $cartItemRepository  // pour vider le panier DB
```

---

## TICKET-03 — Modification du CheckoutController

**Type** : Développement
**Fichier** : `src/Controller/CheckoutController.php`

### Action

Dans la méthode `confirm()` (route `POST /commande/confirmer`), remplacer le bloc TODO :

```php
// Remplacer ceci :
// TODO: Redirection vers Stripe (étape 14)
$this->addFlash('info', 'Le paiement par carte sera disponible très prochainement !');
return $this->redirectToRoute('app_cart');

// Par ceci :
return $this->redirectToRoute('app_payment_checkout');
```

La logique de sauvegarde du profil (`save_profile`) **reste intacte** au-dessus, elle doit continuer à fonctionner.

> La session Stripe sera créée dans `PaymentController::checkout()` — le `CheckoutController` devient uniquement le point d'entrée de la validation adresse.

---

## TICKET-04 — Création des templates de paiement

**Type** : Développement frontend
**Fichiers à créer** :
- `templates/payment/success.html.twig`
- `templates/payment/cancel.html.twig`

### Règles de style

- Hériter de `base.html.twig` (pas `base_accueil.html.twig` comme dans le guide)
- Utiliser les variables CSS du projet : `var(--primary)`, `var(--text-primary)`, `var(--bg-card)`, `var(--border)`, `var(--olive)`, etc.
- Pas de Bootstrap ni de FontAwesome (le projet n'en utilise pas)
- Style cohérent avec le reste : `border-radius: 14px`, `font-weight: 800`, palette artisanale

### success.html.twig

Doit afficher :
- Message de confirmation de paiement
- Lien vers l'accueil (`app_home`)
- Lien vers le profil (`app_profile_show`)

### cancel.html.twig

Doit afficher :
- Message d'annulation
- Lien retour au panier (`app_cart`)
- Lien vers l'accueil (`app_home`)

---

## TICKET-05 — Création du StripeWebhookController

**Type** : Développement
**Fichier à créer** : `src/Controller/StripeWebhookController.php`

### Route

| Route | Méthode | Nom | Auth |
|---|---|---|---|
| `/webhook/stripe` | POST | `app_stripe_webhook` | Aucune (firewall désactivé) |

### Actions

1. Vérifier la signature Stripe avec `Webhook::constructEvent()`.
2. Retourner `400` si payload ou signature invalide.
3. Gérer au minimum les événements :
   - `checkout.session.completed` — paiement accepté (log ou traitement futur)
   - `payment_intent.payment_failed` — paiement échoué (log ou traitement futur)
4. Retourner `200 {"status": "success"}` en cas de succès.

> Le webhook est une sécurité supplémentaire. La création de commande se fait dans `success()` du `PaymentController`. Le webhook peut servir à mettre à jour des statuts ou envoyer des emails à terme.

---

## TICKET-06 — Configuration sécurité pour le webhook

**Type** : Configuration
**Fichier** : `config/packages/security.yaml`

### Action

Ajouter un firewall `webhook` **avant** le firewall `main` pour désactiver l'authentification sur la route `/webhook/stripe` :

```yaml
firewalls:
    webhook:
        pattern: ^/webhook
        security: false
    dev:
        pattern: ^/(_profiler|_wdt|assets|build)/
        security: false
    main:
        # ... reste inchangé
```

> Sans cela, Stripe ne peut pas appeler le webhook (pas de session/cookie valide).

---

## TICKET-07 — Tests avec Stripe CLI

**Type** : Test

### Installation de Stripe CLI (Windows)

```bash
# Avec Scoop
scoop install stripe

# Ou télécharger directement sur https://stripe.com/docs/stripe-cli
```

### Procédure de test complète

Ouvrir **3 terminaux** :

**Terminal 1** — Serveur Symfony :
```bash
symfony serve
```

**Terminal 2** — Forwarding des webhooks :
```bash
stripe login
stripe listen --forward-to http://localhost:8000/webhook/stripe
```
Copier le `whsec_...` affiché dans `.env.local` comme valeur de `STRIPE_WEBHOOK_SECRET`.

**Terminal 3** (optionnel) — Déclencher des événements manuellement :
```bash
stripe trigger checkout.session.completed
stripe trigger payment_intent.payment_failed
```

### Carte de test Stripe

| Numéro | Résultat |
|---|---|
| `4242 4242 4242 4242` | Paiement réussi |
| `4000 0000 0000 0002` | Carte refusée |
| `4000 0025 0000 3155` | Authentification 3D Secure requise |

Utiliser n'importe quelle date d'expiration future, n'importe quel CVC (3 chiffres), n'importe quel code postal.

### Scénario de test end-to-end

1. Se connecter avec `alice@example.com` / `password123`
2. Ajouter des produits au panier
3. Aller sur `/panier` puis cliquer "Commander"
4. Remplir l'adresse sur `/commande`, valider
5. Être redirigé vers Stripe Checkout
6. Payer avec `4242 4242 4242 4242`
7. Être redirigé vers `/paiement/succes`
8. Vérifier en base :
   - La table `order` contient une nouvelle entrée avec `status = 'paid'`
   - La table `order_item` contient les lignes correspondantes
   - La table `cart_item` est vide pour cet utilisateur
   - La session ne contient plus de panier

---

## Récapitulatif des fichiers touchés

| Fichier | Action |
|---|---|
| `composer.json` | Ajout de `stripe/stripe-php` |
| `.env` | Ajout des 3 variables Stripe (vides) |
| `.env.local` | Renseigner les vraies clés (non commité) |
| `config/services.yaml` | Déclaration des 3 paramètres Stripe |
| `config/packages/security.yaml` | Ajout firewall `webhook` |
| `src/Controller/PaymentController.php` | **Créer** |
| `src/Controller/StripeWebhookController.php` | **Créer** |
| `src/Controller/CheckoutController.php` | Modifier `confirm()` |
| `templates/payment/success.html.twig` | **Créer** |
| `templates/payment/cancel.html.twig` | **Créer** |
