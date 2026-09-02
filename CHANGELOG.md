# Journal des versions

Toutes les évolutions notables de **Dalibi** sont consignées ici.
Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le versionnage
respecte [SemVer](https://semver.org/lang/fr/).

## [1.0.1] — 2026-09-02

### Corrigé
- **Couleur des catégories dans les graphiques** : la couleur suivait la *position*
  dans le tableau et non la catégorie. Lorsqu'une catégorie vide était filtrée, les
  suivantes changeaient de couleur — les filles pouvaient hériter du bleu des garçons,
  « Très bien » perdre son vert.

### Accessibilité
- **Palette revue et validée** (bande de luminosité, chroma, séparation en vision
  déficiente, contraste) dans les modes clair *et* sombre. L'ancien couple bleu/violet
  était indistinguable en deutéranopie.
- **Légendes ajoutées** aux camemberts qui n'en avaient pas : l'identité ne repose plus
  sur la seule couleur.
- Les répartitions à plus de trois catégories (modes de paiement, régions) passent en
  **barres étiquetées** ; les mentions, données ordonnées, en **rampe séquentielle**.
- **Mode sombre** : couleurs d'axes et de grille désormais adaptées au thème, au lieu de
  valeurs claires codées en dur (la grille était quasi invisible sur fond sombre).
- L'animation d'entrée des camemberts est retirée : elle laissait le graphique **vide**
  lorsque les images d'animation sont ralenties (onglet en arrière-plan, économie
  d'énergie, `prefers-reduced-motion`).
## [1.0.0] — 2026-09-02

Première version stable. Dalibi couvre le cycle complet de gestion d'un établissement
scolaire, de l'inscription de l'élève au bulletin, en passant par l'écolage et la paie.

### Scolarité
- **Élèves & inscriptions** : dossiers complets (état civil, parents, fiche médicale, pièces
  justificatives sur disque privé), import CSV en masse, inscriptions rattachées à l'année
  académique, effectifs et listes de classe, passage de classe en fin d'année, bourses.
- **Notes & bulletins** : saisie par matière et évaluation, calcul des moyennes paramétrable
  (trimestre ou semestre **par type de classe**), bulletins PDF fidèles avec modèle et
  en-tête configurables, réclamations de notes.
- **Examens** : modèles d'évaluation réutilisables, déploiement par classe, planning
  imprimable, examens officiels (CEPD, BEPC, BAC).
- **Présences** : appel par classe et par séance, statistiques d'assiduité, demandes de
  permission d'absence avec cycle de révision.
- **Emploi du temps** : grille hebdomadaire par classe, export PDF.

### Gestion
- **Comptabilité & écolage** : structures de frais, encaissements, reçus vérifiables par
  code-barres, situation par classe, dépenses, caisses multiples (dont Mobile Money).
- **Personnel & Paie** : fiches employés, grilles salariales et rubriques, cycles de paie
  mensuels avec bulletins PDF, CNSS et ITS calculés automatiquement, décaissement écrit
  en comptabilité.
- **Archives & documents** : modèles de documents avec en-tête glisser-déposer et filigrane,
  archivage avec tags et corbeille, fichiers sur disque privé.
- **Statistiques** : indicateurs alignés sur la carte scolaire (MEPSTA), exports PDF et Excel.

### Portail parents & élèves
- API REST (`/api/v1`) authentifiée par token, spécifiée en OpenAPI 3.1.
- Isolation stricte : un tuteur n'accède qu'à ses enfants, un élève qu'à ses propres données.
- Activable par établissement, accès délivré compte par compte.

### Sécurité
- Contrôle d'accès **piloté par les permissions** (~145), avec **une permission par verbe** :
  un droit de lecture ne permet jamais de créer, modifier ou supprimer.
- **Cloisonnement enseignant** : notes et appels limités aux classes affectées.
- **Intégrité financière** : gardes anti trop-perçu et transitions du cycle de paie évaluées
  sous verrou ; une inscription portant des paiements ne peut pas être supprimée.
- Protection des comptes à privilèges, 2FA, journal d'audit, fichiers sensibles hors du
  dossier public.
- Données de démonstration **interdites en production**.

### Exploitation
- **Sauvegardes** conçues pour la durée : écriture en flux, compression gzip, `pg_dump` natif,
  archives d'année scolaire verrouillées, empreinte SHA-256, sauvegarde optionnelle des médias,
  restauration sélective par table.
- Déploiement **Docker** (app / worker / scheduler), observabilité optionnelle (logs JSON,
  Sentry/GlitchTip).
- Intégration continue : tests, build et vérification des types à chaque contribution.

### Socle technique
Laravel 12 (PHP 8.3+), React 19 + TypeScript via Inertia.js, Tailwind CSS 4, PostgreSQL,
identifiants UUID. **502 tests** automatisés.

[1.0.1]: https://github.com/wearedalibi/dalibi/releases/tag/v1.0.1
[1.0.0]: https://github.com/wearedalibi/dalibi/releases/tag/v1.0.0
