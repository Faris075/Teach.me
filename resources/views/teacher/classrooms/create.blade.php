@extends('layouts.app')
@section('title', 'Create Classroom')

@section('sidebar')
    @include('teacher._sidebar')
@endsection

@section('content')
<div class="max-w-lg">
    <a href="{{ route('teacher.classrooms.index') }}" class="inline-flex items-center text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-6">
        ← Back to classrooms
    </a>

    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-6">Create Classroom</h1>

    <form method="POST" action="{{ route('teacher.classrooms.store') }}"
          class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Classroom Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" autofocus
                   class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="e.g. IGCSE Chemistry 0620">
            @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">
                Create Classroom
            </button>
            <a href="{{ route('teacher.classrooms.index') }}"
               class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
