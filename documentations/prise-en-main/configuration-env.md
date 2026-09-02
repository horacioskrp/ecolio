# Configuration pour le service de matricule

## À ajouter à votre fichier `.env`

```env
##########################################################
# MATRICULE SERVICE CONFIGURATION
##########################################################

# Activer/désactiver le service de matricule
MATRICULE_SERVICE_ENABLED=true

# Auto-génération des matricules utilisateurs
MATRICULE_AUTO_GENERATE_USER=true

# Auto-génération des matricules/numéros d'enregistrement élèves
MATRICULE_AUTO_GENERATE_STUDENT=true

# Code pays pour les numéros d'enregistrement
MATRICULE_COUNTRY_CODE=TG

# URL pour les services matricule (si micro-service)
# MATRICULE_SERVICE_URL=http://localhost:8000/api/matricule

# Clé API pour les services matricule (si micro-service)
# MATRICULE_API_KEY=your-secret-key-here
```

## Configuration par environnement

### Development (.env.local)

```env
MATRICULE_SERVICE_ENABLED=true
MATRICULE_AUTO_GENERATE_USER=true
MATRICULE_AUTO_GENERATE_STUDENT=true
MATRICULE_COUNTRY_CODE=TG
```

### Staging (.env.staging)

```env
MATRICULE_SERVICE_ENABLED=true
MATRICULE_AUTO_GENERATE_USER=true
MATRICULE_AUTO_GENERATE_STUDENT=true
MATRICULE_COUNTRY_CODE=TG
```

### Production (.env.production)

```env
MATRICULE_SERVICE_ENABLED=true
MATRICULE_AUTO_GENERATE_USER=true
MATRICULE_AUTO_GENERATE_STUDENT=true
MATRICULE_COUNTRY_CODE=TG
```

## Configuration via `config/matricule.php`

Le fichier de configuration principal est `config/matricule.php`.

### Paramètres clés

**Préfixes des rôles** (ne pas modifier sans raison):

```php
'role_prefixes' => [
    'administrateur' => 'ADM',      // 3 caractères
    'directeur' => 'DIR',            // 3 caractères
    'enseignant' => 'PROF',          // 4 caractères
    'comptabilité' => 'COMPT',       // 4 caractères
    'secrétariat' => 'SEC',          // 3 caractères
],
```

**Format des matricules**:

```php
'user' => [
    'prefix_length' => 3,        // 3-4 caractères
    'year_length' => 2,          // 26 pour 2026
    'sequence_length' => 3,      // 001, 002, etc.
    'auto_generate' => true,     // Auto-générer?
    'separator' => '',           // Aucun séparateur
],
```

**Numéros d'enregistrement**:

```php
'registration' => [
    'prefix' => 'REG',           // Préfixe
    'country_code' => 'TG',      // Code pays
    'year_length' => 4,          // 2026 (année complète)
    'sequence_length' => 3,      // 001
    'separator' => '-',          // REG-TG-2026-001
],
```

## Personnalisation avancée

### Modifier les préfixes de rôles

Si vous voulez modifier les préfixes (NON RECOMMANDÉ):

```php
// Dans config/matricule.php
'role_prefixes' => [
    'administrateur' => 'ADMIN',     // Exemple: 4 caractères
    'directeur' => 'DIRECTOR',       // Exemple: 8 caractères
    'enseignant' => 'PROF',
    'comptabilité' => 'ACC',
    'secrétariat' => 'SEC',
],
```

⚠️ **ATTENTION**: Changer les préfixes affectera toute la numérotation future!

### Ajouter un nouveau rôle

1. Ajouter le rôle dans `app/Constants/Roles.php`
2. Ajouter le préfixe dans `config/matricule.php`
3. Ajouter la case dans le switch de `MatriculeService::generateUserMatricule()`
4. Créer la migration appropriée

### Désactiver l'auto-génération

Pour un contrôle manuel complet:

```env
MATRICULE_AUTO_GENERATE_USER=false
MATRICULE_AUTO_GENERATE_STUDENT=false
```

Ensuite générer manuellement via:

```bash
php artisan matricule:generate-user --all
php artisan matricule:generate-student --all
```

## Validation de la configuration

Pour vérifier que votre configuration est correcte:

```php
// Dans artisan tinker
>>> config('matricule.enabled')
=> true

>>> config('matricule.role_prefixes')
=> [
     'administrateur' => 'ADM',
     'directeur' => 'DIR',
     ...
   ]

>>> app(\App\Services\MatriculeService::class)->getPrefixes()
=> [
     'administrateur' => 'ADM',
     ...
   ]
```

## Cache de configuration

Si vous modifiez `config/matricule.php`, mettez à jour le cache:

```bash
# Rebuilder le cache
php artisan config:cache

# Ou vider le cache
php artisan config:clear
```

## Variables d'environnement optionnelles

```env
# Pour un stockage Redis des séquences (performance en haute concurrence)
MATRICULE_CACHE_DRIVER=redis

# Pour les logs de génération
MATRICULE_LOG_CHANNEL=single

# Format de log personnalisé
MATRICULE_LOG_FORMAT=single  # ou stack, null, errorlog
```

## Notes de sécurité

1. ⚠️ Ne pas partager les fichiers de configuration avec les matricules dans le git
2. ✅ Utiliser les variables d'environnement pour les données sensibles
3. ✅ Protéger l'accès aux commandes Artisan de génération
4. ✅ Logger tous les accès aux matricules
5. ✅ Implémenter l'authentification/autorisation pour l'API matricule

## Dépannage de configuration

### Erreur: "Matricule service is disabled"

```
Solution: Vérifier MATRICULE_SERVICE_ENABLED=true dans .env
```

### Erreur: "Role not found"

```
Solution: Vérifier que le rôle existe dans config/matricule.php
```

### Matricules non générés automatiquement

```
Solution: Vérifier MATRICULE_AUTO_GENERATE_USER=true et MATRICULE_AUTO_GENERATE_STUDENT=true
```

### Cache stale

```
Solution: Exécuter php artisan config:clear
```
