@extends('layouts.app')
@section('title', 'Grade: ' . $assignment->title)

@section('sidebar')
    @include('teacher._sidebar')
@endsection

@section('content')
<div class="max-w-6xl">
    <a href="{{ route('teacher.assignments.show', $assignment) }}" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5">
        ← {{ $assignment->title }}
    </a>

    <h1 class="text-xl font-bold text-slate-800 dark:text-white mb-4">Grading: {{ $assignment->title }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Submission List --}}
        <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Submissions</p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-slate-700">
                @foreach($submissions as $sub)
                    <a href="{{ route('teacher.grading.show', $sub) }}"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors {{ isset($current) && $current->id === $sub->id ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }}">
                        <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs font-semibold flex-shrink-0">
                            {{ strtoupper(substr($sub->student->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate">{{ $sub->student->name }}</p>
                        </div>
                        @if($sub->grade_literal)
                            @php
                                $gradeClass = match($sub->grade_literal) {
                                    'A*' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
                                    'A'  => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                                    'B'  => 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400',
                                    'C'  => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                                    'D'  => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
                                    'E'  => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400',
                                    'F'  => 'bg-orange-200 dark:bg-orange-900/40 text-orange-800 dark:text-orange-300',
                                    'G'  => 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
                                    'U'  => 'bg-red-200 dark:bg-red-900/40 text-red-800 dark:text-red-300',
                                    default => 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold flex-shrink-0 {{ $gradeClass }}">{{ $sub->grade_literal }}</span>
                        @endif
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $sub->status === 'graded' ? 'bg-green-400' : ($sub->status === 'turned_in_late' ? 'bg-yellow-400' : 'bg-blue-400') }}"></span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Grading Panel --}}
        <div class="lg:col-span-3">
            @if(isset($current))
                @php $sub = $current; @endphp
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                    {{-- Submission Content --}}
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="font-semibold text-slate-800 dark:text-white">{{ $sub->student->name }}</p>
                                @if($sub->student->candidate_number)
                                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ $sub->student->candidate_number }}</p>
                                @endif
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                {{ $sub->status === 'graded' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' :
                                   ($sub->status === 'turned_in_late' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' :
                                    'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400') }}">
                                {{ ucwords(str_replace('_', ' ', $sub->status)) }}
                            </span>
                        </div>

                        @if($sub->submitted_text)
                            <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-4 text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap max-h-72 overflow-y-auto">{{ $sub->submitted_text }}</div>
                        @else
                            <p class="text-sm text-slate-400 dark:text-slate-500">No written answer.</p>
                        @endif

                        @if($sub->file_path)
                            <a href="{{ route('teacher.grading.download', $sub) }}" target="_blank"
                               class="inline-flex items-center gap-2 mt-4 text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download attachment
                            </a>
                        @endif
                    </div>

                    {{-- Grade Form --}}
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">Grade</p>

                        <form method="POST" action="{{ route('teacher.grading.update', $sub) }}" class="space-y-4">
                            @csrf @method('PATCH')

                            @if($assignment->grading_type === 'igcse_letter')
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Letter Grade</label>
                                    <select name="grade_literal"
                                            class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        @foreach(['A*','A','B','C','D','E','F','G','U'] as $g)
                                            <option value="{{ $g }}" {{ ($sub->grade_literal ?? '') === $g ? 'selected' : '' }}>{{ $g }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div>
                                    <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Score (max {{ $assignment->max_score }})</label>
                                    <input type="number" name="grade_numeric" step="0.5" min="0" max="{{ $assignment->max_score }}"
                                           value="{{ old('grade_numeric', $sub->grade_numeric) }}"
                                           class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    @error('grade_numeric') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Feedback <span class="font-normal">(optional)</span></label>
                                <textarea name="teacher_comment" rows="4"
                                          class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                          placeholder="Write feedback for the student...">{{ old('teacher_comment', $sub->teacher_comment) }}</textarea>
                            </div>

                            <button type="submit"
                                    class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">
                                Save & Next →
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-10 text-center text-slate-400 dark:text-slate-500">
                    <p>Select a submission from the list to start grading.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
