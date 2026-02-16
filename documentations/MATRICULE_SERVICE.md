# Guide d'utilisation du Service Matricule

## Vue d'ensemble

Le service `MatriculeService` permet de générer automatiquement les matricules uniques pour les utilisateurs et les élèves selon un format spécifique.

## Formats de Matricules

### Matricule Utilisateur
Format: `{PREFIXE}{ANNEE}{SEQUENCE}`

Exemples:
- **Administrateur**: `ADM26001` (Administrateur, 2026, sequence 001)
- **Professeur**: `PROF26001` (Professeur, 2026, sequence 001)
- **Comptable**: `COMPT26002` (Comptabilité, 2026, sequence 002)
- **Secrétaire**: `SEC26001` (Secrétariat, 2026, sequence 001)
- **Directeur**: `DIR26001` (Directeur, 2026, sequence 001)

### Matricule Élève
Format: `{CODE_ECOLE}STU{ANNEE}{SEQUENCE}`

Exemples:
- `ECOSTU26001` (Élève à l'école ECO, 2026, sequence 001)
- `LOMESTU26002` (Élève à Lomé, 2026, sequence 002)

### Numéro d'Enregistrement
Format: `REG-{CODE_PAYS}-{ANNEE}-{SEQUENCE}`

Exemples:
- `REG-TG-2026-001` (Togo 2026, sequence 001)
- `REG-LO-2026-002` (Lomé 2026, sequence 002)

## Utilisation dans le Code

### 1. Génération Automatique

#### Pour les Utilisateurs
```php
use App\Models\User;

// Créer un utilisateur
$user = User::create([
    'firstname' => 'Jean',
    'lastname' => 'Dupont',
    'email' => 'jean@example.com',
    'password' => bcrypt('password'),
]);

// Le matricule est généré automatiquement dans la migration des rôles
$user->assignRole('enseignant');
$user->natricule = $user->generateMatricule();
$user->save();

echo $user->natricule; // PROF26001
```

#### Pour les Élèves
```php
use App\Models\Student;
use App\Models\User;

// Créer l'utilisateur d'abord
$userStudent = User::create([
    'firstname' => 'Ahmed',
    'lastname' => 'Ouedraogo',
    'email' => 'ahmed@example.com',
    'password' => bcrypt('password'),
]);

// Créer l'enregistrement d'élève
$student = Student::create([
    'user_id' => $userStudent->id,
    'class_id' => $class->id,
    'parent_name' => 'Maman Ahmed',
    'parent_phone' => '+228 22 123 456',
    'enrollment_date' => now(),
]);

// Le numéro d'enregistrement est généré automatiquement
echo $student->registration_number; // REG-TG-2026-001
```

### 2. Génération Manuelle

```php
use App\Services\MatriculeService;

$matriculeService = app(MatriculeService::class);

// Générer un matricule utilisateur
$userMatricule = $matriculeService->generateUserMatricule('enseignant', 'ECOL001');
// Résultat: PROF26001

// Générer un matricule élève
$studentMatricule = $matriculeService->generateStudentMatricule('ECOL001', 'CM1A');
// Résultat: ECSTU26001

// Générer un numéro d'enregistrement
$regNumber = $matriculeService->generateRegistrationNumber('ECOL001', 'CM1A');
// Résultat: REG-EC-2026-001
```

### 3. Vérification d'Existence

```php
use App\Services\MatriculeService;

$matriculeService = app(MatriculeService::class);

// Vérifier si un matricule existe
if ($matriculeService->matriculeExists('PROF26001')) {
    echo 'Ce matricule existe déjà';
}

// Vérifier si un numéro d'enregistrement existe
if ($matriculeService->registrationNumberExists('REG-TG-2026-001')) {
    echo 'Ce numéro d\'enregistrement existe';
}
```

### 4. Parser un Matricule

```php
use App\Services\MatriculeService;

$matriculeService = app(MatriculeService::class);

$info = $matriculeService->parseMatricule('PROF26001');

// Résultat:
// [
//     'prefix' => 'PROF',
//     'year' => '2026',
//     'sequence' => 1,
// ]
```

### 5. Obtenir le Rôle à partir du Matricule

```php
use App\Services\MatriculeService;

$matriculeService = app(MatriculeService::class);

$role = $matriculeService->getRoleFromMatricule('PROF26001');
// Résultat: 'enseignant'
```

## Commandes Artisan

### Générer les Matricules pour les Utilisateurs

```bash
# Générer pour un utilisateur spécifique
php artisan matricule:generate-user --user-id=1

# Générer pour tous les utilisateurs sans matricule
php artisan matricule:generate-user --all

# Forcer la régénération d'un matricule existant
php artisan matricule:generate-user --user-id=1 --force
```

### Générer les Matricules pour les Élèves

```bash
# Générer pour tous les élèves
php artisan matricule:generate-student --all

# Générer pour une classe spécifique
php artisan matricule:generate-student --class-id=1

# Générer aussi les numéros d'enregistrement
php artisan matricule:generate-student --all --registration

# Forcer la régénération
php artisan matricule:generate-student --all --force
```

## Préfixes par Rôle

| Rôle | Préfixe | Exemple |
|------|---------|---------|
| Administrateur | ADM | ADM26001 |
| Directeur | DIR | DIR26001 |
| Enseignant | PROF | PROF26001 |
| Comptabilité | COMPT | COMPT26001 |
| Secrétariat | SEC | SEC26001 |

## Constantes Disponibles

```php
use App\Services\MatriculeService;

// Obtenir les préfixes disponibles
$prefixes = MatriculeService::getPrefixes();

// Résultat:
// [
//     'administrateur' => 'ADM',
//     'directeur' => 'DIR',
//     'enseignant' => 'PROF',
//     'comptabilité' => 'COMPT',
//     'secrétariat' => 'SEC',
// ]
```

## Validations Personnalisées

### Validator pour le Matricule

```php
use Illuminate\Validation\Rule;

$validated = $request->validate([
    'natricule' => [
        'required',
        'string',
        'unique:users,natricule',
        'regex:/^([A-Z]{3,4}[0-9]{2}[0-9]{3})$/',
    ],
]);
```

### Request Personnalisée

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'natricule' => [
                'required',
                'unique:users,natricule',
                'regex:/^([A-Z]{3,4}[0-9]{2}[0-9]{3})$/',
            ],
        ];
    }
}
```

## Cas d'Usage Avancés

### Générer un Matricule Aléatoire Sécurisé

```php
use App\Services\MatriculeService;

$matriculeService = app(MatriculeService::class);

// Pour les cas spéciaux qui nécessitent un format différent
$randomMatricule = $matriculeService->generateRandomMatricule('CUSTOM');
// Résultat: CUSTOM-1708098544-1A2B3C4D
```

### Migration en Masse

```php
use App\Models\User;
use App\Services\MatriculeService;

// Dans une commande ou un seeder
$matriculeService = app(MatriculeService::class);

User::whereNull('natricule')->each(function ($user) use ($matriculeService) {
    $user->update([
        'natricule' => app(MatriculeService::class)->generateUserMatricule(
            $user->roles->first()?->name ?? 'administrator'
        ),
    ]);
});
```

## Besoin d'Aide?

Consultez:
- [Laravel Documentation](https://laravel.com/docs)
- Application: support@ecoliotogo.tg

## Notes Importantes

1. ⚠️ Les matricules sont **uniques** et ne peuvent pas être dupliqués
2. ✅ L'année change automatiquement chaque année civile
3. ✅ La séquence s'incrémente automatiquement
4. ✅ Les matricules incluent le rôle dans le préfixe
5. ✅ Compatible avec les migrations et les seeders
