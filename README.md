# Analyseur d'Interventions Techniques

Application web permettant d'analyser les fichiers Excel/CSV des interventions techniques et de générer des rapports détaillés.

## Fonctionnalités

-   Upload de fichiers Excel/CSV
-   Analyse automatique des interventions
-   Calcul des revenus par technicien
-   Visualisation des données
-   Export PDF des rapports
-   Interface responsive

## Technologies

-   **Backend:** Laravel 11
-   **Frontend:** Vue.js 3 + Tailwind CSS
-   **Base de données:** SQLite
-   **Librairies:**
    -   Laravel Excel pour le traitement des fichiers
    -   Chart.js pour les visualisations
    -   DomPDF pour l'export PDF

## Installation

1. Cloner le projet

```bash
git clone [url-du-projet]
cd [nom-du-projet]
```

2. Installer les dépendances

```bash
composer install
npm install
```

3. Configuration

```bash
cp .env.example .env
php artisan key:generate
```

4. Base de données
   La base de données SQLite est déjà configurée.

5. Lancer l'application

```bash
php artisan serve
npm run dev
```

## Structure des fichiers Excel/CSV attendue

-   Date d'intervention
-   Nom du technicien
-   Type de service
-   Durée
-   Prix

## Tarifs des services

-   Installation : 80€/h
-   Maintenance : 60€/h
-   Dépannage : 90€/h
-   Formation : 70€/h

## License

[MIT License](LICENSE.md)
