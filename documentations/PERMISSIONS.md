# Guide d'utilisation de Laravel Permission

## Configuration de Lararel Permission

L'application utilise **[Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)** pour gérer les rôles et permissions.

## Rôles Disponibles

### 1. Admin (Administrateur)

- **Accès complet** à tous les modules
- Gestion des utilisateurs, rôles et permissions
- Configuration du système

### 2. Directeur (Directeur d'école)

- Gestion de l'école
- Création et modification des années académiques
- Gestion des classes
- Suivi des étudiants
- Consultation des rapports

### 3. Enseignant (Professeur)

- Visualisation de ses classes
- Saisie des notes
- Gestion des présences
- Consultation des informations étudiants

### 4. Comptable (Agent financier)

- Gestion financière
- Consultation des étudiants
- Visualisation des rapports
- Export de rapports

### 5. Secrétaire

- Gestion des étudiants
- Gestion des utilisateurs
- Consultation des classes et présences
- Visualisation des rapports

### 6. Étudiant

- Consultation de ses notes
- Visualisation de ses présences

## Utilisation dans le Code

### Vérifier un rôle

```php
// Vérification simple
if ($user->hasRole('admin')) {
    // L'utilisateur a le rôle admin
}

// Méthodes pratiques
if ($user->isAdmin()) { }
if ($user->isTeacher()) { }
if ($user->isAccountant()) { }
if ($user->isSecretary()) { }
if ($user->isDirector()) { }

// Vérifier plusieurs rôles (OU)
if ($user->hasAnyRole(['admin', 'directeur'])) {
    // L'utilisateur a l'un de ces rôles
}

// Vérifier tous les rôles (ET)
if ($user->hasAllRoles(['admin', 'enseignant'])) {
    // L'utilisateur a TOUS ces rôles
}
```

### Assigner des rôles

```php
// Assigner un rôle
$user->assignRole('enseignant');

// Assigner plusieurs rôles
$user->assignRole(['directeur', 'enseignant']);

// En masse
$user->syncRoles(['admin']); // Remplace tous les rôles
```

### Retirer des rôles

```php
// Retirer un rôle
$user->removeRole('enseignant');

// Retirer plusieurs rôles
$user->removeRole(['directeur', 'enseignant']);

// Vider tous les rôles
$user->syncRoles([]);
```

## Permissions

### Vérifier une permission

```php
// Vérification simple
if ($user->hasPermission('edit_students')) {
    // L'utilisateur a la permission
}

// Via can()
if ($user->can('edit_students')) {
    // L'utilisateur peut éditer les étudiants
}

// Vérifier plusieurs permissions (OU)
if ($user->hasAnyPermission(['edit_students', 'delete_students'])) { }

// Vérifier toutes les permissions (ET)
if ($user->hasAllPermissions(['edit_students', 'view_students'])) { }
```

### Assigner des permissions

```php
// Au rôle
$role = Role::findByName('enseignant');
$role->givePermissionTo('edit_grades');

// À l'utilisateur
$user->givePermissionTo('edit_grades');

// Multiple
$user->givePermissionTo(['edit_grades', 'view_grades']);
```

### Retirer des permissions

```php
// Du rôle
$role->revokePermissionTo('edit_grades');

// De l'utilisateur
$user->revokePermissionTo('edit_grades');
```

## Utilisation dans les Routes

### Middleware de Rôle

```php
Route::group(['middleware' => 'role:admin'], function () {
    Route::get('/admin', 'AdminController@index');
});

// Plusieurs rôles (OU)
Route::group(['middleware' => 'role:admin|directeur'], function () {
    // Accessible aux admins et directeurs
});
```

### Middleware de Permission

```php
Route::group(['middleware' => 'permission:edit_students'], function () {
    Route::post('/students/{student}', 'StudentController@update');
});

// Plusieurs permissions (ET)
Route::group(['middleware' => 'permission:edit_students,view_students'], function () {
    // L'utilisateur doit avoir TOUTES ces permissions
});
```

### Combiné

```php
Route::group(['middleware' => 'role:admin|directeur;permission:edit_students'], function () {
    // Doit avoir le rôle AND la permission
});
```

## Utilisation dans les Contrôleurs

```php
public function edit($id)
{
    // Vérifier la permission
    if (!auth()->user()->can('edit_students')) {
        abort(403, 'Non autorisé');
    }

    // Ou utiliser authorize()
    $this->authorize('edit_students');

    // Code...
}
```

## Utilisation dans les Blade Templates

```blade
@can('edit_students')
    <button>Éditer l'étudiant</button>
@endcan

@role('admin')
    <button>Panel administrateur</button>
@endrole

@hasrole('directeur')
    <a href="/reports">Rapports</a>
@endhasrole

@cannot('delete_students')
    <p>Vous n'avez pas la permission de supprimer</p>
@endcannot
```

## Exemple Complet

### Création d'un nouveau rôle avec permissions

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Créer les permissions
Permission::create(['name' => 'view_custom']);
Permission::create(['name' => 'create_custom']);
Permission::create(['name' => 'edit_custom']);

// Créer le rôle
$customRole = Role::create(['name' => 'custom_role']);

// Assigner les permissions au rôle
$customRole->givePermissionTo(['view_custom', 'create_custom', 'edit_custom']);

// Assigner le rôle à un utilisateur
$user->assignRole('custom_role');
```

## Cache

Les rôles et permissions sont cachés. Après les avoir modifiés en base de données, actualisez le cache:

```php
app()['cache']->forget('spatie.permission.cache');
// ou
\Spatie\Permission\PermissionRegistrar::forgetCachedPermissions();
```

Ou dans l'application:

```bash
php artisan cache:clear
```

## Ressources

- [Documentation Spatie Permission](https://spatie.be/docs/laravel-permission/v6/introduction)
- [GitHub](https://github.com/spatie/laravel-permission)

## Support

Pour les questions, contactez: support@ecoliotogo.tg
