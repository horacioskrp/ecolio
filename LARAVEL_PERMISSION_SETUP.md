# Configuration de Laravel Permission

## Installation

Le package `spatie/laravel-permission` est déjà inclus dans le `composer.json` du projet.

### Étapes d'installation

1. **Exécuter les migrations** (incluses dans `2025_02_16_000004_add_role_to_users_table.php`):

    ```bash
    php artisan migrate
    ```

2. **Publier la configuration** (optionnel si vous voulez personnaliser):

    ```bash
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
    ```

3. **Créer les rôles et permissions initiaux**:

    ```bash
    php artisan db:seed --class=RolesAndPermissionsSeeder
    ```

4. **Créer les données de démo** (optionnel):
    ```bash
    php artisan db:seed --class=SchoolDemoSeeder
    ```

Ou tout d'un coup:

```bash
php artisan db:seed
```

## Configuration du Modèle User

Le modèle `User` inclut déjà le trait `HasRoles`:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

## Guard par défaut

Par défaut, le guard est `web`. Si vous utilisez des guards différents, configurez-le dans le modèle:

```php
class User extends Authenticatable
{
    protected $guard_name = 'web';
}
```

## Constantes de rôles et permissions

Utilisez les classes de constantes pour éviter les erreurs de typage:

```php
use App\Constants\Roles;
use App\Constants\Permissions;

// Rôles
$user->assignRole(Roles::TEACHER); // Au lieu de 'enseignant'

// Permissions
$user->givePermissionTo(Permissions::EDIT_GRADES);
```

## Vérification du cache

Les rôles et permissions sont cachés. Après les avoir modifiés directement en base de données, oubliez le cache:

```php
// Dans le contrôleur ou ailleurs
app()['cache']->forget('spatie.permission.cache');

// Ou
\Spatie\Permission\PermissionRegistrar::forgetCachedPermissions();

// Ou en CLI
php artisan cache:clear
```

## Erreurs courantes

### 1. Role not found

```
Spatie\Permission\Exceptions\RoleDoesNotExist: There is no role named `example`.
```

**Solution**: Vérifiez que le rôle a été créé en base de données avant de l'assigner.

### 2. Permission not found

```
Spatie\Permission\Exceptions\PermissionDoesNotExist: There is no permission named `example`.
```

**Solution**: Vérifiez que la permission a été créée en base de données.

### 3. Le cache ne se met pas à jour

**Solution**: Exécutez `php artisan cache:clear`

## Personnalisation

### Ajouter un nouveau rôle

```php
use Spatie\Permission\Models\Role;

// Créer le rôle
$role = Role::create(['name' => 'nouveau_role']);

// Ou utliser les constantes et mettre à jour la classe
// app/Constants/Roles.php
```

### Ajouter une nouvelle permission

```php
use Spatie\Permission\Models\Permission;

// Créer la permission
$permission = Permission::create(['name' => 'nouvelle_permission']);

// Ou util utiliser les constantes et mettre à jour la classe
// app/Constants/Permissions.php
```

### Assigner une permission à un rôle

```php
$role = Role::findByName('enseignant');
$role->givePermissionTo('edit_grades');
```

## Vérifications courantes

```php
$user = User::find(1);

// Vérifier un rôle
$user->hasRole('admin');
$user->hasRole(['admin', 'directeur']);

// Vérifier une permission
$user->hasPermissionTo('edit_students');
$user->can('edit_students');

// Via rôle
$user->roleIs('admin');

// Obtenir les rôles
$user->getRoleNames();

// Obtenir les permissions
$user->getAllPermissions();
```

## Middleware

### Protéger une route par rôle

```php
Route::group(['middleware' => 'role:admin'], function () {
    Route::get('/admin', 'AdminController@index');
});
```

### Protéger une route par permission

```php
Route::group(['middleware' => 'permission:edit_students'], function () {
    Route::put('/students/{id}', 'StudentController@update');
});
```

### Combinaison

```php
// L'utilisateur doit avoir TOUS les rôles et permissions
Route::group(['middleware' => 'role:admin|directeur;permission:edit_students,view_reports'], function () {
    // Routes
});
```

## Ressources

- [Documentation officielle](https://spatie.be/docs/laravel-permission/)
- [GitHub Repository](https://github.com/spatie/laravel-permission)
- [Changelog](https://github.com/spatie/laravel-permission/releases)

## Support

Si vous rencontrez des problèmes:

1. Consultez la [documentation officielle](https://spatie.be/docs/laravel-permission/)
2. Vérifiez les [issues GitHub](https://github.com/spatie/laravel-permission/issues)
3. Contactez: support@ecoliotogo.tg
