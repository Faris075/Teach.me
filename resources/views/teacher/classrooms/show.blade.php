@extends('layouts.app')
@section('title', $classroom->name)

@section('sidebar')
    @include('teacher._sidebar')
@endsection

@section('content')
<div class="max-w-4xl">
    <a href="{{ route('teacher.classrooms.index') }}" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5">
        ← All classrooms
    </a>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $classroom->name }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Code: <span class="font-mono font-semibold tracking-wider">{{ $classroom->class_code }}</span></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('teacher.classrooms.edit', $classroom) }}"
               class="px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                Edit
            </a>
            @if($classroom->status === 'active')
                <form method="POST" action="{{ route('teacher.classrooms.archive', $classroom) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl hover:bg-yellow-100 dark:hover:bg-yellow-900/40 transition-colors">
                        Archive
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('teacher.classrooms.destroy', $classroom) }}"
                  onsubmit="return confirm('Delete this classroom permanently?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                    Delete
                </button>
            </form>
        </div>
    </div>

    {{-- Invite Box --}}
    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-2xl p-5 mb-8">
        <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300 mb-2">Invite Students</p>
        <div class="flex items-center gap-3">
            <input type="text" readonly value="{{ $inviteUrl }}" id="inviteUrl"
                   class="flex-1 rounded-xl border border-indigo-300 dark:border-indigo-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-3 py-2 text-xs font-mono focus:outline-none">
            <button onclick="navigator.clipboard.writeText(document.getElementById('inviteUrl').value); this.textContent='Copied!';"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors whitespace-nowrap">
                Copy Link
            </button>
        </div>
        <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-2">Or share the class code: <span class="font-mono font-bold">{{ $classroom->class_code }}</span></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Topics & Assignments --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-700 dark:text-slate-300">Topics & Assignments</h2>
                <a href="{{ route('teacher.classrooms.topics.create', $classroom) }}"
                   class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">+ Add Topic</a>
            </div>

            @forelse($classroom->topics as $topic)
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-slate-800 dark:text-white">{{ $topic->title }}</h3>
                        <a href="{{ route('teacher.classrooms.assignments.create', ['classroom' => $classroom, 'topic' => $topic]) }}"
                           class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">+ Assignment</a>
                    </div>
                    @forelse($topic->assignments as $assignment)
                        <a href="{{ route('teacher.assignments.show', $assignment) }}"
                           class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors text-sm">
                            <span class="text-slate-700 dark:text-slate-300">{{ $assignment->title }}</span>
                            <span class="text-xs text-slate-400 dark:text-slate-500">
                                {{ $assignment->due_date ? $assignment->due_date->format('M j') : 'No due date' }}
                            </span>
                        </a>
                    @empty
                        <p class="text-xs text-slate-400 dark:text-slate-500 py-2">No assignments in this topic.</p>
                    @endforelse
                </div>
            @empty
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-dashed border-gray-200 dark:border-slate-700 p-8 text-center text-slate-400 dark:text-slate-500">
                    <p class="text-sm">No topics yet. Add a topic to start adding assignments.</p>
                </div>
            @endforelse
        </div>

        {{-- Students Sidebar --}}
        <div>
            <h2 class="text-base font-semibold text-slate-700 dark:text-slate-300 mb-3">Students ({{ $students->count() }})</h2>
            @forelse($students as $student)
                <div class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-slate-700 last:border-0">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs font-semibold">
                        {{ strtoupper(substr($student->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate">{{ $student->name }}</p>
                        @if($student->candidate_number)
                            <p class="text-xs text-slate-400 dark:text-slate-500">{{ $student->candidate_number }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400 dark:text-slate-500">No students enrolled.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
