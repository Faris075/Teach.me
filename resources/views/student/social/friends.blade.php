@extends('layouts.app')
@section('title', 'Friends & Invites')

@section('sidebar')
    @include('student._sidebar')
@endsection

@section('content')
<div class="max-w-5xl grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
            <h1 class="text-xl font-bold text-slate-800 dark:text-white mb-3">Your Friends</h1>
            @if($friends->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">No friends connected yet.</p>
            @else
                <div class="space-y-2">
                    @foreach($friends as $friend)
                        <div class="flex items-center justify-between rounded-xl border border-gray-100 dark:border-slate-700 px-3 py-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $friend->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $friend->email }}</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">Connected</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
            <h2 class="text-base font-semibold text-slate-800 dark:text-white mb-3">Incoming Friend Requests</h2>
            @if($incomingRequests->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">No pending requests.</p>
            @else
                <div class="space-y-3">
                    @foreach($incomingRequests as $request)
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-xl border border-gray-100 dark:border-slate-700 p-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $request->requester->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $request->requester->email }}</p>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('student.friends.respond', $request) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="accept">
                                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700">Accept</button>
                                </form>
                                <form method="POST" action="{{ route('student.friends.respond', $request) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="decline">
                                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">Decline</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
            <h2 class="text-base font-semibold text-slate-800 dark:text-white mb-3">Add Friend</h2>
            <form method="POST" action="{{ route('student.friends.request') }}" class="space-y-3">
                @csrf
                <input type="email" name="friend_email" required placeholder="friend@email.com" class="w-full rounded-xl border-gray-300 dark:border-slate-600 dark:bg-slate-900 text-sm">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition-colors">Send Request</button>
            </form>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
            <h2 class="text-base font-semibold text-slate-800 dark:text-white mb-3">Course Invites</h2>
            @if($pendingInvites->isEmpty())
                <p class="text-sm text-slate-500 dark:text-slate-400">No pending course invites.</p>
            @else
                <div class="space-y-3">
                    @foreach($pendingInvites as $invite)
                        <div class="rounded-xl border border-gray-100 dark:border-slate-700 p-3">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $invite->classroom->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">From {{ $invite->inviter->name }}</p>
                            @if($invite->message)
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">"{{ $invite->message }}"</p>
                            @endif
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('student.friend-invites.respond', $invite) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="accept">
                                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Accept</button>
                                </form>
                                <form method="POST" action="{{ route('student.friend-invites.respond', $invite) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="action" value="decline">
                                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">Decline</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
