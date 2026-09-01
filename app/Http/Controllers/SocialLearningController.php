<?php

namespace App\Http\Controllers;

use App\Models\AiRecommendationLog;
use App\Models\Classroom;
use App\Models\ClassroomFriendInvite;
use App\Models\Friendship;
use App\Models\User;
use App\Services\AwsBedrockCourseAssistant;
use App\Services\CourseProgressService;
use Illuminate\Http\Request;

class SocialLearningController extends Controller
{
    public function friends(Request $request)
    {
        $user = $request->user();

        $friends = Friendship::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)->orWhere('addressee_id', $user->id);
            })
            ->with(['requester:id,name,email', 'addressee:id,name,email'])
            ->latest('accepted_at')
            ->get()
            ->map(function (Friendship $friendship) use ($user) {
                return $friendship->requester_id === $user->id ? $friendship->addressee : $friendship->requester;
            });

        $incomingRequests = Friendship::query()
            ->where('addressee_id', $user->id)
            ->where('status', 'pending')
            ->with('requester:id,name,email')
            ->latest()
            ->get();

        $pendingInvites = ClassroomFriendInvite::query()
            ->where('invitee_id', $user->id)
            ->where('status', 'pending')
            ->with(['classroom:id,name,class_code', 'inviter:id,name'])
            ->latest()
            ->get();

        return view('student.social.friends', compact('friends', 'incomingRequests', 'pendingInvites'));
    }

    public function sendFriendRequest(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'friend_email' => ['required', 'email'],
        ]);

        $friend = User::query()
            ->where('email', $data['friend_email'])
            ->where('role', 'student')
            ->first();

        if (! $friend || $friend->id === $user->id) {
            return back()->with('error', 'That student account could not be invited.');
        }

        if ($user->school_id && $friend->school_id && (int) $user->school_id !== (int) $friend->school_id) {
            return back()->with('error', 'You can only add friends from the same school tenant.');
        }

        [$requesterId, $addresseeId] = $user->id < $friend->id
            ? [$user->id, $friend->id]
            : [$friend->id, $user->id];

        $friendship = Friendship::query()->firstOrCreate(
            ['requester_id' => $requesterId, 'addressee_id' => $addresseeId],
            ['status' => 'pending']
        );

        if ($friendship->status === 'accepted') {
            return back()->with('success', 'You are already connected as friends.');
        }

        if ($friendship->status === 'declined') {
            $friendship->update(['status' => 'pending', 'accepted_at' => null]);
        }

        return back()->with('success', 'Friend request sent.');
    }

    public function respondFriendRequest(Request $request, Friendship $friendship)
    {
        $user = $request->user();

        if ((int) $friendship->addressee_id !== (int) $user->id || $friendship->status !== 'pending') {
            abort(403);
        }

        $data = $request->validate([
            'action' => ['required', 'in:accept,decline'],
        ]);

        $friendship->update([
            'status' => $data['action'] === 'accept' ? 'accepted' : 'declined',
            'accepted_at' => $data['action'] === 'accept' ? now() : null,
        ]);

        return back()->with('success', $data['action'] === 'accept' ? 'Friend request accepted.' : 'Friend request declined.');
    }

    public function inviteFriendToClassroom(Request $request, Classroom $classroom)
    {
        $user = $request->user();
        $this->ensureStudentInClassroom($user->id, $classroom);

        $data = $request->validate([
            'friend_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:300'],
        ]);

        $friendId = (int) $data['friend_id'];
        if (! $this->areFriends($user->id, $friendId)) {
            return back()->with('error', 'You can only invite users that are already your friends.');
        }

        if ($classroom->students()->where('student_id', $friendId)->exists()) {
            return back()->with('error', 'This friend is already in the course.');
        }

        ClassroomFriendInvite::query()->updateOrCreate(
            [
                'classroom_id' => $classroom->id,
                'inviter_id' => $user->id,
                'invitee_id' => $friendId,
            ],
            [
                'status' => 'pending',
                'message' => $data['message'] ?? null,
                'responded_at' => null,
            ]
        );

        return back()->with('success', 'Course invite sent to your friend.');
    }

    public function respondClassroomInvite(Request $request, ClassroomFriendInvite $invite)
    {
        $user = $request->user();

        if ((int) $invite->invitee_id !== (int) $user->id || $invite->status !== 'pending') {
            abort(403);
        }

        $data = $request->validate([
            'action' => ['required', 'in:accept,decline'],
        ]);

        if ($data['action'] === 'accept') {
            $classroom = $invite->classroom;
            $classroom->students()->syncWithoutDetaching([$user->id => ['joined_at' => now()]]);
        }

        $invite->update([
            'status' => $data['action'] === 'accept' ? 'accepted' : 'declined',
            'responded_at' => now(),
        ]);

        return back()->with('success', $data['action'] === 'accept' ? 'Course invite accepted.' : 'Course invite declined.');
    }

    public function compareProgress(Request $request, CourseProgressService $progressService)
    {
        $user = $request->user();

        $friendIds = Friendship::query()
            ->where('status', 'accepted')
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)->orWhere('addressee_id', $user->id);
            })
            ->get()
            ->map(function (Friendship $friendship) use ($user) {
                return $friendship->requester_id === $user->id ? (int) $friendship->addressee_id : (int) $friendship->requester_id;
            })
            ->values();

        $classrooms = $user->enrolledClassrooms()->with('students:id,name')->get();

        $rows = [];
        foreach ($classrooms as $classroom) {
            $myProgress = $progressService->progressForStudentInClassroom($user, $classroom);

            $friendsInClass = $classroom->students
                ->whereIn('id', $friendIds)
                ->values()
                ->map(function (User $friend) use ($progressService, $classroom) {
                    return [
                        'id' => $friend->id,
                        'name' => $friend->name,
                        'progress' => $progressService->progressForStudentInClassroom($friend, $classroom),
                    ];
                })
                ->sortByDesc('progress')
                ->values();

            $rows[] = [
                'classroom' => $classroom,
                'my_progress' => $myProgress,
                'friends' => $friendsInClass,
            ];
        }

        return view('student.social.compare-progress', compact('rows'));
    }

    public function chatbot(Request $request)
    {
        return view('student.social.chatbot');
    }

    public function recommend(Request $request, AwsBedrockCourseAssistant $assistant, CourseProgressService $progressService)
    {
        $user = $request->user();
        $data = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $classrooms = $user->enrolledClassrooms()->get();

        $context = [
            'enrolled_courses' => $classrooms->pluck('name')->values()->all(),
            'course_progress' => $classrooms->map(function (Classroom $classroom) use ($user, $progressService) {
                return [
                    'course' => $classroom->name,
                    'progress' => $progressService->progressForStudentInClassroom($user, $classroom),
                ];
            })->values()->all(),
        ];

        $recommendations = $assistant->recommend($user, $data['prompt'], $context);

        AiRecommendationLog::query()->create([
            'user_id' => $user->id,
            'prompt' => $data['prompt'],
            'recommendations' => $recommendations,
            'provider' => $assistant->providerLabel(),
        ]);

        return view('student.social.chatbot', [
            'prompt' => $data['prompt'],
            'recommendations' => $recommendations,
            'provider' => $assistant->providerLabel(),
        ]);
    }

    private function ensureStudentInClassroom(int $studentId, Classroom $classroom): void
    {
        if (! $classroom->students()->where('student_id', $studentId)->exists()) {
            abort(403);
        }
    }

    private function areFriends(int $userId, int $friendId): bool
    {
        [$requesterId, $addresseeId] = $userId < $friendId
            ? [$userId, $friendId]
            : [$friendId, $userId];

        return Friendship::query()
            ->where('requester_id', $requesterId)
            ->where('addressee_id', $addresseeId)
            ->where('status', 'accepted')
            ->exists();
    }
}
