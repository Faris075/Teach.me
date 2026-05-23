<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── School ────────────────────────────────────────────────────────
        $school = School::create([
            'uuid'   => Str::uuid(),
            'name'   => 'Teach.me Demo School',
            'domain' => null,
            'status' => 'active',
        ]);

        // ── Super Admin (no school) ───────────────────────────────────────
        User::create([
            'uuid'           => Str::uuid(),
            'name'           => 'Super Admin',
            'email'          => 'superadmin@teach.me',
            'password'       => 'password',
            'role'           => 'super_admin',
            'school_id'      => null,
            'is_independent' => false,
        ]);

        // ── School Admin ──────────────────────────────────────────────────
        User::create([
            'uuid'           => Str::uuid(),
            'name'           => 'School Admin',
            'email'          => 'admin@teach.me',
            'password'       => 'password',
            'role'           => 'school_admin',
            'school_id'      => $school->id,
            'is_independent' => false,
        ]);

        // ── Independent Teacher (no school) ──────────────────────────────
        User::create([
            'uuid'           => Str::uuid(),
            'name'           => 'Independent Teacher',
            'email'          => 'teacher@teach.me',
            'password'       => 'password',
            'role'           => 'teacher',
            'school_id'      => null,
            'is_independent' => true,
        ]);

        // ── Institutional Teacher ─────────────────────────────────────────
        $instTeacher = User::create([
            'uuid'           => Str::uuid(),
            'name'           => 'Institutional Teacher',
            'email'          => 'inst.teacher@teach.me',
            'password'       => 'password',
            'role'           => 'teacher',
            'school_id'      => $school->id,
            'is_independent' => false,
        ]);

        // ── Students ──────────────────────────────────────────────────────
        $students = [];
        foreach ([1, 2, 3] as $n) {
            $students[] = User::create([
                'uuid'             => Str::uuid(),
                'name'             => "Student {$n}",
                'email'            => "student{$n}@teach.me",
                'password'         => 'password',
                'role'             => 'student',
                'school_id'        => $school->id,
                'is_independent'   => false,
                'candidate_number' => "S00{$n}",
                'parent_email'     => "parent{$n}@example.com",
            ]);
        }

        // ── Demo Classroom ────────────────────────────────────────────────
        $classroom = Classroom::create([
            'school_id'  => $school->id,
            'teacher_id' => $instTeacher->id,
            'name'       => 'IGCSE English Second Language',
            'class_code' => 'DEMO01',
            'status'     => 'active',
        ]);

        // Enrol all 3 students
        foreach ($students as $student) {
            $classroom->students()->attach($student->id, ['joined_at' => now()]);
        }
    }
}
