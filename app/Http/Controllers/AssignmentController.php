<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Topic;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function create(Request $request, Classroom $classroom)
    {
        $this->authorizeTeacher($request, $classroom);
        $topic = $request->query('topic') ? Topic::findOrFail($request->query('topic')) : null;
        $topics = $classroom->topics()->orderBy('order')->get();
        return view('teacher.assignments.create', compact('classroom', 'topics', 'topic'));
    }

    public function store(Request $request, Classroom $classroom)
    {
        $this->authorizeTeacher($request, $classroom);

        $data = $request->validate([
            'topic_id'               => ['required', 'exists:topics,id'],
            'title'                  => ['required', 'string', 'max:255'],
            'description'            => ['nullable', 'string'],
            'due_date'               => ['nullable', 'date'],
            'max_score'              => ['nullable', 'numeric', 'min:0'],
            'grading_type'           => ['required', 'in:percentage,igcse_letter'],
            'allow_late_submissions' => ['boolean'],
        ]);

        $data['allow_late_submissions'] = $request->boolean('allow_late_submissions', true);

        Assignment::create($data);

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', 'Assignment created.');
    }

    public function show(Request $request, Assignment $assignment)
    {
        $classroom = $assignment->topic->classroom;
        $this->authorizeTeacher($request, $classroom);

        $submissions = $assignment->submissions()->with('student:id,name,candidate_number')->latest('submitted_at')->get();

        return view('teacher.assignments.show', compact('assignment', 'classroom', 'submissions'));
    }

    private function authorizeTeacher(Request $request, Classroom $classroom): void
    {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $user->id !== $classroom->teacher_id) {
            abort(403);
        }
    }
}
