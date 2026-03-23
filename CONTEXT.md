# CONTEXT.md — État du projet SymfonyComeat

## Résumé

SymfonyComeat est une boutique en ligne de produits alimentaires artisanaux. L'application est fonctionnelle pour la navigation, le panier et l'authentification. **La prochaine étape est l'intégration de Stripe** pour compléter le tunnel d'achat.

## Ce qui est implémenté

### Catalogue
- 21 produits répartis en 6 catégories : Fruits & Légumes, Viandes & Poissons, Produits Laitiers, Boulangerie, Épicerie Fine, Boissons
- Pages catégories, fiches produit, listing homepage

### Panier
- Ajout, suppression, modification de quantité, vidage
- Persistance duale : session (anonymes) + base de données (connectés)
- Synchronisation automatique session → DB à la connexion via `CartSubscriber`
- Validation du stock (quantité plafonnée au stock disponible)
- AJAX pour les mises à jour de quantité avec retour JSON

### Authentification & Profil
- Inscription avec validation (email, prénom, nom, mot de passe min. 6 chars)
- Connexion par email avec CSRF
- Profil utilisateur éditable (identité + adresse de livraison)
- Rôles : `ROLE_USER`, `ROLE_ADMIN`

### Administration
- CRUD complet des catégories sous `/admin/category` (ROLE_ADMIN)
- Génération automatique de slug

### Commandes (entités seulement)
- Les entités `Order` et `OrderItem` existent et sont en base
- Les fixtures créent 10 commandes de test avec statuts variés

## Ce qui manque — À implémenter

### Stripe (priorité 1)
Le `CheckoutController` contient un `TODO` explicite pour Stripe.

**Fichier** : `src/Controller/CheckoutController.php`

La route `POST /commande/confirmer` doit :
1. Créer une session de paiement Stripe (Stripe Checkout ou Payment Intent)
2. Rediriger vers Stripe
3. Gérer le webhook Stripe (événement `checkout.session.completed` ou `payment_intent.succeeded`)
4. À réception du webhook : créer l'entité `Order` + `OrderItem`, vider le panier, changer le statut à `paid`

**Variables d'environnement à ajouter dans `.env`** :
```
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### Gestion des commandes utilisateur
- Pas de page "Mes commandes" dans le profil utilisateur
- Pas de page de confirmation après paiement

### Stock
- Le stock est validé au niveau du panier mais pas décrémenté lors de la commande

### Images produits
- Le champ `product.image` existe mais aucune image n'est uploadée (placeholder utilisé)

## Flux d'achat actuel

```
Homepage → Fiche produit → Ajout au panier
→ Page panier (/panier) → Checkout (/commande) → [TODO Stripe] → Confirmation
```

## Base de données

- **SGBD** : PostgreSQL 17
- **Base** : `fabrice_bdd`
- **Host** : 127.0.0.1:5432
- **Tables** : user, product, category, product_category, order, order_item, cart_item, messenger_messages

## Design

- Palette artisanale : rouge tomate (#C0392B), olive (#556B2F), beige sable (#D7C2A3)
- Font : Inter (Google Fonts)
- CSS entièrement dans `templates/base.html.twig` via variables CSS
- Responsive : breakpoints à 768px et 480px
- Notifications via `showToast(message, type)` (success / warning / error)
