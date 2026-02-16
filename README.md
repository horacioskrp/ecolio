# Ecolio - Système de Gestion Scolaire

Un système de gestion scolaire open-source pour les écoles primaires, collèges et lycées du Togo et d'Afrique.

## 🎓 Fonctionnalités

### Gestion des écoles

- Support multi-écoles (primaire, collège, lycée)
- Paramétrage par établissement
- Gestion des années académiques

### Gestion des utilisateurs

- **Rôles disponibles**: Admin, Enseignant, Comptable, Secrétaire, Directeur
- Authentification sécurisée
- Two-Factor Authentication (2FA)
- Profils utilisateur complets

### Gestion académique

- Création et gestion des classes
- Inscription des élèves
- Gestion des matières (sujets)
- Attribution des enseignants aux classes et matières
- Système de notation (3 trimestres)

### Gestion des présences

- Suivi quotidien des présences
- États: Présent, Absent, Retard, Excusé
- Notes et justifications

### Gestion des notes

- Saisie des notes par matière et trimestre
- Commentaires sur les résultats scolaires
- Suivi des performances

## 📋 Prérequis

- PHP 8.3+
- PostgreSQL 12+
- Composer
- Node.js & npm
- Laravel 11+

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/horacioskrp/ecolio.git
cd ecolio
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les dépendances Node.js

```bash
npm install
```

### 4. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configurer la base de données PostgreSQL

Créez une base de données PostgreSQL:

```sql
CREATE DATABASE ecolio;
```

Mettez à jour le fichier `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecolio
DB_USERNAME=postgres
DB_PASSWORD=votre_mot_de_passe
```

### 6. Exécuter les migrations

```bash
php artisan migrate
```

### 7. Charger les données de démonstration (optionnel)

```bash
php artisan db:seed --class=SchoolDemoSeeder
```

### 8. Compiler les assets

```bash
npm run dev
```

ou pour la production:

```bash
npm run build
```

### 9. Démarrer le serveur

```bash
php artisan serve
```

L'application sera accessible à `http://localhost:8000`

## 👥 Utilisateurs par défaut

Après avoir exécuté le seeder, les utilisateurs suivants sont disponibles:

| Rôle       | Email                       | Mot de passe |
| ---------- | --------------------------- | ------------ |
| Admin      | admin@ecoliotogo.tg         | password     |
| Enseignant | sophie.martin@ecoliotogo.tg | password     |
| Comptable  | claire@ecoliotogo.tg        | password     |
| Secrétaire | isabelle@ecoliotogo.tg      | password     |

> **Sécurité**: Changez ces mots de passe en production!

## 🏗️ Structure de la base de données

### Tables principales

#### `schools`

École ou établissement scolaire

- Niveaux: primaire, collège, lycée
- Informations de contact et de direction

#### `academic_years`

Années académiques liées à chaque école

- Dates de début et fin
- Statut actif/inactif

#### `classes`

Classes ou sections

- Liées à une école et une année académique
- Capacité
- Enseignant principal

#### `users`

Utilisateurs du système

- Rôles: admin, enseignant, comptable, secrétaire, directeur
- UUID comme clé primaire
- Authentification 2FA supportée

#### `students`

Élèves inscrits

- Liés à un utilisateur et une classe
- Numéro d'enregistrement unique
- Coordonnées des parents

#### `subjects`

Matières ou disciplines enseignées

#### `class_subjects`

Attribution de matières aux classes avec enseignants

#### `grades`

Résultats scolaires par trimestre

#### `attendances`

Registre des présences

## 🔐 Sécurité

- Authentification par email/mot de passe
- Two-Factor Authentication (2FA)
- Hachage des mots de passe avec Bcrypt
- UUIDs pour les clés primaires
- Protection CSRF
- Validation des données

## 📦 Structure du projet

```
ecolio/
├── app/
│   ├── Models/          # Modèles Eloquent
│   ├── Http/
│   │   ├── Controllers/ # Contrôleurs
│   │   ├── Middleware/  # Middlewares
│   │   └── Requests/    # Form Requests
│   └── Providers/       # Service Providers
├── database/
│   ├── migrations/      # Migrations
│   └── seeders/         # Seeders
├── resources/
│   ├── js/             # Composants React
│   ├── css/            # Styles
│   └── views/          # Vues Blade
├── routes/             # Routes
├── tests/              # Tests
└── config/             # Configurations
```

## 🧪 Tests

Exécuter les tests:

```bash
php artisan test
```

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 🤝 Contribution

Les contributions sont bienvenues! Veuillez:

1. Fork le projet
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Commiter vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Pusher la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 👨‍💻 Développé par

**Horacio Skrp** - [GitHub](https://github.com/horacioskrp)

## 📧 Support

Pour le support, contactez: support@ecoliotogo.tg

## 🗺️ Roadmap

- [ ] Portail des parents
- [ ] Application mobile
- [ ] Bulletins électroniques
- [ ] Système de communication école-parents
- [ ] Gestion des ressources (livres, équipements)
- [ ] Calendrier académique interactif
- [ ] Gestion financière (frais scolaires)
- [ ] Rapports et statistiques avancées
- [ ] Intégration avec le système éducatif togolais

## 💡 Ressources

- [Documentation Laravel](https://laravel.com/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [React Documentation](https://react.dev)

---

**Important**: Ce logiciel est en développement actif. Veuillez signaler les bugs via les GitHub Issues.
