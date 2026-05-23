<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['teacher', 'school_admin', 'super_admin']);
    }

    public function view(User $user, Classroom $classroom): bool
    {
        if ($user->role === 'super_admin') return true;
        if ($user->role === 'school_admin') return $user->school_id === $classroom->school_id;
        if ($user->role === 'teacher') return $user->id === $classroom->teacher_id;
        if ($user->role === 'student') {
            return $classroom->students()->where('student_id', $user->id)->exists();
        }
        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['teacher', 'school_admin', 'super_admin']);
    }

    public function update(User $user, Classroom $classroom): bool
    {
        if ($user->role === 'super_admin') return true;
        return $user->id === $classroom->teacher_id;
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        // Institutional teachers may only archive — not delete
        if ($user->role === 'teacher' && ! $user->is_independent) {
            return Response::deny(
                'Teachers inside an organisation can only archive classrooms. Contact your administrator for permanent deletion.'
            );
        }

        return $user->id === $classroom->teacher_id || in_array($user->role, ['school_admin', 'super_admin'])
            ? Response::allow()
            : Response::deny('Unauthorised.');
    }

    public function restore(User $user, Classroom $classroom): bool
    {
        return $user->role === 'super_admin' || $user->id === $classroom->teacher_id;
    }

    public function forceDelete(User $user, Classroom $classroom): bool
    {
        return $user->role === 'super_admin';
    }
}
