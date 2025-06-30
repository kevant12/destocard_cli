# Informations Projet Symfony (pour Gemini CLI)

Ce fichier contient des informations clés sur la configuration de ce projet Symfony pour faciliter l'assistance de l'agent Gemini CLI.

## Chemin Racine du Projet
`C:\Users\kevin\OneDrive\Documentos\eedn\projet_perso - Copie\app`

## Environnement
Le projet utilise Docker pour son environnement de développement.
- **Nom du conteneur PHP** : `php_symfony`
- **Nom du conteneur MySQL** : `mysql_symfony`
- **Nom de la base de données MySQL** : `base_symfony`
- **Mot de passe root MySQL** : `cequejeveux`

## Commandes Docker/Symfony Utiles

### Gestion des Conteneurs Docker
- **Démarrer les conteneurs (en mode détaché)** :
  ```bash
  docker compose up -d
  ```
- **Arrêter les conteneurs** :
  ```bash
  docker compose down
  ```
- **Arrêter et supprimer les conteneurs et leurs volumes (réinitialise la base de données)** :
  ```bash
  docker compose down -v
  ```

### Exécution de Commandes Symfony dans le Conteneur PHP
- **Vider le cache Symfony** :
  ```bash
  docker exec php_symfony php bin/console cache:clear
  ```
- **Générer une migration Doctrine (après modification d'entités)** :
  ```bash
  docker exec php_symfony php bin/console make:migration
  ```
- **Exécuter les migrations Doctrine (pour mettre à jour la base de données)** :
  ```bash
  docker exec php_symfony php bin/console doctrine:migrations:migrate
  ```
- **Marquer toutes les migrations comme exécutées (utile après une réinitialisation de DB)** :
  ```bash
  docker exec php_symfony php bin/console doctrine:migrations:version --add --all
  ```
- **Marquer une migration spécifique comme non exécutée** :
  ```bash
  docker exec php_symfony php bin/console doctrine:migrations:version --delete DoctrineMigrations\\VersionYYYYMMDDHHMMSS
  ```
- **Exécuter Composer install** :
  ```bash
  docker exec php_symfony composer install
  ```

### Inspection de la Base de Données via Docker
- **Lister les tables dans la base de données MySQL** :
  ```bash
  docker exec -e MYSQL_PWD=cequejeveux mysql_symfony mysql -u root base_symfony -e "SHOW TABLES;"
  ```
- **Décrire une table spécifique dans la base de données MySQL** :
  ```bash
  docker exec -e MYSQL_PWD=cequejeveux mysql_symfony mysql -u root base_symfony -e "DESCRIBE nom_de_la_table;"
  ```

## Fonctionnalités Implémentées / Améliorées (Vue d'ensemble)

- **Flux d'Inscription et Vérification d'Email** :
    - Gestion améliorée des emails déjà enregistrés (distinction entre compte vérifié et non vérifié).
    - Redirection automatique et connexion de l'utilisateur après vérification de l'email.
    - Configuration du `MAILER_DSN` pour l'envoi d'emails via MailHog.

- **Formulaire d'Ajout de Produit (Cartes Pokémon)** :
    - Sélection dynamique de la carte Pokémon basée sur l'extension choisie.
    - Affichage de l'image de la carte Pokémon sélectionnée dans le formulaire.
    - **Pré-remplissage du titre du produit et sélection de la carte Pokémon via la saisie du numéro de carte.**
    - **Possibilité d'ajouter des médias via webcam (capture directe).**

- **Gestion du Panier**:
    - **Mise à jour de la structure du panier en session pour stocker uniquement l'ID et la quantité du produit.**
    - **Récupération des détails complets du produit depuis la base de données lors de l'affichage du panier.**
    - **Affichage de l'image du produit dans le récapitulatif du panier.**

- **Processus de Commande (Checkout)**:
    - **Formulaire de sélection de l'adresse de livraison et du mode de livraison.**
    - **Calcul dynamique des frais de livraison et mise à jour du total.**

- **Intégration de Paiement (Stripe)**:
    - **Création d'intentions de paiement Stripe.**
    - **Page de paiement dédiée avec le Payment Element de Stripe.**
    - **Finalisation de la commande après confirmation du paiement par Stripe.**

- **Améliorations CSS/UI** :
    - Correction de la balise `<link>` dans `base.html.twig`.
    - Intégration d'un `reset.css` pour une meilleure cohérence des styles.
    - Positionnement de la navigation sous la vidéo dans l'en-tête.
    - Correction du champ de mot de passe unique dans le formulaire d'inscription.

## Synthèse de l'Architecture et des Composants Clés

Ce projet Symfony est une application de e-commerce spécialisée dans les cartes Pokémon, construite autour d'une architecture modulaire et utilisant Docker pour l'environnement de développement.

### 1. Entités Principales et leurs Relations

*   **`Users`**: Gère les utilisateurs, leurs informations, leurs favoris (`likes` - ManyToMany avec `Products`), et leurs adresses (`addresses` - OneToMany avec `Addresses`).
*   **`Products`**: Représente un produit à vendre (une carte Pokémon spécifique).
    *   **Propriétés clés**: `title`, `description`, `price`, `quantity`, `category`.
    *   **Relations**:
        *   `pokemonCard` (ManyToOne avec `PokemonCard`): La carte Pokémon de base associée.
        *   `media` (OneToMany avec `Media`): Images/vidéos associées au produit.
        *   `users` (ManyToOne avec `Users`): L'utilisateur qui a mis le produit en vente.
        *   `likes` (ManyToMany avec `Users`): Utilisateurs ayant mis ce produit en favori.
        *   `ordersProducts` (OneToMany avec `OrdersProducts`): Les lignes de commande associées à ce produit.
*   **`PokemonCard`**: Représente une carte Pokémon générique (non spécifique à un produit en vente).
    *   **Propriétés clés**: `number`, `name`, `rarity`, `extension`, `starRating` (int).
    *   **Relations**: `image` (OneToOne avec `Media`) pour l'image de la carte.
*   **`Media`**: Gère les fichiers médias (images, vidéos).
    *   **Propriétés clés**: `image_url`, `video_url`.
    *   **Propriété non mappée**: `file` (pour l'upload temporaire), **`webcamImage` (pour la capture directe)**.
    *   **Relations**: `products` (ManyToOne avec `Products`).
*   **`Orders`**: Représente une commande passée par un utilisateur.
    *   **Propriétés clés**: `createdAt`, `deliveryAt`, `status` (constantes: `PENDING`, `COMPLETED`, `CANCELLED`), `totalPrice`, `paymentProvider`, **`deliveryMethod` (string), `shippingCost` (float)**.
    *   **Relations**:
        *   `users` (ManyToOne avec `Users`): L'utilisateur qui a passé la commande.
        *   `addresses` (ManyToOne avec `Addresses`): L'adresse de livraison de la commande (nullable).
        *   `ordersProducts` (OneToMany avec `OrdersProducts`): Les lignes de produits de cette commande.
*   **`OrdersProducts`**: Entité de jointure ManyToMany entre `Orders` et `Products`, représentant une ligne de commande.
    *   **Propriétés clés**: `quantity`, `price` (prix du produit au moment de l'achat).
    *   **Relations**: `orders` (ManyToOne avec `Orders`), `products` (ManyToOne avec `Products`).
*   **`Addresses`**: Gère les adresses des utilisateurs.
    *   **Propriétés clés**: `number` (string), `street`, `city`, `zipCode` (string), `country`, `type` (constantes: `HOME`, `BILLING`, `SHIPPING`).
    *   **Relations**: `users` (ManyToOne avec `Users`), `orders` (OneToMany avec `Orders`).
*   **`Messages`**: Gère les messages entre utilisateurs.
    *   **Propriétés clés**: `content`, `expeditionDate`, `status`, `isRead` (bool).
    *   **Relations**: `sender` (ManyToOne avec `Users`), `receper` (ManyToOne avec `Users`).

### 2. Services Clés et leurs Responsabilités

*   **`ProductService`**: Gère la logique métier liée aux produits (création, mise à jour, suppression, gestion des médias). Utilise `FileUploaderService`.
*   **`CartService`**: Gère la logique du panier (ajout, mise à jour, suppression, calcul du total, validation du stock, et finalisation de l'achat via `purchaseCart`). **Mis à jour pour stocker uniquement l'ID et la quantité en session, et récupérer les détails du produit depuis la DB.**
*   **`MessageService`**: Gère la logique métier des messages (récupération des conversations, envoi de messages, marquage comme lu).
*   **`FileUploaderService`**: Service dédié à l'upload physique des fichiers sur le serveur. **À adapter pour gérer les images de la webcam.**
*   **`StripeService`**: Gère les interactions avec l'API Stripe (création d'intentions de paiement, etc.).
*   **`AppExtension` (Twig Extension)**: Fournit des filtres Twig personnalisés, comme le filtre `highlight` pour la mise en évidence du texte.

### 3. Contrôleurs Principaux

*   **`SecurityController`**: Gère l'authentification (connexion, inscription, vérification d'email, réinitialisation de mot de passe). **Mise à jour : suppression des appels `->createView()`**. 
*   **`ProductController`**: Gère les opérations CRUD sur les produits, l'affichage des listes et des détails, et les API pour les cartes Pokémon. **Mise à jour : suppression des champs redondants dans le formulaire d'ajout/édition.**
*   **`CartController`**: Gère les interactions avec le panier (ajout, mise à jour, suppression, achat). **Mise à jour : ajout des actions `checkout`, `payment`, `confirmOrder` pour le processus de commande et l'intégration Stripe. Suppression des appels `->createView()`**. 
*   **`FavoriteController`**: Gère l'ajout/suppression de produits aux favoris.
*   **`MessageController`**: Gère l'affichage des messages et des conversations.
*   **`HomeController`**: Gère la page d'accueil.
*   **`LoginController`**: Gère la page de connexion.
*   **`StripeController`**: Gère l'intégration de Stripe pour les paiements.

### 4. Formulaires

*   **`app/src/Form/MediaType.php`**:
    *   **Nouveau champ `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Form/MessageFormType.php`**:
    *   Ajout de contraintes de validation (`NotBlank`, `Length`) au champ `content`.
    *   Liaison du formulaire à l'entité `Messages` via `data_class`.
    *   Ajout de la protection CSRF explicite (`csrf_protection`, `csrf_token_id`).
*   **`app/src/Form/Product/ProductFormType.php`**:
    *   Mise à jour des attributs `data-` pour les champs `number`, `extension`, `rarity`, `type` et `pokemonCard` afin de faciliter le ciblage JavaScript pour la présélection.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` (redondants avec `PokemonCard`).**
*   **`app/src/Form/CheckoutFormType.php`**:
    *   **Nouveau fichier créé** pour gérer la sélection de l'adresse de livraison et du mode de livraison.
    *   **Reçoit l'utilisateur via les options du formulaire.**

### 5. Fichiers Statiques et Frontend Importants

*   **`composer.json`**: Définit les dépendances PHP du projet.
*   **`config/services.yaml`**: Configure les services de l'application, y compris le `FileUploaderService` et son répertoire cible.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour les interactions AJAX (panier, favoris, filtrage/présélection des cartes Pokémon, messages flash, aperçu dynamique des médias). **Mise à jour : logique de paiement Stripe, gestion des réponses JSON avec redirection, gestion des tokens CSRF pour panier/favoris, ajustement du pré-remplissage des produits.**
*   **`public/css/general.css`**: Fichier CSS principal pour le style global de l'application.
*   **`public/css/reset.css`**: Fichier de réinitialisation CSS.
*   **`templates/base.html.twig`**: Template de base de l'application, incluant les assets et la structure générale.
*   **`templates/product/add.html.twig`**: Template pour l'ajout de produits, avec la logique de présélection des cartes Pokémon. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/edit.html.twig`**: Template pour l'édition de produits. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/show.html.twig`**: Template pour l'affichage détaillé d'un produit, incluant les boutons d'édition/suppression. **Mise à jour : ajout de la protection CSRF au formulaire d'ajout au panier.**
*   **`templates/product/search_results.html.twig`**: Application du filtre Twig `highlight` sur `product.title` et `product.description` pour mettre en évidence le terme de recherche. Ajout des sélecteurs de filtrage par catégorie et rareté, ainsi que les options de tri par date, prix et nom. Intégration de la pagination avec KnpPaginatorBundle.
*   **`templates/security/login.html.twig`**: Suppression du style inline redondant sur le lien "Inscrivez-vous". **Mise à jour : ajout du lien "Mot de passe oublié ?".**
*   **`templates/security/register.html.twig`**: Déplacement des styles inline pour la civilité et le champ `agreeTerms` vers `general.css`.
*   **`templates/message/conversation.html.twig`**: Ajout du JavaScript pour le défilement automatique. Déplacement des styles inline vers `general.css`.
*   **`templates/cart/index.html.twig`**: **Mise à jour : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`templates/cart/checkout.html.twig`**: **Nouveau template créé** pour le processus de commande.
*   **`templates/cart/payment.html.twig`**: **Nouveau template créé** pour la page de paiement Stripe.
*   **`templates/emails/reset_password.html.twig`**: Nouveau template créé pour l'email de réinitialisation de mot de passe.
*   **`templates/emails/verification.html.twig`**: Nouveau template créé pour l'email de vérification de compte.
*   **`app/src/Twig/AppExtension.php`**: Nouveau fichier créé contenant le filtre Twig `highlight` pour la mise en évidence du texte.

### 6. Flux et Interactions Clés

*   **Ajout de Produit**: Le `ProductFormType` collecte les données, y compris les médias via `MediaType`. Le `ProductService` utilise `FileUploaderService` pour sauvegarder les fichiers. La sélection de la `PokemonCard` est dynamique via AJAX (`ajax.js`) et une API dans `ProductController`.
*   **Achat**: Le `CartController` utilise le `CartService` pour gérer le panier. La méthode `purchaseCart` du `CartService` crée les `Orders` et `OrdersProducts` et décrémente les stocks, le tout dans une transaction.
*   **Messagerie**: Le `MessageController` interagit avec le `MessageService` pour gérer les messages entre `Users`.
*   **Recherche**: Le `ProductController` utilise `ProductsRepository` pour la recherche. Les résultats sont affichés dans `search_results.html.twig` avec le terme de recherche mis en évidence via le filtre Twig `highlight` (défini dans `AppExtension`).

## Problèmes Actuels

- **Problème : Lenteur de l'application (temps de chargement très longs).**
    - **Symptôme** : Temps de chargement de page de 30+ secondes.
    - **Cause probable** : Performance des volumes Docker sur Windows/WSL (si non optimisé) ou autres configurations PHP/Apache.
    - **Solution** : Vérifier les allocations de ressources Docker Desktop (CPU/RAM). S'assurer que WSL2 est utilisé pour de meilleures performances de volume. XDebug a été vérifié et n'est pas la cause.

- **Problème : Messages d'erreur non stylisés.**
    - **Symptôme** : Messages d'erreur affichés en texte brut, sans le style rouge attendu.
    - **Cause probable** : Problème de spécificité CSS ou de surcharge par d'autres règles. La structure HTML des messages flash générés par JavaScript est un simple `div`, pas une `ul`/`li`.
    - **Solution** : Les règles CSS ont été ajustées pour cibler directement les `div.alert-error` et les `li` à l'intérieur des `form-errors`. Des tests supplémentaires sont nécessaires pour confirmer l'application complète des styles.

## Axes d'Amélioration Généraux

### Authentification
*   **Déplacer les styles inline** des templates `login.html.twig` et `register.html.twig` vers `general.css` pour une meilleure maintenabilité.

### Mails
*   **Améliorer le design des emails HTML :** Utiliser des styles inline et une structure compatible avec les clients de messagerie pour une meilleure présentation.
*   **Ajouter des versions texte brut des emails :** Pour une meilleure compatibilité et accessibilité.
*   **Préparer la configuration de production** pour le `MAILER_DSN`.

### Ajout de Produits
1.  **Amélioration de l'UX pour la présélection de la carte Pokémon :** Utiliser un écouteur d'événement `input` avec une fonction de "debounce" sur le champ "Numéro" pour une recherche plus réactive et optimisée des cartes Pokémon.

### Recherche
*   **Filtrage et Tri :** Ajouter des options de filtrage (par catégorie, prix, etc.) et de tri (par pertinence, prix, date) pour affiner les résultats.
*   **Performance de la requête :** Pour des bases de données très volumineuses, envisager des optimisations de requête (indexation Full-Text, moteur de recherche dédié).

### Panier
*   **Messages flash :** Ajouter `role="alert"` aux messages flash dans `templates/cart/index.html.twig` pour une meilleure accessibilité.
*   **CSS :** Centraliser les liens CSS de `product.css` et `common.css` dans `general.css` si ce n'est pas déjà fait.

### Favoris
*   **Méta-description :** Surcharger la meta-description dans `templates/favorite/index.html.twig` pour être plus spécifique à la page des favoris.
*   **CSS :** Centraliser les liens CSS de `products.css` dans `general.css` si ce n'est pas déjà fait.

### Messagerie
*   **Améliorer l'interactivité** (défilement automatique, indicateurs de lecture, notifications en temps réel).

### Gestion des Médias
*   **Clarifier la stratégie d'upload :** Décider si l'upload se fait via le `CollectionType` (recommandé pour plusieurs médias) ou via un `input` unique.

### Gestion des Commandes
*   **Validation des adresses :** S'assurer que l'adresse de livraison est sélectionnée ou créée avant l'achat.
*   **Notifications :** Envoyer un email de confirmation de commande à l'utilisateur.
*   **Historique des commandes :** Afficher l'historique des commandes pour l'utilisateur.
*   **Gestion des erreurs :** Améliorer la gestion des erreurs et les messages utilisateur en cas de problème lors de l'achat.

### Gestion des Adresses
*   **Obligation de l'adresse dans `Orders` :** Si toutes les commandes nécessitent une adresse, rendre la relation `addresses` non-nullable dans l'entité `Orders`.

### Gestion des Cartes Pokémon
*   **Champs commentés (`nomEn`, `nomJp`) :** Supprimer si non utilisés, ou décommenter et utiliser si nécessaire.

### Gestion des Produits
*   **Lien vers la page de détail :** Ajouter un lien direct depuis les cartes produits vers la page de détail du produit dans `product/index.html.twig` et `product/user_products.html.twig`.

## Historique des Modifications Apportées par Gemini CLI

Cette section détaille les modifications spécifiques apportées au code par Gemini CLI, organisées par fichier pour une meilleure traçabilité.

*Note : La commande `replace` a rencontré des difficultés avec les chemins de fichiers contenant des antislashs et les blocs de texte complexes. Les mises à jour ont été effectuées en utilisant la commande `write_file` pour réécrire l'intégralité des fichiers concernés, garantissant ainsi la cohérence et l'exactitude des modifications.*

### Contrôleurs

*   **`app/src/Controller/CartController.php`**
    *   Correction d'une erreur de syntaxe dans le message flash (`'Erreur lors de l\'achat'`).
    *   Intégration de la méthode `purchaseCart` du `CartService` dans la méthode `buy` pour gérer la finalisation de la commande.
    *   Ajout de la validation CSRF aux méthodes `updateQuantity` et `removeItem`.
    *   **Ajout des actions `checkout`, `payment`, `confirmOrder` pour le processus de commande et l'intégration Stripe.**
    *   **Injection de `StripeService`.**
    *   **Suppression des appels `->createView()` dans les méthodes `checkout`, `payment`, `confirmOrder`.**
*   **`app/src/Controller/FavoriteController.php`**
    *   Ajout de la protection CSRF à l'action `toggle`.
*   **`app/src/Controller/ProductController.php`**
    *   Mise à jour de la méthode `add` pour passer les extensions uniques au `ProductFormType`.
    *   Modification de l'API `/product/api/pokemon-card-details/{number}` pour inclure l'ID et l'URL de l'image de la carte Pokémon dans la réponse JSON.
    *   Intégration de la pagination avec KnpPaginatorBundle dans la méthode `search`.
    *   Modification de la méthode `search` pour récupérer les paramètres de filtrage et de tri et les passer au repository.
    *   Modification des méthodes `index` et `userProducts` pour utiliser la pagination.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` du `ProductFormType` dans la méthode `add`.**
*   **`app/src/Controller/MessageController.php`**
    *   Modification de la méthode `conversation` pour appeler `markMessagesAsRead`.
*   **`app/src/Controller/SecurityController.php`**
    *   **Suppression des appels `->createView()` dans les méthodes `register`, `resetPasswordRequest`, et `resetPassword`.**

### Entités

*   **`app/src/Entity/Addresses.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Choice`) pour les champs.
    *   Changement des types de `number` et `zipCode` de `int` à `string` pour permettre des formats plus flexibles (ex: "12bis").
    *   Ajout de constantes (`TYPE_HOME`, `TYPE_BILLING`, `TYPE_SHIPPING`) pour le champ `type` avec une contrainte `Assert\Choice`.
*   **`app/src/Entity/Media.php`**
    *   Ajout d'une propriété non mappée `file` de type `UploadedFile` pour faciliter la gestion des uploads via les formulaires.
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image_url` pour l'exposition via l'API.
    *   **Ajout d'une propriété non mappée `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Entity/Messages.php`**
    *   Ajout d'une propriété `isRead` (bool) et de ses accesseurs.
*   **`app/src/Entity/Orders.php`**
    *   Suppression du champ `content`.
    *   Ajout de constantes pour le champ `status` (`STATUS_PENDING`, `STATUS_COMPLETED`, `STATUS_CANCELLED`).
    *   Implémentation de `#[ORM\HasLifecycleCallbacks]` et de la méthode `setCreatedAtValue()` pour définir automatiquement la date de création à la persistance.
    *   **Ajout des propriétés `deliveryMethod` (string) et `shippingCost` (float).**
*   **`app/src/Entity/OrdersProducts.php`**
    *   **Redéfinie** pour fonctionner comme une entité de jointure Many-to-Many entre `Orders` et `Products`.
    *   Ajout des relations `ManyToOne` vers `Orders` et `Products`.
    *   Ajout des champs `quantity` et `price` pour stocker les détails de la ligne de commande.
*   **`app/src/Entity/PokemonCard.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Range`) pour les champs `number`, `name`, `rarity`, `extension`, `starRating`.
    *   Clarification du type de `starRating` en `int` avec une contrainte `Assert\Range` (0 à 5).
    *   Suppression des champs commentés (`nomEn`, `nomJp`).
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image` pour l'exposition via l'API.
*   **`app/src/Entity/Products.php`**
    *   Mise à jour de la relation `ordersProducts` en `OneToMany` vers `OrdersProducts` avec `cascade: ['persist', 'remove']` et `orphanRemoval: true` pour une gestion correcte des lignes de commande.
    *   **Suppression des propriétés `number`, `extension`, `rarity`, `type` et de leurs accessseurs.**

### Formulaires

*   **`app/src/Form/MediaType.php`**
    *   **Nouveau fichier créé** pour gérer l'upload des fichiers médias.
    *   Contient un champ `FileType` avec des contraintes de validation (taille, types MIME).
    *   Mappe le champ `file` à la propriété `file` de l'entité `Media`.
    *   **Ajout d'un champ `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Form/MessageFormType.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`) au champ `content`.
    *   Liaison du formulaire à l'entité `Messages` via `data_class`.
    *   Ajout de la protection CSRF explicite (`csrf_protection`, `csrf_token_id`).
*   **`app/src/Form/Product/ProductFormType.php`**
    *   Mise à jour des attributs `data-` pour les champs `number`, `extension`, `rarity`, `type` et `pokemonCard` afin de faciliter le ciblage JavaScript pour la présélection.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` (redondants avec `PokemonCard`).**
