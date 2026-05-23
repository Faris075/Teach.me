<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $classrooms = $user->ownedClassrooms()
            ->withCount(['students', 'assignments'])
            ->latest()
            ->get();

        return view('teacher.dashboard', compact('classrooms'));
    }
}
