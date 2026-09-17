<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replace enrollment documents | MCARE</title>
    <x-site-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <x-public-official-header
        masthead-aside="Caregiving NC II · Document revision"
        nav-label="Document revision"
        :secondary-href="route('landing')"
        secondary-label="Public site"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Official TESDA NC II document revision</p>
                <h1>Replace documents that need replacement</h1>
                <p class="enrollment-lede">
                    MCARE administration asked you to re-upload only the files listed below. Accepted documents are not shown and cannot be changed from this page.
                </p>
                <dl class="enrollment-status-row">
                    <div>
                        <dt>Applicant</dt>
                        <dd>{{ trim($application->first_name.' '.$application->last_name) ?: 'Enrollment applicant' }}</dd>
                    </div>
                    <div>
                        <dt>Enrollment number</dt>
                        <dd>{{ $application->enrollment_number }}</dd>
                    </div>
                    <div>
                        <dt>Files to replace</dt>
                        <dd>{{ count($documents) }} {{ \Illuminate\Support\Str::plural('document', count($documents)) }}</dd>
                    </div>
                </dl>
            </header>

            <section class="enrollment-form-body">
                @if (session('saved'))
                    <div class="mb-6 border border-emerald-200 bg-emerald-50 p-4" role="status">
                        <p class="text-sm font-semibold leading-6 text-emerald-900">{{ session('saved') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 border border-red-200 bg-red-50 p-4" role="alert">
                        <p class="text-sm font-bold text-red-900">Please upload a replacement for every file listed below.</p>
                    </div>
                @endif

                @if ($documents === [])
                    @unless (session('saved'))
                        <div class="border border-slate-200 bg-slate-50 p-5">
                            <p class="text-sm font-semibold leading-6 text-slate-700">There are no documents currently marked for replacement. If you already re-uploaded the requested files, MCARE administration will review them.</p>
                        </div>
                    @endunless
                @else
                    <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="enrollment-fields grid grid-cols-1 gap-5 md:grid-cols-2">
                            @foreach ($documents as $document)
                                <div class="enrollment-upload-card">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <label for="{{ $document['input'] }}" class="block text-sm font-bold text-slate-900">{{ $document['label'] }}</label>
                                        <span class="text-[11px] font-black uppercase tracking-wide text-amber-800">
                                            {{ $document['status'] === 'missing' ? 'Missing' : 'Needs replacement' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ $document['description'] }}</p>
                                    @if ($document['note'])
                                        <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold leading-5 text-amber-950">{{ $document['note'] }}</p>
                                    @endif
                                    <div data-upload-zone class="enrollment-upload-zone relative mt-4 rounded-2xl border-2 border-dashed border-purple-200 bg-white px-5 py-7 text-center transition hover:border-purple-400 hover:bg-purple-50/50">
                                        <input id="{{ $document['input'] }}" name="{{ $document['input'] }}" type="file" accept="{{ $document['accept'] }}" required class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                                        <p class="text-sm font-bold text-purple-700">Click to upload or drag and drop</p>
                                        <p data-upload-name class="mt-1 text-xs font-semibold text-slate-500">No file selected</p>
                                    </div>
                                    @error($document['input'])
                                        <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-purple-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-purple-800">
                                Submit replacement files
                            </button>
                            <p class="text-xs leading-5 text-slate-500">PDF, JPG, or PNG. ID photo and signature accept JPG or PNG only. Maximum 5MB per file.</p>
                        </div>
                    </form>
                @endif
            </section>
        </article>
    </main>

    <script>
        document.querySelectorAll('[data-upload-zone] input[type="file"]').forEach((input) => {
            input.addEventListener('change', () => {
                const name = input.closest('[data-upload-zone]')?.querySelector('[data-upload-name]');
                if (name) {
                    name.textContent = input.files?.[0]?.name || 'No file selected';
                }
            });
        });
    </script>
</body>
</html>
