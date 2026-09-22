@if ($isAdminPreview ?? false)
    <div class="flex flex-col gap-3 rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-semibold text-purple-900">Admin preview of the shared graduate Career Hub</p>
        <a href="{{ route('admin.learning.alumni-jobs') }}" class="text-sm font-bold text-purple-800 hover:text-purple-950">Back to Job Board management</a>
    </div>
@endif

<header class="border-b border-slate-200 pb-6">
    @unless ($isAdminPreview ?? false)
        <p class="dashboard-section-kicker">MCARE graduate services</p>
    @endunless
    <div class="{{ ($isAdminPreview ?? false) ? '' : 'mt-3 ' }}flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            @unless ($isAdminPreview ?? false)
                <h1 class="text-2xl font-black text-slate-950 sm:text-3xl">Alumni Career Hub</h1>
            @endunless
            <p class="{{ ($isAdminPreview ?? false) ? '' : 'mt-2 ' }}text-sm text-slate-600">Privacy-reviewed caregiving opportunities for MCARE graduates.</p>
        </div>
        @unless ($isAdminPreview ?? false)
            <a href="{{ route('notifications.index') }}" class="secondary-action inline-flex w-full items-center gap-3 self-start sm:w-auto lg:self-auto">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-700"><x-dashboard-icon name="bell" class="h-4 w-4" /></span>
                <span><span class="block text-xs font-bold uppercase text-slate-500">Unread updates</span><span class="block text-lg font-black text-slate-950">{{ $unreadNotifications }}</span></span>
            </a>
        @endunless
    </div>
</header>

@unless ($isAdminPreview ?? false)
    <section class="dashboard-panel flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between" aria-labelledby="availability-title" data-availability-state="{{ $alumniProfile->is_available_for_duty ? 'available' : 'unavailable' }}">
        <div class="flex items-start gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $alumniProfile->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                <x-dashboard-icon :name="$alumniProfile->is_available_for_duty ? 'circle-check' : 'circle-minus'" class="h-5 w-5" />
            </span>
            <div>
                <p class="dashboard-section-kicker">Caregiver availability</p>
                <h2 id="availability-title" class="mt-1 text-xl font-black text-slate-950">{{ $alumniProfile->is_available_for_duty ? 'Available for Duty' : 'Currently unavailable' }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $alumniProfile->availability_updated_at ? 'Updated '.$alumniProfile->availability_updated_at->diffForHumans() : 'Set your current duty status.' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('trainee.career-hub.availability') }}" data-confirm="Update your caregiver availability?">
            @csrf @method('PATCH')
            <input type="hidden" name="is_available_for_duty" value="{{ $alumniProfile->is_available_for_duty ? '0' : '1' }}">
            <button type="submit" data-action-button class="{{ $alumniProfile->is_available_for_duty ? 'secondary-action' : 'primary-action' }} w-full whitespace-normal sm:w-auto sm:whitespace-nowrap">
                {{ $alumniProfile->is_available_for_duty ? 'Mark unavailable' : 'Mark Available for Duty' }}
            </button>
        </form>
    </section>
@endunless

<section aria-labelledby="career-board-title">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="dashboard-section-kicker">Open careers</p><h2 id="career-board-title" class="mt-1 text-2xl font-black text-slate-950">Career opportunities</h2></div>
        <span class="text-sm font-semibold text-slate-500">{{ $jobs->total() }} available</span>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        @forelse ($jobs as $job)
            @include('trainee.partials.career-posting-card')
        @empty
            <div class="dashboard-panel py-16 text-center lg:col-span-2"><x-dashboard-icon name="briefcase" class="mx-auto h-9 w-9 text-slate-300" /><h3 class="mt-4 text-lg font-bold text-slate-900">No open careers yet</h3><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">MCARE will publish privacy-reviewed careers here.</p></div>
        @endforelse
    </div>

    @if ($jobs->hasPages())<div class="mt-6">{{ $jobs->links() }}</div>@endif
</section>
