@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="max-w-5xl">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-6">Admin Dashboard</h1>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        @foreach([['Users','users','bg-blue-50 dark:bg-blue-900/20','text-blue-600 dark:text-blue-400'],['Classrooms','classrooms','bg-indigo-50 dark:bg-indigo-900/20','text-indigo-600 dark:text-indigo-400'],['Teachers','teachers','bg-purple-50 dark:bg-purple-900/20','text-purple-600 dark:text-purple-400'],['Students','students','bg-green-50 dark:bg-green-900/20','text-green-600 dark:text-green-400']] as [$label, $key, $bg, $color])
        <div class="rounded-2xl {{ $bg }} p-5 border border-transparent">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ $label }}</p>
            <p class="text-3xl font-bold {{ $color }} mt-1">{{ $stats[$key] }}</p>
        </div>
        @endforeach
    </div>

    @if($schools->isNotEmpty())
        <h2 class="text-base font-semibold text-slate-700 dark:text-slate-300 mb-3">Schools</h2>
        <div class="space-y-2">
            @foreach($schools as $school)
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-slate-700 px-5 py-4 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="font-medium text-slate-800 dark:text-white">{{ $school->name }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $school->domain }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $school->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-slate-700 text-gray-500' }}">
                            {{ ucfirst($school->status) }}
                        </span>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $school->users_count }} users</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
