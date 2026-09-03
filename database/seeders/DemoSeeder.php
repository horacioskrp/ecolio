<?php

namespace Database\Seeders;

use App\Models\AbsencePermission;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ArchivedDocument;
use App\Models\Attendance;
use App\Models\BulletinTemplate;
use App\Models\CalendarEvent;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\DocumentIssuance;
use App\Models\DocumentTag;
use App\Models\DocumentTemplate;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeProfile;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationTemplate;
use App\Models\EvaluationType;
use App\Models\FeeCategorie;
use App\Models\FeeStructure;
use App\Models\GradingConfig;
use App\Models\Invoice;
use App\Models\NoteReclamation;
use App\Models\OfficialExam;
use App\Models\OfficialExamRegistration;
use App\Models\PayRun;
use App\Models\PayrollSetting;
use App\Models\SalaryComponent;
use App\Models\SalaryGrade;
use App\Models\Scholarship;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentInformation;
use App\Models\StudentMedicalInfo;
use App\Models\StudentParent;
use App\Models\StudentScholarship;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PayrollService;
use App\Services\ReportCardBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Jeu de démonstration complet — DÉVELOPPEMENT UNIQUEMENT.
 *
 * Les seeders de référence installent le squelette (classes, matières, barèmes) ;
 * celui-ci le remplit d'une année scolaire vivante : élèves inscrits, programme,
 * évaluations notées, bulletins, factures et paiements, présences, emploi du temps
 * et cycles de paie. Objectif : ouvrir n'importe quel écran de Dalibi et y trouver
 * des données cohérentes — moyennes plausibles, soldes qui s'équilibrent,
 * statistiques et graphiques réellement alimentés.
 *
 * Idempotent : chaque étape complète ce qui manque au lieu de dupliquer.
 * Déterministe : la graine est fixe, deux exécutions donnent la même école.
 */
class DemoSeeder extends Seeder
{
    /** Graine fixe : une démo reproductible d'une machine à l'autre. */
    private const SEED = 20260903;

    /** Classes retenues et effectif visé (un niveau par cycle). */
    private const CLASS_SIZES = [
        'CP1'   => 28,
        'CE2'   => 30,
        'CM2'   => 32,
        '6ème'  => 35,
        '3ème'  => 30,
        'Tle D' => 25,
    ];

    /** Programme par cycle : code matière => coefficient. */
    private const CURRICULUM = [
        'primaire' => [
            'FRAN' => 4, 'MATH' => 4, 'EST' => 2, 'HG' => 2,
            'ECM' => 1, 'ANG' => 2, 'EPS' => 1, 'AC' => 1,
        ],
        'college' => [
            'FRAN' => 4, 'MATH' => 4, 'ANG' => 3, 'SVT' => 2,
            'SP' => 2, 'HG' => 3, 'ECM' => 1, 'ALL' => 2, 'EPS' => 1,
        ],
        'lycee' => [
            'MATH' => 5, 'SP' => 5, 'SVT' => 5, 'FRAN' => 2,
            'ANG' => 2, 'HG' => 2, 'PHILO' => 2, 'EPS' => 1,
        ],
    ];

    /** Épreuves d'une période : type de référence et coefficient. */
    private const EXAMS = [
        ['type' => 'Interrogation', 'coefficient' => 1],
        ['type' => 'Devoir',        'coefficient' => 2],
        ['type' => 'Composition',   'coefficient' => 3],
    ];

    private Generator $faker;

    private School $school;

    private AcademicYear $year;

    private CarbonImmutable $today;

    /** @var Collection<int, AcademicPeriod> */
    private Collection $periods;

    /** @var Collection<int, Classroom> */
    private Collection $classes;

    /** @var array<int, string> */
    private array $teacherIds = [];

    /** @var array<string, array<string, list<string>>>|null */
    private ?array $localites = null;

    /**
     * Poids de recrutement hors Grand Lomé, par région.
     * Le Maritime domine, puis les régions par éloignement croissant : c'est le
     * sens réel des migrations internes vers la capitale.
     */
    private const REGION_WEIGHTS = [
        'Maritime' => 40,
        'Plateaux' => 25,
        'Centrale' => 15,
        'Kara'     => 12,
        'Savanes'  => 8,
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoSeeder ignoré : données de démonstration interdites en production.');

            return;
        }

        $this->faker = Faker::create('fr_FR');
        $this->faker->seed(self::SEED);
        mt_srand(self::SEED);

        $this->today = CarbonImmutable::now();

        if (! $this->resolveContext()) {
            return;
        }

        $this->step('Barème et modèle de bulletin', fn () => $this->seedGradingConfig());
        $this->step('Enseignants', fn () => $this->seedTeachers());
        $this->step('Élèves et inscriptions', fn () => $this->seedStudentsAndEnrollments());
        $this->step('Programme et affectations', fn () => $this->seedCurriculum());
        $this->step('Évaluations', fn () => $this->seedEvaluations());
        $this->step('Notes', fn () => $this->seedMarks());
        $this->step('Moyennes par matière', fn () => $this->seedSubjectGrades());
        $this->step('Bourses', fn () => $this->seedScholarships());
        $this->step('Frais, factures et paiements', fn () => $this->seedFinance());
        $this->step('Présences', fn () => $this->seedAttendance());
        $this->step('Emploi du temps', fn () => $this->seedTimetable());
        $this->step('Calendrier scolaire', fn () => $this->seedCalendar());
        $this->step('Personnel et paie', fn () => $this->seedPayroll());
        $this->step('Dossiers courants', fn () => $this->seedCasework());
        $this->step('Bulletins', fn () => $this->seedReportCards());

