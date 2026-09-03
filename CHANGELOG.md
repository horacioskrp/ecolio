# Journal des versions

Toutes les évolutions notables de **Dalibi** sont consignées ici.
Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et le versionnage
respecte [SemVer](https://semver.org/lang/fr/).

## [1.2.0] — 2026-09-03

Refonte du module **Statistiques** : chaque onglet gagne des graphiques plus lisibles,
accessibles (palette validée en vision déficiente, mode sombre), interactifs et, là où
c'est pertinent, ventilés par sexe et filtrables.

### Ajouté
- **Comparaisons** : cartes de variation vs année précédente, séparation en deux lectures
  claires — *Performance* (réussite, recouvrement) et *Déperdition* (redoublement,
  abandon) — l'année en cours n'étant pas tracée pour les taux de fin d'année encore
  indécis. Le jeu de démonstration sème deux années passées pour donner de vraies tendances.
- **Géographie** : treemap hiérarchique région → préfecture, une teinte par région
  (nuancée selon l'effectif) et une légende ; KPI de concentration (couverture, part du
  Grand Lomé, régions, préfectures).
- **Réussite & examens** : examens officiels (CEPD, BEPC, BAC) avec résultats
  admis / échoué / absent et taux d'admission ; **répartition des mentions** enfin
  alimentée (quatre paliers).
- **Effectifs & parité** : **pyramide des âges** divergente garçons / filles, **origine
  géographique** (top villes) ventilée par sexe, et **filtre par cycle** (Maternelle /
  Primaire / Collège / Lycée) avec tri sur le graphe des classes.
- **Statistiques élèves** (module Élèves) refondue : vrais graphiques thémés, mode sombre,
  donut de parité, histogramme d'âge, effectifs par classe filtrables et empilés par sexe,
  KPI d'âge moyen et de sur-âge.
- Âge attendu par classe renseigné, ce qui active le calcul du **sur-âge** (retard scolaire).

### Corrigé
- **Répartition des mentions vide** : les libellés (« Très bien », « Assez bien »…) sont
  désormais normalisés avant d'être agrégés sur les quatre paliers standard.
- **Lisibilité de la géographie** : couleurs par région (les faibles effectifs, en teinte
  unique quasi blanche, étaient invisibles) et libellés du treemap en graisse normale.

## [1.1.0] — 2026-09-03

### Ajouté
- **Jeu de démonstration complet** (`DemoSeeder`) : une année scolaire vivante sur toutes
  les classes actives (au moins 20 élèves par classe), avec dossiers d'élèves complets,
  programme et affectations des enseignants, évaluations notées et moyennes par matière,
  bulletins figés, facturation et paiements, présences, emploi du temps, calendrier,
  cycles de paie et dossiers courants (justificatifs, réclamations, primes, documents).
  Noms, prénoms et villes réels du Togo (divisions administratives officielles),
  recrutement pondéré autour de Lomé. Seeder idempotent, déterministe, et interdit en
  production. Lancement : `php artisan db:seed --class=DemoSeeder`.

### Corrigé
- **Bulletin — colonne Composition toujours vide** : la colonne de source « composition »
  était résolue sur une clé absente du bulletin figé (la valeur y est stockée sous
  « compo »). La colonne restait vide **même quand la note existait**, quelle que soit la
  période ou le barème. Correctif d'affichage : aucun bulletin à régénérer.
- **Bulletin — logo de l'en-tête surdimensionné** : les styles de l'en-tête ministériel
  (dont le plafond de taille du logo) n'étaient pas embarqués dans le CSS du bulletin ; le
  logo s'affichait à sa taille naturelle et écrasait l'en-tête.
- **Types d'évaluation — catégorie de la « Composition »** : lorsqu'elle avait dérivé vers
  « contrôle continu », la composition était diluée dans la note de classe et la
  pondération Classe/Compo du barème était ignorée. Rejouer `ReferenceDataSeeder` corrige
  la donnée, puis régénérer les bulletins concernés.

### Modifié
- **Bulletin — la colonne « Classe »** (contrôle continu) s'intitule désormais « Devoir »
  par défaut. Seul l'affichage change : la colonne agrège toujours interro + devoir et
  alimente la moyenne. Un libellé déjà personnalisé par une école n'est pas écrasé.

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