*   **`app/src/Form/CheckoutFormType.php`**:
    *   **Nouveau fichier créé** pour gérer la sélection de l'adresse de livraison et du mode de livraison.
    *   **Reçoit l'utilisateur via les options du formulaire.**

### Services

*   **`app/src/Service/CartService.php`**
    *   Injection de `EntityManagerInterface` dans le constructeur.
    *   **Mise à jour de la méthode `purchaseCart` pour accepter l'adresse, le mode et les frais de livraison.**
    *   **Mise à jour de la structure du panier en session pour stocker uniquement l'ID et la quantité du produit.**
    *   **Ajout de la méthode `getFullCart` pour récupérer les objets `Product` complets.**
    *   **Mise à jour des méthodes `addToCart`, `updateQuantity`, `validateStock`, `calculateTotal`, `getCartCount` pour utiliser la nouvelle structure du panier.**
*   **`app/src/Service/FileUploaderService.php`**
    *   **Nouveau fichier créé** pour gérer l'upload physique des fichiers sur le système de fichiers.
*   **`app/src/Service/MessageService.php`**
    *   Correction des erreurs de nommage (`recipient` remplacé par `receper`) dans les requêtes DQL et les appels de méthode pour correspondre à l'entité `Messages`.
    *   Ajout de la méthode `markMessagesAsRead`.
*   **`app/src/Service/ProductService.php`**
    *   Injection de `FileUploaderService` dans le constructeur.
    *   Implémentation de la logique `handleMediaUpload` pour traiter les fichiers uploadés et les lier à l'entité `Media`.
    *   Mise à jour des méthodes `createProduct` et `updateProduct` pour intégrer la gestion des uploads.

### Fichiers Statiques et Frontend Importants

*   **`composer.json`**: Définit les dépendances PHP du projet.
*   **`config/services.yaml`**: Configure les services de l'application, y compris le `FileUploaderService` et son répertoire cible.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour les interactions AJAX (panier, favoris, filtrage/présélection des cartes Pokémon, messages flash, aperçu dynamique des médias). **Mise à jour : logique de paiement Stripe, gestion des réponses JSON avec redirection, gestion des tokens CSRF pour panier/favoris, ajustement du pré-remplissage des produits, ajout de la logique de capture webcam.**
*   **`public/css/general.css`**: Fichier CSS principal pour le style global de l'application.
*   **`public/css/reset.css`**: Fichier de réinitialisation CSS.
*   **`templates/base.html.twig`**: Template de base de l'application, incluant les assets et la structure générale.
*   **`templates/product/add.html.twig`**: Template pour l'ajout de produits, avec la logique de présélection des cartes Pokémon. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/edit.html.twig`**: Template pour l'édition de produits. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/show.html.twig`**: Template pour l'affichage détaillé d'un produit, incluant les boutons d'édition/suppression. **Mise à jour : ajout de la protection CSRF au formulaire d'ajout au panier.**
*   **`templates/product/search_results.html.twig`**: Application du filtre Twig `highlight` sur `product.title` et `product.description` pour mettre en évidence le terme de recherche. Ajout des sélecteurs de filtrage par catégorie et rareté, ainsi que les options de tri par date, prix et nom. Intégration de la pagination avec KnpPaginatorBundle.
*   **`templates/security/login.html.twig`**: Suppression du style inline redondant sur le lien "Inscrivez-vous". **Mise à jour : ajout du lien "Mot de passe oublié ?".**
*   **`templates/security/register.html.twig`**: Déplacement des styles inline pour la civilité et le champ `agreeTerms` vers `general.css`.
*   **`templates/message/conversation.html.twig`**: Ajout du JavaScript pour le défilement automatique. Déplacement des styles inline vers `general.css`.
*   **`templates/cart/index.html.twig`**: **Mise à jour : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`templates/cart/checkout.html.twig`**: **Nouveau template créé** pour le processus de commande.
*   **`templates/cart/payment.html.twig`**: **Nouveau template créé** pour la page de paiement Stripe.
*   **`templates/emails/reset_password.html.twig`**: Nouveau template créé pour l'email de réinitialisation de mot de passe.
*   **`templates/emails/verification.html.twig`**: Nouveau template créé pour l'email de vérification de compte.
*   **`app/src/Twig/AppExtension.php`**: Nouveau fichier créé contenant le filtre Twig `highlight` pour la mise en évidence du texte.

## Analyse Détaillée des Flux Client

### 1. Flux d'Inscription (Register)

**Composants pertinents :**
*   `SecurityController.php` (`register` action, `sendVerificationEmail` method)
*   `Users.php` (Entité)
*   `RegisterFormType.php` (Formulaire)
*   `templates/security/register.html.twig` (Template)
*   `templates/emails/verification.html.twig` et `templates/emails/verification.txt.twig` (Templates d'email)
*   `src/Repository/UsersRepository.php`
*   `symfony/mailer` et `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/register`, remplit un formulaire avec ses informations (email, mot de passe, nom, prénom, civilité, numéro de téléphone), et soumet. En cas de succès, il reçoit un message flash et un email de vérification.

**Vérification et Observations :**
*   **`RegisterFormType.php`**: Structure solide avec tous les champs nécessaires et validations (`NotBlank`, `Length`, `Email`, `Regex`, `IsTrue`). Protection CSRF configurée.
*   **`SecurityController::register()`**: Implémentation correcte. Gère la redirection si déjà connecté, hache le mot de passe, attribue `ROLE_USER`, gère la vérification d'e-mail (génération/mise à jour de token, envoi d'email), et utilise les messages flash. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/register.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash, rendu du formulaire standard avec accessibilité. Styles spécifiques à consolider dans un fichier CSS externe.

### 2. Flux de Connexion (Login)

**Composants pertinents :**
*   `SecurityController.php` (`login` action)
*   `templates/security/login.html.twig` (Template)
*   `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/login`, remplit un formulaire avec son email et mot de passe, et soumet. En cas de succès, il est connecté et redirigé. En cas d'échec, un message d'erreur s'affiche.

**Vérification et Observations :**
*   **`SecurityController::login()`**: Implémentation standard et correcte. Gère la redirection si déjà connecté, récupère les erreurs et le dernier nom d'utilisateur. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/login.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash et erreurs. Formulaire HTML direct avec champs `_username` et `_password` et token CSRF. **Amélioration : ajout du lien "Mot de passe oublié ?" vers `app_reset_password_request`.**

### 3. Flux d'Ajout au Panier (Add Cart)

**Composants pertinents :**
*   `CartController.php` (`addToCart` action)
*   `CartService.php` (`addToCart` method, `getFullCart`, `calculateTotal`, `getCartCount`)
*   `Products.php` (Entité)
*   `templates/product/show.html.twig` (Template du bouton "Ajouter au panier")
*   `public/js/ajax.js` (Logique AJAX)
*   `templates/base.html.twig` (Badge du panier)

**Parcours utilisateur attendu :**
L'utilisateur clique sur un bouton "Ajouter au panier" sur une page produit. Le produit est ajouté au panier (stocké en session), le badge du panier est mis à jour, et un message flash s'affiche.

**Vérification et Observations :**
*   **`CartController::addToCart`**: Correctement configuré pour retourner un `JsonResponse` avec `success`, `cartCount`, et `total`. Gère les erreurs.
*   **`CartService::addToCart`**: A été modifié pour ne stocker que l'ID et la quantité en session, garantissant la fraîcheur des données produit.
*   **`templates/product/show.html.twig`**: Le formulaire a la classe `add-to-cart-form` pour l'interception AJAX. **Amélioration : ajout d'un champ CSRF caché au formulaire "Ajouter au panier" pour une sécurité accrue.**
*   **`public/js/ajax.js`**: Le gestionnaire d'événements `submit` intercepte le formulaire et traite la réponse JSON.

### 4. Flux de Gestion du Panier (Cart)

**Composants pertinents :**
*   `CartController.php` (`cart`, `updateQuantity`, `removeItem` actions)
*   `CartService.php` (`getFullCart`, `updateQuantity`, `removeFromCart`, `validateStock`)
*   `templates/cart/index.html.twig` (Template)
*   `public/js/ajax.js` (Logique AJAX)

**Parcours utilisateur attendu :**
L'utilisateur consulte son panier, peut ajuster les quantités, supprimer des articles, et voir le total.

**Vérification et Observations :**
*   **`CartController::cart`**: Passe le panier validé (`$validation['cart']`) au template.
*   **`CartService`**: Les méthodes `getFullCart()`, `calculateTotal()`, `validateStock()` retournent les données dans le format attendu par le template.
*   **`templates/cart/index.html.twig`**: Bien adapté à la nouvelle structure du panier, accède correctement aux propriétés des objets `Product`. **Amélioration : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`public/js/ajax.js`**: Les fonctions `updateQuantity` et `removeItem` sont bien configurées pour envoyer les requêtes AJAX et mettre à jour l'interface utilisateur.

### 5. Flux de Checkout (Validation de commande)

**Composants pertinents :**
*   `CartController.php` (`checkout` action)
*   `CheckoutFormType.php` (Formulaire)
*   `templates/cart/checkout.html.twig` (Template)
*   `Addresses.php` (Entité)
*   `Users.php` (Entité)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page `/checkout` après avoir cliqué sur "Passer la commande" depuis le panier. Il choisit son adresse de livraison et son mode de livraison, puis soumet le formulaire pour passer à l'étape de paiement.

**Vérification et Observations :**
*   **`CartController::checkout()`**: Vérifie si le panier est vide, crée et gère le `CheckoutFormType`, calcule le montant total, crée une intention de paiement Stripe, stocke les informations de livraison en session, et redirige vers la page de paiement. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CheckoutFormType.php`**: Gère la sélection de l'adresse de livraison (`EntityType` pour `Addresses` de type `TYPE_SHIPPING`) et du mode de livraison (`ChoiceType`). Le champ `shippingCost` est un `HiddenType` non mappé. **Correction : reçoit l'utilisateur via les options du formulaire pour la `query_builder`.**
*   **`templates/cart/checkout.html.twig`**: Bonne structure pour la page de checkout, affiche le récapitulatif du panier et le formulaire. Le JavaScript met à jour le total dynamiquement. **Amélioration : afficher les erreurs de validation du formulaire pour chaque champ. Ajouter l'image du produit dans le récapitulatif du panier.**

### 6. Flux de Paiement (Stripe)

**Composants pertinents :**
*   `CartController.php` (`payment` action, `confirmOrder` action)
*   `StripeService.php`
*   `templates/cart/payment.html.twig` (Template)
*   `public/js/ajax.js` (Logique Stripe)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page de paiement après avoir validé le formulaire de checkout. Il voit le Payment Element de Stripe, effectue le paiement, et la commande est finalisée.

**Vérification et Observations :**
*   **`CartController::payment()`**: Récupère le `clientSecret` et la clé publique Stripe (`$_ENV['STRIPE_PUBLIC_KEY']`) et les passe au template. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CartController::confirmOrder()`**: Récupère les informations de livraison de la session, valide les données, appelle `cartService->purchaseCart()` pour finaliser la commande, nettoie la session, et retourne un `JsonResponse`.
*   **`templates/cart/payment.html.twig`**: Bonne structure pour la page de paiement, affiche le montant total et l'emplacement pour le Payment Element. Les variables JavaScript sont passées correctement.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour initialiser Stripe, gérer le Payment Element, confirmer le paiement et appeler `confirmOrder()` après succès. Gère les messages d'erreur et les redirections.

## Problèmes Actuels

- **Problème : Lenteur de l'application (temps de chargement très longs).**
    - **Symptôme** : Temps de chargement de page de 30+ secondes.
    - **Cause probable** : Performance des volumes Docker sur Windows/WSL (si non optimisé) ou autres configurations PHP/Apache.
    - **Solution** : Vérifier les allocations de ressources Docker Desktop (CPU/RAM). S'assurer que WSL2 est utilisé pour de meilleures performances de volume. XDebug a été vérifié et n'est pas la cause.

- **Problème : Messages d'erreur non stylisés.**
    - **Symptôme** : Messages d'erreur affichés en texte brut, sans le style rouge attendu.
    - **Cause probable** : Problème de spécificité CSS ou de surcharge par d'autres règles. La structure HTML des messages flash générés par JavaScript est un simple `div`, pas une `ul`/`li`.
    - **Solution** : Les règles CSS ont été ajustées pour cibler directement les `div.alert-error` et les `li` à l'intérieur des `form-errors`. Des tests supplémentaires sont nécessaires pour confirmer l'application complète des styles.

## Axes d'Amélioration Généraux

### Authentification
*   **Déplacer les styles inline** des templates `login.html.twig` et `register.html.twig` vers `general.css` pour une meilleure maintenabilité.

### Mails
*   **Améliorer le design des emails HTML :** Utiliser des styles inline et une structure compatible avec les clients de messagerie pour une meilleure présentation.
*   **Ajouter des versions texte brut des emails :** Pour une meilleure compatibilité et accessibilité.
*   **Préparer la configuration de production** pour le `MAILER_DSN`.

### Ajout de Produits
1.  **Amélioration de l'UX pour la présélection de la carte Pokémon :** Utiliser un écouteur d'événement `input` avec une fonction de "debounce" sur le champ "Numéro" pour une recherche plus réactive et optimisée des cartes Pokémon.

### Recherche
*   **Filtrage et Tri :** Ajouter des options de filtrage (par catégorie, prix, etc.) et de tri (par pertinence, prix, date) pour affiner les résultats.
*   **Performance de la requête :** Pour des bases de données très volumineuses, envisager des optimisations de requête (indexation Full-Text, moteur de recherche dédié).

### Panier
*   **Messages flash :** Ajouter `role="alert"` aux messages flash dans `templates/cart/index.html.twig` pour une meilleure accessibilité.
*   **CSS :** Centraliser les liens CSS de `product.css` et `common.css` dans `general.css` si ce n'est pas déjà fait.

### Favoris
*   **Méta-description :** Surcharger la meta-description dans `templates/favorite/index.html.twig` pour être plus spécifique à la page des favoris.
*   **CSS :** Centraliser les liens CSS de `products.css` dans `general.css` si ce n'est pas déjà fait.

### Messagerie
*   **Améliorer l'interactivité** (défilement automatique, indicateurs de lecture, notifications en temps réel).

### Gestion des Médias
*   **Clarifier la stratégie d'upload :** Décider si l'upload se fait via le `CollectionType` (recommandé pour plusieurs médias) ou via un `input` unique.

### Gestion des Commandes
*   **Validation des adresses :** S'assurer que l'adresse de livraison est sélectionnée ou créée avant l'achat.
*   **Notifications :** Envoyer un email de confirmation de commande à l'utilisateur.
*   **Historique des commandes :** Afficher l'historique des commandes pour l'utilisateur.
*   **Gestion des erreurs :** Améliorer la gestion des erreurs et les messages utilisateur en cas de problème lors de l'achat.

### Gestion des Adresses
*   **Obligation de l'adresse dans `Orders` :** Si toutes les commandes nécessitent une adresse, rendre la relation `addresses` non-nullable dans l'entité `Orders`.

### Gestion des Cartes Pokémon
*   **Champs commentés (`nomEn`, `nomJp`) :** Supprimer si non utilisés, ou décommenter et utiliser si nécessaire.

### Gestion des Produits
*   **Lien vers la page de détail :** Ajouter un lien direct depuis les cartes produits vers la page de détail du produit dans `product/index.html.twig` et `product/user_products.html.twig`.

## Historique des Modifications Apportées par Gemini CLI

Cette section détaille les modifications spécifiques apportées au code par Gemini CLI, organisées par fichier pour une meilleure traçabilité.

*Note : La commande `replace` a rencontré des difficultés avec les chemins de fichiers contenant des antislashs et les blocs de texte complexes. Les mises à jour ont été effectuées en utilisant la commande `write_file` pour réécrire l'intégralité des fichiers concernés, garantissant ainsi la cohérence et l'exactitude des modifications.*

### Contrôleurs

*   **`app/src/Controller/CartController.php`**
    *   Correction d'une erreur de syntaxe dans le message flash (`'Erreur lors de l\'achat'`).
    *   Intégration de la méthode `purchaseCart` du `CartService` dans la méthode `buy` pour gérer la finalisation de la commande.
    *   Ajout de la validation CSRF aux méthodes `updateQuantity` et `removeItem`.
    *   **Ajout des actions `checkout`, `payment`, `confirmOrder` pour le processus de commande et l'intégration Stripe.**
    *   **Injection de `StripeService`.**
    *   **Suppression des appels `->createView()` dans les méthodes `checkout`, `payment`, `confirmOrder`.**
*   **`app/src/Controller/FavoriteController.php`**
    *   Ajout de la protection CSRF à l'action `toggle`.
*   **`app/src/Controller/ProductController.php`**
    *   Mise à jour de la méthode `add` pour passer les extensions uniques au `ProductFormType`.
    *   Modification de l'API `/product/api/pokemon-card-details/{number}` pour inclure l'ID et l'URL de l'image de la carte Pokémon dans la réponse JSON.
    *   Intégration de la pagination avec KnpPaginatorBundle dans la méthode `search`.
    *   Modification de la méthode `search` pour récupérer les paramètres de filtrage et de tri et les passer au repository.
    *   Modification des méthodes `index` et `userProducts` pour utiliser la pagination.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` du `ProductFormType` dans la méthode `add`.**
