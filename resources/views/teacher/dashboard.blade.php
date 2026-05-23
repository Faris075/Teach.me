@extends('layouts.app')
@section('title', 'Teacher Dashboard')

@section('sidebar')
    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-3 px-2">Navigation</p>
    <a href="{{ route('teacher.dashboard') }}"
       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('teacher.dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700' }} transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
        Dashboard
    </a>
    <a href="{{ route('teacher.classrooms.index') }}"
       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('teacher.classrooms.*') ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'text-slate-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700' }} transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
        Classrooms
    </a>
@endsection

@section('content')
<div class="max-w-5xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ now()->format('l, F j, Y') }}
                @if(auth()->user()->is_independent)
                    · <span class="text-indigo-600 dark:text-indigo-400 font-medium">Independent Tutor</span>
                @endif
            </p>
        </div>
        <a href="{{ route('teacher.classrooms.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Classroom
        </a>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-gray-100 dark:border-slate-700 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Classrooms</p>
            <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $classrooms->count() }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-gray-100 dark:border-slate-700 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Total Students</p>
            <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $classrooms->sum('students_count') }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-gray-100 dark:border-slate-700 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Assignments</p>
            <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1">{{ $classrooms->sum('assignments_count') }}</p>
        </div>
    </div>

    {{-- Classrooms Grid --}}
    @if($classrooms->isEmpty())
        <div class="text-center py-16 text-slate-400 dark:text-slate-500">
            <svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            <p class="font-medium">No classrooms yet.</p>
            <a href="{{ route('teacher.classrooms.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm mt-1 inline-block">Create your first classroom →</a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($classrooms as $classroom)
                <a href="{{ route('teacher.classrooms.show', $classroom) }}"
                   class="group block bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition-all p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $classroom->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400' }}">
                            {{ ucfirst($classroom->status) }}
                        </span>
                        <span class="text-xs font-mono text-slate-400 dark:text-slate-500 bg-gray-50 dark:bg-slate-700 px-2 py-0.5 rounded">{{ $classroom->class_code }}</span>
                    </div>
                    <h3 class="font-semibold text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-snug">{{ $classroom->name }}</h3>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">{{ $classroom->students_count }} {{ Str::plural('student', $classroom->students_count) }} · {{ $classroom->assignments_count }} {{ Str::plural('assignment', $classroom->assignments_count) }}</p>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
