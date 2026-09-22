@php
    $notificationLayout = match (auth()->user()?->role) {
        'admin' => 'admin.layouts.app',
        'trainer' => 'trainer.layouts.app',
        'trainee' => 'trainee.layouts.app',
        'alumni' => 'alumni.layouts.app',
        default => 'trainee.layouts.app',
    };
@endphp

@extends($notificationLayout, ['title' => 'Notifications | MCARE'])

@section('content')
<section class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        @unless (auth()->user()?->role === 'admin')
            <div><p class="dashboard-section-kicker">Account updates</p><h1 class="mt-2 dashboard-section-title text-2xl sm:text-3xl">Notification center</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Review classroom, career, and system updates connected to this account.</p></div>
        @else
            <p class="max-w-2xl text-sm leading-6 text-slate-600">Review classroom, career, and system updates connected to this account.</p>
        @endunless
        @if ($notifications->contains(fn ($notification) => $notification->read_at === null))
            <form method="POST" action="{{ route('notifications.read-all') }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="secondary-action inline-flex items-center justify-center gap-2"><x-dashboard-icon name="circle-check" class="h-4 w-4" />Mark all as read</button></form>
        @endif
    </header>

    <section class="dashboard-panel divide-y divide-slate-100 p-0" aria-label="Notifications">
        @forelse ($notifications as $notification)
            @php
                $notificationData = is_array($notification->data) ? $notification->data : [];
                $notificationUrl = $notificationData['url'] ?? route('notifications.index');
            @endphp
            <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between {{ $notification->read_at ? 'bg-white' : 'bg-purple-50/40' }}">
                <div class="flex min-w-0 gap-4"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700"><x-dashboard-icon :name="$notificationData['icon'] ?? 'bell'" class="h-5 w-5" /></span><div class="min-w-0"><h2 class="font-bold text-slate-950">{{ $notificationData['title'] ?? 'MCARE update' }}</h2><p class="mt-1 text-sm leading-6 text-slate-600">{{ $notificationData['message'] ?? 'Open the linked workspace for more details.' }}</p><p class="mt-2 text-xs font-semibold text-slate-400">{{ $notification->created_at?->format('M d, Y g:i A') ?? 'Recently' }}</p></div></div>
                <div class="flex w-full shrink-0 flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center">
                    @if ($notificationUrl)<a href="{{ $notificationUrl }}" class="secondary-action inline-flex items-center justify-center text-sm">Open</a>@endif
                    @if (! $notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}" class="w-full sm:w-auto"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH"><button type="submit" class="primary-action w-full text-sm sm:w-auto">Mark read</button></form>
                    @else
                        <span class="text-xs font-bold text-emerald-700">Read</span>
                    @endif
                </div>
            </article>
        @empty
            <div class="py-16 text-center"><x-dashboard-icon name="bell" class="mx-auto h-9 w-9 text-slate-300" /><h2 class="mt-4 text-lg font-bold text-slate-900">You are all caught up</h2><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">New MCARE updates will appear in this notification center.</p></div>
        @endforelse
    </section>

    @if ($notifications->hasPages())<div class="dashboard-panel py-4">{{ $notifications->links() }}</div>@endif
</section>
@endsection