*   **`app/src/Controller/MessageController.php`**
    *   Modification de la méthode `conversation` pour appeler `markMessagesAsRead`.
*   **`app/src/Controller/SecurityController.php`**
    *   **Suppression des appels `->createView()` dans les méthodes `register`, `resetPasswordRequest`, et `resetPassword`.**

### Entités

*   **`app/src/Entity/Addresses.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Choice`) pour les champs.
    *   Changement des types de `number` et `zipCode` de `int` à `string` pour permettre des formats plus flexibles (ex: "12bis").
    *   Ajout de constantes (`TYPE_HOME`, `TYPE_BILLING`, `TYPE_SHIPPING`) pour le champ `type` avec une contrainte `Assert\Choice`.
*   **`app/src/Entity/Media.php`**
    *   Ajout d'une propriété non mappée `file` de type `UploadedFile` pour faciliter la gestion des uploads via les formulaires.
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image_url` pour l'exposition via l'API.
    *   **Ajout d'une propriété non mappée `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Entity/Messages.php`**
    *   Ajout d'une propriété `isRead` (bool) et de ses accesseurs.
*   **`app/src/Entity/Orders.php`**
    *   Suppression du champ `content`.
    *   Ajout de constantes pour le champ `status` (`STATUS_PENDING`, `STATUS_COMPLETED`, `STATUS_CANCELLED`).
    *   Implémentation de `#[ORM\HasLifecycleCallbacks]` et de la méthode `setCreatedAtValue()` pour définir automatiquement la date de création à la persistance.
    *   **Ajout des propriétés `deliveryMethod` (string) et `shippingCost` (float).**
*   **`app/src/Entity/OrdersProducts.php`**
    *   **Redéfinie** pour fonctionner comme une entité de jointure Many-to-Many entre `Orders` et `Products`.
    *   Ajout des relations `ManyToOne` vers `Orders` et `Products`.
    *   Ajout des champs `quantity` et `price` pour stocker les détails de la ligne de commande.
*   **`app/src/Entity/PokemonCard.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Range`) pour les champs `number`, `name`, `rarity`, `extension`, `starRating`.
    *   Clarification du type de `starRating` en `int` avec une contrainte `Assert\Range` (0 à 5).
    *   Suppression des champs commentés (`nomEn`, `nomJp`).
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image` pour l'exposition via l'API.
*   **`app/src/Entity/Products.php`**
    *   Mise à jour de la relation `ordersProducts` en `OneToMany` vers `OrdersProducts` avec `cascade: ['persist', 'remove']` et `orphanRemoval: true` pour une gestion correcte des lignes de commande.
    *   **Suppression des propriétés `number`, `extension`, `rarity`, `type` et de leurs accessseurs.**

### Formulaires

*   **`app/src/Form/MediaType.php`**
    *   **Nouveau fichier créé** pour gérer l'upload des fichiers médias.
    *   Contient un champ `FileType` avec des contraintes de validation (taille, types MIME).
    *   Mappe le champ `file` à la propriété `file` de l'entité `Media`.
    *   **Ajout d'un champ `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Form/MessageFormType.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`) au champ `content`.
    *   Liaison du formulaire à l'entité `Messages` via `data_class`.
    *   Ajout de la protection CSRF explicite (`csrf_protection`, `csrf_token_id`).
*   **`app/src/Form/Product/ProductFormType.php`**
    *   Mise à jour des attributs `data-` pour les champs `number`, `extension`, `rarity`, `type` et `pokemonCard` afin de faciliter le ciblage JavaScript pour la présélection.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` (redondants avec `PokemonCard`).**
*   **`app/src/Form/CheckoutFormType.php`**:
    *   **Nouveau fichier créé** pour gérer la sélection de l'adresse de livraison et du mode de livraison.
    *   **Reçoit l'utilisateur via les options du formulaire.**

### Services

*   **`app/src/Service/CartService.php`**
    *   Injection de `EntityManagerInterface` dans le constructeur.
    *   **Mise à jour de la méthode `purchaseCart` pour accepter l'adresse, le mode et les frais de livraison.**
    *   **Mise à jour de la structure du panier en session pour stocker uniquement l'ID et la quantité du produit.**
    *   **Ajout de la méthode `getFullCart` pour récupérer les objets `Product` complets.**
    *   **Mise à jour des méthodes `addToCart`, `updateQuantity`, `validateStock`, `calculateTotal`, `getCartCount` pour utiliser la nouvelle structure du panier.**
*   **`app/src/Service/FileUploaderService.php`**
    *   **Nouveau fichier créé** pour gérer l'upload physique des fichiers sur le système de fichiers.
*   **`app/src/Service/MessageService.php`**
    *   Correction des erreurs de nommage (`recipient` remplacé par `receper`) dans les requêtes DQL et les appels de méthode pour correspondre à l'entité `Messages`.
    *   Ajout de la méthode `markMessagesAsRead`.
*   **`app/src/Service/ProductService.php`**
    *   Injection de `FileUploaderService` dans le constructeur.
    *   Implémentation de la logique `handleMediaUpload` pour traiter les fichiers uploadés et les lier à l'entité `Media`.
    *   Mise à jour des méthodes `createProduct` et `updateProduct` pour intégrer la gestion des uploads.

### Fichiers Statiques et Frontend Importants

*   **`composer.json`**: Définit les dépendances PHP du projet.
*   **`config/services.yaml`**: Configure les services de l'application, y compris le `FileUploaderService` et son répertoire cible.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour les interactions AJAX (panier, favoris, filtrage/présélection des cartes Pokémon, messages flash, aperçu dynamique des médias). **Mise à jour : logique de paiement Stripe, gestion des réponses JSON avec redirection, gestion des tokens CSRF pour panier/favoris, ajustement du pré-remplissage des produits, ajout de la logique de capture webcam.**
*   **`public/css/general.css`**: Fichier CSS principal pour le style global de l'application.
*   **`public/css/reset.css`**: Fichier de réinitialisation CSS.
*   **`templates/base.html.twig`**: Template de base de l'application, incluant les assets et la structure générale.
*   **`templates/product/add.html.twig`**: Template pour l'ajout de produits, avec la logique de présélection des cartes Pokémon. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/edit.html.twig`**: Template pour l'édition de produits. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/show.html.twig`**: Template pour l'affichage détaillé d'un produit, incluant les boutons d'édition/suppression. **Mise à jour : ajout de la protection CSRF au formulaire d'ajout au panier.**
*   **`templates/product/search_results.html.twig`**: Application du filtre Twig `highlight` sur `product.title` et `product.description` pour mettre en évidence le terme de recherche. Ajout des sélecteurs de filtrage par catégorie et rareté, ainsi que les options de tri par date, prix et nom. Intégration de la pagination avec KnpPaginatorBundle.
*   **`templates/security/login.html.twig`**: Suppression du style inline redondant sur le lien "Inscrivez-vous". **Mise à jour : ajout du lien "Mot de passe oublié ?".**
*   **`templates/security/register.html.twig`**: Déplacement des styles inline pour la civilité et le champ `agreeTerms` vers `general.css`.
*   **`templates/message/conversation.html.twig`**: Ajout du JavaScript pour le défilement automatique. Déplacement des styles inline vers `general.css`.
*   **`templates/cart/index.html.twig`**: **Mise à jour : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`templates/cart/checkout.html.twig`**: **Nouveau template créé** pour le processus de commande.
*   **`templates/cart/payment.html.twig`**: **Nouveau template créé** pour la page de paiement Stripe.
*   **`templates/emails/reset_password.html.twig`**: Nouveau template créé pour l'email de réinitialisation de mot de passe.
*   **`templates/emails/verification.html.twig`**: Nouveau template créé pour l'email de vérification de compte.
*   **`app/src/Twig/AppExtension.php`**: Nouveau fichier créé contenant le filtre Twig `highlight` pour la mise en évidence du texte.

## Analyse Détaillée des Flux Client

### 1. Flux d'Inscription (Register)

**Composants pertinents :**
*   `SecurityController.php` (`register` action, `sendVerificationEmail` method)
*   `Users.php` (Entité)
*   `RegisterFormType.php` (Formulaire)
*   `templates/security/register.html.twig` (Template)
*   `templates/emails/verification.html.twig` et `templates/emails/verification.txt.twig` (Templates d'email)
*   `src/Repository/UsersRepository.php`
*   `symfony/mailer` et `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/register`, remplit un formulaire avec ses informations (email, mot de passe, nom, prénom, civilité, numéro de téléphone), et soumet. En cas de succès, il reçoit un message flash et un email de vérification.

**Vérification et Observations :**
*   **`RegisterFormType.php`**: Structure solide avec tous les champs nécessaires et validations (`NotBlank`, `Length`, `Email`, `Regex`, `IsTrue`). Protection CSRF configurée.
*   **`SecurityController::register()`**: Implémentation correcte. Gère la redirection si déjà connecté, hache le mot de passe, attribue `ROLE_USER`, gère la vérification d'e-mail (génération/mise à jour de token, envoi d'email), et utilise les messages flash. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/register.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash, rendu du formulaire standard avec accessibilité. Styles spécifiques à consolider dans un fichier CSS externe.

### 2. Flux de Connexion (Login)

**Composants pertinents :**
*   `SecurityController.php` (`login` action)
*   `templates/security/login.html.twig` (Template)
*   `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/login`, remplit un formulaire avec son email et mot de passe, et soumet. En cas de succès, il est connecté et redirigé. En cas d'échec, un message d'erreur s'affiche.

**Vérification et Observations :**
*   **`SecurityController::login()`**: Implémentation standard et correcte. Gère la redirection si déjà connecté, récupère les erreurs et le dernier nom d'utilisateur. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/login.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash et erreurs. Formulaire HTML direct avec champs `_username` et `_password` et token CSRF. **Amélioration : ajout du lien "Mot de passe oublié ?" vers `app_reset_password_request`.**

### 3. Flux d'Ajout au Panier (Add Cart)

**Composants pertinents :**
*   `CartController.php` (`addToCart` action)
*   `CartService.php` (`addToCart` method, `getFullCart`, `calculateTotal`, `getCartCount`)
*   `Products.php` (Entité)
*   `templates/product/show.html.twig` (Template du bouton "Ajouter au panier")
*   `public/js/ajax.js` (Logique AJAX)
*   `templates/base.html.twig` (Badge du panier)

**Parcours utilisateur attendu :**
L'utilisateur clique sur un bouton "Ajouter au panier" sur une page produit. Le produit est ajouté au panier (stocké en session), le badge du panier est mis à jour, et un message flash s'affiche.

**Vérification et Observations :**
*   **`CartController::addToCart`**: Correctement configuré pour retourner un `JsonResponse` avec `success`, `cartCount`, et `total`. Gère les erreurs.
*   **`CartService::addToCart`**: A été modifié pour ne stocker que l'ID et la quantité en session, garantissant la fraîcheur des données produit.
*   **`templates/product/show.html.twig`**: Le formulaire a la classe `add-to-cart-form` pour l'interception AJAX. **Amélioration : ajout d'un champ CSRF caché au formulaire "Ajouter au panier" pour une sécurité accrue.**
*   **`public/js/ajax.js`**: Le gestionnaire d'événements `submit` intercepte le formulaire et traite la réponse JSON.

### 4. Flux de Gestion du Panier (Cart)

**Composants pertinents :**
*   `CartController.php` (`cart`, `updateQuantity`, `removeItem` actions)
*   `CartService.php` (`getFullCart`, `updateQuantity`, `removeFromCart`, `validateStock`)
*   `templates/cart/index.html.twig` (Template)
*   `public/js/ajax.js` (Logique AJAX)

**Parcours utilisateur attendu :**
L'utilisateur consulte son panier, peut ajuster les quantités, supprimer des articles, et voir le total.

**Vérification et Observations :**
*   **`CartController::cart`**: Passe le panier validé (`$validation['cart']`) au template.
*   **`CartService`**: Les méthodes `getFullCart()`, `calculateTotal()`, `validateStock()` retournent les données dans le format attendu par le template.
*   **`templates/cart/index.html.twig`**: Bien adapté à la nouvelle structure du panier, accède correctement aux propriétés des objets `Product`. **Amélioration : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`public/js/ajax.js`**: Les fonctions `updateQuantity` et `removeItem` sont bien configurées pour envoyer les requêtes AJAX et mettre à jour l'interface utilisateur.

### 5. Flux de Checkout (Validation de commande)

**Composants pertinents :**
*   `CartController.php` (`checkout` action)
*   `CheckoutFormType.php` (Formulaire)
*   `templates/cart/checkout.html.twig` (Template)
*   `Addresses.php` (Entité)
*   `Users.php` (Entité)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page `/checkout` après avoir cliqué sur "Passer la commande" depuis le panier. Il choisit son adresse de livraison et son mode de livraison, puis soumet le formulaire pour passer à l'étape de paiement.

**Vérification et Observations :**
*   **`CartController::checkout()`**: Vérifie si le panier est vide, crée et gère le `CheckoutFormType`, calcule le montant total, crée une intention de paiement Stripe, stocke les informations de livraison en session, et redirige vers la page de paiement. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CheckoutFormType.php`**: Gère la sélection de l'adresse de livraison (`EntityType` pour `Addresses` de type `TYPE_SHIPPING`) et du mode de livraison (`ChoiceType`). Le champ `shippingCost` est un `HiddenType` non mappé. **Correction : reçoit l'utilisateur via les options du formulaire pour la `query_builder`.**
*   **`templates/cart/checkout.html.twig`**: Bonne structure pour la page de checkout, affiche le récapitulatif du panier et le formulaire. Le JavaScript met à jour le total dynamiquement. **Amélioration : afficher les erreurs de validation du formulaire pour chaque champ. Ajouter l'image du produit dans le récapitulatif du panier.**

### 6. Flux de Paiement (Stripe)

**Composants pertinents :**
*   `CartController.php` (`payment` action, `confirmOrder` action)
*   `StripeService.php`
*   `templates/cart/payment.html.twig` (Template)
*   `public/js/ajax.js` (Logique Stripe)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page de paiement après avoir validé le formulaire de checkout. Il voit le Payment Element de Stripe, effectue le paiement, et la commande est finalisée.

