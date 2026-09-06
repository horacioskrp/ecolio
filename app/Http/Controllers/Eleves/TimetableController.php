<?php

namespace App\Http\Controllers\Eleves;
use App\Http\Controllers\Controller;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\School;
use App\Services\DocumentRenderer;
use App\Models\Subject;
use App\Models\TimetableSlot;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TimetableController extends Controller
{
    public function index(Request $request): Response
    {
        $classId = $request->string('class_id')->toString();

        $classrooms = Classroom::orderBy('name')->get(['id', 'name', 'code']);
        $subjects   = Subject::orderBy('name')->get(['id', 'name']);
        $teachers   = User::permission('create_marks')
            ->orderBy('lastname')->orderBy('firstname')
            ->get(['id', 'firstname', 'lastname'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        $slots = collect();
        if ($classId) {
            $slots = TimetableSlot::with(['subject:id,name', 'teacher:id,firstname,lastname'])
                ->where('class_id', $classId)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->map(fn ($s) => [
                    'id'           => $s->id,
                    'day_of_week'  => $s->day_of_week,
                    'start_time'   => substr($s->start_time, 0, 5),
                    'end_time'     => substr($s->end_time, 0, 5),
                    'subject_id'   => $s->subject_id,
                    'subject_name' => $s->subject?->name,
                    'teacher_id'   => $s->teacher_id,
                    'teacher_name' => $s->teacher?->name,
                    'room'         => $s->room,
                ]);
        }

        return Inertia::render('Eleves/Timetable/Index', [
            'classrooms' => $classrooms,
            'subjects'   => $subjects,
            'teachers'   => $teachers,
            'slots'      => $slots,
            'days'       => TimetableSlot::DAYS,
            'filters'    => ['class_id' => $classId],
            'canManage'  => $request->user()->can('create_timetable'),
        ]);
    }

    public function export(Request $request, string $classId)
    {
        $classroom = Classroom::findOrFail($classId);
        $school    = School::query()->first();

        $slots = TimetableSlot::with(['subject:id,name', 'teacher:id,firstname,lastname'])
            ->where('class_id', $classId)
            ->orderBy('start_time')
            ->get();

        // Lignes = plages horaires distinctes (triées), colonnes = jours
        $timeRanges = $slots
            ->map(fn ($s) => substr($s->start_time, 0, 5) . '-' . substr($s->end_time, 0, 5))
            ->unique()
            ->sort()
            ->values();

        // Index : [plage][jour] => slot
        $grid = [];
        foreach ($slots as $slot) {
            $range = substr($slot->start_time, 0, 5) . '-' . substr($slot->end_time, 0, 5);
            $grid[$range][$slot->day_of_week] = $slot;
        }

        $renderer = app(DocumentRenderer::class);

        $pdf = Pdf::loadView('exports.timetable', [
            'school'     => $school,
            'headerHtml' => $school ? $renderer->headerHtml($school, $renderer->resolveVariables($school)) : '',
            'headerCss'  => $renderer->headerCss(),
            'classroom'  => $classroom,
            'days'       => TimetableSlot::DAYS,
            'timeRanges' => $timeRanges,
            'grid'       => $grid,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('emploi-du-temps-' . Str::slug($classroom->name) . '.pdf');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('create_timetable'), 403);

        $data = $this->validateSlot($request);
        $data['school_id']        = School::query()->value('id');
        $data['academic_year_id'] = AcademicYear::where('active', true)->value('id');

        TimetableSlot::create($data);

        return back()->with('message', 'Créneau ajouté.');
    }

    public function update(Request $request, TimetableSlot $timetableSlot): RedirectResponse
    {
        abort_unless($request->user()->can('edit_timetable'), 403);

        $timetableSlot->update($this->validateSlot($request));

        return back()->with('message', 'Créneau mis à jour.');
    }

    public function destroy(Request $request, TimetableSlot $timetableSlot): RedirectResponse
    {
        abort_unless($request->user()->can('delete_timetable'), 403);

        $timetableSlot->delete();

        return back()->with('message', 'Créneau supprimé.');
    }

    private function validateSlot(Request $request): array
    {
        return $request->validate([
            'class_id'    => ['required', 'uuid', 'exists:classes,id'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:6'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'subject_id'  => ['nullable', 'uuid', 'exists:subjects,id'],
            'teacher_id'  => ['nullable', 'uuid', 'exists:users,id'],
            'room'        => ['nullable', 'string', 'max:50'],
        ], [
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début.',
        ]);
    }
}
