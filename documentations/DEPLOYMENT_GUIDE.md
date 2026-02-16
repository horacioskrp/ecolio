# Guide de Déploiement - Service de Matricule

## 🚀 Installation et Déploiement

### Étape 1: Préparer l'environnement

```bash
# Aller au répertoire du projet
cd c:\laragon\www\ecolio

# Vérifier que composer est à jour
composer update

# Vérifier les dépendances
composer install
```

### Étape 2: Configuration

1. **Mettre à jour `.env`** avec les paramètres matricule:

```env
# Ajouter à votre fichier .env
MATRICULE_SERVICE_ENABLED=true
MATRICULE_AUTO_GENERATE_USER=true
MATRICULE_AUTO_GENERATE_STUDENT=true
MATRICULE_COUNTRY_CODE=TG
```

Voir le fichier `ENV_CONFIGURATION.md` pour plus de détails.

### Étape 3: Migrations

```bash
# Exécuter les migrations
php artisan migrate

# Ou avec rollback avant (pour être sûr):
php artisan migrate:rollback
php artisan migrate
```

La migration suivante sera exécutée:

- `2026_02_16_150000_add_unique_indexes_to_matricules.php` - Ajoute les index uniques

### Étape 4: Seeders (optionnel)

Si c'est une nouvelle installation:

```bash
# Créer les rôles et permissions
php artisan db:seed --class=RolesAndPermissionsSeeder

# Créer les données de démonstration
php artisan db:seed --class=SchoolDemoSeeder
```

### Étape 5: Cache (production uniquement)

```bash
# Rebuilder le cache de configuration
php artisan config:cache

# Optionnel: Vider tout le cache
php artisan cache:clear
```

### Étape 6: Tests

```bash
# Exécuter tous les tests
php artisan test

# Ou juste les tests matricule
php artisan test tests/Unit/MatriculeServiceTest.php
php artisan test tests/Feature/MatriculeGenerationTest.php

# Avec rapport verbose
php artisan test --verbose
```

### Étape 7: Génération en masse (optionnel)

Si vous avez des utilisateurs/élèves existants sans matricule:

```bash
# Générer les matricules des utilisateurs
php artisan matricule:generate-user --all

# Générer les matricules des élèves ET numéros d'enregistrement
php artisan matricule:generate-student --all
```

## 📝 Commandes disponibles

### Pour les Utilisateurs

```bash
# Générer pour UN utilisateur spécifique
php artisan matricule:generate-user --user-id=1

# Générer pour TOUS les utilisateurs sans matricule
php artisan matricule:generate-user --all

# Forcer la régénération d'un matricule existant
php artisan matricule:generate-user --user-id=1 --force

# Voir l'aide
php artisan matricule:generate-user --help
```

### Pour les Élèves

```bash
# Générer pour TOUS les élèves
php artisan matricule:generate-student --all

# Générer pour une CLASSE spécifique
php artisan matricule:generate-student --class-id=1

# Forcer la régénération
php artisan matricule:generate-student --all --force

# Voir l'aide
php artisan matricule:generate-student --help
```

## 🧪 Vérification post-installation

### Via Artisan Tinker

```bash
# Ouvrir tinker
php artisan tinker

# Tester le service
>>> $service = app(\App\Services\MatriculeService::class);
>>> $service->generateUserMatricule('enseignant');
=> "PROF26001"

# Tester avec un utilisateur réel
>>> $user = App\Models\User::first();
>>> $user->natricule
=> "ADM26001" (ou null si non encore généré)

# Générer un matricule
>>> $user->generateMatricule();
>>> $user->save();
>>> $user->refresh()->natricule
=> "ADM26001"

# Tester le parsing
>>> $service->parseMatricule('PROF26001');
=> [
     "prefix" => "PROF",
     "year" => "26",
     "sequence" => 1,
   ]

# Exit tinker
>>> exit
```

### Via Test Unitaire

