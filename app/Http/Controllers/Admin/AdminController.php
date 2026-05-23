<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = User::query();
        $classroomsQuery = Classroom::query();
        $teacherQuery = User::query()->where('role', '=', 'teacher');
        $studentQuery = User::query()->where('role', '=', 'student');

        if ($user->role === 'school_admin') {
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

        return view('admin.dashboard', compact('stats', 'schools'));
    }
}
