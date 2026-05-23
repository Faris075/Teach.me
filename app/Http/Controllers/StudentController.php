<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $classrooms = $user->enrolledClassrooms()
            ->with('teacher:id,name')
            ->withCount('assignments')
            ->latest('classroom_student.joined_at')
            ->get();

        return view('student.dashboard', compact('classrooms'));
    }

    public function showClassroom(Request $request, \App\Models\Classroom $classroom)
    {
        $user = $request->user();

        if (! $classroom->students()->where('student_id', $user->id)->exists()) {
            abort(403);
        }

        $classroom->load(['topics.assignments' => fn($q) => $q->orderBy('due_date')]);

        return view('student.classroom', compact('classroom'));
    }

    public function showAssignment(Request $request, \App\Models\Assignment $assignment)
    {
        $user = $request->user();
        $classroom = $assignment->topic->classroom;

        if (! $classroom->students()->where('student_id', $user->id)->exists()) {
            abort(403);
        }

        $submission = $assignment->submissions()->where('student_id', $user->id)->first();

        return view('student.assignment', compact('assignment', 'submission', 'classroom'));
    }
}
