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
                @php
                    $gcMap = [
                        'A*' => ['box' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-700', 'label' => 'text-purple-700 dark:text-purple-300', 'value' => 'text-purple-800 dark:text-purple-200', 'note' => 'text-purple-700 dark:text-purple-300'],
                        'A'  => ['box' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700',   'label' => 'text-green-700 dark:text-green-400',   'value' => 'text-green-800 dark:text-green-300',   'note' => 'text-green-700 dark:text-green-400'],
                        'B'  => ['box' => 'bg-teal-50 dark:bg-teal-900/20 border-teal-200 dark:border-teal-700',       'label' => 'text-teal-700 dark:text-teal-400',     'value' => 'text-teal-800 dark:text-teal-300',     'note' => 'text-teal-700 dark:text-teal-400'],
                        'C'  => ['box' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700',       'label' => 'text-blue-700 dark:text-blue-400',     'value' => 'text-blue-800 dark:text-blue-300',     'note' => 'text-blue-700 dark:text-blue-400'],
                        'D'  => ['box' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700','label' => 'text-yellow-700 dark:text-yellow-500', 'value' => 'text-yellow-800 dark:text-yellow-300', 'note' => 'text-yellow-700 dark:text-yellow-500'],
                        'E'  => ['box' => 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-700','label' => 'text-orange-700 dark:text-orange-400', 'value' => 'text-orange-800 dark:text-orange-300', 'note' => 'text-orange-700 dark:text-orange-400'],
                        'F'  => ['box' => 'bg-orange-100 dark:bg-orange-900/30 border-orange-300 dark:border-orange-700','label' => 'text-orange-700 dark:text-orange-300','value' => 'text-orange-900 dark:text-orange-200', 'note' => 'text-orange-700 dark:text-orange-300'],
                        'G'  => ['box' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700',           'label' => 'text-red-600 dark:text-red-400',       'value' => 'text-red-700 dark:text-red-300',       'note' => 'text-red-600 dark:text-red-400'],
                        'U'  => ['box' => 'bg-red-100 dark:bg-red-900/30 border-red-300 dark:border-red-700',          'label' => 'text-red-700 dark:text-red-300',       'value' => 'text-red-800 dark:text-red-200',       'note' => 'text-red-700 dark:text-red-300'],
                    ];
                    $gc = $gcMap[$submission->grade_literal ?? ''] ?? ['box' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700', 'label' => 'text-green-700 dark:text-green-400', 'value' => 'text-green-800 dark:text-green-300', 'note' => 'text-green-700 dark:text-green-400'];
                @endphp
                <div class="mb-4 p-4 rounded-xl border {{ $gc['box'] }}">
                    <p class="text-sm font-semibold {{ $gc['label'] }} mb-1">Grade</p>
                    @if($submission->grade_literal)
                        <p class="text-2xl font-bold {{ $gc['value'] }}">{{ $submission->grade_literal }}</p>
                    @elseif($submission->grade_numeric !== null)
                        <p class="text-2xl font-bold {{ $gc['value'] }}">{{ $submission->grade_numeric }} / {{ $assignment->max_score }}</p>
                    @endif
                    @if($submission->teacher_comment)
                        <p class="text-sm {{ $gc['note'] }} mt-2">{{ $submission->teacher_comment }}</p>
                    @endif
                </div>
            @endif

            <p class="text-sm text-slate-500 dark:text-slate-400">Submitted {{ $submission->created_at->diffForHumans() }}</p>
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
