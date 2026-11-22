<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credential Verification — MCARE Hub</title>
    <x-site-favicon />
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col justify-center items-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-700 to-indigo-800 p-6 text-white text-center">
            <img src="{{ asset('assets/official-logo.png') }}" alt="MCARE Logo" class="h-12 w-12 mx-auto mb-2 object-contain bg-white/10 rounded-xl p-1">
            <h1 class="text-lg font-bold">Mission Care Training Center</h1>
            <p class="text-xs text-purple-200 uppercase tracking-wider font-semibold">Official Credential Verification</p>
        </div>

        <!-- Verification Body -->
        <div class="p-6 space-y-4">
            @if($isValid)
                <div class="flex items-center gap-3 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900">
                    <div class="h-9 w-9 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0 font-black">
                        ✓
                    </div>
                    <div>
                        <h2 class="text-sm font-bold">Authentic & Verified Document</h2>
                        <p class="text-xs text-emerald-700">Issued by Mission Care Training Center</p>
                    </div>
                </div>

                <div class="space-y-3 pt-2 text-xs">
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500">Document Type</span>
                        <span class="font-semibold text-slate-900 text-right">{{ $typeName }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500">Credential ID</span>
                        <span class="font-mono font-bold text-indigo-700">{{ $documentNumber }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500">Recipient</span>
                        <span class="font-semibold text-slate-900 font-mono">{{ $traineeName }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500">Training Program</span>
                        <span class="font-semibold text-slate-900 text-right">{{ $programName }}</span>
                    </div>
                    @if(!empty($batchName))
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500">Batch</span>
                        <span class="font-semibold text-slate-900">{{ $batchName }}</span>
                    </div>
                    @endif
                    @if(!empty($issuedAt))
                    <div class="flex justify-between border-b border-slate-100 pb-2">
                        <span class="text-slate-500">Issuance Date</span>
                        <span class="font-semibold text-slate-900">{{ $issuedAt }}</span>
                    </div>
                    @endif
                </div>
            @else
                <div class="flex items-center gap-3 p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-900">
                    <div class="h-9 w-9 rounded-full bg-rose-500 text-white flex items-center justify-center shrink-0 font-black">
                        !
                    </div>
                    <div>
                        <h2 class="text-sm font-bold">{{ !empty($isRevoked) ? 'Credential Revoked' : 'Record Not Found' }}</h2>
                        <p class="text-xs text-rose-700">{{ $message ?? 'This document could not be authenticated.' }}</p>
                    </div>
                </div>

                <div class="text-xs text-slate-500 bg-slate-50 p-3 rounded-lg border border-slate-100">
                    <p>Searched Reference: <span class="font-mono font-bold text-slate-700">{{ $documentNumber }}</span></p>
                    <p class="mt-1">If you believe this is in error, contact the registrar at registrar@mcarehub.com.</p>
                </div>
            @endif
        </div>

        <div class="bg-slate-50 p-4 border-t border-slate-100 text-center">
            <a href="{{ route('landing') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                Return to MCARE Homepage
            </a>
        </div>
    </div>
</body>
</html>
