<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BulletinTemplate;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\EmployeeProfile;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\EvaluationTemplate;
use App\Models\EvaluationType;
use App\Models\FeeCategorie;
use App\Models\FeeStructure;
use App\Models\GradingConfig;
use App\Models\Invoice;
use App\Models\PayRun;
use App\Models\PayrollSetting;
use App\Models\SalaryComponent;
use App\Models\SalaryGrade;
use App\Models\Scholarship;
use App\Models\School;
use App\Models\Student;
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
use Carbon\CarbonImmutable;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $this->step('Bourses', fn () => $this->seedScholarships());
        $this->step('Frais, factures et paiements', fn () => $this->seedFinance());
        $this->step('Présences', fn () => $this->seedAttendance());
        $this->step('Emploi du temps', fn () => $this->seedTimetable());
        $this->step('Personnel et paie', fn () => $this->seedPayroll());
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
            $firstname = $this->faker->firstName();
            $lastname  = Str::upper($this->faker->lastName());

            $user = User::create([
                'firstname'         => $firstname,
                'lastname'          => $lastname,
                'email'             => 'prof' . ($i + 1) . '@dalibi.tg',
                'gender'            => $this->faker->randomElement(['male', 'female']),
                'telephone'         => '+228 9' . $this->faker->numerify('# ## ## ##'),
                'address'           => $this->faker->streetAddress() . ', Lomé',
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

        $user = User::create([
            'firstname'         => $firstname,
            'lastname'          => $lastname,
            'email'             => 'eleve' . $seq . '@dalibi.tg',
            'gender'            => $gender,
            'birth_date'        => $this->today->subYears($age)->toDateString(),
            'telephone'         => '+228 9' . $this->faker->numerify('# ## ## ##'),
            'address'           => $this->faker->streetAddress() . ', Lomé',
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
            'place_of_birth' => $this->faker->randomElement(['Lomé', 'Kara', 'Sokodé', 'Kpalimé', 'Atakpamé', 'Dapaong', 'Tsévié', 'Aného']),
            'nationality'    => 'Togolaise',
            'address'        => $this->faker->streetAddress(),
            'city'           => 'Lomé',
            'region'         => 'Maritime',
            'prefecture'     => 'Golfe',
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