**Vérification et Observations :**
*   **`CartController::payment()`**: Récupère le `clientSecret` et la clé publique Stripe (`$_ENV['STRIPE_PUBLIC_KEY']`) et les passe au template. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CartController::confirmOrder()`**: Récupère les informations de livraison de la session, valide les données, appelle `cartService->purchaseCart()` pour finaliser la commande, nettoie la session, et retourne un `JsonResponse`.
*   **`templates/cart/payment.html.twig`**: Bonne structure pour la page de paiement, affiche le montant total et l'emplacement pour le Payment Element. Les variables JavaScript sont passées correctement.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour initialiser Stripe, gérer le Payment Element, confirmer le paiement et appeler `confirmOrder()` après succès. Gère les messages d'erreur et les redirections.

## Problèmes Actuels

- **Problème : Lenteur de l'application (temps de chargement très longs).**
    - **Symptôme** : Temps de chargement de page de 30+ secondes.
    - **Cause probable** : Performance des volumes Docker sur Windows/WSL (si non optimisé) ou autres configurations PHP/Apache.
    - **Solution** : Vérifier les allocations de ressources Docker Desktop (CPU/RAM). S'assurer que WSL2 est utilisé pour de meilleures performances de volume. XDebug a été vérifié et n'est pas la cause.

- **Problème : Messages d'erreur non stylisés.**
    - **Symptôme** : Messages d'erreur affichés en texte brut, sans le style rouge attendu.
    - **Cause probable** : Problème de spécificité CSS ou de surcharge par d'autres règles. La structure HTML des messages flash générés par JavaScript est un simple `div`, pas une `ul`/`li`.
    - **Solution** : Les règles CSS ont été ajustées pour cibler directement les `div.alert-error` et les `li` à l'intérieur des `form-errors`. Des tests supplémentaires sont nécessaires pour confirmer l'application complète des styles.

## Axes d'Amélioration Généraux

### Authentification
*   **Déplacer les styles inline** des templates `login.html.twig` et `register.html.twig` vers `general.css` pour une meilleure maintenabilité.

### Mails
*   **Améliorer le design des emails HTML :** Utiliser des styles inline et une structure compatible avec les clients de messagerie pour une meilleure présentation.
*   **Ajouter des versions texte brut des emails :** Pour une meilleure compatibilité et accessibilité.
*   **Préparer la configuration de production** pour le `MAILER_DSN`.

### Ajout de Produits
1.  **Amélioration de l'UX pour la présélection de la carte Pokémon :** Utiliser un écouteur d'événement `input` avec une fonction de "debounce" sur le champ "Numéro" pour une recherche plus réactive et optimisée des cartes Pokémon.

### Recherche
*   **Filtrage et Tri :** Ajouter des options de filtrage (par catégorie, prix, etc.) et de tri (par pertinence, prix, date) pour affiner les résultats.
*   **Performance de la requête :** Pour des bases de données très volumineuses, envisager des optimisations de requête (indexation Full-Text, moteur de recherche dédié).

### Panier
*   **Messages flash :** Ajouter `role="alert"` aux messages flash dans `templates/cart/index.html.twig` pour une meilleure accessibilité.
*   **CSS :** Centraliser les liens CSS de `product.css` et `common.css` dans `general.css` si ce n'est pas déjà fait.

### Favoris
*   **Méta-description :** Surcharger la meta-description dans `templates/favorite/index.html.twig` pour être plus spécifique à la page des favoris.
*   **CSS :** Centraliser les liens CSS de `products.css` dans `general.css` si ce n'est pas déjà fait.

### Messagerie
*   **Améliorer l'interactivité** (défilement automatique, indicateurs de lecture, notifications en temps réel).

### Gestion des Médias
*   **Clarifier la stratégie d'upload :** Décider si l'upload se fait via le `CollectionType` (recommandé pour plusieurs médias) ou via un `input` unique.

### Gestion des Commandes
*   **Validation des adresses :** S'assurer que l'adresse de livraison est sélectionnée ou créée avant l'achat.
*   **Notifications :** Envoyer un email de confirmation de commande à l'utilisateur.
*   **Historique des commandes :** Afficher l'historique des commandes pour l'utilisateur.
*   **Gestion des erreurs :** Améliorer la gestion des erreurs et les messages utilisateur en cas de problème lors de l'achat.

### Gestion des Adresses
*   **Obligation de l'adresse dans `Orders` :** Si toutes les commandes nécessitent une adresse, rendre la relation `addresses` non-nullable dans l'entité `Orders`.

### Gestion des Cartes Pokémon
*   **Champs commentés (`nomEn`, `nomJp`) :** Supprimer si non utilisés, ou décommenter et utiliser si nécessaire.

### Gestion des Produits
*   **Lien vers la page de détail :** Ajouter un lien direct depuis les cartes produits vers la page de détail du produit dans `product/index.html.twig` et `product/user_products.html.twig`.

## Historique des Modifications Apportées par Gemini CLI

Cette section détaille les modifications spécifiques apportées au code par Gemini CLI, organisées par fichier pour une meilleure traçabilité.

*Note : La commande `replace` a rencontré des difficultés avec les chemins de fichiers contenant des antislashs et les blocs de texte complexes. Les mises à jour ont été effectuées en utilisant la commande `write_file` pour réécrire l'intégralité des fichiers concernés, garantissant ainsi la cohérence et l'exactitude des modifications.*

### Contrôleurs

*   **`app/src/Controller/CartController.php`**
    *   Correction d'une erreur de syntaxe dans le message flash (`'Erreur lors de l\'achat'`).
    *   Intégration de la méthode `purchaseCart` du `CartService` dans la méthode `buy` pour gérer la finalisation de la commande.
    *   Ajout de la validation CSRF aux méthodes `updateQuantity` et `removeItem`.
    *   **Ajout des actions `checkout`, `payment`, `confirmOrder` pour le processus de commande et l'intégration Stripe.**
    *   **Injection de `StripeService`.**
    *   **Suppression des appels `->createView()` dans les méthodes `checkout`, `payment`, `confirmOrder`.**
*   **`app/src/Controller/FavoriteController.php`**
    *   Ajout de la protection CSRF à l'action `toggle`.
*   **`app/src/Controller/ProductController.php`**
    *   Mise à jour de la méthode `add` pour passer les extensions uniques au `ProductFormType`.
    *   Modification de l'API `/product/api/pokemon-card-details/{number}` pour inclure l'ID et l'URL de l'image de la carte Pokémon dans la réponse JSON.
    *   Intégration de la pagination avec KnpPaginatorBundle dans la méthode `search`.
    *   Modification de la méthode `search` pour récupérer les paramètres de filtrage et de tri et les passer au repository.
    *   Modification des méthodes `index` et `userProducts` pour utiliser la pagination.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` du `ProductFormType` dans la méthode `add`.**
*   **`app/src/Controller/MessageController.php`**
    *   Modification de la méthode `conversation` pour appeler `markMessagesAsRead`.
*   **`app/src/Controller/SecurityController.php`**
    *   **Suppression des appels `->createView()` dans les méthodes `register`, `resetPasswordRequest`, et `resetPassword`.**

### Entités

*   **`app/src/Entity/Addresses.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Choice`) pour les champs.
    *   Changement des types de `number` et `zipCode` de `int` à `string` pour permettre des formats plus flexibles (ex: "12bis").
    *   Ajout de constantes (`TYPE_HOME`, `TYPE_BILLING`, `TYPE_SHIPPING`) pour le champ `type` avec une contrainte `Assert\Choice`.
*   **`app/src/Entity/Media.php`**
    *   Ajout d'une propriété non mappée `file` de type `UploadedFile` pour faciliter la gestion des uploads via les formulaires.
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image_url` pour l'exposition via l'API.
    *   **Ajout d'une propriété non mappée `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Entity/Messages.php`**
    *   Ajout d'une propriété `isRead` (bool) et de ses accesseurs.
*   **`app/src/Entity/Orders.php`**
    *   Suppression du champ `content`.
    *   Ajout de constantes pour le champ `status` (`STATUS_PENDING`, `STATUS_COMPLETED`, `STATUS_CANCELLED`).
    *   Implémentation de `#[ORM\HasLifecycleCallbacks]` et de la méthode `setCreatedAtValue()` pour définir automatiquement la date de création à la persistance.
    *   **Ajout des propriétés `deliveryMethod` (string) et `shippingCost` (float).**
*   **`app/src/Entity/OrdersProducts.php`**
    *   **Redéfinie** pour fonctionner comme une entité de jointure Many-to-Many entre `Orders` et `Products`.
    *   Ajout des relations `ManyToOne` vers `Orders` et `Products`.
    *   Ajout des champs `quantity` et `price` pour stocker les détails de la ligne de commande.
*   **`app/src/Entity/PokemonCard.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Range`) pour les champs `number`, `name`, `rarity`, `extension`, `starRating`.
    *   Clarification du type de `starRating` en `int` avec une contrainte `Assert\Range` (0 à 5).
    *   Suppression des champs commentés (`nomEn`, `nomJp`).
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image` pour l'exposition via l'API.
*   **`app/src/Entity/Products.php`**
    *   Mise à jour de la relation `ordersProducts` en `OneToMany` vers `OrdersProducts` avec `cascade: ['persist', 'remove']` et `orphanRemoval: true` pour une gestion correcte des lignes de commande.
    *   **Suppression des propriétés `number`, `extension`, `rarity`, `type` et de leurs accessseurs.**

### Formulaires

*   **`app/src/Form/MediaType.php`**
    *   **Nouveau fichier créé** pour gérer l'upload des fichiers médias.
    *   Contient un champ `FileType` avec des contraintes de validation (taille, types MIME).
    *   Mappe le champ `file` à la propriété `file` de l'entité `Media`.
    *   **Ajout d'un champ `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Form/MessageFormType.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`) au champ `content`.
    *   Liaison du formulaire à l'entité `Messages` via `data_class`.
    *   Ajout de la protection CSRF explicite (`csrf_protection`, `csrf_token_id`).
*   **`app/src/Form/Product/ProductFormType.php`**
    *   Mise à jour des attributs `data-` pour les champs `number`, `extension`, `rarity`, `type` et `pokemonCard` afin de faciliter le ciblage JavaScript pour la présélection.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` (redondants avec `PokemonCard`).**
*   **`app/src/Form/CheckoutFormType.php`**:
    *   **Nouveau fichier créé** pour gérer la sélection de l'adresse de livraison et du mode de livraison.
    *   **Reçoit l'utilisateur via les options du formulaire.**

### Services

*   **`app/src/Service/CartService.php`**
    *   Injection de `EntityManagerInterface` dans le constructeur.
    *   **Mise à jour de la méthode `purchaseCart` pour accepter l'adresse, le mode et les frais de livraison.**
    *   **Mise à jour de la structure du panier en session pour stocker uniquement l'ID et la quantité du produit.**
    *   **Ajout de la méthode `getFullCart` pour récupérer les objets `Product` complets.**
    *   **Mise à jour des méthodes `addToCart`, `updateQuantity`, `validateStock`, `calculateTotal`, `getCartCount` pour utiliser la nouvelle structure du panier.**
*   **`app/src/Service/FileUploaderService.php`**
    *   **Nouveau fichier créé** pour gérer l'upload physique des fichiers sur le système de fichiers.
*   **`app/src/Service/MessageService.php`**
    *   Correction des erreurs de nommage (`recipient` remplacé par `receper`) dans les requêtes DQL et les appels de méthode pour correspondre à l'entité `Messages`.
    *   Ajout de la méthode `markMessagesAsRead`.
*   **`app/src/Service/ProductService.php`**
    *   Injection de `FileUploaderService` dans le constructeur.
    *   Implémentation de la logique `handleMediaUpload` pour traiter les fichiers uploadés et les lier à l'entité `Media`.
    *   Mise à jour des méthodes `createProduct` et `updateProduct` pour intégrer la gestion des uploads.

### Fichiers Statiques et Frontend Importants

*   **`composer.json`**: Définit les dépendances PHP du projet.
*   **`config/services.yaml`**: Configure les services de l'application, y compris le `FileUploaderService` et son répertoire cible.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour les interactions AJAX (panier, favoris, filtrage/présélection des cartes Pokémon, messages flash, aperçu dynamique des médias). **Mise à jour : logique de paiement Stripe, gestion des réponses JSON avec redirection, gestion des tokens CSRF pour panier/favoris, ajustement du pré-remplissage des produits, ajout de la logique de capture webcam.**
*   **`public/css/general.css`**: Fichier CSS principal pour le style global de l'application.
*   **`public/css/reset.css`**: Fichier de réinitialisation CSS.
*   **`templates/base.html.twig`**: Template de base de l'application, incluant les assets et la structure générale.
*   **`templates/product/add.html.twig`**: Template pour l'ajout de produits, avec la logique de présélection des cartes Pokémon. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/edit.html.twig`**: Template pour l'édition de produits. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/show.html.twig`**: Template pour l'affichage détaillé d'un produit, incluant les boutons d'édition/suppression. **Mise à jour : ajout de la protection CSRF au formulaire d'ajout au panier.**
*   **`templates/product/search_results.html.twig`**: Application du filtre Twig `highlight` sur `product.title` et `product.description` pour mettre en évidence le terme de recherche. Ajout des sélecteurs de filtrage par catégorie et rareté, ainsi que les options de tri par date, prix et nom. Intégration de la pagination avec KnpPaginatorBundle.
*   **`templates/security/login.html.twig`**: Suppression du style inline redondant sur le lien "Inscrivez-vous". **Mise à jour : ajout du lien "Mot de passe oublié ?".**
*   **`templates/security/register.html.twig`**: Déplacement des styles inline pour la civilité et le champ `agreeTerms` vers `general.css`.
*   **`templates/message/conversation.html.twig`**: Ajout du JavaScript pour le défilement automatique. Déplacement des styles inline vers `general.css`.
*   **`templates/cart/index.html.twig`**: **Mise à jour : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`templates/cart/checkout.html.twig`**: **Nouveau template créé** pour le processus de commande.
*   **`templates/cart/payment.html.twig`**: **Nouveau template créé** pour la page de paiement Stripe.
*   **`templates/emails/reset_password.html.twig`**: Nouveau template créé pour l'email de réinitialisation de mot de passe.
*   **`templates/emails/verification.html.twig`**: Nouveau template créé pour l'email de vérification de compte.
*   **`app/src/Twig/AppExtension.php`**: Nouveau fichier créé contenant le filtre Twig `highlight` pour la mise en évidence du texte.

## Analyse Détaillée des Flux Client

### 1. Flux d'Inscription (Register)

**Composants pertinents :**
*   `SecurityController.php` (`register` action, `sendVerificationEmail` method)
*   `Users.php` (Entité)
*   `RegisterFormType.php` (Formulaire)
*   `templates/security/register.html.twig` (Template)
*   `templates/emails/verification.html.twig` et `templates/emails/verification.txt.twig` (Templates d'email)
*   `src/Repository/UsersRepository.php`
*   `symfony/mailer` et `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/register`, remplit un formulaire avec ses informations (email, mot de passe, nom, prénom, civilité, numéro de téléphone), et soumet. En cas de succès, il reçoit un message flash et un email de vérification.

**Vérification et Observations :**
*   **`RegisterFormType.php`**: Structure solide avec tous les champs nécessaires et validations (`NotBlank`, `Length`, `Email`, `Regex`, `IsTrue`). Protection CSRF configurée.
*   **`SecurityController::register()`**: Implémentation correcte. Gère la redirection si déjà connecté, hache le mot de passe, attribue `ROLE_USER`, gère la vérification d'e-mail (génération/mise à jour de token, envoi d'email), et utilise les messages flash. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/register.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash, rendu du formulaire standard avec accessibilité. Styles spécifiques à consolider dans un fichier CSS externe.

### 2. Flux de Connexion (Login)

**Composants pertinents :**
*   `SecurityController.php` (`login` action)
*   `templates/security/login.html.twig` (Template)
*   `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/login`, remplit un formulaire avec son email et mot de passe, et soumet. En cas de succès, il est connecté et redirigé. En cas d'échec, un message d'erreur s'affiche.

**Vérification et Observations :**
*   **`SecurityController::login()`**: Implémentation standard et correcte. Gère la redirection si déjà connecté, récupère les erreurs et le dernier nom d'utilisateur. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/login.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash et erreurs. Formulaire HTML direct avec champs `_username` et `_password` et token CSRF. **Amélioration : ajout du lien "Mot de passe oublié ?" vers `app_reset_password_request`.**

### 3. Flux d'Ajout au Panier (Add Cart)

**Composants pertinents :**
*   `CartController.php` (`addToCart` action)
*   `CartService.php` (`addToCart` method, `getFullCart`, `calculateTotal`, `getCartCount`)
*   `Products.php` (Entité)
*   `templates/product/show.html.twig` (Template du bouton "Ajouter au panier")
*   `public/js/ajax.js` (Logique AJAX)
*   `templates/base.html.twig` (Badge du panier)

**Parcours utilisateur attendu :**
L'utilisateur clique sur un bouton "Ajouter au panier" sur une page produit. Le produit est ajouté au panier (stocké en session), le badge du panier est mis à jour, et un message flash s'affiche.

**Vérification et Observations :**
*   **`CartController::addToCart`**: Correctement configuré pour retourner un `JsonResponse` avec `success`, `cartCount`, et `total`. Gère les erreurs.
*   **`CartService::addToCart`**: A été modifié pour ne stocker que l'ID et la quantité en session, garantissant la fraîcheur des données produit.
*   **`templates/product/show.html.twig`**: Le formulaire a la classe `add-to-cart-form` pour l'interception AJAX. **Amélioration : ajout d'un champ CSRF caché au formulaire "Ajouter au panier" pour une sécurité accrue.**
*   **`public/js/ajax.js`**: Le gestionnaire d'événements `submit` intercepte le formulaire et traite la réponse JSON.

### 4. Flux de Gestion du Panier (Cart)

**Composants pertinents :**
*   `CartController.php` (`cart`, `updateQuantity`, `removeItem` actions)
*   `CartService.php` (`getFullCart`, `updateQuantity`, `removeFromCart`, `validateStock`)
*   `templates/cart/index.html.twig` (Template)
*   `public/js/ajax.js` (Logique AJAX)

**Parcours utilisateur attendu :**
L'utilisateur consulte son panier, peut ajuster les quantités, supprimer des articles, et voir le total.

**Vérification et Observations :**
*   **`CartController::cart`**: Passe le panier validé (`$validation['cart']`) au template.
*   **`CartService`**: Les méthodes `getFullCart()`, `calculateTotal()`, `validateStock()` retournent les données dans le format attendu par le template.
*   **`templates/cart/index.html.twig`**: Bien adapté à la nouvelle structure du panier, accède correctement aux propriétés des objets `Product`. **Amélioration : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`public/js/ajax.js`**: Les fonctions `updateQuantity` et `removeItem` sont bien configurées pour envoyer les requêtes AJAX et mettre à jour l'interface utilisateur.

### 5. Flux de Checkout (Validation de commande)

**Composants pertinents :**
*   `CartController.php` (`checkout` action)
*   `CheckoutFormType.php` (Formulaire)
*   `templates/cart/checkout.html.twig` (Template)
*   `Addresses.php` (Entité)
*   `Users.php` (Entité)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page `/checkout` après avoir cliqué sur "Passer la commande" depuis le panier. Il choisit son adresse de livraison et son mode de livraison, puis soumet le formulaire pour passer à l'étape de paiement.

**Vérification et Observations :**
*   **`CartController::checkout()`**: Vérifie si le panier est vide, crée et gère le `CheckoutFormType`, calcule le montant total, crée une intention de paiement Stripe, stocke les informations de livraison en session, et redirige vers la page de paiement. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CheckoutFormType.php`**: Gère la sélection de l'adresse de livraison (`EntityType` pour `Addresses` de type `TYPE_SHIPPING`) et du mode de livraison (`ChoiceType`). Le champ `shippingCost` est un `HiddenType` non mappé. **Correction : reçoit l'utilisateur via les options du formulaire pour la `query_builder`.**
*   **`templates/cart/checkout.html.twig`**: Bonne structure pour la page de checkout, affiche le récapitulatif du panier et le formulaire. Le JavaScript met à jour le total dynamiquement. **Amélioration : afficher les erreurs de validation du formulaire pour chaque champ. Ajouter l'image du produit dans le récapitulatif du panier.**

### 6. Flux de Paiement (Stripe)

**Composants pertinents :**
*   `CartController.php` (`payment` action, `confirmOrder` action)
*   `StripeService.php`
*   `templates/cart/payment.html.twig` (Template)
*   `public/js/ajax.js` (Logique Stripe)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page de paiement après avoir validé le formulaire de checkout. Il voit le Payment Element de Stripe, effectue le paiement, et la commande est finalisée.

