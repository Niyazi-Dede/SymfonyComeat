# CLAUDE.md — SymfonyComeat

## Présentation du projet

**SymfonyComeat** est une application e-commerce française de produits alimentaires (épicerie fine, fruits, viandes, etc.). Elle est construite avec Symfony 8.0 et tourne sur PHP 8.4 et PostgreSQL 17.

## Stack technique

- **Framework** : Symfony 8.0
- **PHP** : >= 8.4
- **Base de données** : PostgreSQL 17 (base : `fabrice_bdd`, port 5432)
- **ORM** : Doctrine ORM 3.6 avec mappings par attributs PHP
- **Templates** : Twig
- **Frontend** : Symfony AssetMapper + Stimulus + Hotwired Turbo (pas de Webpack)
- **CSS** : Embarqué dans `templates/base.html.twig` (variables CSS, palette artisanale)
- **Tests** : PHPUnit 12.5

## Commandes essentielles

```bash
# Lancer le serveur de développement
symfony serve

# Effacer le cache
php bin/console cache:clear

# Lancer les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures (données de test)
php bin/console doctrine:fixtures:load

# Créer une migration après modification d'entité
php bin/console doctrine:migrations:diff

# Lancer les tests
php bin/phpunit

# Installer les dépendances PHP
composer install

# Installer les assets JS (importmap)
php bin/console importmap:install
```

## Architecture

```
src/
├── Controller/         # 9 contrôleurs (logique métier embarquée, pas de couche Service)
├── Entity/             # 6 entités Doctrine
├── Repository/         # 6 repositories
├── Form/               # 3 types de formulaires
├── EventSubscriber/    # CartSubscriber (synchronisation panier session → DB)
└── DataFixtures/       # 6 fixtures (utilisateurs, produits, catégories, commandes)

templates/              # Templates Twig (16 fichiers)
assets/                 # JS Stimulus + styles minimes
config/                 # Configuration Symfony
migrations/             # 4 migrations Doctrine
```

## Entités

| Entité | Rôle |
|---|---|
| `User` | Utilisateur avec authentification par email |
| `Product` | Produit du catalogue (nom, prix, stock, slug) |
| `Category` | Catégorie de produits (Many-to-Many avec Product) |
| `CartItem` | Panier persistant pour les utilisateurs connectés |
| `Order` | Commande (statuts : pending, paid, shipped, delivered) |
| `OrderItem` | Ligne de commande (snapshot prix + quantité) |

## Contrôleurs principaux

| Contrôleur | Route | Rôle |
|---|---|---|
| `HomeController` | `GET /` | Page d'accueil |
| `CartController` | `/panier/*` | Gestion du panier (AJAX supporté) |
| `CheckoutController` | `/commande/*` | Tunnel de commande (ROLE_USER requis) |
| `ProductController` | `/produits/{id}` | Fiche produit |
| `CategoryPublicController` | `/categories/*` | Listing catégories/produits |
| `CategoryController` | `/admin/category/*` | CRUD catégories (ROLE_ADMIN) |
| `ProfileController` | `/profil` | Profil utilisateur (ROLE_USER) |
| `RegistrationController` | `/register` | Inscription |
| `SecurityController` | `/login`, `/logout` | Authentification |

## Panier : fonctionnement dual

- **Anonyme** : Stocké en session (`$_SESSION['cart']` = `[productId => quantity]`)
- **Connecté** : Stocké en base via entité `CartItem`
- **Synchronisation** : `CartSubscriber` transfère le panier session → DB à la première requête après connexion

## Conventions à respecter

- Mappings Doctrine par **attributs PHP** (pas de YAML/XML)
- Nommage des routes en français (ex: `app_panier_ajouter`)
- Slugs générés automatiquement depuis le nom (dans les contrôleurs admin)
- Prix stockés en `decimal(10,2)` sous forme de **string** (pas float)
- CSRF requis sur toutes les actions POST de modification
- Messages de validation en **français**
- Pas de couche Service dédiée : la logique est dans les contrôleurs
- Les templates utilisent les variables CSS définies dans `base.html.twig`

## Sécurité

- `/admin/*` → `ROLE_ADMIN`
- `/profil/*` et `/commande/*` → `ROLE_USER`
- Login par email, CSRF activé sur le formulaire de connexion
- Hachage de mot de passe automatique (bcrypt/argon2)

## Utilisateurs de test (fixtures)

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@comeat.fr | admin1234 | ROLE_ADMIN |
| alice@example.com | password123 | ROLE_USER |
| bob@example.com | password123 | ROLE_USER |

## Points d'attention

- `OrderItem` a des noms de méthodes incohérents (`getOrderName()`/`getOrderItem()` au lieu de `getOrder()`/`getProduct()`) — ne pas reproduire ce pattern
- `User.cartData` (JSON) est présent mais inutilisé — ignorer
- Le CSS est entièrement dans `templates/base.html.twig`, ne pas créer de fichiers CSS séparés sans raison
- Symfony AssetMapper est utilisé (pas Webpack) — les assets JS/CSS vont dans `assets/`
