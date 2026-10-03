@extends('admin.layouts.app', ['title' => 'Alumni List | MCARE Admin'])

@section('content')
    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Alumni directory</p>
            <h1 class="dashboard-section-title mt-2 text-2xl">Alumni list</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">All MCARE graduates with Career Hub access, including duty availability, alumni standing, and career activity.</p>
        </header>

        <div class="grid gap-3 sm:grid-cols-3">
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Total alumni</p>
                    <p class="dashboard-stat-value">{{ $stats['total'] }}</p>
                    <p class="dashboard-stat-help">Graduates in the system</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><x-dashboard-icon name="users" class="h-5 w-5" /></span>
            </article>
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Available for duty</p>
                    <p class="dashboard-stat-value">{{ $stats['available'] }}</p>
                    <p class="dashboard-stat-help">Marked available in Career Hub</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><x-dashboard-icon name="circle-check" class="h-5 w-5" /></span>
            </article>
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Senior alumni</p>
                    <p class="dashboard-stat-value">{{ $stats['senior'] }}</p>
                    <p class="dashboard-stat-help">Promoted senior standing</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-700 ring-1 ring-violet-100"><x-dashboard-icon name="award" class="h-5 w-5" /></span>
            </article>
        </div>

        <div class="dashboard-panel">
            <form method="GET" action="{{ route('admin.alumni.index') }}" data-auto-filter class="grid gap-4 md:grid-cols-[minmax(0,1fr)_12rem_auto] md:items-end">
                <div>
                    <label for="alumni-search" class="form-label">Search</label>
                    <input id="alumni-search" name="search" type="search" value="{{ $search }}" placeholder="Name, email, or contact number" class="form-field">
                </div>
                <div>
                    <label for="alumni-availability" class="form-label">Duty status</label>
                    <select id="alumni-availability" name="availability" class="form-field">
                        <option value="">All alumni</option>
                        <option value="available" @selected($availability === 'available')>Available for duty</option>
                        <option value="unavailable" @selected($availability === 'unavailable')>Unavailable</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="primary-action">Filter</button>
                    @if ($search !== '' || $availability !== '')
                        <a href="{{ route('admin.alumni.index') }}" class="secondary-action">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Alumni</th>
                        <th class="px-4 py-3">Contact</th>
                        <th class="px-4 py-3">Standing</th>
                        <th class="px-4 py-3">Duty status</th>
                        <th class="px-4 py-3">Applications</th>
                        <th class="px-4 py-3">Awarded careers</th>
                        <th class="px-4 py-3">Updated</th>
                        <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($alumni as $person)
                        @php
                            $profile = $person->alumniProfile;
                            $contactNumber = $person->contact_number ?: $person->enrollmentApplication?->contact_number;
                        @endphp
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-900">{{ $person->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $person->email }}</p>
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $contactNumber ?: '—' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ ($profile?->rank ?? \App\Models\AlumniProfile::RANK_JUNIOR) === \App\Models\AlumniProfile::RANK_SENIOR ? 'bg-purple-50 text-purple-800 ring-purple-100' : 'bg-slate-50 text-slate-700 ring-slate-200' }}">
                                    {{ \App\Models\AlumniProfile::ranks()[$profile?->rank ?? \App\Models\AlumniProfile::RANK_JUNIOR] }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $profile?->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $profile?->is_available_for_duty ? 'Available for Duty' : 'Unavailable' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 font-semibold text-slate-800">{{ $person->applications_count }}</td>
                            <td class="px-4 py-4 font-semibold text-slate-800">{{ $person->awarded_careers_count }}</td>
                            <td class="px-4 py-4 text-slate-500">{{ $profile?->availability_updated_at?->diffForHumans() ?? 'Not updated' }}</td>
                            <td class="px-4 py-4">
                                <a href="{{ route('admin.alumni.show', $person) }}" class="secondary-action inline-flex h-9 items-center gap-2 text-xs">
                                    <x-dashboard-icon name="eye" class="h-4 w-4" />
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-500">No alumni match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($alumni->hasPages())
                <div class="border-t border-slate-100 px-4 py-4">{{ $alumni->links() }}</div>
            @endif
        </div>
    </section>
@endsection