**Vérification et Observations :**
*   **`CartController::payment()`**: Récupère le `clientSecret` et la clé publique Stripe (`$_ENV['STRIPE_PUBLIC_KEY']`) et les passe au template. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CartController::confirmOrder()`**: Récupère les informations de livraison de la session, valide les données, appelle `cartService->purchaseCart()` pour finaliser la commande, nettoie la session, et retourne un `JsonResponse`.
*   **`templates/cart/payment.html.twig`**: Bonne structure pour la page de paiement, affiche le montant total et l'emplacement pour le Payment Element. Les variables JavaScript sont passées correctement.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour initialiser Stripe, gérer le Payment Element, confirmer le paiement et appeler `confirmOrder()` après succès. Gère les messages d'erreur et les redirections.

## Problèmes Actuels

- **Problème : Lenteur de l'application (temps de chargement très longs).**
    - **Symptôme** : Temps de chargement de page de 30+ secondes.
    - **Cause probable** : Performance des volumes Docker sur Windows/WSL (si non optimisé) ou autres configurations PHP/Apache.
    - **Solution** : Vérifier les allocations de ressources Docker Desktop (CPU/RAM). S'assurer que WSL2 est utilisé pour de meilleures performances de volume. XDebug a été vérifié et n'est pas la cause.

- **Problème : Messages d'erreur non stylisés.**
    - **Symptôme** : Messages d'erreur affichés en texte brut, sans le style rouge attendu.
    - **Cause probable** : Problème de spécificité CSS ou de surcharge par d'autres règles. La structure HTML des messages flash générés par JavaScript est un simple `div`, pas une `ul`/`li`.
    - **Solution** : Les règles CSS ont été ajustées pour cibler directement les `div.alert-error` et les `li` à l'intérieur des `form-errors`. Des tests supplémentaires sont nécessaires pour confirmer l'application complète des styles.

## Axes d'Amélioration Généraux

### Authentification
*   **Déplacer les styles inline** des templates `login.html.twig` et `register.html.twig` vers `general.css` pour une meilleure maintenabilité.

### Mails
*   **Améliorer le design des emails HTML :** Utiliser des styles inline et une structure compatible avec les clients de messagerie pour une meilleure présentation.
*   **Ajouter des versions texte brut des emails :** Pour une meilleure compatibilité et accessibilité.
*   **Préparer la configuration de production** pour le `MAILER_DSN`.

### Ajout de Produits
1.  **Amélioration de l'UX pour la présélection de la carte Pokémon :** Utiliser un écouteur d'événement `input` avec une fonction de "debounce" sur le champ "Numéro" pour une recherche plus réactive et optimisée des cartes Pokémon.

### Recherche
*   **Filtrage et Tri :** Ajouter des options de filtrage (par catégorie, prix, etc.) et de tri (par pertinence, prix, date) pour affiner les résultats.
*   **Performance de la requête :** Pour des bases de données très volumineuses, envisager des optimisations de requête (indexation Full-Text, moteur de recherche dédié).

### Panier
*   **Messages flash :** Ajouter `role="alert"` aux messages flash dans `templates/cart/index.html.twig` pour une meilleure accessibilité.
*   **CSS :** Centraliser les liens CSS de `product.css` et `common.css` dans `general.css` si ce n'est pas déjà fait.

### Favoris
*   **Méta-description :** Surcharger la meta-description dans `templates/favorite/index.html.twig` pour être plus spécifique à la page des favoris.
*   **CSS :** Centraliser les liens CSS de `products.css` dans `general.css` si ce n'est pas déjà fait.

### Messagerie
*   **Améliorer l'interactivité** (défilement automatique, indicateurs de lecture, notifications en temps réel).

### Gestion des Médias
*   **Clarifier la stratégie d'upload :** Décider si l'upload se fait via le `CollectionType` (recommandé pour plusieurs médias) ou via un `input` unique.

### Gestion des Commandes
*   **Validation des adresses :** S'assurer que l'adresse de livraison est sélectionnée ou créée avant l'achat.
*   **Notifications :** Envoyer un email de confirmation de commande à l'utilisateur.
*   **Historique des commandes :** Afficher l'historique des commandes pour l'utilisateur.
*   **Gestion des erreurs :** Améliorer la gestion des erreurs et les messages utilisateur en cas de problème lors de l'achat.

### Gestion des Adresses
*   **Obligation de l'adresse dans `Orders` :** Si toutes les commandes nécessitent une adresse, rendre la relation `addresses` non-nullable dans l'entité `Orders`.

### Gestion des Cartes Pokémon
*   **Champs commentés (`nomEn`, `nomJp`) :** Supprimer si non utilisés, ou décommenter et utiliser si nécessaire.

### Gestion des Produits
*   **Lien vers la page de détail :** Ajouter un lien direct depuis les cartes produits vers la page de détail du produit dans `product/index.html.twig` et `product/user_products.html.twig`.

## Historique des Modifications Apportées par Gemini CLI

Cette section détaille les modifications spécifiques apportées au code par Gemini CLI, organisées par fichier pour une meilleure traçabilité.

*Note : La commande `replace` a rencontré des difficultés avec les chemins de fichiers contenant des antislashs et les blocs de texte complexes. Les mises à jour ont été effectuées en utilisant la commande `write_file` pour réécrire l'intégralité des fichiers concernés, garantissant ainsi la cohérence et l'exactitude des modifications.*

### Contrôleurs

*   **`app/src/Controller/CartController.php`**
    *   Correction d'une erreur de syntaxe dans le message flash (`'Erreur lors de l\'achat'`).
    *   Intégration de la méthode `purchaseCart` du `CartService` dans la méthode `buy` pour gérer la finalisation de la commande.
    *   Ajout de la validation CSRF aux méthodes `updateQuantity` et `removeItem`.
    *   **Ajout des actions `checkout`, `payment`, `confirmOrder` pour le processus de commande et l'intégration Stripe.**
    *   **Injection de `StripeService`.**
    *   **Suppression des appels `->createView()` dans les méthodes `checkout`, `payment`, `confirmOrder`.**
*   **`app/src/Controller/FavoriteController.php`**
    *   Ajout de la protection CSRF à l'action `toggle`.
*   **`app/src/Controller/ProductController.php`**
    *   Mise à jour de la méthode `add` pour passer les extensions uniques au `ProductFormType`.
    *   Modification de l'API `/product/api/pokemon-card-details/{number}` pour inclure l'ID et l'URL de l'image de la carte Pokémon dans la réponse JSON.
    *   Intégration de la pagination avec KnpPaginatorBundle dans la méthode `search`.
    *   Modification de la méthode `search` pour récupérer les paramètres de filtrage et de tri et les passer au repository.
    *   Modification des méthodes `index` et `userProducts` pour utiliser la pagination.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` du `ProductFormType` dans la méthode `add`.**
*   **`app/src/Controller/MessageController.php`**
    *   Modification de la méthode `conversation` pour appeler `markMessagesAsRead`.
*   **`app/src/Controller/SecurityController.php`**
    *   **Suppression des appels `->createView()` dans les méthodes `register`, `resetPasswordRequest`, et `resetPassword`.**

### Entités

*   **`app/src/Entity/Addresses.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Choice`) pour les champs.
    *   Changement des types de `number` et `zipCode` de `int` à `string` pour permettre des formats plus flexibles (ex: "12bis").
    *   Ajout de constantes (`TYPE_HOME`, `TYPE_BILLING`, `TYPE_SHIPPING`) pour le champ `type` avec une contrainte `Assert\Choice`.
*   **`app/src/Entity/Media.php`**
    *   Ajout d'une propriété non mappée `file` de type `UploadedFile` pour faciliter la gestion des uploads via les formulaires.
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image_url` pour l'exposition via l'API.
    *   **Ajout d'une propriété non mappée `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Entity/Messages.php`**
    *   Ajout d'une propriété `isRead` (bool) et de ses accesseurs.
*   **`app/src/Entity/Orders.php`**
    *   Suppression du champ `content`.
    *   Ajout de constantes pour le champ `status` (`STATUS_PENDING`, `STATUS_COMPLETED`, `STATUS_CANCELLED`).
    *   Implémentation de `#[ORM\HasLifecycleCallbacks]` et de la méthode `setCreatedAtValue()` pour définir automatiquement la date de création à la persistance.
    *   **Ajout des propriétés `deliveryMethod` (string) et `shippingCost` (float).**
*   **`app/src/Entity/OrdersProducts.php`**
    *   **Redéfinie** pour fonctionner comme une entité de jointure Many-to-Many entre `Orders` et `Products`.
    *   Ajout des relations `ManyToOne` vers `Orders` et `Products`.
    *   Ajout des champs `quantity` et `price` pour stocker les détails de la ligne de commande.
*   **`app/src/Entity/PokemonCard.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Range`) pour les champs `number`, `name`, `rarity`, `extension`, `starRating`.
    *   Clarification du type de `starRating` en `int` avec une contrainte `Assert\Range` (0 à 5).
    *   Suppression des champs commentés (`nomEn`, `nomJp`).
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image` pour l'exposition via l'API.
*   **`app/src/Entity/Products.php`**
    *   Mise à jour de la relation `ordersProducts` en `OneToMany` vers `OrdersProducts` avec `cascade: ['persist', 'remove']` et `orphanRemoval: true` pour une gestion correcte des lignes de commande.
    *   **Suppression des propriétés `number`, `extension`, `rarity`, `type` et de leurs accessseurs.**

### Formulaires

*   **`app/src/Form/MediaType.php`**
    *   **Nouveau fichier créé** pour gérer l'upload des fichiers médias.
    *   Contient un champ `FileType` avec des contraintes de validation (taille, types MIME).
    *   Mappe le champ `file` à la propriété `file` de l'entité `Media`.
    *   **Ajout d'un champ `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Form/MessageFormType.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`) au champ `content`.
    *   Liaison du formulaire à l'entité `Messages` via `data_class`.
    *   Ajout de la protection CSRF explicite (`csrf_protection`, `csrf_token_id`).
*   **`app/src/Form/Product/ProductFormType.php`**
    *   Mise à jour des attributs `data-` pour les champs `number`, `extension`, `rarity`, `type` et `pokemonCard` afin de faciliter le ciblage JavaScript pour la présélection.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` (redondants avec `PokemonCard`).**
*   **`app/src/Form/CheckoutFormType.php`**:
    *   **Nouveau fichier créé** pour gérer la sélection de l'adresse de livraison et du mode de livraison.
    *   **Reçoit l'utilisateur via les options du formulaire.**

### Services

*   **`app/src/Service/CartService.php`**
    *   Injection de `EntityManagerInterface` dans le constructeur.
    *   **Mise à jour de la méthode `purchaseCart` pour accepter l'adresse, le mode et les frais de livraison.**
    *   **Mise à jour de la structure du panier en session pour stocker uniquement l'ID et la quantité du produit.**
    *   **Ajout de la méthode `getFullCart` pour récupérer les objets `Product` complets.**
    *   **Mise à jour des méthodes `addToCart`, `updateQuantity`, `validateStock`, `calculateTotal`, `getCartCount` pour utiliser la nouvelle structure du panier.**
*   **`app/src/Service/FileUploaderService.php`**
    *   **Nouveau fichier créé** pour gérer l'upload physique des fichiers sur le système de fichiers.
*   **`app/src/Service/MessageService.php`**
    *   Correction des erreurs de nommage (`recipient` remplacé par `receper`) dans les requêtes DQL et les appels de méthode pour correspondre à l'entité `Messages`.
    *   Ajout de la méthode `markMessagesAsRead`.
*   **`app/src/Service/ProductService.php`**
    *   Injection de `FileUploaderService` dans le constructeur.
    *   Implémentation de la logique `handleMediaUpload` pour traiter les fichiers uploadés et les lier à l'entité `Media`.
    *   Mise à jour des méthodes `createProduct` et `updateProduct` pour intégrer la gestion des uploads.

### Fichiers Statiques et Frontend Importants

*   **`composer.json`**: Définit les dépendances PHP du projet.
*   **`config/services.yaml`**: Configure les services de l'application, y compris le `FileUploaderService` et son répertoire cible.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour les interactions AJAX (panier, favoris, filtrage/présélection des cartes Pokémon, messages flash, aperçu dynamique des médias). **Mise à jour : logique de paiement Stripe, gestion des réponses JSON avec redirection, gestion des tokens CSRF pour panier/favoris, ajustement du pré-remplissage des produits, ajout de la logique de capture webcam.**
*   **`public/css/general.css`**: Fichier CSS principal pour le style global de l'application.
*   **`public/css/reset.css`**: Fichier de réinitialisation CSS.
*   **`templates/base.html.twig`**: Template de base de l'application, incluant les assets et la structure générale.
*   **`templates/product/add.html.twig`**: Template pour l'ajout de produits, avec la logique de présélection des cartes Pokémon. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/edit.html.twig`**: Template pour l'édition de produits. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/show.html.twig`**: Template pour l'affichage détaillé d'un produit, incluant les boutons d'édition/suppression. **Mise à jour : ajout de la protection CSRF au formulaire d'ajout au panier.**
*   **`templates/product/search_results.html.twig`**: Application du filtre Twig `highlight` sur `product.title` et `product.description` pour mettre en évidence le terme de recherche. Ajout des sélecteurs de filtrage par catégorie et rareté, ainsi que les options de tri par date, prix et nom. Intégration de la pagination avec KnpPaginatorBundle.
*   **`templates/security/login.html.twig`**: Suppression du style inline redondant sur le lien "Inscrivez-vous". **Mise à jour : ajout du lien "Mot de passe oublié ?".**
*   **`templates/security/register.html.twig`**: Déplacement des styles inline pour la civilité et le champ `agreeTerms` vers `general.css`.
*   **`templates/message/conversation.html.twig`**: Ajout du JavaScript pour le défilement automatique. Déplacement des styles inline vers `general.css`.
*   **`templates/cart/index.html.twig`**: **Mise à jour : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`templates/cart/checkout.html.twig`**: **Nouveau template créé** pour le processus de commande.
*   **`templates/cart/payment.html.twig`**: **Nouveau template créé** pour la page de paiement Stripe.
*   **`templates/emails/reset_password.html.twig`**: Nouveau template créé pour l'email de réinitialisation de mot de passe.
*   **`templates/emails/verification.html.twig`**: Nouveau template créé pour l'email de vérification de compte.
*   **`app/src/Twig/AppExtension.php`**: Nouveau fichier créé contenant le filtre Twig `highlight` pour la mise en évidence du texte.

## Analyse Détaillée des Flux Client

### 1. Flux d'Inscription (Register)

**Composants pertinents :**
*   `SecurityController.php` (`register` action, `sendVerificationEmail` method)
*   `Users.php` (Entité)
*   `RegisterFormType.php` (Formulaire)
*   `templates/security/register.html.twig` (Template)
*   `templates/emails/verification.html.twig` et `templates/emails/verification.txt.twig` (Templates d'email)
*   `src/Repository/UsersRepository.php`
*   `symfony/mailer` et `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/register`, remplit un formulaire avec ses informations (email, mot de passe, nom, prénom, civilité, numéro de téléphone), et soumet. En cas de succès, il reçoit un message flash et un email de vérification.

**Vérification et Observations :**
*   **`RegisterFormType.php`**: Structure solide avec tous les champs nécessaires et validations (`NotBlank`, `Length`, `Email`, `Regex`, `IsTrue`). Protection CSRF configurée.
*   **`SecurityController::register()`**: Implémentation correcte. Gère la redirection si déjà connecté, hache le mot de passe, attribue `ROLE_USER`, gère la vérification d'e-mail (génération/mise à jour de token, envoi d'email), et utilise les messages flash. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/register.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash, rendu du formulaire standard avec accessibilité. Styles spécifiques à consolider dans un fichier CSS externe.

### 2. Flux de Connexion (Login)

**Composants pertinents :**
*   `SecurityController.php` (`login` action)
*   `templates/security/login.html.twig` (Template)
*   `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/login`, remplit un formulaire avec son email et mot de passe, et soumet. En cas de succès, il est connecté et redirigé. En cas d'échec, un message d'erreur s'affiche.

**Vérification et Observations :**
*   **`SecurityController::login()`**: Implémentation standard et correcte. Gère la redirection si déjà connecté, récupère les erreurs et le dernier nom d'utilisateur. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/login.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash et erreurs. Formulaire HTML direct avec champs `_username` et `_password` et token CSRF. **Amélioration : ajout du lien "Mot de passe oublié ?" vers `app_reset_password_request`.**

### 3. Flux d'Ajout au Panier (Add Cart)

**Composants pertinents :**
*   `CartController.php` (`addToCart` action)
*   `CartService.php` (`addToCart` method, `getFullCart`, `calculateTotal`, `getCartCount`)
*   `Products.php` (Entité)
*   `templates/product/show.html.twig` (Template du bouton "Ajouter au panier")
*   `public/js/ajax.js` (Logique AJAX)
*   `templates/base.html.twig` (Badge du panier)

**Parcours utilisateur attendu :**
L'utilisateur clique sur un bouton "Ajouter au panier" sur une page produit. Le produit est ajouté au panier (stocké en session), le badge du panier est mis à jour, et un message flash s'affiche.

**Vérification et Observations :**
*   **`CartController::addToCart`**: Correctement configuré pour retourner un `JsonResponse` avec `success`, `cartCount`, et `total`. Gère les erreurs.
*   **`CartService::addToCart`**: A été modifié pour ne stocker que l'ID et la quantité en session, garantissant la fraîcheur des données produit.
*   **`templates/product/show.html.twig`**: Le formulaire a la classe `add-to-cart-form` pour l'interception AJAX. **Amélioration : ajout d'un champ CSRF caché au formulaire "Ajouter au panier" pour une sécurité accrue.**
*   **`public/js/ajax.js`**: Le gestionnaire d'événements `submit` intercepte le formulaire et traite la réponse JSON.

### 4. Flux de Gestion du Panier (Cart)

**Composants pertinents :**
*   `CartController.php` (`cart`, `updateQuantity`, `removeItem` actions)
*   `CartService.php` (`getFullCart`, `updateQuantity`, `removeFromCart`, `validateStock`)
*   `templates/cart/index.html.twig` (Template)
*   `public/js/ajax.js` (Logique AJAX)

**Parcours utilisateur attendu :**
L'utilisateur consulte son panier, peut ajuster les quantités, supprimer des articles, et voir le total.

**Vérification et Observations :**
*   **`CartController::cart`**: Passe le panier validé (`$validation['cart']`) au template.
*   **`CartService`**: Les méthodes `getFullCart()`, `calculateTotal()`, `validateStock()` retournent les données dans le format attendu par le template.
*   **`templates/cart/index.html.twig`**: Bien adapté à la nouvelle structure du panier, accède correctement aux propriétés des objets `Product`. **Amélioration : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`public/js/ajax.js`**: Les fonctions `updateQuantity` et `removeItem` sont bien configurées pour envoyer les requêtes AJAX et mettre à jour l'interface utilisateur.

### 5. Flux de Checkout (Validation de commande)

**Composants pertinents :**
*   `CartController.php` (`checkout` action)
*   `CheckoutFormType.php` (Formulaire)
*   `templates/cart/checkout.html.twig` (Template)
*   `Addresses.php` (Entité)
*   `Users.php` (Entité)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page `/checkout` après avoir cliqué sur "Passer la commande" depuis le panier. Il choisit son adresse de livraison et son mode de livraison, puis soumet le formulaire pour passer à l'étape de paiement.

**Vérification et Observations :**
*   **`CartController::checkout()`**: Vérifie si le panier est vide, crée et gère le `CheckoutFormType`, calcule le montant total, crée une intention de paiement Stripe, stocke les informations de livraison en session, et redirige vers la page de paiement. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CheckoutFormType.php`**: Gère la sélection de l'adresse de livraison (`EntityType` pour `Addresses` de type `TYPE_SHIPPING`) et du mode de livraison (`ChoiceType`). Le champ `shippingCost` est un `HiddenType` non mappé. **Correction : reçoit l'utilisateur via les options du formulaire pour la `query_builder`.**
*   **`templates/cart/checkout.html.twig`**: Bonne structure pour la page de checkout, affiche le récapitulatif du panier et le formulaire. Le JavaScript met à jour le total dynamiquement. **Amélioration : afficher les erreurs de validation du formulaire pour chaque champ. Ajouter l'image du produit dans le récapitulatif du panier.**

