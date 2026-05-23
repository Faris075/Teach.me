<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class ClassroomController extends Controller
{
    // ── Teacher: list classrooms ───────────────────────────────────────────
    public function index(Request $request)
    {
        $classrooms = $request->user()->ownedClassrooms()
            ->withCount('students')
            ->latest()
            ->get();

        return view('teacher.classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        return view('teacher.classrooms.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $code = $this->generateUniqueCode();

        $classroom = $request->user()->ownedClassrooms()->create([
            'school_id'  => $request->user()->school_id,
            'name'       => $data['name'],
            'class_code' => $code,
            'status'     => 'active',
        ]);

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', 'Classroom created successfully.');
    }

    public function show(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroomAccess($request, $classroom);

        $inviteUrl = URL::signedRoute('classroom.join.show', ['class_code' => $classroom->class_code]);

        $classroom->load(['topics.materials', 'topics.assignments' => fn($q) => $q->latest()]);
        $students = $classroom->students()->orderBy('name')->get();

        return view('teacher.classrooms.show', compact('classroom', 'inviteUrl', 'students'));
    }

    public function edit(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroomAccess($request, $classroom);
        return view('teacher.classrooms.edit', compact('classroom'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroomAccess($request, $classroom);

        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $classroom->update($data);

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', 'Classroom updated.');
    }

    public function archive(Request $request, Classroom $classroom)
    {
        $this->authorizeClassroomAccess($request, $classroom);
        $classroom->update(['status' => 'archived']);
        return back()->with('success', 'Classroom archived.');
    }

    public function destroy(Request $request, Classroom $classroom)
    {
        $this->authorize('delete', $classroom);
        $classroom->deleteOrFail();
        return redirect()->route('teacher.classrooms.index')
            ->with('success', 'Classroom deleted.');
    }

    // ── Student: show join form ────────────────────────────────────────────
    public function joinShow(Request $request, string $class_code = '')
    {
        // Validate signed URL if signature present, otherwise just show form
        if ($class_code && $request->hasValidSignature()) {
            $classroom = Classroom::query()
                ->where('class_code', '=', strtoupper($class_code))
                ->where('status', '=', 'active')
                ->firstOrFail();
        } else {
            $classroom = null;
        }

        return view('auth.join', compact('classroom', 'class_code'));
    }

    // ── Student: process join ─────────────────────────────────────────────
    public function join(Request $request)
    {
        $data = $request->validate([
            'class_code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $code = strtoupper($data['class_code']);

        // Cross-tenant: scope to user's school (or return 404 if none found)
        $query = Classroom::query()->where('class_code', '=', $code)->where('status', '=', 'active');

        if ($user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $classroom = $query->first();

        if (! $classroom) {
            abort(404, 'Classroom not found.');
        }

        // Duplicate enrolment guard
        if ($classroom->students()->where('student_id', $user->id)->exists()) {
            return redirect()->route('student.classrooms.show', $classroom)
                ->with('success', 'You are already enrolled in this classroom.');
        }

        $classroom->students()->attach($user->id, ['joined_at' => now()]);

        return redirect()->route('student.classrooms.show', $classroom)
            ->with('success', 'You have joined ' . $classroom->name . '!');
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    private function generateUniqueCode(): string
    {
        $attempts = 0;
        do {
            $code = Str::upper(Str::random(6));
            $exists = Classroom::query()->where('class_code', '=', $code)->exists();
            $attempts++;
        } while ($exists && $attempts < 5);

        if ($exists) {
            abort(500, 'Could not generate a unique class code. Please try again.');
        }

        return $code;
    }

    private function authorizeClassroomAccess(Request $request, Classroom $classroom): void
    {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $user->id !== $classroom->teacher_id) {
            abort(403);
        }
    }
}
