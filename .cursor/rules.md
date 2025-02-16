# Règles Cursor pour le Projet

## Configuration de l'Éditeur

```json
{
    "editor.formatOnSave": true,
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "editor.tabSize": 4,
    "editor.insertSpaces": true,
    "files.trimTrailingWhitespace": true,
    "files.insertFinalNewline": true
}
```

## Extensions Recommandées

-   PHP Intelephense
-   Vue Language Features
-   Tailwind CSS IntelliSense
-   ESLint
-   Prettier
-   Laravel Blade formatter
-   PHP Debug

## Snippets Personnalisés

### Vue Component

```vue
<template>
    <div>
        <!-- Contenu -->
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

// Code
</script>
```

### Laravel Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NomController extends Controller
{
    public function index()
    {
        //
    }
}
```

## Raccourcis Clavier Recommandés

-   `Cmd/Ctrl + Shift + P`: Palette de commandes
-   `Cmd/Ctrl + P`: Recherche rapide de fichiers
-   `Cmd/Ctrl + Shift + F`: Recherche globale
-   `Alt + ↑/↓`: Déplacer une ligne
-   `Cmd/Ctrl + /`: Commenter/décommenter

## Organisation des Fichiers

-   Components Vue: `PascalCase.vue`
-   Classes PHP: `PascalCase.php`
-   Fichiers de config: `kebab-case.js`
-   Fichiers CSS: `kebab-case.css`

## Règles de Formatage

### PHP

-   PSR-12
-   Accolades sur la même ligne
-   Espaces après les virgules
-   Pas d'espaces avant les parenthèses de fonction

### Vue/JavaScript

-   Single quotes pour les chaînes
-   Semicolons obligatoires
-   2 espaces d'indentation
-   Trailing commas dans les objets multilignes

### CSS/SCSS

-   Utiliser les classes Tailwind quand possible
-   BEM pour les classes personnalisées
-   Éviter !important

## Debugging

### PHP

```php
dump($variable); // Pour le debug dans le code
Log::info('Message'); // Pour les logs
```

### JavaScript

```javascript
console.log('Debug:', variable);
console.table(array); // Pour les tableaux
```

## Git Hooks Recommandés

```bash
# pre-commit
./vendor/bin/php-cs-fixer fix
npm run lint
```

## Tests

### PHP

```bash
php artisan test --filter=TestName
```

### Vue

```bash
npm run test:unit ComponentName
```

## Performances

-   Utiliser le lazy loading pour les composants Vue
-   Optimiser les requêtes SQL avec eager loading
-   Mettre en cache les réponses API quand possible

## Sécurité

-   Valider toutes les entrées utilisateur
-   Échapper les sorties HTML
-   Utiliser les prepared statements
-   CSRF token pour tous les formulaires

## CI/CD

-   Linting automatique
-   Tests automatiques
-   Build et déploiement automatisés

## Maintenance

-   Nettoyer régulièrement le cache
-   Mettre à jour les dépendances
-   Vérifier les logs d'erreur
-   Optimiser la base de données

# Règles du Projet Analyseur d'Interventions

## Description

Projet d'analyse des interventions techniques avec import de fichiers CSV et calcul des revenus.

## Contexte du Projet

Application web permettant d'analyser les fichiers CSV des interventions techniques et de générer des rapports détaillés.

## Structure des Données CSV

-   date_de_rdv: Date de l'intervention
-   nom_technicien: Nom du technicien
-   prenom_technicien: Prénom du technicien
-   duree: Durée en minutes
-   type: Type d'intervention (SAV, RACC)

## Points d'Attention

1. Validation des fichiers CSV :

    - Vérifier la présence de toutes les colonnes requises
    - Valider le format des dates
    - Convertir la durée en heures

2. Gestion des erreurs API :

    - Toujours renvoyer des réponses JSON
    - Logger les erreurs avec détails
    - Gérer les validations côté serveur

3. Configuration CORS :
    - Autoriser les origines localhost
    - Activer credentials
    - Gérer les headers appropriés

## Solutions Communes

### Erreur "Unexpected token '<'"

```php
// Dans app/Exceptions/Handler.php
public function register()
{
    $this->renderable(function (Throwable $e, Request $request) {
        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    });
}
```

### Validation Upload

```php
// Dans InterventionController
$request->validate([
    'file' => 'required|file|mimes:csv,txt|max:16384'
]);
```

### Headers CORS

```php
// Dans config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```