### 6. Flux de Paiement (Stripe)

**Composants pertinents :**
*   `CartController.php` (`payment` action, `confirmOrder` action)
*   `StripeService.php`
*   `templates/cart/payment.html.twig` (Template)
*   `public/js/ajax.js` (Logique Stripe)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page de paiement après avoir validé le formulaire de checkout. Il voit le Payment Element de Stripe, effectue le paiement, et la commande est finalisée.

**Vérification et Observations :**
*   **`CartController::payment()`**: Récupère le `clientSecret` et la clé publique Stripe (`$_ENV['STRIPE_PUBLIC_KEY']`) et les passe au template. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CartController::confirmOrder()`**: Récupère les informations de livraison de la session, valide les données, appelle `cartService->purchaseCart()` pour finaliser la commande, nettoie la session, et retourne un `JsonResponse`.
*   **`templates/cart/payment.html.twig`**: Bonne structure pour la page de paiement, affiche le montant total et l'emplacement pour le Payment Element. Les variables JavaScript sont passées correctement.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour initialiser Stripe, gérer le Payment Element, confirmer le paiement et appeler `confirmOrder()` après succès. Gère les messages d'erreur et les redirections.

## Problèmes Actuels

- **Problème : Lenteur de l'application (temps de chargement très longs).**
    - **Symptôme** : Temps de chargement de page de 30+ secondes.
    - **Cause probable** : Performance des volumes Docker sur Windows/WSL (si non optimisé) ou autres configurations PHP/Apache.
    - **Solution** : Vérifier les allocations de ressources Docker Desktop (CPU/RAM). S'assurer que WSL2 est utilisé pour de meilleures performances de volume. XDebug a été vérifié et n'est pas la cause.

- **Problème : Messages d'erreur non stylisés.**
    - **Symptôme** : Messages d'erreur affichés en texte brut, sans le style rouge attendu.
    - **Cause probable** : Problème de spécificité CSS ou de surcharge par d'autres règles. La structure HTML des messages flash générés par JavaScript est un simple `div`, pas une `ul`/`li`.
    - **Solution** : Les règles CSS ont été ajustées pour cibler directement les `div.alert-error` et les `li` à l'intérieur des `form-errors`. Des tests supplémentaires sont nécessaires pour confirmer l'application complète des styles.

## Axes d'Amélioration Généraux

### Authentification
*   **Déplacer les styles inline** des templates `login.html.twig` et `register.html.twig` vers `general.css` pour une meilleure maintenabilité.

### Mails
*   **Améliorer le design des emails HTML :** Utiliser des styles inline et une structure compatible avec les clients de messagerie pour une meilleure présentation.
*   **Ajouter des versions texte brut des emails :** Pour une meilleure compatibilité et accessibilité.
*   **Préparer la configuration de production** pour le `MAILER_DSN`.

### Ajout de Produits
1.  **Amélioration de l'UX pour la présélection de la carte Pokémon :** Utiliser un écouteur d'événement `input` avec une fonction de "debounce" sur le champ "Numéro" pour une recherche plus réactive et optimisée des cartes Pokémon.

### Recherche
*   **Filtrage et Tri :** Ajouter des options de filtrage (par catégorie, prix, etc.) et de tri (par pertinence, prix, date) pour affiner les résultats.
*   **Performance de la requête :** Pour des bases de données très volumineuses, envisager des optimisations de requête (indexation Full-Text, moteur de recherche dédié).

### Panier
*   **Messages flash :** Ajouter `role="alert"` aux messages flash dans `templates/cart/index.html.twig` pour une meilleure accessibilité.
*   **CSS :** Centraliser les liens CSS de `product.css` et `common.css` dans `general.css` si ce n'est pas déjà fait.

### Favoris
*   **Méta-description :** Surcharger la meta-description dans `templates/favorite/index.html.twig` pour être plus spécifique à la page des favoris.
*   **CSS :** Centraliser les liens CSS de `products.css` dans `general.css` si ce n'est pas déjà fait.

### Messagerie
*   **Améliorer l'interactivité** (défilement automatique, indicateurs de lecture, notifications en temps réel).

### Gestion des Médias
*   **Clarifier la stratégie d'upload :** Décider si l'upload se fait via le `CollectionType` (recommandé pour plusieurs médias) ou via un `input` unique.

### Gestion des Commandes
*   **Validation des adresses :** S'assurer que l'adresse de livraison est sélectionnée ou créée avant l'achat.
*   **Notifications :** Envoyer un email de confirmation de commande à l'utilisateur.
*   **Historique des commandes :** Afficher l'historique des commandes pour l'utilisateur.
*   **Gestion des erreurs :** Améliorer la gestion des erreurs et les messages utilisateur en cas de problème lors de l'achat.

### Gestion des Adresses
*   **Obligation de l'adresse dans `Orders` :** Si toutes les commandes nécessitent une adresse, rendre la relation `addresses` non-nullable dans l'entité `Orders`.

### Gestion des Cartes Pokémon
*   **Champs commentés (`nomEn`, `nomJp`) :** Supprimer si non utilisés, ou décommenter et utiliser si nécessaire.

### Gestion des Produits
*   **Lien vers la page de détail :** Ajouter un lien direct depuis les cartes produits vers la page de détail du produit dans `product/index.html.twig` et `product/user_products.html.twig`.

## Historique des Modifications Apportées par Gemini CLI

Cette section détaille les modifications spécifiques apportées au code par Gemini CLI, organisées par fichier pour une meilleure traçabilité.

*Note : La commande `replace` a rencontré des difficultés avec les chemins de fichiers contenant des antislashs et les blocs de texte complexes. Les mises à jour ont été effectuées en utilisant la commande `write_file` pour réécrire l'intégralité des fichiers concernés, garantissant ainsi la cohérence et l'exactitude des modifications.*

### Contrôleurs

*   **`app/src/Controller/CartController.php`**
    *   Correction d'une erreur de syntaxe dans le message flash (`'Erreur lors de l\'achat'`).
    *   Intégration de la méthode `purchaseCart` du `CartService` dans la méthode `buy` pour gérer la finalisation de la commande.
    *   Ajout de la validation CSRF aux méthodes `updateQuantity` et `removeItem`.
    *   **Ajout des actions `checkout`, `payment`, `confirmOrder` pour le processus de commande et l'intégration Stripe.**
    *   **Injection de `StripeService`.**
    *   **Suppression des appels `->createView()` dans les méthodes `checkout`, `payment`, `confirmOrder`.**
*   **`app/src/Controller/FavoriteController.php`**
    *   Ajout de la protection CSRF à l'action `toggle`.
*   **`app/src/Controller/ProductController.php`**
    *   Mise à jour de la méthode `add` pour passer les extensions uniques au `ProductFormType`.
    *   Modification de l'API `/product/api/pokemon-card-details/{number}` pour inclure l'ID et l'URL de l'image de la carte Pokémon dans la réponse JSON.
    *   Intégration de la pagination avec KnpPaginatorBundle dans la méthode `search`.
    *   Modification de la méthode `search` pour récupérer les paramètres de filtrage et de tri et les passer au repository.
    *   Modification des méthodes `index` et `userProducts` pour utiliser la pagination.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` du `ProductFormType` dans la méthode `add`.**
*   **`app/src/Controller/MessageController.php`**
    *   Modification de la méthode `conversation` pour appeler `markMessagesAsRead`.
*   **`app/src/Controller/SecurityController.php`**
    *   **Suppression des appels `->createView()` dans les méthodes `register`, `resetPasswordRequest`, et `resetPassword`.**

### Entités

*   **`app/src/Entity/Addresses.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Choice`) pour les champs.
    *   Changement des types de `number` et `zipCode` de `int` à `string` pour permettre des formats plus flexibles (ex: "12bis").
    *   Ajout de constantes (`TYPE_HOME`, `TYPE_BILLING`, `TYPE_SHIPPING`) pour le champ `type` avec une contrainte `Assert\Choice`.
*   **`app/src/Entity/Media.php`**
    *   Ajout d'une propriété non mappée `file` de type `UploadedFile` pour faciliter la gestion des uploads via les formulaires.
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image_url` pour l'exposition via l'API.
    *   **Ajout d'une propriété non mappée `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Entity/Messages.php`**
    *   Ajout d'une propriété `isRead` (bool) et de ses accesseurs.
*   **`app/src/Entity/Orders.php`**
    *   Suppression du champ `content`.
    *   Ajout de constantes pour le champ `status` (`STATUS_PENDING`, `STATUS_COMPLETED`, `STATUS_CANCELLED`).
    *   Implémentation de `#[ORM\HasLifecycleCallbacks]` et de la méthode `setCreatedAtValue()` pour définir automatiquement la date de création à la persistance.
    *   **Ajout des propriétés `deliveryMethod` (string) et `shippingCost` (float).**
*   **`app/src/Entity/OrdersProducts.php`**
    *   **Redéfinie** pour fonctionner comme une entité de jointure Many-to-Many entre `Orders` et `Products`.
    *   Ajout des relations `ManyToOne` vers `Orders` et `Products`.
    *   Ajout des champs `quantity` et `price` pour stocker les détails de la ligne de commande.
*   **`app/src/Entity/PokemonCard.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Range`) pour les champs `number`, `name`, `rarity`, `extension`, `starRating`.
    *   Clarification du type de `starRating` en `int` avec une contrainte `Assert\Range` (0 à 5).
    *   Suppression des champs commentés (`nomEn`, `nomJp`).
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image` pour l'exposition via l'API.
*   **`app/src/Entity/Products.php`**
    *   Mise à jour de la relation `ordersProducts` en `OneToMany` vers `OrdersProducts` avec `cascade: ['persist', 'remove']` et `orphanRemoval: true` pour une gestion correcte des lignes de commande.
    *   **Suppression des propriétés `number`, `extension`, `rarity`, `type` et de leurs accessseurs.**

### Formulaires

*   **`app/src/Form/MediaType.php`**
    *   **Nouveau fichier créé** pour gérer l'upload des fichiers médias.
    *   Contient un champ `FileType` avec des contraintes de validation (taille, types MIME).
    *   Mappe le champ `file` à la propriété `file` de l'entité `Media`.
    *   **Ajout d'un champ `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Form/MessageFormType.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`) au champ `content`.
    *   Liaison du formulaire à l'entité `Messages` via `data_class`.
    *   Ajout de la protection CSRF explicite (`csrf_protection`, `csrf_token_id`).
*   **`app/src/Form/Product/ProductFormType.php`**
    *   Mise à jour des attributs `data-` pour les champs `number`, `extension`, `rarity`, `type` et `pokemonCard` afin de faciliter le ciblage JavaScript pour la présélection.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` (redondants avec `PokemonCard`).**
*   **`app/src/Form/CheckoutFormType.php`**:
    *   **Nouveau fichier créé** pour gérer la sélection de l'adresse de livraison et du mode de livraison.
    *   **Reçoit l'utilisateur via les options du formulaire.**

### Services

*   **`app/src/Service/CartService.php`**
    *   Injection de `EntityManagerInterface` dans le constructeur.
    *   **Mise à jour de la méthode `purchaseCart` pour accepter l'adresse, le mode et les frais de livraison.**
    *   **Mise à jour de la structure du panier en session pour stocker uniquement l'ID et la quantité du produit.**
    *   **Ajout de la méthode `getFullCart` pour récupérer les objets `Product` complets.**
    *   **Mise à jour des méthodes `addToCart`, `updateQuantity`, `validateStock`, `calculateTotal`, `getCartCount` pour utiliser la nouvelle structure du panier.**
*   **`app/src/Service/FileUploaderService.php`**
    *   **Nouveau fichier créé** pour gérer l'upload physique des fichiers sur le système de fichiers.
*   **`app/src/Service/MessageService.php`**
    *   Correction des erreurs de nommage (`recipient` remplacé par `receper`) dans les requêtes DQL et les appels de méthode pour correspondre à l'entité `Messages`.
    *   Ajout de la méthode `markMessagesAsRead`.
*   **`app/src/Service/ProductService.php`**
    *   Injection de `FileUploaderService` dans le constructeur.
    *   Implémentation de la logique `handleMediaUpload` pour traiter les fichiers uploadés et les lier à l'entité `Media`.
    *   Mise à jour des méthodes `createProduct` et `updateProduct` pour intégrer la gestion des uploads.

### Fichiers Statiques et Frontend Importants

*   **`composer.json`**: Définit les dépendances PHP du projet.
*   **`config/services.yaml`**: Configure les services de l'application, y compris le `FileUploaderService` et son répertoire cible.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour les interactions AJAX (panier, favoris, filtrage/présélection des cartes Pokémon, messages flash, aperçu dynamique des médias). **Mise à jour : logique de paiement Stripe, gestion des réponses JSON avec redirection, gestion des tokens CSRF pour panier/favoris, ajustement du pré-remplissage des produits, ajout de la logique de capture webcam.**
*   **`public/css/general.css`**: Fichier CSS principal pour le style global de l'application.
*   **`public/css/reset.css`**: Fichier de réinitialisation CSS.
*   **`templates/base.html.twig`**: Template de base de l'application, incluant les assets et la structure générale.
*   **`templates/product/add.html.twig`**: Template pour l'ajout de produits, avec la logique de présélection des cartes Pokémon. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/edit.html.twig`**: Template pour l'édition de produits. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/show.html.twig`**: Template pour l'affichage détaillé d'un produit, incluant les boutons d'édition/suppression. **Mise à jour : ajout de la protection CSRF au formulaire d'ajout au panier.**
*   **`templates/product/search_results.html.twig`**: Application du filtre Twig `highlight` sur `product.title` et `product.description` pour mettre en évidence le terme de recherche. Ajout des sélecteurs de filtrage par catégorie et rareté, ainsi que les options de tri par date, prix et nom. Intégration de la pagination avec KnpPaginatorBundle.
*   **`templates/security/login.html.twig`**: Suppression du style inline redondant sur le lien "Inscrivez-vous". **Mise à jour : ajout du lien "Mot de passe oublié ?".**
*   **`templates/security/register.html.twig`**: Déplacement des styles inline pour la civilité et le champ `agreeTerms` vers `general.css`.
*   **`templates/message/conversation.html.twig`**: Ajout du JavaScript pour le défilement automatique. Déplacement des styles inline vers `general.css`.
*   **`templates/cart/index.html.twig`**: **Mise à jour : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`templates/cart/checkout.html.twig`**: **Nouveau template créé** pour le processus de commande.
*   **`templates/cart/payment.html.twig`**: **Nouveau template créé** pour la page de paiement Stripe.
*   **`templates/emails/reset_password.html.twig`**: Nouveau template créé pour l'email de réinitialisation de mot de passe.
*   **`templates/emails/verification.html.twig`**: Nouveau template créé pour l'email de vérification de compte.
*   **`app/src/Twig/AppExtension.php`**: Nouveau fichier créé contenant le filtre Twig `highlight` pour la mise en évidence du texte.

## Analyse Détaillée des Flux Client

### 1. Flux d'Inscription (Register)

**Composants pertinents :**
*   `SecurityController.php` (`register` action, `sendVerificationEmail` method)
*   `Users.php` (Entité)
*   `RegisterFormType.php` (Formulaire)
*   `templates/security/register.html.twig` (Template)
*   `templates/emails/verification.html.twig` et `templates/emails/verification.txt.twig` (Templates d'email)
*   `src/Repository/UsersRepository.php`
*   `symfony/mailer` et `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/register`, remplit un formulaire avec ses informations (email, mot de passe, nom, prénom, civilité, numéro de téléphone), et soumet. En cas de succès, il reçoit un message flash et un email de vérification.

**Vérification et Observations :**
*   **`RegisterFormType.php`**: Structure solide avec tous les champs nécessaires et validations (`NotBlank`, `Length`, `Email`, `Regex`, `IsTrue`). Protection CSRF configurée.
*   **`SecurityController::register()`**: Implémentation correcte. Gère la redirection si déjà connecté, hache le mot de passe, attribue `ROLE_USER`, gère la vérification d'e-mail (génération/mise à jour de token, envoi d'email), et utilise les messages flash. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/register.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash, rendu du formulaire standard avec accessibilité. Styles spécifiques à consolider dans un fichier CSS externe.

### 2. Flux de Connexion (Login)

**Composants pertinents :**
*   `SecurityController.php` (`login` action)
*   `templates/security/login.html.twig` (Template)
*   `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/login`, remplit un formulaire avec son email et mot de passe, et soumet. En cas de succès, il est connecté et redirigé. En cas d'échec, un message d'erreur s'affiche.

**Vérification et Observations :**
*   **`SecurityController::login()`**: Implémentation standard et correcte. Gère la redirection si déjà connecté, récupère les erreurs et le dernier nom d'utilisateur. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/login.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash et erreurs. Formulaire HTML direct avec champs `_username` et `_password` et token CSRF. **Amélioration : ajout du lien "Mot de passe oublié ?" vers `app_reset_password_request`.**

### 3. Flux d'Ajout au Panier (Add Cart)

**Composants pertinents :**
*   `CartController.php` (`addToCart` action)
*   `CartService.php` (`addToCart` method, `getFullCart`, `calculateTotal`, `getCartCount`)
*   `Products.php` (Entité)
*   `templates/product/show.html.twig` (Template du bouton "Ajouter au panier")
*   `public/js/ajax.js` (Logique AJAX)
*   `templates/base.html.twig` (Badge du panier)

**Parcours utilisateur attendu :**
L'utilisateur clique sur un bouton "Ajouter au panier" sur une page produit. Le produit est ajouté au panier (stocké en session), le badge du panier est mis à jour, et un message flash s'affiche.

**Vérification et Observations :**
*   **`CartController::addToCart`**: Correctement configuré pour retourner un `JsonResponse` avec `success`, `cartCount`, et `total`. Gère les erreurs.
*   **`CartService::addToCart`**: A été modifié pour ne stocker que l'ID et la quantité en session, garantissant la fraîcheur des données produit.
*   **`templates/product/show.html.twig`**: Le formulaire a la classe `add-to-cart-form` pour l'interception AJAX. **Amélioration : ajout d'un champ CSRF caché au formulaire "Ajouter au panier" pour une sécurité accrue.**
*   **`public/js/ajax.js`**: Le gestionnaire d'événements `submit` intercepte le formulaire et traite la réponse JSON.

### 4. Flux de Gestion du Panier (Cart)

**Composants pertinents :**
*   `CartController.php` (`cart`, `updateQuantity`, `removeItem` actions)
*   `CartService.php` (`getFullCart`, `updateQuantity`, `removeFromCart`, `validateStock`)
*   `templates/cart/index.html.twig` (Template)
*   `public/js/ajax.js` (Logique AJAX)

**Parcours utilisateur attendu :**
L'utilisateur consulte son panier, peut ajuster les quantités, supprimer des articles, et voir le total.

**Vérification et Observations :**
*   **`CartController::cart`**: Passe le panier validé (`$validation['cart']`) au template.
*   **`CartService`**: Les méthodes `getFullCart()`, `calculateTotal()`, `validateStock()` retournent les données dans le format attendu par le template.
*   **`templates/cart/index.html.twig`**: Bien adapté à la nouvelle structure du panier, accède correctement aux propriétés des objets `Product`. **Amélioration : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`public/js/ajax.js`**: Les fonctions `updateQuantity` et `removeItem` sont bien configurées pour envoyer les requêtes AJAX et mettre à jour l'interface utilisateur.

### 5. Flux de Checkout (Validation de commande)

**Composants pertinents :**
*   `CartController.php` (`checkout` action)
*   `CheckoutFormType.php` (Formulaire)
*   `templates/cart/checkout.html.twig` (Template)
*   `Addresses.php` (Entité)
*   `Users.php` (Entité)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page `/checkout` après avoir cliqué sur "Passer la commande" depuis le panier. Il choisit son adresse de livraison et son mode de livraison, puis soumet le formulaire pour passer à l'étape de paiement.

**Vérification et Observations :**
*   **`CartController::checkout()`**: Vérifie si le panier est vide, crée et gère le `CheckoutFormType`, calcule le montant total, crée une intention de paiement Stripe, stocke les informations de livraison en session, et redirige vers la page de paiement. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CheckoutFormType.php`**: Gère la sélection de l'adresse de livraison (`EntityType` pour `Addresses` de type `TYPE_SHIPPING`) et du mode de livraison (`ChoiceType`). Le champ `shippingCost` est un `HiddenType` non mappé. **Correction : reçoit l'utilisateur via les options du formulaire pour la `query_builder`.**
*   **`templates/cart/checkout.html.twig`**: Bonne structure pour la page de checkout, affiche le récapitulatif du panier et le formulaire. Le JavaScript met à jour le total dynamiquement. **Amélioration : afficher les erreurs de validation du formulaire pour chaque champ. Ajouter l'image du produit dans le récapitulatif du panier.**