        $this->command?->newLine();
        $this->command?->info('Jeu de démonstration prêt.');
    }

    /* ------------------------------------------------------------------ */
    /* Contexte                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * École, année active, périodes et classes de la démo : sans ce socle
     * (posé par les seeders de référence) rien ne peut être rattaché.
     */
    private function resolveContext(): bool
    {
        $school = School::query()->first();
        $year   = AcademicYear::query()->where('active', true)->first()
            ?? AcademicYear::query()->latest('start_date')->first();

        if (! $school || ! $year) {
            $this->command?->error('DemoSeeder : école ou année académique manquante. Lancez db:seed au préalable.');

            return false;
        }

        $this->school = $school;
        $this->year   = $year;

        $this->periods = AcademicPeriod::query()
            ->where('academic_year_id', $year->id)
            ->orderBy('start_date')
            ->get();

        if ($this->periods->isEmpty()) {
            $this->command?->error('DemoSeeder : aucune période académique pour l\'année active.');

            return false;
        }

        $this->classes = Classroom::query()
            ->with('type')
            ->whereIn('code', array_keys(self::CLASS_SIZES))
            ->get();

        if ($this->classes->isEmpty()) {
            $this->command?->error('DemoSeeder : les classes attendues sont absentes (ClassroomSeeder).');

            return false;
        }

        return true;
    }

    /** Cycle d'une classe, tel que référencé par self::CURRICULUM. */
    private function cycleOf(Classroom $class): string
    {
        $type = Str::lower($class->type?->name ?? '');

        return match (true) {
            str_contains($type, 'primaire'), str_contains($type, 'maternelle') => 'primaire',
            str_contains($type, 'collège')                                     => 'college',
            default                                                            => 'lycee',
        };
    }

    private function step(string $label, callable $work): void
    {
        $this->command?->line("  <fg=gray>-</> {$label}...");
        $work();
    }

    /* ------------------------------------------------------------------ */
    /* Géographie togolaise                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Divisions administratives réelles du Togo, extraites de Togonou :
     * 5 régions, 40 préfectures et un échantillon de localités par préfecture.
     *
     * @return array<string, array<string, list<string>>>
     */
    private function localites(): array
    {
        if ($this->localites !== null) {
            return $this->localites;
        }

        $path = database_path('data/togo-localites.json');

        if (! is_file($path)) {
            $this->command?->warn('togo-localites.json introuvable : les élèves seront créés sans origine géographique.');

            return $this->localites = [];
        }

        return $this->localites = json_decode(file_get_contents($path), true)['regions'] ?? [];
    }

    /**
     * Origine géographique d'un élève.
     *
     * L'établissement est à Lomé, son recrutement l'est aussi : deux élèves sur
     * trois viennent du Grand Lomé (Golfe et Agoè-Nyivé), le reste du pays selon
     * des poids décroissants avec l'éloignement. Un tirage uniforme sur les 40
     * préfectures donnerait une école dont un quart des élèves vient des Savanes.
     *
     * @return array{region: string, prefecture: string, ville: string}|null
     */
    private function origin(): ?array
    {
        $regions = $this->localites();

        if ($regions === []) {
            return null;
        }

        if ($this->faker->boolean(65) && isset($regions['Maritime'])) {
            $region     = 'Maritime';
            $prefecture = $this->faker->randomElement(
                array_values(array_intersect(['Golfe', 'Agoè-Nyivé'], array_keys($regions['Maritime'])))
                    ?: array_keys($regions['Maritime'])
            );
        } else {
            $region     = $this->weighted(array_intersect_key(self::REGION_WEIGHTS, $regions));
            $prefecture = $this->faker->randomElement(array_keys($regions[$region]));
        }

        $villes = $regions[$region][$prefecture] ?? [];

        return [
            'region'     => $region,
            'prefecture' => $prefecture,
            'ville'      => $villes === [] ? $prefecture : $this->faker->randomElement($villes),
        ];
    }

    /**
     * Tirage pondéré.
     *
     * @param  array<string, int>  $weights
     */
    private function weighted(array $weights): string
    {
        $roll = $this->faker->numberBetween(1, max(array_sum($weights), 1));

        foreach ($weights as $key => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }

    /** Adresse à la togolaise : quartier et repère, plutôt qu'un numéro de rue. */
    private function address(string $ville): string
    {
        return 'Quartier ' . $ville . ', ' . $this->faker->randomElement([
            'face à l\'école primaire',
            'près du marché',
            'derrière la station',
            'non loin du dispensaire',
            'à côté de la pharmacie',
            'en face du terrain de football',
            'près du château d\'eau',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Socle pédagogique                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Barème de notation et modèle de bulletin.
     * Sans configuration active, moyennes et bulletins n'ont aucune règle de calcul.
     */
    private function seedGradingConfig(): void
    {
        GradingConfig::firstOrCreate(
            ['school_id' => $this->school->id, 'classroom_type_id' => null],
            [
                'name'              => 'Barème par défaut',
                'is_active'         => true,
                'passing_score'     => 10,
                'default_max_score' => 20,
                'class_weight'      => 1,
                'comp_weight'       => 1,
                'round_precision'   => 2,
                'mentions'          => GradingConfig::defaultMentions(),
            ],
        );

        $hasTemplate = BulletinTemplate::query()
            ->where('school_id', $this->school->id)
            ->whereNull('classroom_type_id')
            ->exists();

        if (! $hasTemplate) {
            $this->call(BulletinTemplateSeeder::class);
        }
    }

    /** Corps enseignant : comptes utilisateurs + rôle, réutilisés par les affectations. */
    private function seedTeachers(): void
    {
        $existing = User::query()->role('enseignant')->pluck('id')->all();

        for ($i = count($existing); $i < 14; $i++) {
            // Le sexe est tiré d'abord : le prénom en découle, sinon la liste des
            // enseignants affiche des « Pauline » déclarées masculines.
            $gender    = $this->faker->randomElement(['male', 'female']);
            $firstname = $this->faker->firstName($gender);
            $lastname  = Str::upper($this->faker->lastName());

            $user = User::create([
                'firstname'         => $firstname,
                'lastname'          => $lastname,
                'email'             => 'prof' . ($i + 1) . '@dalibi.tg',
                'gender'            => $gender,
                'telephone'         => '+228 9' . $this->faker->numerify('# ## ## ##'),
                'address'           => $this->address($this->origin()['ville'] ?? 'Lomé'),
                'password'          => Hash::make('password123'),
                'is_demo'           => true,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('enseignant');
            $existing[] = $user->id;
        }

        $this->teacherIds = $existing;
    }

    /* ------------------------------------------------------------------ */
    /* Élèves et inscriptions                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Complète l'effectif de chaque classe jusqu'à la cible.
     * Les élèves déjà présents mais non inscrits sont récupérés avant d'en créer
     * de nouveaux : une base à moitié semée ne produit pas de doublons.
     */
    private function seedStudentsAndEnrollments(): void
    {
        $enrolledBy = User::query()->role('administrateur')->value('id') ?? User::query()->value('id');
        $sequence   = Enrollment::query()->count();

        $orphans = Student::query()
            ->whereNotIn('id', Enrollment::query()->select('student_id'))
            ->get()
            ->all();

        foreach ($this->classes as $class) {
            $target  = self::CLASS_SIZES[$class->code] ?? 25;
            $current = Enrollment::query()
                ->where('class_id', $class->id)
                ->where('academic_year_id', $this->year->id)
                ->count();

            for ($i = $current; $i < $target; $i++) {
                $student = array_pop($orphans) ?? $this->createStudent($class);

                Enrollment::create([
                    'school_id'        => $this->school->id,
                    'student_id'       => $student->id,
                    'class_id'         => $class->id,
                    'academic_year_id' => $this->year->id,
                    'enrollment_code'  => 'INS-' . $this->year->year . '-' . str_pad((string) (++$sequence), 4, '0', STR_PAD_LEFT),
                    'enrolled_by'      => $enrolledBy,
                    'enrollment_date'  => $this->year->start_date,
                    'status'           => Enrollment::STATUS_ACTIVE,
                    'academic_status'  => 'en_cours',
                ]);
            }
        }
    }

    /** Élève complet : compte, dossier administratif, parents et fiche médicale. */
    private function createStudent(Classroom $class): Student
    {
        $gender    = $this->faker->randomElement(['male', 'female']);
        $firstname = $this->faker->firstName($gender === 'male' ? 'male' : 'female');
        $lastname  = Str::upper($this->faker->lastName());

        // L'âge suit le niveau : un CP1 de 17 ans décrédibiliserait toute la démo.
        $age  = match ($this->cycleOf($class)) {
            'primaire' => $this->faker->numberBetween(6, 11),
            'college'  => $this->faker->numberBetween(12, 15),
            default    => $this->faker->numberBetween(16, 19),
        };
        $seq = Student::query()->count() + 1;

        $origin = $this->origin();
        $ville  = $origin['ville'] ?? 'Lomé';

        // Beaucoup d'élèves scolarisés à Lomé sont nés à l'intérieur du pays :
        // un quart se voit attribuer une autre localité de naissance.
        $naissance = $this->faker->boolean(25) ? ($this->origin()['ville'] ?? $ville) : $ville;

        $user = User::create([
            'firstname'         => $firstname,
            'lastname'          => $lastname,
            'email'             => 'eleve' . $seq . '@dalibi.tg',
            'gender'            => $gender,
            'birth_date'        => $this->today->subYears($age)->toDateString(),
            'telephone'         => '+228 9' . $this->faker->numerify('# ## ## ##'),
            'address'           => $this->address($ville),
            'password'          => Hash::make('password123'),
            'is_demo'           => true,
            'email_verified_at' => now(),
        ]);

        $student = Student::create([
            'user_id'        => $user->id,
            'matricule'      => 'DEM' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'firstname'      => $firstname,
            'lastname'       => $lastname,
            'gender'         => $gender,
            'birth_date'     => $this->today->subYears($age)->toDateString(),
            'place_of_birth' => $naissance,
            'nationality'    => 'Togolaise',
            'address'        => $this->address($ville),
            'city'           => $ville,
            'region'         => $origin['region'] ?? null,
            'prefecture'     => $origin['prefecture'] ?? null,
            'phone'          => '+228 9' . $this->faker->numerify('# ## ## ##'),
            'email'          => 'eleve' . $seq . '@dalibi.tg',
            'active'         => true,
        ]);

        StudentInformation::create([
            'student_id'                    => $student->id,
            'birth_certificate_number'      => 'ACT-' . $this->faker->unique()->numberBetween(100000, 999999),
            'birth_certificate_issue_date'  => $this->today->subYears($age)->addMonths(2)->toDateString(),
            'birth_certificate_issue_place' => 'Lomé',
            'admission_type'                => $this->faker->randomElement(['new', 'transfer', 're_admission']),
        ]);

        StudentParent::create([
            'student_id'        => $student->id,
            'father_firstname'  => $this->faker->firstName('male'),
            'father_lastname'   => $lastname,
            'father_profession' => $this->faker->randomElement(['Enseignant', 'Commerçant', 'Agriculteur', 'Fonctionnaire', 'Chauffeur', 'Menuisier', 'Infirmier']),
            'father_phone'      => '+228 9' . $this->faker->numerify('# ## ## ##'),
            'mother_firstname'  => $this->faker->firstName('female'),
            'mother_lastname'   => Str::upper($this->faker->lastName()),
            'mother_profession' => $this->faker->randomElement(['Commerçante', 'Couturière', 'Enseignante', 'Infirmière', 'Coiffeuse', 'Secrétaire']),
            'mother_phone'      => '+228 9' . $this->faker->numerify('# ## ## ##'),
            'email'             => 'parent' . $seq . '@dalibi.tg',
        ]);

        StudentMedicalInfo::create([
            'student_id'              => $student->id,
            'blood_group'             => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'O+', 'O-']),
            'allergies'               => $this->faker->optional(0.2)->randomElement(['Arachides', 'Poussière', 'Pollen', 'Fruits de mer']),
            'vaccinations'            => 'DTC-Polio-Hib-HepB',
            'emergency_contact_name'  => $this->faker->name(),
            'emergency_contact_phone' => '+228 9' . $this->faker->numerify('# ## ## ##'),
        ]);

        return $student;
    }

    /* ------------------------------------------------------------------ */
    /* Programme                                                           */
    /* ------------------------------------------------------------------ */

    /** Matières de chaque classe + professeur titulaire par matière. */
    private function seedCurriculum(): void
    {
        $subjects = Subject::query()->get()->keyBy('code');
        $slot     = 0;

        foreach ($this->classes as $class) {
            foreach (self::CURRICULUM[$this->cycleOf($class)] as $code => $coefficient) {
                $subject = $subjects->get($code);

                if (! $subject) {
                    continue;
                }

                ClassSubject::firstOrCreate(
                    [
                        'class_id'         => $class->id,
                        'subject_id'       => $subject->id,
                        'academic_year_id' => $this->year->id,
                    ],
                    ['coefficient' => $coefficient, 'group' => 'obligatoire'],
                );

                SubjectAssignment::firstOrCreate(
                    [
                        'class_id'         => $class->id,
                        'subject_id'       => $subject->id,
                        'academic_year_id' => $this->year->id,
                    ],
                    [
                        'teacher_id' => $this->teacherIds[$slot++ % max(count($this->teacherIds), 1)],
                        'active'     => true,
                    ],
                );
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Évaluations et notes                                                */
    /* ------------------------------------------------------------------ */

    /**
     * Une interrogation, un devoir et une composition par matière et par période.
     * Les épreuves passées sont marquées « completed » ; la composition de la
     * période courante reste planifiée — une démo montre aussi du travail en cours.
     */
    private function seedEvaluations(): void
    {
        $types         = EvaluationType::query()->get()->keyBy('name');
        $classSubjects = ClassSubject::query()
            ->whereIn('class_id', $this->classes->pluck('id'))
            ->where('academic_year_id', $this->year->id)
            ->get();

        foreach ($this->periods as $period) {
            $start = CarbonImmutable::parse($period->start_date);
            $end   = CarbonImmutable::parse($period->end_date);

            // Contrôle continu réparti sur la partie écoulée de la période, composition
            // en fin de période : dans le trimestre en cours celle-ci reste donc à venir.
            $horizon = $end->min($this->today);
            $span    = max((int) $start->diffInDays($horizon), 1);

            foreach (self::EXAMS as $index => $exam) {
                $type = $types->get($exam['type']);

                if (! $type) {
                    continue;
                }

                $date = $index === 2
                    ? $end
                    : $start->addDays((int) round($span * ($index === 0 ? 0.3 : 0.7)));

                $template = EvaluationTemplate::firstOrCreate(
                    [
                        'academic_period_id'  => $period->id,
                        'evaluation_type_id'  => $type->id,
                        'name'                => $exam['type'] . ' — ' . $period->name,
                    ],
                    [
                        'coefficient' => $exam['coefficient'],
                        'max_score'   => 20,
                        'date'        => $date->toDateString(),
                    ],
                );

                $isPast = $date->lessThanOrEqualTo($this->today);

                foreach ($classSubjects as $classSubject) {
                    Evaluation::firstOrCreate(
                        [
                            'evaluation_template_id' => $template->id,
                            'class_subject_id'       => $classSubject->id,
                        ],
                        [
                            'date'   => $date->toDateString(),
                            'status' => $isPast ? 'completed' : 'scheduled',
                        ],
                    );
                }
            }
        }
    }

    /**
     * Notes des épreuves déjà passées.
     *
     * Chaque élève reçoit un niveau propre, stable d'une matière à l'autre : sans
     * cela le classement serait un tirage au sort et les bulletins n'auraient aucun
     * sens. Environ 3 % d'absences pour que la colonne « absent » ne soit pas vide.
     */
    private function seedMarks(): void
    {
        $enteredBy = User::query()->role('enseignant')->value('id') ?? User::query()->value('id');

        foreach ($this->classes as $class) {
            $studentIds = Enrollment::query()
                ->where('class_id', $class->id)
                ->where('academic_year_id', $this->year->id)
                ->active()
                ->pluck('student_id')
                ->all();

            if ($studentIds === []) {
                continue;
            }

            // Niveau propre à l'élève (moyenne visée), réparti autour de 11.5/20.
            $levels = [];
            foreach ($studentIds as $studentId) {
                $levels[$studentId] = min(18.5, max(4.0, 11.5 + $this->gaussian() * 3.0));
            }

            $evaluations = Evaluation::query()
                ->whereIn('class_subject_id', ClassSubject::query()
                    ->where('class_id', $class->id)
                    ->where('academic_year_id', $this->year->id)
                    ->select('id'))
                ->where('status', 'completed')
                ->pluck('id')
                ->all();

            foreach (array_chunk($evaluations, 20) as $chunk) {
                $rows  = [];
                $known = DB::table('marks')
                    ->whereIn('evaluation_id', $chunk)
                    ->get(['evaluation_id', 'student_id'])
                    ->map(fn ($r) => $r->evaluation_id . '|' . $r->student_id)
                    ->flip();

                foreach ($chunk as $evaluationId) {
                    foreach ($studentIds as $studentId) {
                        if ($known->has($evaluationId . '|' . $studentId)) {
                            continue;
                        }

                        $absent = $this->faker->boolean(3);
                        $score  = $absent ? null : round(min(20, max(0, $levels[$studentId] + $this->gaussian() * 2.2)), 2);

                        $rows[] = [
                            'id'            => (string) Str::uuid7(),
                            'evaluation_id' => $evaluationId,
                            'student_id'    => $studentId,
                            'score'         => $score,
                            'absent'        => $absent,
                            'created_by'    => $enteredBy,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ];
                    }
                }

                foreach (array_chunk($rows, 500) as $batch) {
                    DB::table('marks')->insert($batch);
                }
            }
        }
    }

    /** Tirage normal centré réduit (Box-Muller) : des notes qui se répartissent en cloche. */
    private function gaussian(): float
    {
        $u1 = max(mt_rand() / mt_getrandmax(), 1e-9);
        $u2 = mt_rand() / mt_getrandmax();

        return sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
    }

    /**
     * Moyenne consolidée par matière et par période, dérivée des notes saisies.
     *
     * L'écran « Notes » lit la table `grades`, distincte des notes d'épreuves :
     * sans cette consolidation il resterait vide alors que les bulletins, eux,
     * se calculent depuis les évaluations. La moyenne est pondérée par le
     * coefficient de l'épreuve, comme le fait la saisie manuelle.
     */
    private function seedSubjectGrades(): void
    {
        $computed = DB::table('marks as m')
            ->join('evaluations as e', 'e.id', '=', 'm.evaluation_id')
            ->join('evaluation_templates as et', 'et.id', '=', 'e.evaluation_template_id')
            ->whereNotNull('m.score')
            ->where('m.absent', false)
            ->groupBy('m.student_id', 'e.class_subject_id', 'et.academic_period_id')
            ->selectRaw('m.student_id, e.class_subject_id, et.academic_period_id,
                round(sum(m.score * et.coefficient) / nullif(sum(et.coefficient), 0), 2) as score')
            ->get();

        $known = DB::table('grades')
            ->get(['student_id', 'class_subject_id', 'academic_period_id'])
            ->map(fn ($g) => $g->student_id . '|' . $g->class_subject_id . '|' . $g->academic_period_id)
            ->flip();

        $rows = [];
        foreach ($computed as $row) {
            $key = $row->student_id . '|' . $row->class_subject_id . '|' . $row->academic_period_id;

            if ($known->has($key) || $row->score === null) {
                continue;
            }

            $rows[] = [
                'id'                 => (string) Str::uuid7(),
                'student_id'         => $row->student_id,
                'class_subject_id'   => $row->class_subject_id,
                'academic_period_id' => $row->academic_period_id,
                'score'              => $row->score,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        foreach (array_chunk($rows, 500) as $batch) {
            DB::table('grades')->insert($batch);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Dossiers courants                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Les demandes et pièces qui font la vie quotidienne d'un établissement :
     * justificatifs d'absence, réclamations de notes, inscriptions aux examens
     * officiels, primes du personnel et documents délivrés. Chacun de ces écrans
     * resterait vide sans quoi la démonstration s'arrêterait aux gros modules.
     */
    private function seedCasework(): void
    {
        $this->seedAbsencePermissions();
        $this->seedNoteReclamations();
        $this->seedExamRegistrations();
        $this->seedAllowances();
        $this->seedDocumentIssuances();
        $this->seedFiledDocuments();
    }

    /**
     * Archives et pièces du dossier élève.
     *
     * Ces deux modules servent de vrais fichiers en téléchargement : une ligne
     * sans fichier sur le disque produirait un 404 dès le premier clic. Chaque
     * entrée est donc accompagnée d'un PDF réellement écrit sur le disque
     * « secure », hors du dossier public comme en exploitation.
     */
    private function seedFiledDocuments(): void
    {
        $archivist = User::query()->role('administrateur')->value('id') ?? User::query()->value('id');
        $tags      = DocumentTag::query()->get();

        $dossiers = [
            ['category' => 'juridique',   'title' => 'Arrêté d\'ouverture de l\'établissement',   'tag' => 'Juridique'],
            ['category' => 'juridique',   'title' => 'Statuts de l\'établissement',               'tag' => 'Juridique'],
            ['category' => 'rh',          'title' => 'Convention collective du personnel',        'tag' => 'Ressources humaines'],
            ['category' => 'rh',          'title' => 'Règlement intérieur du personnel',          'tag' => 'Contrat'],
            ['category' => 'comptable',   'title' => 'Rapport financier ' . $this->year->year,    'tag' => 'Comptable'],
            ['category' => 'comptable',   'title' => 'Budget prévisionnel ' . $this->year->year,  'tag' => 'Comptable'],
            ['category' => 'pedagogique', 'title' => 'Programmes officiels — cycle primaire',     'tag' => 'Pédagogique'],
            ['category' => 'pedagogique', 'title' => 'Projet d\'établissement',                   'tag' => 'Pédagogique'],
            ['category' => 'courrier',    'title' => 'Correspondance — Inspection de Lomé Golfe', 'tag' => 'Courrier'],
            ['category' => 'administratif', 'title' => 'Rapport d\'inspection pédagogique',       'tag' => 'Inspection'],
        ];

        foreach ($dossiers as $index => $dossier) {
            if (ArchivedDocument::query()->where('title', $dossier['title'])->exists()) {
                continue;
            }

            $archivedAt = $this->today->subDays($this->faker->numberBetween(10, 300));
            $path       = 'archives/demo-' . Str::slug($dossier['title']) . '.pdf';
            $size       = $this->writePdf($path, $dossier['title'], $this->school->name);

            $document = ArchivedDocument::create([
                'reference'       => 'ARC-' . $archivedAt->format('Y') . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'title'           => $dossier['title'],
                'description'     => 'Document de référence classé aux archives de l\'établissement.',
                'category'        => $dossier['category'],
                'path'            => $path,
                'disk'            => 'secure',
                'original_name'   => Str::slug($dossier['title']) . '.pdf',
                'mime'            => 'application/pdf',
                'size'            => $size,
                'retention_until' => $archivedAt->addYears(10)->toDateString(),
                'archived_by'     => $archivist,
                'archived_at'     => $archivedAt,
            ]);

            if ($tag = $tags->firstWhere('name', $dossier['tag'])) {
                $document->tags()->sync([$tag->id]);
            }
        }

        if (StudentDocument::query()->exists()) {
            return;
        }

        $pieces  = ['Acte de naissance', 'Certificat de scolarité antérieure', 'Photo d\'identité', 'Carnet de vaccination'];
        $students = Student::query()
            ->whereIn('id', Enrollment::query()->where('academic_year_id', $this->year->id)->active()->select('student_id'))
            ->inRandomOrder()
            ->limit(15)
            ->get();

        foreach ($students as $student) {
            foreach ($this->faker->randomElements($pieces, $this->faker->numberBetween(1, 3)) as $piece) {
                $path = $student->storageFolder() . '/documents/' . Str::slug($piece) . '.pdf';
                $size = $this->writePdf($path, $piece, $student->lastname . ' ' . $student->firstname);

                StudentDocument::create([
                    'student_id'    => $student->id,
                    'name'          => $piece,
                    'path'          => $path,
                    'original_name' => Str::slug($piece) . '.pdf',
                    'mime'          => 'application/pdf',
                    'size'          => $size,
                    'uploaded_by'   => $archivist,
                ]);
            }
        }
    }

    /** Écrit un PDF de démonstration sur le disque « secure » et retourne sa taille. */
    private function writePdf(string $path, string $title, string $subtitle): int
    {
        // Police interne (Helvetica) plutôt que DejaVu Sans : cette dernière est
        // embarquée dans chaque fichier et ferait passer une page de 6 Ko à 850 Ko,
        // soit ~38 Mo de pièces de démonstration à sauvegarder pour rien.
        $html = '<html><head><meta charset="utf-8"></head><body style="font-family: helvetica, sans-serif; padding: 60px">'
            . '<p style="color:#6b7280; font-size:11px; letter-spacing:2px">DOCUMENT DE DÉMONSTRATION</p>'
            . '<h1 style="font-size:22px; margin:8px 0">' . e($title) . '</h1>'
            . '<p style="color:#374151">' . e($subtitle) . '</p>'
            . '<hr style="border:none; border-top:1px solid #e5e7eb; margin:24px 0">'
            . '<p style="color:#6b7280; font-size:12px">Contenu fictif généré par DemoSeeder. '
            . 'Ce fichier existe pour que le téléchargement fonctionne pendant la démonstration.</p>'
            . '</body></html>';

        $content = Pdf::loadHTML($html)->output();
        Storage::disk('secure')->put($path, $content);

        return strlen($content);
    }

    /** Justificatifs d'absence, répartis sur les trois états du circuit de validation. */
    private function seedAbsencePermissions(): void
    {
        if (AbsencePermission::query()->exists()) {
            return;
        }

        $reviewer = User::query()->role('directeur')->value('id') ?? User::query()->value('id');
        $parent   = User::query()->role('secrétariat')->value('id') ?? $reviewer;

        // On part des élèves réellement absents : un justificatif sans absence
        // correspondante serait incohérent au moindre recoupement.
        $absences = DB::table('attendance_records as ar')
            ->join('attendances as a', 'a.id', '=', 'ar.attendance_id')
            ->whereIn('ar.status', ['absent', 'excused'])
            ->inRandomOrder()
            ->limit(24)
            ->get(['ar.student_id', 'a.date']);

        foreach ($absences as $index => $absence) {
            $start  = CarbonImmutable::parse($absence->date);
            $status = match ($index % 5) {
                0, 1, 2 => 'approved',
                3       => 'pending',
                default => 'rejected',
            };

            $reason = $this->faker->randomElement(['medical', 'medical', 'familial', 'autre']);

            AbsencePermission::create([
                'student_id'     => $absence->student_id,
                'requested_by'   => $parent,
                'reviewed_by'    => $status === 'pending' ? null : $reviewer,
                'start_date'     => $start->toDateString(),
                'end_date'       => $start->addDays($this->faker->numberBetween(0, 3))->toDateString(),
                'reason'         => $reason,
                'description'    => match ($reason) {
                    'medical'  => 'Consultation médicale, certificat fourni.',
                    'familial' => 'Événement familial hors de Lomé.',
                    default    => 'Motif communiqué par la famille.',
                },
                'status'         => $status,
                'review_comment' => $status === 'rejected' ? 'Justificatif non transmis dans les délais.' : null,
                'reviewed_at'    => $status === 'pending' ? null : $start->addDays(2),
            ]);
        }
    }

    /** Réclamations de notes : quelques contestations, dont certaines déjà arbitrées. */
    private function seedNoteReclamations(): void
    {
        if (NoteReclamation::query()->exists()) {
            return;
        }

        $reviewer = User::query()->role('directeur')->value('id') ?? User::query()->value('id');

        $marks = DB::table('marks')
            ->whereNotNull('score')
            ->where('score', '<', 10)
            ->inRandomOrder()
            ->limit(12)
            ->get(['evaluation_id', 'student_id', 'score']);

        foreach ($marks as $index => $mark) {
            $status = match ($index % 4) {
                0, 1    => 'pending',
                2       => 'approved',
                default => 'rejected',
            };

            $requested = min(20, (float) $mark->score + $this->faker->randomFloat(1, 1, 4));

            NoteReclamation::create([
                'evaluation_id'   => $mark->evaluation_id,
                'student_id'      => $mark->student_id,
                'requested_by'    => $reviewer,
                'reason'          => $this->faker->randomElement([
                    'Une question corrigée comme fausse alors que la réponse est juste.',
                    'Total des points erroné sur la copie.',
                    'Une page de la copie n\'a pas été corrigée.',
                ]),
                'original_score'  => $mark->score,
                'requested_score' => $requested,
                'status'          => $status,
                'reviewed_by'     => $status === 'pending' ? null : $reviewer,
                'reviewed_at'     => $status === 'pending' ? null : now()->subDays($this->faker->numberBetween(1, 20)),
                'corrected_score' => $status === 'approved' ? $requested : null,
                'correction_note' => match ($status) {
                    'approved' => 'Erreur de report confirmée, note rectifiée.',
                    'rejected' => 'Correction vérifiée, barème correctement appliqué.',
                    default    => null,
                },
            ]);
        }
    }

    /** Inscriptions aux examens officiels enregistrés, pour la classe concernée. */
    private function seedExamRegistrations(): void
    {
        $exams = OfficialExam::query()->where('academic_year_id', $this->year->id)->get();

        foreach ($exams as $exam) {
            $students = Enrollment::query()
                ->where('academic_year_id', $this->year->id)
                ->when($exam->class_id, fn ($q) => $q->where('class_id', $exam->class_id))
                ->active()
                ->pluck('student_id');

            foreach ($students as $index => $studentId) {
                $exists = OfficialExamRegistration::query()
                    ->where('official_exam_id', $exam->id)
                    ->where('student_id', $studentId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                OfficialExamRegistration::create([
                    'official_exam_id'    => $exam->id,
                    'student_id'          => $studentId,
                    'registration_number' => Str::upper($exam->type) . '-' . $exam->year . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'serie'               => null,
                    'status'              => 'inscrit',
                ]);
            }
        }
    }

    /** Primes et retenues individuelles du personnel. */
    private function seedAllowances(): void
    {
        if (EmployeeAllowance::query()->exists()) {
            return;
        }

        $author    = User::query()->role('administrateur')->value('id') ?? User::query()->value('id');
        $employees = EmployeeProfile::query()->inRandomOrder()->limit(8)->get();

        $catalogue = [
            ['type' => 'earning',   'label' => 'Indemnité de logement',   'mode' => EmployeeAllowance::MODE_FIXED,        'amount' => 25000],
            ['type' => 'earning',   'label' => 'Prime de responsabilité', 'mode' => EmployeeAllowance::MODE_PERCENT_BASE, 'amount' => 10],
            ['type' => 'earning',   'label' => 'Heures supplémentaires',  'mode' => EmployeeAllowance::MODE_FIXED,        'amount' => 18000],
            ['type' => 'deduction', 'label' => 'Avance sur salaire',      'mode' => EmployeeAllowance::MODE_FIXED,        'amount' => 30000],
        ];

        foreach ($employees as $index => $employee) {
            $line = $catalogue[$index % count($catalogue)];

            EmployeeAllowance::create([
                'employee_profile_id' => $employee->id,
                'type'                => $line['type'],
                'label'               => $line['label'],
                'mode'                => $line['mode'],
                'amount'              => $line['amount'],
                'reason'              => $line['type'] === 'deduction' ? 'Remboursement échelonné sur trois mois.' : null,
                'starts_on'           => $this->today->startOfMonth()->subMonths(3)->toDateString(),
                'active'              => true,
                'created_by'          => $author,
            ]);
        }
    }

    /** Documents délivrés aux familles (certificats, attestations). */
    private function seedDocumentIssuances(): void
    {
        if (DocumentIssuance::query()->exists()) {
            return;
        }

        $templates = DocumentTemplate::query()->get();
        $issuedBy  = User::query()->role('secrétariat')->value('id') ?? User::query()->value('id');

        if ($templates->isEmpty()) {
            return;
        }

        $students = Student::query()
            ->whereIn('id', Enrollment::query()->where('academic_year_id', $this->year->id)->active()->select('student_id'))
            ->inRandomOrder()
            ->limit(20)
            ->get();

        foreach ($students as $index => $student) {
            $template = $templates[$index % $templates->count()];
            $issuedAt = $this->today->subDays($this->faker->numberBetween(1, 120));

            DocumentIssuance::create([
                'template_id'      => $template->id,
                'student_id'       => $student->id,
                'reference_number' => 'DOC-' . $issuedAt->format('Y') . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'issued_by'        => $issuedBy,
                'payload'          => [
                    'eleve'     => $student->lastname . ' ' . $student->firstname,
                    'matricule' => $student->matricule,
                ],
                'issued_at'        => $issuedAt,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Bourses et facturation                                              */
    /* ------------------------------------------------------------------ */

    /** Quelques boursiers : la remise alimente la ligne DISCOUNT des factures. */
    private function seedScholarships(): void
    {
        $scholarships = Scholarship::query()->pluck('id')->all();

        if ($scholarships === []) {
            return;
        }

        $candidates = Enrollment::query()
            ->where('academic_year_id', $this->year->id)
            ->active()
            ->inRandomOrder()
            ->limit(18)
            ->pluck('student_id');

        foreach ($candidates as $studentId) {
            StudentScholarship::firstOrCreate(
                [
                    'student_id'       => $studentId,
                    'academic_year_id' => $this->year->id,
                ],
                [
                    'scholarship_id' => $this->faker->randomElement($scholarships),
                    'start_date'     => $this->year->start_date,
                    'number_of_year' => 1,
                ],
            );
        }
    }

    /**
     * Barème de frais par classe, puis une facture par inscription.
     *
     * Le règlement passe par InvoiceService : reçus, écritures de caisse et statut
     * de facture suivent la même logique qu'une saisie réelle au guichet. La
     * répartition (soldé / partiel / impayé) donne des impayés à montrer.
     */
    private function seedFinance(): void
    {
        $this->seedFeeStructures();

        $invoices = app(InvoiceService::class);
        $cashier  = User::query()->role('comptabilité')->value('id') ?? User::query()->value('id');

        $enrollments = Enrollment::query()
            ->with('student')
            ->whereIn('class_id', $this->classes->pluck('id'))
            ->where('academic_year_id', $this->year->id)
            ->whereNotIn('id', Invoice::query()->select('enrollment_id'))
            ->get();

        foreach ($enrollments as $enrollment) {
            $invoice = $invoices->createFromEnrollment($enrollment);

            if ((float) $invoice->total <= 0) {
                continue;
            }

            // 55 % soldé, 30 % partiel, 15 % impayé : un recouvrement crédible.
            $draw  = $this->faker->numberBetween(1, 100);
            $share = match (true) {
                $draw <= 55 => 1.0,
                $draw <= 85 => $this->faker->randomFloat(2, 0.25, 0.8),
                default     => 0.0,
            };

            if ($share === 0.0) {
                continue;
            }

            $method = $this->faker->randomElement(['CASH', 'CASH', 'MOBILE_MONEY', 'MOBILE_MONEY', 'BANK_TRANSFER', 'CHEQUE']);
            $paidAt = CarbonImmutable::parse($this->year->start_date)
                ->addDays($this->faker->numberBetween(0, max(1, (int) CarbonImmutable::parse($this->year->start_date)->diffInDays($this->today))));

            $invoices->recordPayment($invoice, [
                'amount'         => round((float) $invoice->total * $share, 2),
                'payment_method' => $method,
                'paid_by'        => $enrollment->student?->lastname . ' ' . $enrollment->student?->firstname,
                'paid_at'        => $paidAt->toDateString(),
                'created_by'     => $cashier,
                'reference_number' => $method === 'CASH' ? null : Str::upper(Str::random(10)),
            ]);
        }
    }

    /** Frais par classe : inscription + écolage, montants croissants selon le cycle. */
    private function seedFeeStructures(): void
    {
        $categories = FeeCategorie::query()->get()->keyBy('name');

        foreach ($this->classes as $class) {
            $ecolage = match ($this->cycleOf($class)) {
                'primaire' => 90000,
                'college'  => 135000,
                default    => 180000,
            };

            $amounts = [
                'Inscription' => 15000,
                'Écolage'     => $ecolage,
                'Cantine'     => 45000,
            ];

            foreach ($amounts as $name => $amount) {
                $category = $categories->get($name);

                if (! $category) {
                    continue;
                }

                FeeStructure::firstOrCreate(
                    [
                        'academic_year_id' => $this->year->id,
                        'fee_category_id'  => $category->id,
                        'class_id'         => $class->id,
                    ],
                    ['amount' => $amount],
                );
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Présences                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Appel du matin sur les huit dernières semaines de cours.
     * Taux de présence ~92 %, avec retards et absences justifiées pour que les
     * statistiques d'assiduité et le bloc « absences » du bulletin soient parlants.
     */
    private function seedAttendance(): void
    {
        $period = $this->periods->firstWhere('is_current', true) ?? $this->periods->last();
        $author = User::query()->role('secrétariat')->value('id') ?? User::query()->value('id');

        $start = CarbonImmutable::parse($period->start_date)->max($this->today->subWeeks(8));
        $end   = $this->today->min(CarbonImmutable::parse($period->end_date));

        foreach ($this->classes as $class) {
            $studentIds = Enrollment::query()
                ->where('class_id', $class->id)
                ->where('academic_year_id', $this->year->id)
                ->active()
                ->pluck('student_id')
                ->all();

            if ($studentIds === []) {
                continue;
            }

            for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
                if ($day->isWeekend()) {
                    continue;
                }

                $attendance = Attendance::firstOrCreate(
                    [
                        'class_id'           => $class->id,
                        'academic_period_id' => $period->id,
                        'date'               => $day->toDateString(),
                        'session'            => 'matin',
                    ],
                    ['recorded_by' => $author],
                );

                if ($attendance->records()->exists()) {
                    continue;
                }

                $rows = [];
                foreach ($studentIds as $studentId) {
                    $draw = $this->faker->numberBetween(1, 100);
                    [$status, $late] = match (true) {
                        $draw <= 92 => ['present', null],
                        $draw <= 96 => ['late', $this->faker->numberBetween(5, 40)],
                        $draw <= 98 => ['excused', null],
                        default     => ['absent', null],
                    };

                    $rows[] = [
                        'id'            => (string) Str::uuid7(),
                        'attendance_id' => $attendance->id,
                        'student_id'    => $studentId,
                        'status'        => $status,
                        'minutes_late'  => $late,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }

                DB::table('attendance_records')->insert($rows);
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Emploi du temps                                                     */
    /* ------------------------------------------------------------------ */

    /** Grille hebdomadaire : les matières de la classe réparties du lundi au vendredi. */
    private function seedTimetable(): void
    {
        $slots = [['07:00', '09:00'], ['09:15', '11:15'], ['11:30', '12:30'], ['15:00', '17:00']];

        foreach ($this->classes as $class) {
            $assignments = SubjectAssignment::query()
                ->where('class_id', $class->id)
                ->where('academic_year_id', $this->year->id)
                ->get();

            if ($assignments->isEmpty()) {
                continue;
            }

            $cursor = 0;
            foreach (range(1, 5) as $day) {
                foreach ($slots as [$from, $to]) {
                    // Le mercredi après-midi reste libre, comme dans la plupart des établissements.
                    if ($day === 3 && $from === '15:00') {
                        continue;
                    }

                    $assignment = $assignments[$cursor++ % $assignments->count()];

                    TimetableSlot::firstOrCreate(
                        [
                            'class_id'    => $class->id,
                            'day_of_week' => $day,
                            'start_time'  => $from,
                        ],
                        [
                            'school_id'        => $this->school->id,
                            'academic_year_id' => $this->year->id,
                            'end_time'         => $to,
                            'subject_id'       => $assignment->subject_id,
                            'teacher_id'       => $assignment->teacher_id,
                            'room'             => 'Salle ' . $class->code,
                        ],
                    );
                }
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Calendrier scolaire                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Calendrier de l'année : jalons pédagogiques calés sur les périodes réelles
     * (compositions, conseils de classe, remise des bulletins, congés), fêtes
     * légales togolaises et sessions d'examens officiels déjà enregistrées.
     *
     * Les dates sont dérivées des périodes plutôt qu'écrites en dur : si l'année
     * de démonstration change, le calendrier suit au lieu de pointer à côté.
     */
    private function seedCalendar(): void
    {
        $author = User::query()->role('administrateur')->value('id') ?? User::query()->value('id');
        $first  = $this->periods->first();
        $last   = $this->periods->last();

        $events = [[
            'title'       => 'Rentrée scolaire ' . $this->year->year,
            'description' => 'Accueil des élèves et reprise des cours.',
            'type'        => 'event',
            'start_date'  => $first->start_date,
            'color'       => '#2a78d6',
        ], [
            'title'       => 'Réunion de rentrée des parents',
            'description' => 'Présentation de l\'équipe pédagogique et du règlement intérieur.',
            'type'        => 'meeting',
            'start_date'  => CarbonImmutable::parse($first->start_date)->addWeek(),
            'start_time'  => '15:00',
            'end_time'    => '17:00',
            'all_day'     => false,
        ], [
            'title'       => 'Journée portes ouvertes',
            'description' => 'Visite de l\'établissement et rencontre avec les enseignants.',
            'type'        => 'event',
            'start_date'  => CarbonImmutable::parse($first->start_date)->addMonths(2),
        ]];

        // Jalons de fin de période : composition, conseil, bulletins, congés.
        foreach ($this->periods as $period) {
            $end = CarbonImmutable::parse($period->end_date);

            $events[] = [
                'title'       => 'Compositions — ' . $period->name,
                'description' => 'Épreuves de synthèse de fin de période.',
                'type'        => 'exam',
                'start_date'  => $end->subWeek(),
                'end_date'    => $end->subDays(3),
                'color'       => '#eb6834',
            ];

            $events[] = [
                'title'       => 'Conseils de classe — ' . $period->name,
                'description' => 'Délibérations et appréciations par classe.',
                'type'        => 'meeting',
                'start_date'  => $end->addDays(3),
                'start_time'  => '08:00',
                'end_time'    => '13:00',
                'all_day'     => false,
            ];

            $events[] = [
                'title'       => 'Remise des bulletins — ' . $period->name,
                'description' => 'Réception des parents et remise des bulletins.',
                'type'        => 'meeting',
                'start_date'  => $end->addDays(7),
            ];

            $events[] = [
                'title'       => 'Congés de fin de ' . Str::lower($period->name),
                'type'        => 'holiday',
                'start_date'  => $end->addDays(8),
                'end_date'    => $end->addDays(20),
                'color'       => '#1baf7a',
            ];
        }

        foreach ($this->nationalHolidays($first->start_date, $last->end_date) as $date => $title) {
            $events[] = [
                'title'      => $title,
                'type'       => 'holiday',
                'start_date' => $date,
                'color'      => '#1baf7a',
            ];
        }

        // Sessions officielles déjà saisies dans le module Examens.
        foreach (DB::table('official_exams')->where('academic_year_id', $this->year->id)->get() as $exam) {
            $events[] = [
                'title'       => 'Examen officiel — ' . $exam->name . ' (session ' . $exam->session . ')',
                'description' => 'Centre : ' . $exam->center,
                'type'        => 'exam',
                'start_date'  => $exam->exam_date,
                'color'       => '#eb6834',
            ];
        }

        foreach ($events as $event) {
            CalendarEvent::firstOrCreate(
                [
                    // La date fait partie de la clé : une fête légale revient d'une
                    // année civile à l'autre sous le même intitulé.
                    'title'            => $event['title'],
                    'start_date'       => CarbonImmutable::parse($event['start_date'])->toDateString(),
                    'academic_year_id' => $this->year->id,
                ],
                [
                    'description' => $event['description'] ?? null,
                    'type'        => $event['type'],
                    'end_date'    => isset($event['end_date']) ? CarbonImmutable::parse($event['end_date'])->toDateString() : null,
                    'all_day'     => $event['all_day'] ?? true,
                    'start_time'  => $event['start_time'] ?? null,
                    'end_time'    => $event['end_time'] ?? null,
                    'color'       => $event['color'] ?? null,
                    'created_by'  => $author,
                ],
            );
        }
    }

    /**
     * Jours fériés togolais tombant dans l'intervalle donné.
     *
     * @return array<string, string> date ISO => libellé
     */
    private function nationalHolidays(string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from);
        $end   = CarbonImmutable::parse($to);

        $fixed = [
            '01-01' => 'Jour de l\'An',
            '01-13' => 'Fête de la Libération nationale',
            '04-27' => 'Fête de l\'Indépendance',
            '05-01' => 'Fête du Travail',
            '06-21' => 'Journée des Martyrs',
            '08-15' => 'Assomption',
            '11-01' => 'Toussaint',
            '12-25' => 'Noël',
        ];

        $holidays = [];

        foreach (range($start->year, $end->year) as $year) {
            foreach ($fixed as $dayMonth => $title) {
                $date = CarbonImmutable::parse($year . '-' . $dayMonth);

                if ($date->between($start, $end)) {
                    $holidays[$date->toDateString()] = $title;
                }
            }
        }

        return $holidays;
    }

    /* ------------------------------------------------------------------ */
    /* Personnel et paie                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Fiches employés pour tout le personnel, puis les cycles de paie des trois
     * derniers mois : le plus ancien payé, le précédent validé, le courant en
     * brouillon — les trois états du module se voient d'un coup d'œil.
     */
    private function seedPayroll(): void
    {
        $this->seedPayrollSettings();

        $grade = SalaryGrade::firstOrCreate(
            ['name' => 'Enseignant certifié'],
            ['category' => 'Enseignement', 'echelon' => '1', 'base_amount' => 145000, 'active' => true, 'sort_order' => 1],
        );

        SalaryComponent::firstOrCreate(
            ['name' => 'Indemnité de transport'],
            ['code' => 'TRANSP', 'type' => 'earning', 'default_amount' => 15000, 'is_default' => true, 'active' => true, 'sort_order' => 1],
        );

        SalaryComponent::firstOrCreate(
            ['name' => 'Prime de rendement'],
            ['code' => 'PRIME', 'type' => 'earning', 'default_amount' => 20000, 'is_default' => true, 'active' => true, 'sort_order' => 2],
        );

        // Le personnel, c'est-à-dire les comptes porteurs d'un rôle qui ne sont pas
        // le compte d'accès d'un élève.
        $staff = User::query()
            ->whereHas('roles')
            ->whereNotIn('id', Student::query()->whereNotNull('user_id')->select('user_id'))
            ->get();

        $number = EmployeeProfile::query()->count();

        foreach ($staff as $user) {
            if (EmployeeProfile::query()->where('user_id', $user->id)->exists()) {
                continue;
            }

            $isTeacher = $user->hasRole('enseignant');

            EmployeeProfile::create([
                'user_id'         => $user->id,
                'employee_number' => 'EMP' . str_pad((string) (++$number), 3, '0', STR_PAD_LEFT),
                'job_title'       => $isTeacher ? 'Enseignant' : ($user->getRoleNames()->first() ?? 'Personnel'),
                'department'      => $isTeacher ? 'Pédagogie' : 'Administration',
                'contract_type'   => $this->faker->randomElement(['CDI', 'CDI', 'CDD']),
                'hire_date'       => $this->today->subYears($this->faker->numberBetween(1, 12))->toDateString(),
                'base_salary'     => $isTeacher ? $this->faker->numberBetween(130, 190) * 1000 : $this->faker->numberBetween(150, 260) * 1000,
                'payment_method'  => $this->faker->randomElement(['MOBILE_MONEY', 'BANK_TRANSFER']),
                'momo_number'     => '+228 9' . $this->faker->numerify('# ## ## ##'),
                'cnss_number'     => 'CNSS' . $this->faker->numerify('#######'),
                'status'          => 'active',
                'salary_grade_id' => $isTeacher ? $grade->id : null,
            ]);
        }

        $payroll = app(PayrollService::class);
        $cash    = DB::table('cash_accounts')->where('type', 'BANK')->value('id');


        foreach ([3, 2, 1] as $offset) {
            $month = $this->today->subMonths($offset);

            $exists = PayRun::query()
                ->where('period_month', $month->month)
                ->where('period_year', $month->year)
                ->where('status', '!=', PayRun::CANCELLED)
                ->exists();

            if ($exists) {
                continue;
            }

            $run = $payroll->generate($month->month, $month->year, 'Paie ' . $month->translatedFormat('F Y'));

            if ($offset >= 2) {
                $payroll->validate($run);
            }

            if ($offset === 3 && $cash) {
                $payroll->pay($run->fresh(), $cash);
            }
        }
    }

    /**
     * Active les retenues légales togolaises : CNSS (4 % salarié / 17,5 % employeur)
     * et ITS par tranches. Sans elles, brut et net sont égaux sur tous les bulletins
     * de paie — le module s'afficherait, mais ne démontrerait rien.
     */
    private function seedPayrollSettings(): void
    {
        $settings = PayrollSetting::current();

        if ($settings->cnss_enabled && $settings->its_enabled) {
            return;
        }

        $settings->update([
            'cnss_enabled'       => true,
            'cnss_employee_rate' => 4,
            'cnss_employer_rate' => 17.5,
            'its_enabled'        => true,
            'its_brackets'       => [
                ['up_to' => 60000,  'rate' => 0.5],
                ['up_to' => 150000, 'rate' => 7],
                ['up_to' => 300000, 'rate' => 15],
                ['up_to' => 500000, 'rate' => 25],
                ['up_to' => 800000, 'rate' => 30],
                ['up_to' => null,   'rate' => 35],
            ],
        ]);

        SalaryComponent::firstOrCreate(
            ['name' => 'Avance sur salaire'],
            ['code' => 'AVANCE', 'type' => 'deduction', 'default_amount' => 0, 'is_default' => false, 'active' => true, 'sort_order' => 10],
        );
    }

    /* ------------------------------------------------------------------ */
    /* Bulletins                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Bulletins figés de la première période pour toutes les classes de la démo.
     * La période courante reste ouverte : on peut la générer en direct pendant
     * la démonstration sans écraser un résultat déjà validé.
     */
    private function seedReportCards(): void
    {
        $period = $this->periods->first();
        $author = User::query()->role('administrateur')->value('id') ?? User::query()->value('id');

        if (! $period || ! $author) {
            return;
        }

        $builder = app(ReportCardBuilder::class);

        foreach ($this->classes as $class) {
            $builder->build($class, $period, $this->year, null, false, $author);
        }
    }
}
