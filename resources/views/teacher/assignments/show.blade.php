@extends('layouts.app')
@section('title', $assignment->title)

@section('sidebar')
    @include('teacher._sidebar')
@endsection

@section('content')
<div class="max-w-5xl">
    <a href="{{ route('teacher.classrooms.show', $classroom) }}" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5">
        ← {{ $classroom->name }}
    </a>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $assignment->title }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ $assignment->topic->title }}
                @if($assignment->due_date)
                    · Due {{ $assignment->due_date->format('M j, Y g:i A') }}
                @endif
                · Max score: {{ $assignment->max_score }}
                · <span class="capitalize">{{ str_replace('_', ' ', $assignment->grading_type) }}</span>
            </p>
        </div>
        <a href="{{ route('teacher.grading.index', $assignment) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm whitespace-nowrap">
            Grade Submissions
        </a>
    </div>

    @if($assignment->description)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-5 mb-6 shadow-sm text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
            {!! nl2br(e($assignment->description)) !!}
        </div>
    @endif

    <h2 class="text-base font-semibold text-slate-700 dark:text-slate-300 mb-3">Submissions ({{ $submissions->count() }})</h2>

    @if($submissions->isEmpty())
        <p class="text-sm text-slate-400 dark:text-slate-500">No submissions yet.</p>
    @else
        <div class="space-y-2">
            @foreach($submissions as $sub)
                <a href="{{ route('teacher.grading.show', $sub) }}"
                   class="flex items-center justify-between bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 px-5 py-3.5 shadow-sm hover:border-indigo-300 dark:hover:border-indigo-600 transition-all group">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs font-semibold">
                            {{ strtoupper(substr($sub->student->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $sub->student->name }}</p>
                            @if($sub->student->candidate_number)
                                <p class="text-xs text-slate-400 dark:text-slate-500">{{ $sub->student->candidate_number }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                            {{ $sub->status === 'graded' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' :
                               ($sub->status === 'turned_in_late' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' :
                                'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400') }}">
                            {{ ucwords(str_replace('_', ' ', $sub->status)) }}
                        </span>
                        @if($sub->status === 'graded')
                            <span class="font-semibold text-slate-700 dark:text-slate-300">
                                {{ $sub->grade_literal ?? $sub->grade_numeric }}
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
