<?php

namespace App\Http\Controllers;

use App\Events\GradePublished;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;

class GradingController extends Controller
{
    public function index(Request $request, Assignment $assignment)
    {
        $classroom = $assignment->topic->classroom;
        $this->authorizeTeacher($request, $classroom->teacher_id);

        $submissions = $assignment->submissions()
            ->with('student:id,name,candidate_number')
            ->orderByRaw("CASE status WHEN 'submitted' THEN 0 WHEN 'turned_in_late' THEN 1 ELSE 2 END")
            ->get();

        // Find first ungraded
        $current = $submissions->where('status', '!=', 'graded')->first()
            ?? $submissions->first();

        return view('teacher.grading.index', compact('assignment', 'classroom', 'submissions', 'current'));
    }

    public function show(Request $request, Submission $submission)
    {
        $assignment = $submission->assignment;
        $classroom = $assignment->topic->classroom;
        $this->authorizeTeacher($request, $classroom->teacher_id);

        $submissions = $assignment->submissions()
            ->with('student:id,name,candidate_number')
            ->orderByRaw("CASE status WHEN 'submitted' THEN 0 WHEN 'turned_in_late' THEN 1 ELSE 2 END")
            ->get();

        return view('teacher.grading.index', compact('assignment', 'classroom', 'submissions', 'submission'))->with('current', $submission);
    }

    public function update(Request $request, Submission $submission)
    {
        $assignment = $submission->assignment;
        $classroom = $assignment->topic->classroom;
        $this->authorizeTeacher($request, $classroom->teacher_id);

        $isIgcse = $assignment->grading_type === 'igcse_letter';

        $data = $request->validate([
            'grade_numeric' => $isIgcse ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0', 'max:' . $assignment->max_score],
            'grade_literal' => $isIgcse ? ['required', 'string', 'in:A*,A,B,C,D,E,F,G,U'] : ['nullable'],
            'feedback'      => ['nullable', 'string', 'max:2000'],
        ]);

        $submission->update([
            'grade_numeric' => $data['grade_numeric'] ?? null,
            'grade_literal' => $data['grade_literal'] ?? null,
            'feedback'      => $data['feedback'] ?? null,
            'status'        => 'graded',
            'graded_at'     => now(),
        ]);

        event(new GradePublished($submission));

        // Navigate to next ungraded
        $next = $assignment->submissions()
            ->where('id', '!=', $submission->id)
            ->where('status', '!=', 'graded')
            ->first();

        if ($next) {
            return redirect()->route('teacher.grading.show', $next)
                ->with('success', 'Grade saved.');
        }

        return redirect()->route('teacher.assignments.show', $assignment)
            ->with('success', 'All submissions graded!');
    }

    private function authorizeTeacher(Request $request, int $teacherId): void
    {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $user->id !== $teacherId) {
            abort(403);
        }
    }
}