```bash
# Exécuter un test spécifique
php artisan test --filter=MatriculeServiceTest::it_generates_user_matricule_with_correct_format

# Ou tous les tests matricule
php artisan test tests/Unit/MatriculeServiceTest.php --verbose
```

## ✅ Checklist de déploiement

- [ ] `.env` mis à jour avec paramètres matricule
- [ ] Migrations exécutées (`php artisan migrate`)
- [ ] Rôles et permissions créés (`php artisan db:seed`)
- [ ] Tests passants (`php artisan test`)
- [ ] Matricules générés pour les utilisateurs existants
- [ ] Configuration en cache (`php artisan config:cache` pour production)
- [ ] Logs vérifiés pour les erreurs

## 📊 Résultats attendus après déploiement

### Utilisateurs créés automatiquement

```
User 1 (Administrateur): natricule = ADM26001
User 2 (Directeur): natricule = DIR26001
User 3 (Enseignant): natricule = PROF26001
User 4 (Comptable): natricule = COMPT26001
User 5 (Secrétaire): natricule = SEC26001
```

### Élèves créés automatiquement

```
Student 1: registration_number = REG-TG-2026-001
Student 2: registration_number = REG-TG-2026-002
...
```

## 🔄 Rollback (en cas de problème)

```bash
# Revenir à la dernière migration
php artisan migrate:rollback

# Revenir à un point spécifique
php artisan migrate:rollback --step=1

# Redémarrer complètement
php artisan migrate:reset
php artisan migrate
```

## 🐛 Dépannage

### Problème: "Matricule service disabled"

**Cause**: `MATRICULE_SERVICE_ENABLED=false` dans `.env`

**Solution**:

```env
MATRICULE_SERVICE_ENABLED=true
```

### Problème: Matricules non générés automatiquement

**Cause**: Auto-génération désactivée

**Solution**:

```env
MATRICULE_AUTO_GENERATE_USER=true
MATRICULE_AUTO_GENERATE_STUDENT=true
```

### Problème: Index unique violation

**Cause**: Matricule en double en base de données

**Solution**:

```bash
# Identifier les doublons
php artisan tinker
>>> DB::table('users')->where('natricule', '!=', null)->groupBy('natricule')
    ->havingRaw('count(*) > 1')->pluck('natricule');

# Supprimer les doublons ou régénérer
php artisan matricule:generate-user --all --force
```

### Problème: Cache stale

**Cause**: Configuration en cache

**Solution**:

```bash
php artisan config:clear
php artisan config:cache
```

## 📈 Performance en production

### Recommandations

1. **Cache Redis** pour les séquences (haute concurrence):

```env
MATRICULE_CACHE_DRIVER=redis
```

2. **Queue** pour les générations en masse:

```bash
php artisan queue:work
```

3. **Indexes** sur les colonnes:

```sql
CREATE UNIQUE INDEX idx_natricule ON users(natricule);
CREATE UNIQUE INDEX idx_registration_number ON students(registration_number);
```

L'index est créé automatiquement par la migration.

## 📞 Support

En cas de problème:

1. Vérifier les logs:

```bash
tail -f storage/logs/laravel.log
```

2. Consulter la documentation:

- `MATRICULE_SERVICE.md` - Guide d'utilisation
- `MATRICULE_README.md` - Documentation complète
- `IMPLEMENTATION_SUMMARY.md` - Résumé technique
- `ENV_CONFIGURATION.md` - Configuration

3. Contacter le support:

- Email: support@ecoliotogo.tg
- Documentation en ligne: [lien à définir]

## 🎉 Prochaines étapes

Après le déploiement du service de matricule:

1. **Créer les contrôleurs API** pour CRUD des utilisateurs/élèves
2. **Intégrer avec les notifications** - Envoyer matricules par email
3. **Ajouter les QR codes** - Générer codes QR des matricules
4. **Implémenter le reporting** - Rapports avec matricules
5. **Audit trail** - Logger toutes les générations/modifications

Voir `IMPLEMENTATION_SUMMARY.md` pour la roadmap complète.
