<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRecommendationLog;
use App\Models\Classroom;
use App\Models\ClassroomFriendInvite;
use App\Models\Friendship;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolScoped = $user->role === 'school_admin';

        $query = User::query();
        $classroomsQuery = Classroom::query();
        $teacherQuery = User::query()->where('role', '=', 'teacher');
        $studentQuery = User::query()->where('role', '=', 'student');

        if ($schoolScoped) {
            $query->where('school_id', '=', $user->school_id);
            $classroomsQuery->where('school_id', '=', $user->school_id);
            $teacherQuery->where('school_id', '=', $user->school_id);
            $studentQuery->where('school_id', '=', $user->school_id);
        }

        $stats = [
            'users'      => $query->count('id'),
            'classrooms' => $classroomsQuery->count('id'),
            'teachers'   => $teacherQuery->count('id'),
            'students'   => $studentQuery->count('id'),
        ];

        $schools = $user->role === 'super_admin' ? School::withCount('users')->latest()->get() : collect();

        $socialStats = $this->socialLearningStats($schoolScoped ? $user->school_id : null);

        return view('admin.dashboard', compact('stats', 'schools', 'socialStats'));
    }

    private function socialLearningStats(?int $schoolId): array
    {
        $friendshipQuery = Friendship::query();
        $inviteQuery = ClassroomFriendInvite::query();
        $aiLogQuery = AiRecommendationLog::query();

        if ($schoolId) {
            $friendshipQuery->where(function ($q) use ($schoolId) {
                $q->whereHas('requester', fn ($q2) => $q2->where('school_id', $schoolId))
                    ->orWhereHas('addressee', fn ($q2) => $q2->where('school_id', $schoolId));
            });

            $inviteQuery->whereHas('classroom', fn ($q) => $q->where('school_id', $schoolId));

            $aiLogQuery->whereHas('user', fn ($q) => $q->where('school_id', $schoolId));
        }

        $totalInvites = (clone $inviteQuery)->count();
        $acceptedInvites = (clone $inviteQuery)->where('status', 'accepted')->count();

        return [
            'accepted_friendships' => (clone $friendshipQuery)->where('status', 'accepted')->count(),
            'pending_friend_requests' => (clone $friendshipQuery)->where('status', 'pending')->count(),
            'total_course_invites' => $totalInvites,
            'accepted_course_invites' => $acceptedInvites,
            'invite_acceptance_rate' => $totalInvites > 0 ? round(($acceptedInvites / $totalInvites) * 100, 1) : 0.0,
            'ai_recommendations_generated' => (clone $aiLogQuery)->count(),
            'ai_bedrock_calls' => (clone $aiLogQuery)->where('provider', 'aws-bedrock')->count(),
        ];
    }
}
