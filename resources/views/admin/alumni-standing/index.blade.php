@extends('admin.layouts.app', ['title' => 'Alumni standing | MCARE Admin'])

@section('content')
    <div class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Alumni standing</p>
            <h1 class="dashboard-section-title mt-2 text-2xl">Junior and senior alumni</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">This list combines alumni claims and trainees who graduated in the system. Approved claims and graduates start as junior alumni. Promote someone to senior alumni only when you decide they should hold that standing.</p>
        </header>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Record</th>
                        <th class="px-4 py-3">Alumni standing</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($people as $person)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $person['name'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $person['email'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $person['source'] }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $person['detail'] }}</td>
                            <td class="px-4 py-3">
                                @if ($person['rank'])
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $person['rank'] === \App\Models\AlumniProfile::RANK_SENIOR ? 'bg-purple-50 text-purple-800 ring-purple-100' : 'bg-slate-50 text-slate-700 ring-slate-200' }}">{{ \App\Models\AlumniProfile::ranks()[$person['rank']] }}</span>
                                        @if ($person['promote_url'])
                                            <form method="POST" action="{{ $person['promote_url'] }}" data-confirm="Promote {{ $person['name'] }} to senior alumni?">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex items-center rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">Promote to senior</button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">{{ $person['status'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No alumni claims or training graduates yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($people->hasPages())
                <div class="border-t border-slate-100 px-4 py-4">{{ $people->links() }}</div>
            @endif
        </div>
    </div>
@endsection