### 6. Flux de Paiement (Stripe)

**Composants pertinents :**
*   `CartController.php` (`payment` action, `confirmOrder` action)
*   `StripeService.php`
*   `templates/cart/payment.html.twig` (Template)
*   `public/js/ajax.js` (Logique Stripe)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page de paiement après avoir validé le formulaire de checkout. Il voit le Payment Element de Stripe, effectue le paiement, et la commande est finalisée.

**Vérification et Observations :**
*   **`CartController::payment()`**: Récupère le `clientSecret` et la clé publique Stripe (`$_ENV['STRIPE_PUBLIC_KEY']`) et les passe au template. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CartController::confirmOrder()`**: Récupère les informations de livraison de la session, valide les données, appelle `cartService->purchaseCart()` pour finaliser la commande, nettoie la session, et retourne un `JsonResponse`.
*   **`templates/cart/payment.html.twig`**: Bonne structure pour la page de paiement, affiche le montant total et l'emplacement pour le Payment Element. Les variables JavaScript sont passées correctement.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour initialiser Stripe, gérer le Payment Element, confirmer le paiement et appeler `confirmOrder()` après succès. Gère les messages d'erreur et les redirections.

## Problèmes Actuels

- **Problème : Lenteur de l'application (temps de chargement très longs).**
    - **Symptôme** : Temps de chargement de page de 30+ secondes.
    - **Cause probable** : Performance des volumes Docker sur Windows/WSL (si non optimisé) ou autres configurations PHP/Apache.
    - **Solution** : Vérifier les allocations de ressources Docker Desktop (CPU/RAM). S'assurer que WSL2 est utilisé pour de meilleures performances de volume. XDebug a été vérifié et n'est pas la cause.

- **Problème : Messages d'erreur non stylisés.**
    - **Symptôme** : Messages d'erreur affichés en texte brut, sans le style rouge attendu.
    - **Cause probable** : Problème de spécificité CSS ou de surcharge par d'autres règles. La structure HTML des messages flash générés par JavaScript est un simple `div`, pas une `ul`/`li`.
    - **Solution** : Les règles CSS ont été ajustées pour cibler directement les `div.alert-error` et les `li` à l'intérieur des `form-errors`. Des tests supplémentaires sont nécessaires pour confirmer l'application complète des styles.

## Axes d'Amélioration Généraux

### Authentification
*   **Déplacer les styles inline** des templates `login.html.twig` et `register.html.twig` vers `general.css` pour une meilleure maintenabilité.

### Mails
*   **Améliorer le design des emails HTML :** Utiliser des styles inline et une structure compatible avec les clients de messagerie pour une meilleure présentation.
*   **Ajouter des versions texte brut des emails :** Pour une meilleure compatibilité et accessibilité.
*   **Préparer la configuration de production** pour le `MAILER_DSN`.

### Ajout de Produits
1.  **Amélioration de l'UX pour la présélection de la carte Pokémon :** Utiliser un écouteur d'événement `input` avec une fonction de "debounce" sur le champ "Numéro" pour une recherche plus réactive et optimisée des cartes Pokémon.

### Recherche
*   **Filtrage et Tri :** Ajouter des options de filtrage (par catégorie, prix, etc.) et de tri (par pertinence, prix, date) pour affiner les résultats.
*   **Performance de la requête :** Pour des bases de données très volumineuses, envisager des optimisations de requête (indexation Full-Text, moteur de recherche dédié).

### Panier
*   **Messages flash :** Ajouter `role="alert"` aux messages flash dans `templates/cart/index.html.twig` pour une meilleure accessibilité.
*   **CSS :** Centraliser les liens CSS de `product.css` et `common.css` dans `general.css` si ce n'est pas déjà fait.

### Favoris
*   **Méta-description :** Surcharger la meta-description dans `templates/favorite/index.html.twig` pour être plus spécifique à la page des favoris.
*   **CSS :** Centraliser les liens CSS de `products.css` dans `general.css` si ce n'est pas déjà fait.

### Messagerie
*   **Améliorer l'interactivité** (défilement automatique, indicateurs de lecture, notifications en temps réel).

### Gestion des Médias
*   **Clarifier la stratégie d'upload :** Décider si l'upload se fait via le `CollectionType` (recommandé pour plusieurs médias) ou via un `input` unique.

### Gestion des Commandes
*   **Validation des adresses :** S'assurer que l'adresse de livraison est sélectionnée ou créée avant l'achat.
*   **Notifications :** Envoyer un email de confirmation de commande à l'utilisateur.
*   **Historique des commandes :** Afficher l'historique des commandes pour l'utilisateur.
*   **Gestion des erreurs :** Améliorer la gestion des erreurs et les messages utilisateur en cas de problème lors de l'achat.

### Gestion des Adresses
*   **Obligation de l'adresse dans `Orders` :** Si toutes les commandes nécessitent une adresse, rendre la relation `addresses` non-nullable dans l'entité `Orders`.

### Gestion des Cartes Pokémon
*   **Champs commentés (`nomEn`, `nomJp`) :** Supprimer si non utilisés, ou décommenter et utiliser si nécessaire.

### Gestion des Produits
*   **Lien vers la page de détail :** Ajouter un lien direct depuis les cartes produits vers la page de détail du produit dans `product/index.html.twig` et `product/user_products.html.twig`.

## Historique des Modifications Apportées par Gemini CLI

Cette section détaille les modifications spécifiques apportées au code par Gemini CLI, organisées par fichier pour une meilleure traçabilité.

*Note : La commande `replace` a rencontré des difficultés avec les chemins de fichiers contenant des antislashs et les blocs de texte complexes. Les mises à jour ont été effectuées en utilisant la commande `write_file` pour réécrire l'intégralité des fichiers concernés, garantissant ainsi la cohérence et l'exactitude des modifications.*

### Contrôleurs

*   **`app/src/Controller/CartController.php`**
    *   Correction d'une erreur de syntaxe dans le message flash (`'Erreur lors de l\'achat'`).
    *   Intégration de la méthode `purchaseCart` du `CartService` dans la méthode `buy` pour gérer la finalisation de la commande.
    *   Ajout de la validation CSRF aux méthodes `updateQuantity` et `removeItem`.
    *   **Ajout des actions `checkout`, `payment`, `confirmOrder` pour le processus de commande et l'intégration Stripe.**
    *   **Injection de `StripeService`.**
    *   **Suppression des appels `->createView()` dans les méthodes `checkout`, `payment`, `confirmOrder`.**
*   **`app/src/Controller/FavoriteController.php`**
    *   Ajout de la protection CSRF à l'action `toggle`.
*   **`app/src/Controller/ProductController.php`**
    *   Mise à jour de la méthode `add` pour passer les extensions uniques au `ProductFormType`.
    *   Modification de l'API `/product/api/pokemon-card-details/{number}` pour inclure l'ID et l'URL de l'image de la carte Pokémon dans la réponse JSON.
    *   Intégration de la pagination avec KnpPaginatorBundle dans la méthode `search`.
    *   Modification de la méthode `search` pour récupérer les paramètres de filtrage et de tri et les passer au repository.
    *   Modification des méthodes `index` et `userProducts` pour utiliser la pagination.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` du `ProductFormType` dans la méthode `add`.**
*   **`app/src/Controller/MessageController.php`**
    *   Modification de la méthode `conversation` pour appeler `markMessagesAsRead`.
*   **`app/src/Controller/SecurityController.php`**
    *   **Suppression des appels `->createView()` dans les méthodes `register`, `resetPasswordRequest`, et `resetPassword`.**

### Entités

*   **`app/src/Entity/Addresses.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Choice`) pour les champs.
    *   Changement des types de `number` et `zipCode` de `int` à `string` pour permettre des formats plus flexibles (ex: "12bis").
    *   Ajout de constantes (`TYPE_HOME`, `TYPE_BILLING`, `TYPE_SHIPPING`) pour le champ `type` avec une contrainte `Assert\Choice`.
*   **`app/src/Entity/Media.php`**
    *   Ajout d'une propriété non mappée `file` de type `UploadedFile` pour faciliter la gestion des uploads via les formulaires.
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image_url` pour l'exposition via l'API.
    *   **Ajout d'une propriété non mappée `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Entity/Messages.php`**
    *   Ajout d'une propriété `isRead` (bool) et de ses accesseurs.
*   **`app/src/Entity/Orders.php`**
    *   Suppression du champ `content`.
    *   Ajout de constantes pour le champ `status` (`STATUS_PENDING`, `STATUS_COMPLETED`, `STATUS_CANCELLED`).
    *   Implémentation de `#[ORM\HasLifecycleCallbacks]` et de la méthode `setCreatedAtValue()` pour définir automatiquement la date de création à la persistance.
    *   **Ajout des propriétés `deliveryMethod` (string) et `shippingCost` (float).**
*   **`app/src/Entity/OrdersProducts.php`**
    *   **Redéfinie** pour fonctionner comme une entité de jointure Many-to-Many entre `Orders` et `Products`.
    *   Ajout des relations `ManyToOne` vers `Orders` et `Products`.
    *   Ajout des champs `quantity` et `price` pour stocker les détails de la ligne de commande.
*   **`app/src/Entity/PokemonCard.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Range`) pour les champs `number`, `name`, `rarity`, `extension`, `starRating`.
    *   Clarification du type de `starRating` en `int` avec une contrainte `Assert\Range` (0 à 5).
    *   Suppression des champs commentés (`nomEn`, `nomJp`).
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image` pour l'exposition via l'API.
*   **`app/src/Entity/Products.php`**
    *   Mise à jour de la relation `ordersProducts` en `OneToMany` vers `OrdersProducts` avec `cascade: ['persist', 'remove']` et `orphanRemoval: true` pour une gestion correcte des lignes de commande.
    *   **Suppression des propriétés `number`, `extension`, `rarity`, `type` et de leurs accessseurs.**

### Formulaires

*   **`app/src/Form/MediaType.php`**
    *   **Nouveau fichier créé** pour gérer l'upload des fichiers médias.
    *   Contient un champ `FileType` avec des contraintes de validation (taille, types MIME).
    *   Mappe le champ `file` à la propriété `file` de l'entité `Media`.
    *   **Ajout d'un champ `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Form/MessageFormType.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`) au champ `content`.
    *   Liaison du formulaire à l'entité `Messages` via `data_class`.
    *   Ajout de la protection CSRF explicite (`csrf_protection`, `csrf_token_id`).
*   **`app/src/Form/Product/ProductFormType.php`**
    *   Mise à jour des attributs `data-` pour les champs `number`, `extension`, `rarity`, `type` et `pokemonCard` afin de faciliter le ciblage JavaScript pour la présélection.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` (redondants avec `PokemonCard`).**
*   **`app/src/Form/CheckoutFormType.php`**:
    *   **Nouveau fichier créé** pour gérer la sélection de l'adresse de livraison et du mode de livraison.
    *   **Reçoit l'utilisateur via les options du formulaire.**

### Services

*   **`app/src/Service/CartService.php`**
    *   Injection de `EntityManagerInterface` dans le constructeur.
    *   **Mise à jour de la méthode `purchaseCart` pour accepter l'adresse, le mode et les frais de livraison.**
    *   **Mise à jour de la structure du panier en session pour stocker uniquement l'ID et la quantité du produit.**
    *   **Ajout de la méthode `getFullCart` pour récupérer les objets `Product` complets.**
    *   **Mise à jour des méthodes `addToCart`, `updateQuantity`, `validateStock`, `calculateTotal`, `getCartCount` pour utiliser la nouvelle structure du panier.**
*   **`app/src/Service/FileUploaderService.php`**
    *   **Nouveau fichier créé** pour gérer l'upload physique des fichiers sur le système de fichiers.
*   **`app/src/Service/MessageService.php`**
    *   Correction des erreurs de nommage (`recipient` remplacé par `receper`) dans les requêtes DQL et les appels de méthode pour correspondre à l'entité `Messages`.
    *   Ajout de la méthode `markMessagesAsRead`.
*   **`app/src/Service/ProductService.php`**
    *   Injection de `FileUploaderService` dans le constructeur.
    *   Implémentation de la logique `handleMediaUpload` pour traiter les fichiers uploadés et les lier à l'entité `Media`.
    *   Mise à jour des méthodes `createProduct` et `updateProduct` pour intégrer la gestion des uploads.

### Fichiers Statiques et Frontend Importants

*   **`composer.json`**: Définit les dépendances PHP du projet.
*   **`config/services.yaml`**: Configure les services de l'application, y compris le `FileUploaderService` et son répertoire cible.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour les interactions AJAX (panier, favoris, filtrage/présélection des cartes Pokémon, messages flash, aperçu dynamique des médias). **Mise à jour : logique de paiement Stripe, gestion des réponses JSON avec redirection, gestion des tokens CSRF pour panier/favoris, ajustement du pré-remplissage des produits, ajout de la logique de capture webcam.**
*   **`public/css/general.css`**: Fichier CSS principal pour le style global de l'application.
*   **`public/css/reset.css`**: Fichier de réinitialisation CSS.
*   **`templates/base.html.twig`**: Template de base de l'application, incluant les assets et la structure générale.
*   **`templates/product/add.html.twig`**: Template pour l'ajout de produits, avec la logique de présélection des cartes Pokémon. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/edit.html.twig`**: Template pour l'édition de produits. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/show.html.twig`**: Template pour l'affichage détaillé d'un produit, incluant les boutons d'édition/suppression. **Mise à jour : ajout de la protection CSRF au formulaire d'ajout au panier.**
*   **`templates/product/search_results.html.twig`**: Application du filtre Twig `highlight` sur `product.title` et `product.description` pour mettre en évidence le terme de recherche. Ajout des sélecteurs de filtrage par catégorie et rareté, ainsi que les options de tri par date, prix et nom. Intégration de la pagination avec KnpPaginatorBundle.
*   **`templates/security/login.html.twig`**: Suppression du style inline redondant sur le lien "Inscrivez-vous". **Mise à jour : ajout du lien "Mot de passe oublié ?".**
*   **`templates/security/register.html.twig`**: Déplacement des styles inline pour la civilité et le champ `agreeTerms` vers `general.css`.
*   **`templates/message/conversation.html.twig`**: Ajout du JavaScript pour le défilement automatique. Déplacement des styles inline vers `general.css`.
*   **`templates/cart/index.html.twig`**: **Mise à jour : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`templates/cart/checkout.html.twig`**: **Nouveau template créé** pour le processus de commande.
*   **`templates/cart/payment.html.twig`**: **Nouveau template créé** pour la page de paiement Stripe.
*   **`templates/emails/reset_password.html.twig`**: Nouveau template créé pour l'email de réinitialisation de mot de passe.
*   **`templates/emails/verification.html.twig`**: Nouveau template créé pour l'email de vérification de compte.
*   **`app/src/Twig/AppExtension.php`**: Nouveau fichier créé contenant le filtre Twig `highlight` pour la mise en évidence du texte.

## Analyse Détaillée des Flux Client

### 1. Flux d'Inscription (Register)

**Composants pertinents :**
*   `SecurityController.php` (`register` action, `sendVerificationEmail` method)
*   `Users.php` (Entité)
*   `RegisterFormType.php` (Formulaire)
*   `templates/security/register.html.twig` (Template)
*   `templates/emails/verification.html.twig` et `templates/emails/verification.txt.twig` (Templates d'email)
*   `src/Repository/UsersRepository.php`
*   `symfony/mailer` et `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/register`, remplit un formulaire avec ses informations (email, mot de passe, nom, prénom, civilité, numéro de téléphone), et soumet. En cas de succès, il reçoit un message flash et un email de vérification.

**Vérification et Observations :**
*   **`RegisterFormType.php`**: Structure solide avec tous les champs nécessaires et validations (`NotBlank`, `Length`, `Email`, `Regex`, `IsTrue`). Protection CSRF configurée.
*   **`SecurityController::register()`**: Implémentation correcte. Gère la redirection si déjà connecté, hache le mot de passe, attribue `ROLE_USER`, gère la vérification d'e-mail (génération/mise à jour de token, envoi d'email), et utilise les messages flash. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/register.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash, rendu du formulaire standard avec accessibilité. Styles spécifiques à consolider dans un fichier CSS externe.

### 2. Flux de Connexion (Login)

**Composants pertinents :**
*   `SecurityController.php` (`login` action)
*   `templates/security/login.html.twig` (Template)
*   `symfony/security-bundle`

**Parcours utilisateur attendu :**
L'utilisateur accède à la page `/login`, remplit un formulaire avec son email et mot de passe, et soumet. En cas de succès, il est connecté et redirigé. En cas d'échec, un message d'erreur s'affiche.

**Vérification et Observations :**
*   **`SecurityController::login()`**: Implémentation standard et correcte. Gère la redirection si déjà connecté, récupère les erreurs et le dernier nom d'utilisateur. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`templates/security/login.html.twig`**: Bonne structure, métadonnées SEO, affichage des messages flash et erreurs. Formulaire HTML direct avec champs `_username` et `_password` et token CSRF. **Amélioration : ajout du lien "Mot de passe oublié ?" vers `app_reset_password_request`.**

### 3. Flux d'Ajout au Panier (Add Cart)

**Composants pertinents :**
*   `CartController.php` (`addToCart` action)
*   `CartService.php` (`addToCart` method, `getFullCart`, `calculateTotal`, `getCartCount`)
*   `Products.php` (Entité)
*   `templates/product/show.html.twig` (Template du bouton "Ajouter au panier")
*   `public/js/ajax.js` (Logique AJAX)
*   `templates/base.html.twig` (Badge du panier)

**Parcours utilisateur attendu :**
L'utilisateur clique sur un bouton "Ajouter au panier" sur une page produit. Le produit est ajouté au panier (stocké en session), le badge du panier est mis à jour, et un message flash s'affiche.

**Vérification et Observations :**
*   **`CartController::addToCart`**: Correctement configuré pour retourner un `JsonResponse` avec `success`, `cartCount`, et `total`. Gère les erreurs.
*   **`CartService::addToCart`**: A été modifié pour ne stocker que l'ID et la quantité en session, garantissant la fraîcheur des données produit.
*   **`templates/product/show.html.twig`**: Le formulaire a la classe `add-to-cart-form` pour l'interception AJAX. **Amélioration : ajout d'un champ CSRF caché au formulaire "Ajouter au panier" pour une sécurité accrue.**
*   **`public/js/ajax.js`**: Le gestionnaire d'événements `submit` intercepte le formulaire et traite la réponse JSON.

### 4. Flux de Gestion du Panier (Cart)

**Composants pertinents :**
*   `CartController.php` (`cart`, `updateQuantity`, `removeItem` actions)
*   `CartService.php` (`getFullCart`, `updateQuantity`, `removeFromCart`, `validateStock`)
*   `templates/cart/index.html.twig` (Template)
*   `public/js/ajax.js` (Logique AJAX)

**Parcours utilisateur attendu :**
L'utilisateur consulte son panier, peut ajuster les quantités, supprimer des articles, et voir le total.

**Vérification et Observations :**
*   **`CartController::cart`**: Passe le panier validé (`$validation['cart']`) au template.
*   **`CartService`**: Les méthodes `getFullCart()`, `calculateTotal()`, `validateStock()` retournent les données dans le format attendu par le template.
*   **`templates/cart/index.html.twig`**: Bien adapté à la nouvelle structure du panier, accède correctement aux propriétés des objets `Product`. **Amélioration : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`public/js/ajax.js`**: Les fonctions `updateQuantity` et `removeItem` sont bien configurées pour envoyer les requêtes AJAX et mettre à jour l'interface utilisateur.

### 5. Flux de Checkout (Validation de commande)

**Composants pertinents :**
*   `CartController.php` (`checkout` action)
*   `CheckoutFormType.php` (Formulaire)
*   `templates/cart/checkout.html.twig` (Template)
*   `Addresses.php` (Entité)
*   `Users.php` (Entité)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page `/checkout` après avoir cliqué sur "Passer la commande" depuis le panier. Il choisit son adresse de livraison et son mode de livraison, puis soumet le formulaire pour passer à l'étape de paiement.

**Vérification et Observations :**
*   **`CartController::checkout()`**: Vérifie si le panier est vide, crée et gère le `CheckoutFormType`, calcule le montant total, crée une intention de paiement Stripe, stocke les informations de livraison en session, et redirige vers la page de paiement. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CheckoutFormType.php`**: Gère la sélection de l'adresse de livraison (`EntityType` pour `Addresses` de type `TYPE_SHIPPING`) et du mode de livraison (`ChoiceType`). Le champ `shippingCost` est un `HiddenType` non mappé. **Correction : reçoit l'utilisateur via les options du formulaire pour la `query_builder`.**
*   **`templates/cart/checkout.html.twig`**: Bonne structure pour la page de checkout, affiche le récapitulatif du panier et le formulaire. Le JavaScript met à jour le total dynamiquement. **Amélioration : afficher les erreurs de validation du formulaire pour chaque champ. Ajouter l'image du produit dans le récapitulatif du panier.**

