@extends('layouts.app')
@section('title', $assignment->title)

@section('sidebar')
    @include('student._sidebar')
@endsection

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('student.classrooms.show', $classroom) }}" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5">
        ← {{ $classroom->name }}
    </a>

    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">{{ $assignment->title }}</h1>

    @if($assignment->due_date)
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1">
            Due: <span class="{{ $assignment->due_date->isPast() ? 'text-red-500 dark:text-red-400 font-semibold' : '' }}">{{ $assignment->due_date->format('l, F j, Y g:i A') }}</span>
        </p>
    @endif

    @if($assignment->description)
        <div class="mt-4 mb-6 bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 p-5 shadow-sm prose dark:prose-invert max-w-none text-sm">
            {!! nl2br(e($assignment->description)) !!}
        </div>
    @endif

    {{-- Submission Box --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6">
        @if($submission)
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                    {{ $submission->status === 'graded' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' :
                       ($submission->status === 'turned_in_late' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' :
                        'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400') }}">
                    {{ ucwords(str_replace('_', ' ', $submission->status)) }}
                </span>
            </div>

            @if($submission->status === 'graded')
                <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-700">
                    <p class="text-sm font-semibold text-green-700 dark:text-green-400 mb-1">Grade</p>
                    @if($submission->grade_literal)
                        <p class="text-2xl font-bold text-green-800 dark:text-green-300">{{ $submission->grade_literal }}</p>
                    @elseif($submission->grade_numeric !== null)
                        <p class="text-2xl font-bold text-green-800 dark:text-green-300">{{ $submission->grade_numeric }} / {{ $assignment->max_score }}</p>
                    @endif
                    @if($submission->teacher_comment)
                        <p class="text-sm text-green-700 dark:text-green-400 mt-2">{{ $submission->teacher_comment }}</p>
                    @endif
                </div>
            @endif

            <p class="text-sm text-slate-500 dark:text-slate-400">Submitted {{ $submission->submitted_at ? $submission->submitted_at->diffForHumans() : '' }}</p>
        @else
            {{-- Late check --}}
            @php
                $isLate = $assignment->due_date && $assignment->due_date->isPast();
                $blocked = $isLate && ! $assignment->allow_late_submissions;
            @endphp

            @if($blocked)
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-700 text-sm text-red-600 dark:text-red-400 font-medium mb-4">
                    The deadline has passed and late submissions are not allowed.
                </div>
            @else
                @if($isLate)
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-700 text-sm text-yellow-700 dark:text-yellow-400 mb-4">
                        This assignment is past due. Your submission will be marked as late.
                    </div>
                @endif

                <form method="POST" action="{{ route('student.submissions.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Your Answer</label>
                        <textarea name="submitted_text" rows="5"
                                  class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                  placeholder="Write your answer here...">{{ old('answer') }}</textarea>
                        @error('answer') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Attachment (optional)</label>
                        <input type="file" name="file"
                               class="w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100">
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">PDF, DOCX, PNG, JPG up to 10 MB</p>
                        @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">
                        Submit Assignment
                    </button>
                </form>
            @endif
        @endif
    </div>
</div>
@endsection
