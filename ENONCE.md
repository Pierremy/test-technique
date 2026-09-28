# Test technique - Gestion de catalogue produit

## Projet

Outil de gestion d'un catalogue avec des produits et des catégories.

## Stack imposée

| Domaine | Stack |
|---|---|
| API | Laravel (PHP 8.4), Sail (Docker), MySQL |
| Front | Vue 3 (Composition API), Vite, Element Plus, Axios |

## Structure du dépôt

```
test-technique/
├── api/     → Laravel + Sail
├── front/   → Vue 3 + Element Plus
└── README.md
```

## Initialisation

- API : `./vendor/bin/sail up -d`. `http://localhost:8000`.
- Front : `npm install`, `npm run dev`. `http://localhost:5173`.

Route de test : `GET /api/ping` → `{"status":"ok"}`.

## Exercice

### Modèle de données

Mettez en place au minimum :

- **Catégorie** : nom.
- **Produit** : nom, référence/SKU (unique), prix, quantité en stock, catégorie associée (relation).

Le détail des colonnes/contraintes vous appartient.

### API à développer

Exposez une API REST sous `/api` permettant de gérer les produits et de lister les catégories :

- `GET /api/products` - liste paginée, avec recherche par nom (autres critères de filtrage possibles).
- `GET /api/products/{id}` - détail d'un produit.
- `POST /api/products` - création.
- `DELETE /api/products/{id}` - suppression.
- `GET /api/categories` - liste des catégories (pas besoin de gérer leur création).
- **Bonus** : `PUT/PATCH /api/products/{id}` - mise à jour.

### Front à développer

En utilisant Element Plus, construisez les écrans permettant de :

- Lister les produits.
- Créer un produit.
- Supprimer un produit.
- **Bonus** : éditer un produit.

## Livrable

- Le dépôt GitHub.
- Un court README expliquant vos choix techniques, ce qui est fait/pas fait, et comment lancer le projet si vous avez modifié la procédure de démarrage.

## Temps indicatif

Comptez environ 2 à 3 heures. Il n'est pas nécessaire de tout terminer : privilégiez la qualité de ce que vous livrez à l'exhaustivité, et indiquez dans votre README ce que vous auriez fait avec plus de temps.
