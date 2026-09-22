@props([
    'logoutRoute',
    'roleLabel',
    'showAdminLogs' => false,
    'careerHubRoute' => null,
])

@php
    // Keep notification access inside the account menu so the top bar stays
    // aligned and the same control works for every authenticated role.
    $accountNotificationUser = auth()->user();
    $accountUnreadNotificationCount = $accountNotificationUser?->unreadNotifications()->count() ?? 0;
@endphp

<p class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-slate-400">{{ $roleLabel }} account</p>

@if ($careerHubRoute)
    <a href="{{ $careerHubRoute }}" class="dashboard-account-action">
        <x-dashboard-icon name="briefcase" class="mr-3 w-4" />Career Hub
    </a>
@endif

@if ($accountNotificationUser && Route::has('notifications.index'))
    <a href="{{ route('notifications.index') }}" class="dashboard-account-action">
        <x-dashboard-icon name="bell" class="mr-3 w-4" />
        <span class="min-w-0 flex-1">Notifications</span>
        @if ($accountUnreadNotificationCount > 0)
            <span class="ml-3 inline-flex min-w-5 items-center justify-center rounded-full bg-purple-100 px-1.5 py-0.5 text-[10px] font-black leading-none text-purple-800" aria-label="{{ $accountUnreadNotificationCount }} unread notifications">
                {{ $accountUnreadNotificationCount > 99 ? '99+' : $accountUnreadNotificationCount }}
            </span>
        @endif
    </a>
@endif

@if ($showAdminLogs)
    <a href="{{ route('admin.logs.index') }}" class="dashboard-account-action">
        <x-dashboard-icon name="shield-halved" class="mr-3 w-4" />Admin logs
    </a>
@endif

<a href="{{ route('account.settings') }}" class="dashboard-account-action">
    <x-dashboard-icon name="gear" class="mr-3 w-4" />Settings
</a>
<a href="{{ route('account.help') }}" class="dashboard-account-action">
    <x-dashboard-icon name="circle-question" class="mr-3 w-4" />Help
</a>

<form method="POST" action="{{ $logoutRoute }}" class="mt-1 border-t border-slate-100 pt-1">
    @csrf
    <button type="submit" class="dashboard-account-action is-danger">
        <x-dashboard-icon name="arrow-right-from-bracket" class="mr-3 w-4" />Sign out
    </button>
</form>
