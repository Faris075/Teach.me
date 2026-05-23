<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function create(Request $request, Classroom $classroom)
    {
        $this->authorizeTeacher($request, $classroom);
        return view('teacher.topics.create', compact('classroom'));
    }

    public function store(Request $request, Classroom $classroom)
    {
        $this->authorizeTeacher($request, $classroom);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $data['order'] = $classroom->topics()->max('order') + 1;
        $classroom->topics()->create($data);

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', 'Topic created.');
    }

    public function update(Request $request, Topic $topic)
    {
        $this->authorizeTeacher($request, $topic->classroom);

        $data = $request->validate(['title' => ['required', 'string', 'max:255']]);
        $topic->update($data);

        return back()->with('success', 'Topic updated.');
    }

    public function destroy(Request $request, Topic $topic)
    {
        $this->authorizeTeacher($request, $topic->classroom);
        $classroom = $topic->classroom;
        $topic->deleteOrFail();

        return redirect()->route('teacher.classrooms.show', $classroom)
            ->with('success', 'Topic deleted.');
    }

    private function authorizeTeacher(Request $request, Classroom $classroom): void
    {
        $user = $request->user();
        if ($user->role !== 'super_admin' && $user->id !== $classroom->teacher_id) {
            abort(403);
        }
    }
}
