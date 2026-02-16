# Service de Génération des Matricules - Ecolio

## 📋 Vue d'ensemble

Le service de gen matricules Ecolio est un système automatisé et complet pour générer des identifiants uniques pour les utilisateurs et les élèves du système de gestion scolaire.

## ✨ Caractéristiques principales

- ✅ **Auto-génération automatique**: Les matricules sont générés automatiquement lors de la création d'utilisateurs/élèves
- ✅ **Formats distincts par rôle**: Chaque rôle a un préfixe unique (ADM, DIR, PROF, COMPT, SEC)
- ✅ **Numérotation séquentielle**: Les numéros s'incrémentent automatiquement par année et rôle
- ✅ **Validation robuste**: Formats et unicité garantis
- ✅ **Commandes Artisan**: CLI pour la génération en masse
- ✅ **Configuration flexible**: Paramètres ajustables via config/matricule.php
- ✅ **Tests complets**: Suite de tests unitaires et d'intégration
- ✅ **Événements**: Tracking des générations via Laravel Events

## 🚀 Démarrage rapide

### Installation

1. **Exécuter les migrations** (si ce n'est pas déjà fait):

```bash
php artisan migrate
```

2. **Publier la configuration** (optionnel):

```bash
php artisan vendor:publish --tag=matricule-config
```

### Utilisation basique

#### Créer un utilisateur et générer son matricule:

```php
use App\Models\User;
use App\Constants\Roles;

$user = User::create([
    'firstname' => 'Jean',
    'lastname' => 'Dupont',
    'email' => 'jean@example.com',
    'password' => bcrypt('password'),
    'school_id' => $schoolId,
]);

$user->assignRole(Roles::TEACHER);
$user->generateMatricule();
$user->save();

// $user->natricule = "PROF26001"
```

#### Créer un élève (avec matricule et numéro d'enregistrement):

```php
use App\Models\Student;
use App\Models\User;

$user = User::create([...]);
$student = Student::create([
    'user_id' => $user->id,
    'class_id' => $classId,
    'parent_name' => 'Parent Name',
    'parent_phone' => '+228 22 123 456',
    'enrollment_date' => now(),
]);

// $student->registration_number = "REG-TG-2026-001"
```

## 📚 Structure des Matricules

### Format Utilisateur

```
{PREFIXE}{ANNEE}{SEQUENCE}
```

**Exemple**: `PROF26001`

- `PROF` = Préfixe du rôle (Professeur/Enseignant)
- `26` = Année (2026)
- `001` = Numéro séquentiel

### Format Élève

```
{CODE_ECOLE}STU{ANNEE}{SEQUENCE}
```

**Exemple**: `ECOSTU26001`

- `ECO` = Code de l'école
- `STU` = Code pour Étudiant
- `26` = Année (2026)
- `001` = Numéro séquentiel

### Format Numéro d'Enregistrement

```
REG-{CODE_ECOLE}-{ANNEE}-{SEQUENCE}
```

**Exemple**: `REG-TG-2026-001`

- `REG` = Préfixe
- `TG` = Code du pays (Togo)
- `2026` = Année complète
- `001` = Numéro séquentiel

## 👥 Préfixes par Rôle

| Rôle           | Préfixe | Code BD        |
| -------------- | ------- | -------------- |
| Administrateur | ADM     | administrateur |
| Directeur      | DIR     | directeur      |
| Enseignant     | PROF    | enseignant     |
| Comptabilité   | COMPT   | comptabilité   |
| Secrétariat    | SEC     | secrétariat    |

## 🔧 Commands Artisan

### Générer les matricules des utilisateurs

```bash
# Générer pour un utilisateur spécifique
php artisan matricule:generate-user --user-id=1

# Générer pour tous les utilisateurs sans matricule
php artisan matricule:generate-user --all

# Forcer la régénération d'un matricule existant
php artisan matricule:generate-user --user-id=1 --force

# Afficher la progression
php artisan matricule:generate-user --all  # Affiche la barre de progression
```

### Générer les matricules des élèves

```bash
# Générer pour tous les élèves
php artisan matricule:generate-student --all

# Générer pour une classe spécifique
php artisan matricule:generate-student --class-id=1

# Générer aussi les numéros d'enregistrement
php artisan matricule:generate-student --all

# Forcer la régénération
php artisan matricule:generate-student --all --force
```

## 🎯 Cas d'usage

### 1. Micro-service de validation

```php
use App\Services\MatriculeService;

$service = app(MatriculeService::class);

// Vérifier si un matricule existe
if ($service->matriculeExists('PROF26001')) {
    echo 'Ce matricule est déjà utilisé';
}

// Valider le format
if (!$service->isValidMatriculeFormat('PROF26001')) {
    echo 'Format de matricule invalide';
}

// Parser un matricule
$info = $service->parseMatricule('PROF26001');
// [
//   'prefix' => 'PROF',
//   'year' => '26',
//   'sequence' => 1,
// ]

// Obtenir le rôle
$role = $service->getRoleFromMatricule('PROF26001');
// 'enseignant'
```

### 2. Validation dans un formulaire (Laravel Form Request)

```php
namespace App\Http\Requests;

use App\Rules\ValidMatriculeFormat;
use App\Rules\UniqueMatricule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'natricule' => [
                'nullable',
                new ValidMatriculeFormat(),
                new UniqueMatricule($this->user?->id),
            ],
        ];
    }

    public function messages()
    {
        return [
            'natricule.required' => 'Le matricule est requis',
        ];
    }
}
```

### 3. Migration en masse depuis une autre application

```php
use App\Models\User;
use App\Services\MatriculeService;

class ImportUsersFromLegacySystem {
    public function handle()
    {
        $service = app(MatriculeService::class);

        User::whereNull('natricule')->chunk(100, function ($users) use ($service) {
            foreach ($users as $user) {
                $role = $user->roles->first()?->name ?? 'administrateur';
                $user->update([
                    'natricule' => $service->generateUserMatricule($role)
                ]);
            }
        });
    }
}
```

## 🔐 Sécurité et Unicité

### Garantie d'unicité

- ✅ Index unique sur `users.natricule`
- ✅ Index unique sur `students.registration_number`
- ✅ Vérification DB avant création
- ✅ Validation des doublons

### Protection contre les duplicatas

```php
// Tous les matricules sont validés avant insertion
$user->update(['natricule' => 'PROF26001']);
// Lance une erreur si ce matricule existe déjà

// La commande CLI gère aussi les doublons
php artisan matricule:generate-user --all
// Saute les utilisateurs avec un matricule existant
```

## 📊 Configuration

### Fichier `config/matricule.php`

```php
return [
    'enabled' => env('MATRICULE_SERVICE_ENABLED', true),

    'user' => [
        'prefix_length' => 3,
        'year_length' => 2,
        'sequence_length' => 3,
        'auto_generate' => env('MATRICULE_AUTO_GENERATE_USER', true),
    ],

    'student' => [
        'prefix' => 'STU',
        'year_length' => 2,
        'sequence_length' => 3,
        'auto_generate' => env('MATRICULE_AUTO_GENERATE_STUDENT', true),
    ],

    'registration' => [
        'prefix' => 'REG',
        'country_code' => env('MATRICULE_COUNTRY_CODE', 'TG'),
        'year_length' => 4,
        'sequence_length' => 3,
    ],

    'role_prefixes' => [
        'administrateur' => 'ADM',
        'directeur' => 'DIR',
        'enseignant' => 'PROF',
        'comptabilité' => 'COMPT',
        'secrétariat' => 'SEC',
    ],
];
```

### Variables d'environnement

```env
# .env
MATRICULE_SERVICE_ENABLED=true
MATRICULE_AUTO_GENERATE_USER=true
MATRICULE_AUTO_GENERATE_STUDENT=true
MATRICULE_COUNTRY_CODE=TG
```

## 🧪 Tests

### Exécuter les tests

```bash
# Tous les tests matricule
php artisan test tests/Unit/MatriculeServiceTest.php

# Tests d'intégration
php artisan test tests/Feature/MatriculeGenerationTest.php

# Tous les tests
php artisan test
```

### Structure des tests

```
tests/
├── Unit/
│   └── MatriculeServiceTest.php          # Tests du service
└── Feature/
    └── MatriculeGenerationTest.php       # Tests d'intégration
```

## 📝 API des Méthodes

### MatriculeService

```php
// Génération
generateUserMatricule(string $role): string
generateStudentMatricule(string $schoolCode, string $classCode): string
generateRegistrationNumber(string $schoolCode, string $classCode): string

// Parsing et analyse
parseMatricule(string $matricule): array
getRoleFromMatricule(string $matricule): string

// Vérification
matriculeExists(string $matricule): bool
registrationNumberExists(string $regNumber): bool
isValidMatriculeFormat(string $matricule): bool

// Utilitaires
getNextSequence(string $prefix): int
getPrefixes(): array
```

### Traits

```php
// Dans User et Student
generateMatricule(): void
generateRegistrationNumber(): void  // Student uniquement
```

## 🐛 Dépannage

### Problème: Matricule non généré automatiquement

**Solution**: Vérifier que `MATRICULE_AUTO_GENERATE_USER=true` dans `.env`

### Problème: Erreur "Matricule already exists"

**Solution**: Utiliser `--force` avec la commande CLI:

```bash
php artisan matricule:generate-user --user-id=1 --force
```

### Problème: Matricule avec format erroné

**Solution**: Vérifier la configuration dans `config/matricule.php`

## 📞 Support

Pour toutes les questions ou problèmes:

- Consultez la documentation complète: [MATRICULE_SERVICE.md](MATRICULE_SERVICE.md)
- Ouvrez une issue sur GitHub
- Contactez: support@ecoliotogo.tg

## 📜 Changelog

### Version 1.0.0

- ✅ Service principal MatriculeService
- ✅ Trait HasMatricule pour auto-génération
- ✅ Commandes Artisan pour batch generation
- ✅ Observers pour tracking
- ✅ Validation Rules
- ✅ Configuration centralisée
- ✅ Tests complets
- ✅ Documentation

## 📄 License

Spécifiez votre license (MIT, Apache 2.0, etc.)
