<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\ClassroomType;
use Inertia\Inertia;

class ClassroomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Classroom::with('type');

        if (request('search')) {
            $search = request('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        $classrooms = $query->orderBy('created_at', 'desc')->paginate(10);
        $activeCount = Classroom::where('active', true)->count();
        $classroomTypes = ClassroomType::select('id', 'name')->where('active', true)->get();

        return Inertia::render('Classrooms/Index', [
            'classrooms' => $classrooms,
            'activeCount' => $activeCount,
            'classroomTypes' => $classroomTypes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('classrooms.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClassroomRequest $request)
    {
        Classroom::create($request->validated());

        return redirect()->route('classrooms.index')
            ->with('message', 'Classe créée avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Classroom $classroom)
    {
        $classroom->load('type', 'students');

        return Inertia::render('Classrooms/Show', [
            'classroom' => $classroom,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Classroom $classroom)
    {
        return redirect()->route('classrooms.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClassroomRequest $request, Classroom $classroom)
    {
        $classroom->update($request->validated());

        return redirect()->route('classrooms.index')
            ->with('message', 'Classe mise à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Classroom $classroom)
    {
        $classroom->delete();

        return redirect()->route('classrooms.index')
            ->with('message', 'Classe supprimée avec succès.');
    }
}
