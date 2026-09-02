# Rôles & permissions

Dalibi utilise **[Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)**. Le contrôle d'accès est **piloté par les permissions** (`{capacité}_{module}`, ex. `view_students`, `create_archives`, `delete_backups`), pas par les rôles : un rôle doté des bonnes permissions fonctionne automatiquement pour les menus, les routes (`can:*`), les cartes d'accueil et le tableau de bord. **~145 permissions** au total (voir `App\Constants\Permissions`).

> **Une permission par verbe.** Les routes appliquent une permission distincte selon l'action : un droit de lecture (`view_*`) ne permet jamais de créer, modifier ou supprimer.

## Rôles fournis par défaut

| Rôle | Portée |
| --- | --- |
| **Administrateur** | Accès complet ; gestion des utilisateurs, rôles et permissions ; configuration système |
| **Directeur** | Gestion de l'école, années académiques, classes, suivi des élèves, rapports |
| **Enseignant** | Ses classes, saisie des notes, présences, consultation des élèves |
| **Comptable** | Gestion financière, consultation des élèves, rapports et exports |
| **Secrétaire** | Élèves & inscriptions, utilisateurs, consultation classes/présences, rapports |
| **Étudiant / Parent** | Consultation des notes et présences (portail) |

> Il n'existe **pas** de super-rôle « magique » : même l'administrateur tire son accès des permissions accordées par le seeder.

### Garde-fous sur les comptes à privilèges

- Seul un **administrateur** peut modifier ou supprimer un compte administrateur.
- Un mot de passe défini par un tiers impose un **changement à la première connexion**.
- Le **rôle administrateur** ne peut être ni renommé ni dépouillé de ses permissions (sans quoi l'instance serait verrouillée sans recours).

## Installation & seeding

Le package `spatie/laravel-permission` est déjà inclus dans le `composer.json`.

```bash
# 1. Migrations (rôles/permissions inclus)
php artisan migrate

# 2. Créer rôles & permissions initiaux
php artisan db:seed --class=RolesAndPermissionsSeeder

# 3. Production : données de référence complètes (catalogues inclus)
php artisan db:seed --class=ReferenceDataSeeder
```

Le modèle `User` inclut déjà le trait `HasRoles` et le guard `web` par défaut.

> Les seeders de **démonstration** sont automatiquement ignorés quand `APP_ENV=production`, y compris avec `--force`.

### Constantes typées

Utilisez les classes de constantes plutôt que des chaînes brutes :

```php
use App\Constants\Roles;
use App\Constants\Permissions;

$user->assignRole(Roles::TEACHER);          // au lieu de 'enseignant'
$user->givePermissionTo(Permissions::EDIT_GRADES);
```

## Utilisation dans le code

### Rôles

```php
$user->assignRole('enseignant');
$user->syncRoles(['administrateur']);       // remplace tous les rôles
$user->removeRole('enseignant');

$user->hasRole('administrateur');
$user->hasAnyRole(['administrateur', 'directeur']);   // OU
$user->hasAllRoles(['administrateur', 'enseignant']); // ET
$user->getRoleNames();
```

### Permissions

```php
$role = Role::findByName('enseignant');
$role->givePermissionTo('edit_grades');
$user->givePermissionTo(['edit_grades', 'view_grades']);
$role->revokePermissionTo('edit_grades');

$user->can('edit_students');
$user->hasAnyPermission(['edit_students', 'delete_students']);  // OU
$user->hasAllPermissions(['edit_students', 'view_students']);   // ET
```

### Routes

```php
Route::group(['middleware' => 'permission:edit_students'], function () { /* … */ });

// Une permission par verbe sur une ressource :
Route::resource('students', StudentController::class)
    ->middleware('can:view_students')
    ->middlewareFor(['create', 'store'], 'can:create_students')
    ->middlewareFor(['edit', 'update'], 'can:edit_students')
    ->middlewareFor('destroy', 'can:delete_students');
```

`middlewareFor` **fusionne** avec le middleware de base : `destroy` exige donc *view + delete*.

### Contrôleurs & Blade

```php
$this->authorize('edit_students');   // ou : abort_unless($request->user()->can('edit_students'), 403);
```

```blade
@can('edit_students') <button>Éditer</button> @endcan
@role('administrateur') <button>Panel admin</button> @endrole
```

## Ajouter un rôle personnalisé (sans code)

1. **Créer le rôle et lui accorder des permissions** depuis l'interface (Administration → Rôles & permissions, gardée par `manage_roles_permissions`).
2. **Assigner le rôle** à des utilisateurs (Administration → Utilisateurs) : la liste des rôles est lue depuis la base, votre rôle y apparaît immédiatement.

### Préfixe de matricule d'un rôle personnalisé

Par défaut, un rôle inconnu reçoit le préfixe générique `USR`. Pour un préfixe dédié, ajoutez une entrée dans `config/matricule.php` (voir [Matricule](../architecture/matricule.md)) :

```php
'role_prefixes' => [
    'censeur'     => 'CENS',
    'surveillant' => 'SURV',
],
```

### Limites connues (couplages métier)

- Les listes déroulantes « enseignant » (affectations, emploi du temps) recensent les utilisateurs ayant la permission **`create_marks`** — donnez cette permission au rôle pour qu'il soit sélectionnable comme enseignant.
- **Cloisonnement enseignant** : un utilisateur porteur d'affectations ne saisit notes et appels que dans ses classes. Les profils sans aucune affectation (administration, vie scolaire) restent transverses.

## Cache

Les rôles et permissions sont mis en cache. Après une modification directe en base :

```bash
php artisan cache:clear
```

```php
\Spatie\Permission\PermissionRegistrar::forgetCachedPermissions();
```

## Ressources

- [Documentation Spatie Permission](https://spatie.be/docs/laravel-permission/v6/introduction)
- [GitHub du package](https://github.com/spatie/laravel-permission)
