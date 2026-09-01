@extends('layouts.app')
@section('title', 'My Classrooms')

@section('sidebar')
    @include('student._sidebar')
@endsection

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Welcome, {{ auth()->user()->name }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ now()->format('l, F j, Y') }}</p>
        </div>
        <a href="{{ route('classroom.join.show') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Join Classroom
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Friends</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white mt-1">{{ $friendsCount }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Pending Requests</p>
            <p class="text-2xl font-bold text-slate-800 dark:text-white mt-1">{{ $pendingFriendRequests }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">AI Coach</p>
            <a href="{{ route('student.chatbot') }}" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline inline-block mt-2">Get recommendations</a>
        </div>
    </div>

    @if($classrooms->isEmpty())
        <div class="text-center py-20 text-slate-400 dark:text-slate-500">
            <svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            <p class="font-medium">You haven't joined any classrooms yet.</p>
            <a href="{{ route('classroom.join.show') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline text-sm mt-1 inline-block">Join a classroom →</a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            @foreach($classrooms as $classroom)
                <a href="{{ route('student.classrooms.show', $classroom) }}"
                   class="group block bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition-all p-5">
                    <h3 class="font-semibold text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $classroom->name }}</h3>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Teacher: {{ $classroom->teacher->name }}</p>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-0.5">{{ $classroom->assignments_count }} {{ Str::plural('assignment', $classroom->assignments_count) }}</p>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
