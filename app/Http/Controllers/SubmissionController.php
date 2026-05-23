<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SubmissionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'assignment_id' => ['required', 'exists:assignments,id'],
            'submitted_text' => ['nullable', 'string'],
            'file'          => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,png,jpg,jpeg'],
        ]);

        $assignment = Assignment::findOrFail($data['assignment_id']);
        $user = $request->user();

        // Ensure student is enrolled
        $classroom = $assignment->topic->classroom;
        if (! $classroom->students()->where('student_id', $user->id)->exists()) {
            abort(403);
        }

        // Duplicate submission guard
        if ($assignment->submissions()->where('student_id', $user->id)->exists()) {
            return back()->with('error', 'You have already submitted this assignment.');
        }

        // Late blocking
        $isLate = $assignment->due_date && $assignment->due_date->isPast();
        if ($isLate && ! $assignment->allow_late_submissions) {
            abort(422, 'Late submissions are not allowed for this assignment.');
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('submissions/' . $assignment->id, 'private');
        }

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'student_id'    => $user->id,
            'submitted_text' => $data['submitted_text'] ?? null,
            'file_path'     => $filePath,
            'status'        => $isLate ? 'turned_in_late' : 'submitted',
            'submitted_at'  => now(),
        ]);

        return redirect()->route('student.assignments.show', $assignment)
            ->with('success', 'Assignment submitted successfully!');
    }
}
