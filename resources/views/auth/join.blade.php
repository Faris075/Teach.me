@extends('layouts.app')
@section('title', 'Join Classroom')

@section('content')
<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">Join a Classroom</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Enter the 6-character class code given by your teacher.</p>

    @if($classroom)
        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-2xl p-5 mb-6">
            <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Joining: {{ $classroom->name }}</p>
        </div>
    @endif

    @auth
        <form method="POST" action="{{ route('classroom.join') }}"
              class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6 space-y-5">
            @csrf
            <div>
                <label for="class_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Class Code</label>
                <input type="text" id="class_code" name="class_code"
                       value="{{ old('class_code', strtoupper($class_code)) }}"
                       maxlength="6" autofocus autocomplete="off"
                       class="w-full rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white px-4 py-3 text-lg font-mono tracking-widest text-center uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       placeholder="ABC123">
                @error('class_code') <p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p> @enderror
            </div>
            <button type="submit"
                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors">
                Join Classroom
            </button>
        </form>
    @else
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6 text-center">
            <p class="text-slate-600 dark:text-slate-400 mb-4">You need to be logged in to join a classroom.</p>
            <div class="flex gap-3 justify-center">
                <a href="{{ route('login') }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    Log In
                </a>
                <a href="{{ route('register') }}" class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                    Register
                </a>
            </div>
        </div>
    @endauth
</div>
@endsection
