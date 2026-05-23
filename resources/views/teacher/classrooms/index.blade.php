@extends('layouts.app')
@section('title', 'Classrooms')

@section('sidebar')
    @include('teacher._sidebar')
@endsection

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Classrooms</h1>
        <a href="{{ route('teacher.classrooms.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Classroom
        </a>
    </div>

    @if($classrooms->isEmpty())
        <div class="text-center py-20 text-slate-400 dark:text-slate-500">
            <p class="font-medium">No classrooms yet.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($classrooms as $classroom)
                <div class="flex items-center justify-between bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 px-5 py-4 shadow-sm">
                    <div>
                        <span class="font-semibold text-slate-800 dark:text-white">{{ $classroom->name }}</span>
                        <span class="ml-3 text-xs font-mono text-slate-400 dark:text-slate-500">{{ $classroom->class_code }}</span>
                        <span class="ml-2 text-xs text-slate-400 dark:text-slate-500">{{ $classroom->students_count }} {{ Str::plural('student', $classroom->students_count) }}</span>
                    </div>
                    <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Open →</a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
