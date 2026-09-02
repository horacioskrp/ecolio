# Observabilité — Logs (Grafana/Loki) & capture d'erreurs (Sentry/GlitchTip)

Deux briques complémentaires, 100 % auto-hébergeables et open source. Aucune n'est
active par défaut : elles se configurent par variables d'environnement.

## A. Logs structurés → Loki / Grafana

En production (Docker/K8s), l'application journalise sur **stderr**. Il suffit de
passer au format **JSON** pour que les logs soient requêtables dans Loki/Grafana
(ou n'importe quel backend OpenTelemetry).

### Configuration (aucun changement de code)

```env
LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter
LOG_LEVEL=info
```

Le canal `stderr` (voir `config/logging.php`) applique alors `JsonFormatter` : chaque
ligne de log devient un objet JSON (`message`, `level_name`, `context`, `datetime`…).

### Collecte

L'image K8s écrit sur stdout/stderr : un agent scrape les logs des pods vers Loki.

- **Grafana Alloy** (recommandé) ou **Promtail** : `discovery.kubernetes` → `loki.write`.
- Dans Grafana : source de données **Loki**, puis exploration `{app="dalibi"} | json`.
- **Alertes** sur motif d'erreur, ex. :
  `count_over_time({app="dalibi"} | json | level_name="ERROR" [5m]) > 0`.

## B. Capture des exceptions → Sentry ou GlitchTip

Le SDK **`sentry/sentry-laravel`** est installé et branché dans le gestionnaire
d'exceptions (`bootstrap/app.php` → `Integration::handles($exceptions)`). Il est
**inactif tant que `SENTRY_LARAVEL_DSN` est vide** (no-op, y compris en tests).

> **GlitchTip** est un serveur open source **compatible avec le SDK Sentry** : il
> suffit de pointer le DSN vers votre instance GlitchTip auto-hébergée. Aucune
> différence côté application.

### Configuration

```env
# Sentry SaaS OU votre GlitchTip auto-hébergé
SENTRY_LARAVEL_DSN=https://xxxxx@votre-glitchtip.example/1
# Échantillonnage des traces de performance (0 = désactivé, 0.2 = 20 %)
SENTRY_TRACES_SAMPLE_RATE=0
# Ne pas envoyer d'informations personnelles (recommandé)
SENTRY_SEND_DEFAULT_PII=false
```

Réglages fins dans `config/sentry.php`. Après un déploiement avec cache de config :
`php artisan config:clear` (ou `config:cache`).

### Ce qui est capturé

- Toute exception non gérée (les 500), avec contexte requête/utilisateur (PII off).
- Regroupement, tendance et **alertes** côté Sentry/GlitchTip.

### Vérifier l'intégration

```bash
php artisan sentry:test   # envoie un événement de test au DSN configuré
```

## Recommandation de mise en place

1. **A** d'abord (logs JSON + Loki/Grafana) : visibilité immédiate, quasi zéro effort.
2. **B** ensuite (GlitchTip/Sentry) : vrai triage d'erreurs + alertes — c'est ce qui
   rend fiable le message de la page 500.
3. Plus tard : OpenTelemetry (traces + métriques) — l'export JSON/stderr est déjà
   compatible, il ne resterait qu'à ajouter l'auto-instrumentation.

> L'infrastructure (Loki, Grafana, Alloy, GlitchTip) se déploie hors application ;
> l'app est déjà prête à l'alimenter.
