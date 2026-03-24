# Prompt — Refonte graphique complète de SymfonyComeat

## Contexte du projet

**SymfonyComeat** est une application e-commerce française de produits alimentaires artisanaux (épicerie fine, fruits, viandes, boulangerie, boissons). Elle tourne sur Symfony 8.0 / PHP 8.4 / PostgreSQL et utilise Twig pour le templating.

Le design actuel est un thème dark/light avec une palette lime (#AAFF00) sur fond sombre (#121216). Le CSS est entièrement embarqué dans les templates Twig (pas de fichiers CSS séparés). Le résultat actuel est fonctionnel mais générique — il manque une identité visuelle forte, une cohérence globale, et un vrai "effet waouh" pour un site alimentaire artisanal.

## Ce que je te demande

Crée **des maquettes HTML statiques** pour une refonte graphique complète du site. Chaque maquette sera un fichier HTML autonome (avec CSS inline ou dans un `<style>`) que je pourrai ouvrir directement dans un navigateur pour visualiser le résultat.

### Fichiers à produire

Crée les fichiers suivants, chacun étant une maquette HTML complète et autonome :

```
maquettes/
├── 01-home.html                  # Page d'accueil (hero + catégories + produits vedettes)
├── 02-categories.html            # Listing de toutes les catégories
├── 03-category-products.html     # Page d'une catégorie avec ses produits
├── 04-product-detail.html        # Fiche produit détaillée
├── 05-cart.html                  # Page panier (avec articles + récapitulatif)
├── 06-cart-empty.html            # Panier vide (état vide)
├── 07-checkout.html              # Tunnel de commande (adresse + récapitulatif)
├── 08-login.html                 # Page de connexion
├── 09-register.html              # Page d'inscription
├── 10-profile.html               # Page profil utilisateur
├── 11-payment-success.html       # Page de confirmation après paiement réussi
├── 12-payment-cancel.html        # Page d'annulation de paiement
├── 13-admin-categories.html      # Admin : liste des catégories (tableau)
├── 14-admin-category-detail.html # Admin : détail d'une catégorie
├── 15-admin-category-form.html   # Admin : formulaire création/édition catégorie
└── design-system.html            # Récapitulatif du design system (couleurs, typos, composants)
```

## Direction artistique

### Identité visuelle souhaitée

Le site vend des **produits alimentaires artisanaux français** (fromages, viandes, fruits, boulangerie, épicerie fine, boissons). Le design doit évoquer :

- **L'artisanat et le terroir** : authenticité, fait-main, savoir-faire
- **La gourmandise** : des couleurs chaudes qui donnent envie, des visuels qui évoquent la fraîcheur
- **Le premium accessible** : haut de gamme sans être élitiste, élégant sans être froid
- **La modernité** : un design web moderne (2025-2026), pas un look "vieux site de fromager"

### Palette de couleurs — À REPENSER COMPLÈTEMENT

La palette actuelle (lime #AAFF00 sur noir) ne correspond pas du tout à l'univers alimentaire. Propose une nouvelle palette qui :
- Utilise des **tons chauds et organiques** (terracotta, olive, crème, bordeaux, miel, vert sauge...)
- Garde un **mode dark ET light** (le switch de thème doit rester)
- Offre un bon contraste et une lisibilité excellente
- A une couleur d'accent qui fonctionne pour les CTA et les prix

### Typographie

- Remplace ou complète les fonts actuelles (Inter + Syne)
- Propose une combinaison titre/corps qui évoque le premium alimentaire
- Garde une excellente lisibilité sur écran
- Utilise Google Fonts (ou des fonts auto-hébergées) uniquement

### Composants à retravailler

1. **Navbar** : Plus distinctive, possiblement avec un logo textuel retravaillé ("Comeat"), navigation claire
2. **Hero section (accueil)** : Grand impact visuel, évocateur de la nourriture, CTA clair
3. **Cartes produits** : Espace image (actuellement juste une lettre placeholder), nom, prix, bouton panier — rendre ça appétissant
4. **Cartes catégories** : Identifiables visuellement, chaque catégorie devrait avoir un caractère
5. **Formulaires** : Login, inscription, profil, checkout — élégants et modernes
6. **Panier** : Clair, facile à modifier, récapitulatif visible
7. **Checkout** : Inspirant confiance, étapes claires
8. **Pages de statut** (succès/annulation paiement) : Feedback visuel fort
9. **Footer** : Plus riche, avec liens utiles, branding cohérent
10. **Toast notifications** : Subtiles mais visibles
11. **Admin** : Clean, fonctionnel, pas besoin d'être flashy mais doit rester cohérent avec le design system

## Contraintes techniques à respecter

### Structure HTML

Les maquettes doivent refléter la structure Twig existante. Utilise des **commentaires HTML** pour indiquer les blocs Twig correspondants :

```html
<!-- {% block hero %} -->
<section class="hero">...</section>
<!-- {% endblock %} -->

<!-- {% block body %} -->
<div class="container">...</div>
<!-- {% endblock %} -->
```

### Variables CSS

Organise les couleurs et dimensions via des **variables CSS dans `:root`** et un sélecteur `[data-theme="light"]`. C'est comme ça que le site fonctionne actuellement et ça doit rester ainsi. Voici la structure attendue :

```css
:root {
    /* Couleurs principales */
    --primary: ...;
    --primary-dark: ...;
    --primary-light: ...;
    --accent: ...;

    /* Fonds */
    --bg-main: ...;
    --bg-secondary: ...;
    --bg-card: ...;
    --bg-elevated: ...;

    /* Texte */
    --text-primary: ...;
    --text-secondary: ...;
    --text-muted: ...;

    /* Sémantique */
    --success: ...;
    --error: ...;
    --warning: ...;

    /* Bordures & ombres */
    --border: ...;
    --shadow: ...;

    /* Navbar / Footer */
    --navbar-bg: ...;
    --footer-bg: ...;
}

[data-theme="light"] {
    /* Override pour le mode clair */
}
```

### Classes CSS réutilisables

Les classes suivantes sont utilisées partout dans le code. Tu peux les restyler mais **garde les mêmes noms de classes** pour faciliter l'intégration :

- Layout : `.container`, `.grid-2`, `.grid-3`, `.grid-4`
- Cartes : `.card`, `.card-img`, `.card-body`, `.card-title`, `.card-text`
- Boutons : `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-accent`
- Formulaires : `.form-container`, `.form-title`, `.form-group`, `.form-label`, `.form-control`
- Alertes : `.alert`, `.alert-success`, `.alert-error`
- Navigation : `.header`, `.navbar`, `.navbar-container`, `.navbar-brand`, `.navbar-menu`, `.navbar-link`, `.navbar-actions`
- Section : `.section-header`
- Prix : `.price-badge`
- Animations : `.fade-up`, `.fade-up.visible`
- Footer : `.footer`, `.footer-container`, `.footer-brand`
- Toast : `#toast-container`, `.toast`, `.toast-success`, `.toast-warning`, `.toast-error`

### Données fictives pour les maquettes

Utilise ces données pour remplir les maquettes :

**Catégories :**
1. Fruits & Légumes — "Produits frais de saison, cultivés localement"
2. Viandes & Poissons — "Viandes sélectionnées et poissons de nos côtes"
3. Produits Laitiers — "Fromages, yaourts et crèmes artisanaux"
4. Boulangerie — "Pains, viennoiseries et pâtisseries maison"
5. Épicerie Fine — "Huiles, épices, confitures et produits d'exception"
6. Boissons — "Vins, jus artisanaux et boissons naturelles"

**Produits exemples (pour la home et les fiches) :**
- Camembert AOP Normandie — 8,90 € — Stock: 25
- Côte de bœuf Limousine — 34,50 € — Stock: 8
- Huile d'olive extra-vierge Provence — 12,75 € — Stock: 42
- Pain au levain tradition — 4,20 € — Stock: 15
- Confiture de figues maison — 6,80 € — Stock: 30
- Vin rouge Côtes du Rhône 2022 — 11,90 € — Stock: 18
- Saumon fumé artisanal — 19,90 € — Stock: 3 (stock limité)
- Yaourt fermier nature — 3,50 € — Stock: 0 (rupture de stock)

**Utilisateur connecté (pour navbar, profil, checkout) :**
- Prénom : Alice
- Nom : Dupont
- Email : alice@example.com
- Adresse : 12 rue de la République
- Code postal : 69001
- Ville : Lyon
- Téléphone : 06 12 34 56 78

**Panier (pour cart et checkout) :**
- Camembert AOP Normandie × 2 = 17,80 €
- Huile d'olive extra-vierge Provence × 1 = 12,75 €
- Pain au levain tradition × 3 = 12,60 €
- **Total : 43,15 €**

## Responsive

Chaque maquette doit être **responsive** avec au minimum 3 breakpoints :
- **Desktop** : > 1024px
- **Tablette** : 768px — 1024px
- **Mobile** : < 768px

La navigation mobile doit avoir un **menu hamburger** fonctionnel (en JS vanilla, pas de framework).

## Qualité attendue

- **Pixel-perfect** : Le design doit être soigné dans les moindres détails (espacements, alignements, tailles)
- **Micro-interactions** : Hover states sur les cartes, boutons, liens. Transitions subtiles (0.2-0.3s)
- **Images placeholder** : Utilise des rectangles avec un dégradé ou un emoji/icône pour simuler les images produits (pas d'images externes)
- **Accessibilité** : Bon contraste (WCAG AA minimum), structure sémantique (h1-h6, nav, main, footer, section, article)
- **Performance** : Pas de librairie externe lourde. Vanilla CSS + JS uniquement. Google Fonts autorisé.

## Fichier design-system.html

Ce fichier doit documenter visuellement :
1. **La palette complète** (avec codes hex, mode dark et light)
2. **L'échelle typographique** (toutes les tailles, poids, fonts utilisées)
3. **Tous les composants** : boutons (tous les états), inputs (normal, focus, error, disabled), cartes, badges, alertes, toasts
4. **Les espacements** (système de spacing utilisé)
5. **Les breakpoints responsive**
6. **Les ombres et rayons de bordure**

## Ce qu'il ne faut PAS faire

- Ne PAS utiliser Tailwind, Bootstrap, ou tout framework CSS
- Ne PAS utiliser d'images externes (URL d'images)
- Ne PAS créer de fichiers CSS séparés — tout doit être dans le HTML (style inline ou bloc `<style>`)
- Ne PAS utiliser de JavaScript frameworks (React, Vue, etc.)
- Ne PAS changer les noms de classes existantes listées ci-dessus
- Ne PAS oublier le mode dark ET light
- Ne PAS faire un design "tech/startup" — c'est un site de nourriture artisanale

## Livrable final

Chaque fichier HTML doit :
1. Être **autonome** (ouvrable directement dans un navigateur)
2. Contenir tout son CSS dans un bloc `<style>` en haut
3. Contenir tout son JS en bas dans un bloc `<script>`
4. Avoir le **switch de thème** fonctionnel (dark/light)
5. Inclure des **commentaires** indiquant la correspondance avec les blocs Twig
6. Être en **français** (textes, labels, boutons, placeholders)

Le dossier `maquettes/` sera placé à la racine du projet Symfony. Ces maquettes serviront ensuite de référence pour modifier les vrais templates Twig.
