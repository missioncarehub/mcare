@extends('admin.layouts.app')

@section('title', 'Document Studio Setup')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto py-8">
    <div class="rounded-2xl border border-purple-200 bg-white p-6 sm:p-8 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="rounded-xl bg-purple-100 p-3 text-purple-700 shrink-0">
                <x-dashboard-icon name="award" class="w-8 h-8" />
            </div>
            <div class="space-y-3">
                <h2 class="text-xl font-bold text-slate-900">Database Tables Initializing</h2>
                <p class="text-sm text-slate-600 leading-relaxed">
                    The interactive Document Studio requires its database tables (<code class="font-mono text-purple-800 bg-purple-50 px-1.5 py-0.5 rounded">document_templates</code>) to be migrated before first use.
                </p>
                <div class="rounded-xl bg-slate-900 p-4 text-xs font-mono text-emerald-400">
                    <p class="text-slate-400"># Run this command in your Hostinger SSH terminal:</p>
                    <p class="mt-1">php artisan migrate --force</p>
                </div>
                <div class="pt-2 flex flex-wrap gap-3">
                    <a href="{{ route('admin.document-studio.index') }}" class="primary-action inline-flex items-center gap-2">
                        <span>Refresh and Retry Setup</span>
                        <x-dashboard-icon name="arrow-right" class="w-4 h-4" />
                    </a>
                    <a href="{{ route('admin.learning.certificate-templates') }}" class="secondary-action">
                        Back to Certificate Templates
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
