@extends('admin.layouts.app')

@section('title', 'Version History — '.$template->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-gray-200 dark:border-gray-800">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.document-studio.edit', $template) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <x-dashboard-icon name="arrow-left" class="w-5 h-5" />
                </a>
                <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">Version History</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    {{ strtoupper($template->document_kind) }}
                </span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Template: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $template->name }}</span> ({{ $template->engineLabel() }})
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.document-studio.edit', $template) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                <x-dashboard-icon name="pencil" class="w-4 h-4" />
                Return to Studio
            </a>
        </div>
    </div>

    <!-- Versions List -->
    <div class="bg-white dark:bg-gray-850 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Published Immutable Versions</h2>
            <span class="text-xs text-gray-500 dark:text-gray-400">Total: {{ $versions->count() }} versions</span>
        </div>

        @if($versions->isEmpty())
            <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                No versions published yet. Publish your working draft in the Studio to create Version 1.
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach($versions as $ver)
                    <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 @if($ver->isCurrentActive()) bg-indigo-50/20 dark:bg-indigo-950/20 @endif">
                        <div class="space-y-1.5 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-base font-bold text-gray-900 dark:text-white">
                                    Version {{ $ver->version_number }}
                                </span>
                                @if($ver->isCurrentActive())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <x-dashboard-icon name="circle-check" class="w-3 h-3" />
                                        Active in Production
                                    </span>
                                @endif
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    schema v{{ $ver->schema_version }}
                                </span>
                            </div>

                            @if($ver->changelog)
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $ver->changelog }}
                                </p>
                            @endif

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400 pt-1">
                                <div>
                                    Published: <span class="text-gray-700 dark:text-gray-300">{{ $ver->created_at->format('M j, Y g:i A') }}</span>
                                    @if($ver->createdBy)
                                        by <span class="font-medium text-gray-700 dark:text-gray-300">{{ $ver->createdBy->name }}</span>
                                    @endif
                                </div>
                                <div class="font-mono">
                                    SHA-256: <span class="text-gray-600 dark:text-gray-400" title="{{ $ver->sha256 }}">{{ substr($ver->sha256, 0, 16) }}...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 self-start md:self-center">
                            @if(! $ver->isCurrentActive())
                                <form method="POST" action="{{ route('admin.document-studio.versions.activate', [$template, $ver]) }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:text-indigo-300 dark:hover:bg-indigo-900 border border-indigo-200 dark:border-indigo-800 transition-colors">
                                        <x-dashboard-icon name="check" class="w-3.5 h-3.5" />
                                        Set as Active
                                    </button>
                                </form>
                            @else
                                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                    Current Active Version
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
