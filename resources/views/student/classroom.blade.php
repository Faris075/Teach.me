@extends('layouts.app')
@section('title', $classroom->name)

@section('sidebar')
    @include('student._sidebar')
@endsection

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('student.dashboard') }}" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5">
        ← My classrooms
    </a>

    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-1">{{ $classroom->name }}</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Teacher: {{ $classroom->teacher->name }}</p>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-4 mb-6">
        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Invite a Friend to This Course</p>
        @if($inviteableFriends->isEmpty())
            <p class="text-xs text-slate-500 dark:text-slate-400">No available friends to invite right now.</p>
        @else
            <form method="POST" action="{{ route('student.classrooms.friend-invites.store', $classroom) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                @csrf
                <select name="friend_id" class="sm:col-span-2 rounded-xl border-gray-300 dark:border-slate-600 dark:bg-slate-900 text-sm" required>
                    <option value="">Choose a friend</option>
                    @foreach($inviteableFriends as $friend)
                        <option value="{{ $friend->id }}">{{ $friend->name }} ({{ $friend->email }})</option>
                    @endforeach
                </select>
                <input type="text" name="message" class="sm:col-span-1 rounded-xl border-gray-300 dark:border-slate-600 dark:bg-slate-900 text-sm" maxlength="300" placeholder="Optional message">
                <button type="submit" class="sm:col-span-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">Send Invite</button>
            </form>
        @endif
    </div>

    @forelse($classroom->topics as $topic)
        <div class="mb-6">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-3 px-1">{{ $topic->title }}</h2>
            @forelse($topic->assignments as $assignment)
                <a href="{{ route('student.assignments.show', $assignment) }}"
                   class="flex items-center justify-between bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 px-5 py-3.5 mb-2 shadow-sm hover:border-indigo-300 dark:hover:border-indigo-600 transition-all group">
                    <span class="font-medium text-slate-700 dark:text-slate-300 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $assignment->title }}</span>
                    <span class="text-xs text-slate-400 dark:text-slate-500 whitespace-nowrap ml-4">
                        @if($assignment->due_date)
                            Due {{ $assignment->due_date->format('M j, Y') }}
                        @else
                            No due date
                        @endif
                    </span>
                </a>
            @empty
                <p class="text-sm text-slate-400 dark:text-slate-500 px-1">No assignments in this topic.</p>
            @endforelse
        </div>
    @empty
        <div class="text-center py-16 text-slate-400 dark:text-slate-500">
            <p>No topics have been added yet.</p>
        </div>
    @endforelse
</div>
@endsection