### 6. Flux de Paiement (Stripe)

**Composants pertinents :**
*   `CartController.php` (`payment` action, `confirmOrder` action)
*   `StripeService.php`
*   `templates/cart/payment.html.twig` (Template)
*   `public/js/ajax.js` (Logique Stripe)

**Parcours utilisateur attendu :**
L'utilisateur est redirigé vers la page de paiement après avoir validé le formulaire de checkout. Il voit le Payment Element de Stripe, effectue le paiement, et la commande est finalisée.

**Vérification et Observations :**
*   **`CartController::payment()`**: Récupère le `clientSecret` et la clé publique Stripe (`$_ENV['STRIPE_PUBLIC_KEY']`) et les passe au template. **Correction : suppression de l'appel `->createView()` lors du rendu du formulaire.**
*   **`CartController::confirmOrder()`**: Récupère les informations de livraison de la session, valide les données, appelle `cartService->purchaseCart()` pour finaliser la commande, nettoie la session, et retourne un `JsonResponse`.
*   **`templates/cart/payment.html.twig`**: Bonne structure pour la page de paiement, affiche le montant total et l'emplacement pour le Payment Element. Les variables JavaScript sont passées correctement.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour initialiser Stripe, gérer le Payment Element, confirmer le paiement et appeler `confirmOrder()` après succès. Gère les messages d'erreur et les redirections.

## Problèmes Actuels

- **Problème : Lenteur de l'application (temps de chargement très longs).**
    - **Symptôme** : Temps de chargement de page de 30+ secondes.
    - **Cause probable** : Performance des volumes Docker sur Windows/WSL (si non optimisé) ou autres configurations PHP/Apache.
    - **Solution** : Vérifier les allocations de ressources Docker Desktop (CPU/RAM). S'assurer que WSL2 est utilisé pour de meilleures performances de volume. XDebug a été vérifié et n'est pas la cause.

- **Problème : Messages d'erreur non stylisés.**
    - **Symptôme** : Messages d'erreur affichés en texte brut, sans le style rouge attendu.
    - **Cause probable** : Problème de spécificité CSS ou de surcharge par d'autres règles. La structure HTML des messages flash générés par JavaScript est un simple `div`, pas une `ul`/`li`.
    - **Solution** : Les règles CSS ont été ajustées pour cibler directement les `div.alert-error` et les `li` à l'intérieur des `form-errors`. Des tests supplémentaires sont nécessaires pour confirmer l'application complète des styles.

## Axes d'Amélioration Généraux

### Authentification
*   **Déplacer les styles inline** des templates `login.html.twig` et `register.html.twig` vers `general.css` pour une meilleure maintenabilité.

### Mails
*   **Améliorer le design des emails HTML :** Utiliser des styles inline et une structure compatible avec les clients de messagerie pour une meilleure présentation.
*   **Ajouter des versions texte brut des emails :** Pour une meilleure compatibilité et accessibilité.
*   **Préparer la configuration de production** pour le `MAILER_DSN`.

### Ajout de Produits
1.  **Amélioration de l'UX pour la présélection de la carte Pokémon :** Utiliser un écouteur d'événement `input` avec une fonction de "debounce" sur le champ "Numéro" pour une recherche plus réactive et optimisée des cartes Pokémon.

### Recherche
*   **Filtrage et Tri :** Ajouter des options de filtrage (par catégorie, prix, etc.) et de tri (par pertinence, prix, date) pour affiner les résultats.
*   **Performance de la requête :** Pour des bases de données très volumineuses, envisager des optimisations de requête (indexation Full-Text, moteur de recherche dédié).

### Panier
*   **Messages flash :** Ajouter `role="alert"` aux messages flash dans `templates/cart/index.html.twig` pour une meilleure accessibilité.
*   **CSS :** Centraliser les liens CSS de `product.css` et `common.css` dans `general.css` si ce n'est pas déjà fait.

### Favoris
*   **Méta-description :** Surcharger la meta-description dans `templates/favorite/index.html.twig` pour être plus spécifique à la page des favoris.
*   **CSS :** Centraliser les liens CSS de `products.css` dans `general.css` si ce n'est pas déjà fait.

### Messagerie
*   **Améliorer l'interactivité** (défilement automatique, indicateurs de lecture, notifications en temps réel).

### Gestion des Médias
*   **Clarifier la stratégie d'upload :** Décider si l'upload se fait via le `CollectionType` (recommandé pour plusieurs médias) ou via un `input` unique.

### Gestion des Commandes
*   **Validation des adresses :** S'assurer que l'adresse de livraison est sélectionnée ou créée avant l'achat.
*   **Notifications :** Envoyer un email de confirmation de commande à l'utilisateur.
*   **Historique des commandes :** Afficher l'historique des commandes pour l'utilisateur.
*   **Gestion des erreurs :** Améliorer la gestion des erreurs et les messages utilisateur en cas de problème lors de l'achat.

### Gestion des Adresses
*   **Obligation de l'adresse dans `Orders` :** Si toutes les commandes nécessitent une adresse, rendre la relation `addresses` non-nullable dans l'entité `Orders`.

### Gestion des Cartes Pokémon
*   **Champs commentés (`nomEn`, `nomJp`) :** Supprimer si non utilisés, ou décommenter et utiliser si nécessaire.

### Gestion des Produits
*   **Lien vers la page de détail :** Ajouter un lien direct depuis les cartes produits vers la page de détail du produit dans `product/index.html.twig` et `product/user_products.html.twig`.

## Historique des Modifications Apportées par Gemini CLI

Cette section détaille les modifications spécifiques apportées au code par Gemini CLI, organisées par fichier pour une meilleure traçabilité.

*Note : La commande `replace` a rencontré des difficultés avec les chemins de fichiers contenant des antislashs et les blocs de texte complexes. Les mises à jour ont été effectuées en utilisant la commande `write_file` pour réécrire l'intégralité des fichiers concernés, garantissant ainsi la cohérence et l'exactitude des modifications.*

### Contrôleurs

*   **`app/src/Controller/CartController.php`**
    *   Correction d'une erreur de syntaxe dans le message flash (`'Erreur lors de l\'achat'`).
    *   Intégration de la méthode `purchaseCart` du `CartService` dans la méthode `buy` pour gérer la finalisation de la commande.
    *   Ajout de la validation CSRF aux méthodes `updateQuantity` et `removeItem`.
    *   **Ajout des actions `checkout`, `payment`, `confirmOrder` pour le processus de commande et l'intégration Stripe.**
    *   **Injection de `StripeService`.**
    *   **Suppression des appels `->createView()` dans les méthodes `checkout`, `payment`, `confirmOrder`.**
*   **`app/src/Controller/FavoriteController.php`**
    *   Ajout de la protection CSRF à l'action `toggle`.
*   **`app/src/Controller/ProductController.php`**
    *   Mise à jour de la méthode `add` pour passer les extensions uniques au `ProductFormType`.
    *   Modification de l'API `/product/api/pokemon-card-details/{number}` pour inclure l'ID et l'URL de l'image de la carte Pokémon dans la réponse JSON.
    *   Intégration de la pagination avec KnpPaginatorBundle dans la méthode `search`.
    *   Modification de la méthode `search` pour récupérer les paramètres de filtrage et de tri et les passer au repository.
    *   Modification des méthodes `index` et `userProducts` pour utiliser la pagination.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` du `ProductFormType` dans la méthode `add`.**
*   **`app/src/Controller/MessageController.php`**
    *   Modification de la méthode `conversation` pour appeler `markMessagesAsRead`.
*   **`app/src/Controller/SecurityController.php`**
    *   **Suppression des appels `->createView()` dans les méthodes `register`, `resetPasswordRequest`, et `resetPassword`.**

### Entités

*   **`app/src/Entity/Addresses.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Choice`) pour les champs.
    *   Changement des types de `number` et `zipCode` de `int` à `string` pour permettre des formats plus flexibles (ex: "12bis").
    *   Ajout de constantes (`TYPE_HOME`, `TYPE_BILLING`, `TYPE_SHIPPING`) pour le champ `type` avec une contrainte `Assert\Choice`.
*   **`app/src/Entity/Media.php`**
    *   Ajout d'une propriété non mappée `file` de type `UploadedFile` pour faciliter la gestion des uploads via les formulaires.
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image_url` pour l'exposition via l'API.
    *   **Ajout d'une propriété non mappée `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Entity/Messages.php`**
    *   Ajout d'une propriété `isRead` (bool) et de ses accesseurs.
*   **`app/src/Entity/Orders.php`**
    *   Suppression du champ `content`.
    *   Ajout de constantes pour le champ `status` (`STATUS_PENDING`, `STATUS_COMPLETED`, `STATUS_CANCELLED`).
    *   Implémentation de `#[ORM\HasLifecycleCallbacks]` et de la méthode `setCreatedAtValue()` pour définir automatiquement la date de création à la persistance.
    *   **Ajout des propriétés `deliveryMethod` (string) et `shippingCost` (float).**
*   **`app/src/Entity/OrdersProducts.php`**
    *   **Redéfinie** pour fonctionner comme une entité de jointure Many-to-Many entre `Orders` et `Products`.
    *   Ajout des relations `ManyToOne` vers `Orders` et `Products`.
    *   Ajout des champs `quantity` et `price` pour stocker les détails de la ligne de commande.
*   **`app/src/Entity/PokemonCard.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`, `Range`) pour les champs `number`, `name`, `rarity`, `extension`, `starRating`.
    *   Clarification du type de `starRating` en `int` avec une contrainte `Assert\Range` (0 à 5).
    *   Suppression des champs commentés (`nomEn`, `nomJp`).
    *   Ajout du groupe de sérialisation `product:read` à la propriété `image` pour l'exposition via l'API.
*   **`app/src/Entity/Products.php`**
    *   Mise à jour de la relation `ordersProducts` en `OneToMany` vers `OrdersProducts` avec `cascade: ['persist', 'remove']` et `orphanRemoval: true` pour une gestion correcte des lignes de commande.
    *   **Suppression des propriétés `number`, `extension`, `rarity`, `type` et de leurs accessseurs.**

### Formulaires

*   **`app/src/Form/MediaType.php`**
    *   **Nouveau fichier créé** pour gérer l'upload des fichiers médias.
    *   Contient un champ `FileType` avec des contraintes de validation (taille, types MIME).
    *   Mappe le champ `file` à la propriété `file` de l'entité `Media`.
    *   **Ajout d'un champ `webcamImage` (HiddenType) pour la capture directe.**
*   **`app/src/Form/MessageFormType.php`**
    *   Ajout de contraintes de validation (`NotBlank`, `Length`) au champ `content`.
    *   Liaison du formulaire à l'entité `Messages` via `data_class`.
    *   Ajout de la protection CSRF explicite (`csrf_protection`, `csrf_token_id`).
*   **`app/src/Form/Product/ProductFormType.php`**
    *   Mise à jour des attributs `data-` pour les champs `number`, `extension`, `rarity`, `type` et `pokemonCard` afin de faciliter le ciblage JavaScript pour la présélection.
    *   **Suppression des champs `number`, `extension`, `rarity`, `type` (redondants avec `PokemonCard`).**
*   **`app/src/Form/CheckoutFormType.php`**:
    *   **Nouveau fichier créé** pour gérer la sélection de l'adresse de livraison et du mode de livraison.
    *   **Reçoit l'utilisateur via les options du formulaire.**

### Services

*   **`app/src/Service/CartService.php`**
    *   Injection de `EntityManagerInterface` dans le constructeur.
    *   **Mise à jour de la méthode `purchaseCart` pour accepter l'adresse, le mode et les frais de livraison.**
    *   **Mise à jour de la structure du panier en session pour stocker uniquement l'ID et la quantité du produit.**
    *   **Ajout de la méthode `getFullCart` pour récupérer les objets `Product` complets.**
    *   **Mise à jour des méthodes `addToCart`, `updateQuantity`, `validateStock`, `calculateTotal`, `getCartCount` pour utiliser la nouvelle structure du panier.**
*   **`app/src/Service/FileUploaderService.php`**
    *   **Nouveau fichier créé** pour gérer l'upload physique des fichiers sur le système de fichiers.
*   **`app/src/Service/MessageService.php`**
    *   Correction des erreurs de nommage (`recipient` remplacé par `receper`) dans les requêtes DQL et les appels de méthode pour correspondre à l'entité `Messages`.
    *   Ajout de la méthode `markMessagesAsRead`.
*   **`app/src/Service/ProductService.php`**
    *   Injection de `FileUploaderService` dans le constructeur.
    *   Implémentation de la logique `handleMediaUpload` pour traiter les fichiers uploadés et les lier à l'entité `Media`.
    *   Mise à jour des méthodes `createProduct` et `updateProduct` pour intégrer la gestion des uploads.

### Fichiers Statiques et Frontend Importants

*   **`composer.json`**: Définit les dépendances PHP du projet.
*   **`config/services.yaml`**: Configure les services de l'application, y compris le `FileUploaderService` et son répertoire cible.
*   **`public/js/ajax.js`**: Contient la logique JavaScript pour les interactions AJAX (panier, favoris, filtrage/présélection des cartes Pokémon, messages flash, aperçu dynamique des médias). **Mise à jour : logique de paiement Stripe, gestion des réponses JSON avec redirection, gestion des tokens CSRF pour panier/favoris, ajustement du pré-remplissage des produits, ajout de la logique de capture webcam.**
*   **`public/css/general.css`**: Fichier CSS principal pour le style global de l'application.
*   **`public/css/reset.css`**: Fichier de réinitialisation CSS.
*   **`templates/base.html.twig`**: Template de base de l'application, incluant les assets et la structure générale.
*   **`templates/product/add.html.twig`**: Template pour l'ajout de produits, avec la logique de présélection des cartes Pokémon. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/edit.html.twig`**: Template pour l'édition de produits. **Mise à jour : suppression des champs redondants, ajout des éléments HTML pour la capture webcam.**
*   **`templates/product/show.html.twig`**: Template pour l'affichage détaillé d'un produit, incluant les boutons d'édition/suppression. **Mise à jour : ajout de la protection CSRF au formulaire d'ajout au panier.**
*   **`templates/product/search_results.html.twig`**: Application du filtre Twig `highlight` sur `product.title` et `product.description` pour mettre en évidence le terme de recherche. Ajout des sélecteurs de filtrage par catégorie et rareté, ainsi que les options de tri par date, prix et nom. Intégration de la pagination avec KnpPaginatorBundle.
*   **`templates/security/login.html.twig`**: Suppression du style inline redondant sur le lien "Inscrivez-vous". **Mise à jour : ajout du lien "Mot de passe oublié ?".**
*   **`templates/security/register.html.twig`**: Déplacement des styles inline pour la civilité et le champ `agreeTerms` vers `general.css`.
*   **`templates/message/conversation.html.twig`**: Ajout du JavaScript pour le défilement automatique. Déplacement des styles inline vers `general.css`.
*   **`templates/cart/index.html.twig`**: **Mise à jour : affichage de l'image du produit dans le récapitulatif du panier.**
*   **`templates/cart/checkout.html.twig`**: **Nouveau template créé** pour le processus de commande.
*   **`templates/cart/payment.html.twig`**: **Nouveau template créé** pour la page de paiement Stripe.
*   **`templates/emails/reset_password.html.twig`**: Nouveau template créé pour l'email de réinitialisation de mot de passe.
*   **`templates/emails/verification.html.twig`**: Nouveau template créé pour l'email de vérification de compte.
*   **`app/src/Twig/AppExtension.php`**: Nouveau fichier créé contenant le filtre Twig `highlight` pour la mise en évidence du texte.

---
*Dernière mise à jour par Gemini CLI.*
