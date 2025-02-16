# Guide de Contribution

## Structure du Projet

```
excel-compta-salaire/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Imports/
│   └── Models/
├── resources/
│   ├── js/
│   │   ├── components/
│   │   └── App.vue
│   ├── css/
│   └── views/
└── routes/
    ├── api.php
    └── web.php
```

## Standards de Code

### Backend (PHP)

-   Suivre les PSR-1 et PSR-12
-   Utiliser le typage strict des paramètres et retours de fonction
-   Documenter les méthodes avec des commentaires PHPDoc
-   Utiliser les injections de dépendances
-   Logger les erreurs et exceptions

### Frontend (Vue.js)

-   Utiliser la Composition API
-   Nommer les composants en PascalCase
-   Nommer les props en camelCase
-   Utiliser TypeScript pour les types
-   Documenter les props et événements

### CSS

-   Utiliser Tailwind CSS
-   Suivre une approche mobile-first
-   Utiliser les classes utilitaires de Tailwind

## Format des Fichiers CSV

Le système attend les colonnes suivantes :

-   `date_de_rdv`: Date de l'intervention (YYYY-MM-DD)
-   `nom_technicien`: Nom du technicien
-   `prenom_technicien`: Prénom du technicien
-   `duree`: Durée en minutes
-   `type`: Type d'intervention (SAV, RACC, etc.)

## Gestion des Erreurs

### Backend

1. Toujours retourner des réponses JSON pour les routes API
2. Utiliser les codes HTTP appropriés :
    - 200: Succès
    - 201: Création réussie
    - 400: Erreur de requête
    - 422: Erreur de validation
    - 500: Erreur serveur
3. Logger les erreurs avec le niveau approprié

### Frontend

1. Gérer les erreurs de réseau
2. Afficher des messages d'erreur utilisateur
3. Valider les données avant envoi
4. Gérer les timeouts

## Configuration CORS

Le fichier `config/cors.php` doit être configuré avec :

-   Origins autorisés : localhost:5173, localhost:5174
-   Credentials supportés
-   Headers autorisés

## Tests

### Backend

```bash
php artisan test
```

### Frontend

```bash
npm run test
```

## Déploiement

1. Compiler les assets :

```bash
npm run build
```

2. Optimiser Laravel :

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
```

## Résolution des Problèmes Courants

### Erreur CORS

1. Vérifier la configuration CORS
2. Ajouter les headers appropriés
3. Activer credentials si nécessaire

### Erreur Upload

1. Vérifier les limites de taille dans php.ini
2. Valider le format du fichier
3. Vérifier les permissions du dossier storage

### Erreur Base de Données

1. Vérifier la connexion SQLite
2. Exécuter les migrations
3. Vérifier les permissions du fichier

## Maintenance

-   Nettoyer les logs régulièrement
-   Vérifier l'espace disque
-   Sauvegarder la base de données
-   Mettre à jour les dépendances
