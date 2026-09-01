<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
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

        $friendIds = $this->acceptedFriendIds($user->id);

        $pendingFriendRequests = Friendship::query()
            ->where('addressee_id', $user->id)
            ->where('status', 'pending')
            ->count();

        return view('student.dashboard', [
            'classrooms' => $classrooms,
            'friendsCount' => $friendIds->count(),
            'pendingFriendRequests' => $pendingFriendRequests,
        ]);
    }

    public function showClassroom(Request $request, \App\Models\Classroom $classroom)
    {
        $user = $request->user();

        if (! $classroom->students()->where('student_id', $user->id)->exists()) {
            abort(403);
        }

        $classroom->load(['topics.assignments' => fn($q) => $q->orderBy('due_date')]);

        $friendIds = $this->acceptedFriendIds($user->id);

        $inviteableFriends = User::query()
            ->whereIn('id', $friendIds)
            ->whereDoesntHave('enrolledClassrooms', function ($q) use ($classroom) {
                $q->where('classrooms.id', $classroom->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('student.classroom', compact('classroom', 'inviteableFriends'));
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

    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);
        $request->user()->unreadNotifications->markAsRead();
        return view('student.notifications', compact('notifications'));
    }

    public function markRead(Request $request, string $notificationId)
    {
        $notification = $request->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();
        return back();
    }

    private function acceptedFriendIds(int $userId)
    {
        return Friendship::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_id', $userId)->orWhere('addressee_id', $userId);
            })
            ->get()
            ->map(function (Friendship $friendship) use ($userId) {
                return $friendship->requester_id === $userId
                    ? (int) $friendship->addressee_id
                    : (int) $friendship->requester_id;
            })
            ->values();
    }
}
