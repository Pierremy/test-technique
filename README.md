# Gestion de catalogue produit

Mon rendu pour le test technique.

## Lancer le projet

J'ai dû adapter un peu la procédure de départ. Le dépôt ne contenait ni `vendor/` ni `.env`, et les ports par défaut entraient en conflit avec mon environnement local.

Pour l'API (sous Windows, les commandes Sail se lancent depuis WSL) :

```bash
cd api
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
  laravelsail/php84-composer:latest composer install --ignore-platform-reqs
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

L'API répond ensuite sur `http://localhost:8000`. Le seed crée quelques catégories et une soixantaine de produits pour avoir de quoi tester. Les tests se lancent avec `./vendor/bin/sail test`.

Pour le front, rien de changé : `npm install` puis `npm run dev`, et l'interface est sur `http://localhost:5173`.

Les petites modifications apportées au squelette :
- `APP_PORT=8000` dans le `.env` pour coller à l'énoncé (Sail utilise le port 80 par défaut), ainsi que MySQL sur 3307 et le Vite de Laravel sur 5174, pour éviter les conflits ;
- le runtime Sail repassé en PHP 8.4 au lieu de 8.5, comme demandé dans la stack ;
- `oxlint` aligné en 1.73 dans le front, car le conflit de version avec `eslint-plugin-oxlint` bloquait le `npm install`.

## Ce que j'ai fait

Tout l'énoncé est couvert, bonus compris : liste, détail, création, modification et suppression des produits côté API, et les écrans correspondants côté front.

Pour le modèle de données, le SKU est unique, le prix est en `decimal` (pas de float pour les prix) et le stock ne peut pas être négatif. J'ai choisi d'empêcher la suppression d'une catégorie qui contient encore des produits, plutôt qu'une suppression en cascade pour éviter la perte de données. Une autre solution pour la suppression des catégories aurait été de garder les produits et leur attribuer une catégorie 'non classé'.

Côté API, la validation passe par des FormRequests, avec des messages en français qui remontent directement dans le formulaire. Le SKU est mis en majuscules avant la vérification d'unicité, pour éviter d'avoir des doublons. En plus de la recherche par nom (qui cherche aussi dans le SKU), la liste accepte un filtre par catégorie, par prix et par stock, ainsi qu'un tri. Les colonnes triables sont limitées à une liste fixe. J'ai écrit une série de tests Feature qui couvrent les principaux cas.

Côté front, tout est en Composition API. Les appels HTTP sont regroupés dans des services, et un intercepteur Axios affiche les erreurs générales. La création et l'édition partagent le même composant de formulaire. La pagination et le tri se font côté serveur. La recherche attend un court délai avant de partir et annule la requête précédente, pour qu'une réponse lente n'écrase pas un résultat plus récent.

## Ce que je n'ai pas fait

- l'authentification (Sanctum est installé mais pas branché, l'énoncé ne la demandait pas) ;
- les tests côté front ;
- la gestion des catégories, ainsi qu'une page listant les catégories et une page affichant les produits d'une catégorie. Pour l'instant, les catégories servent uniquement au filtre de la liste et au formulaire produit.

## Utilisation de l'IA

Je viens d'un développement web plus classique (PHP, JavaScript, XHR). Je connais Vue.js, mais je n'avais jamais travaillé avec Laravel ni avec Element Plus. J'ai donc travaillé avec un assistant IA (Claude), qui m'a aidé à mettre en place l'environnement (Sail, Docker, WSL) et à comprendre la structure du squelette et où ajouter le code. J'ai développé moi-même les différentes briques de code (Laravel pour l'API et Element Plus/Vue pour le front), Claude m'a aidé notamment pour la mise en place des tests et la validation des formulaires.

## Avec plus de temps

J'aurais commencé par protéger les routes d'écriture et ajouter une phase d'authentification. J'aurais aussi aimé garder les filtres et la pagination dans l'URL, pour pouvoir recharger ou partager une recherche, ajouter une page de détail produit et un parcours des produits par catégories (une page listant les catégories et permettant d'accéder aux produits associés).
