<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class StaffVisiblePhoto
{
    /** @return array{path: string, mime: string, filename: string}|null */
    public function locate(?User $user, ?EnrollmentApplication $application = null): ?array
    {
        foreach ($this->candidates($user, $application) as $candidate) {
            $resolved = $this->resolve($candidate['disk'], $candidate['path']);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /** @return list<array{disk: string, path: string}> */
    private function candidates(?User $user, ?EnrollmentApplication $application): array
    {
        $candidates = [];

        // The trainee's own profile photo is the freshest, most deliberate
        // choice. Prefer it so a photo updated in Account Settings shows in
        // every admin surface right away, instead of forever showing the old
        // enrollment ID picture.
        if ($user && filled($user->profile_photo_path)) {
            $candidates[] = ['disk' => 'public', 'path' => (string) $user->profile_photo_path];
            $candidates[] = ['disk' => 'local', 'path' => (string) $user->profile_photo_path];
        }

        if (filled($application?->id_photo_path)) {
            $candidates[] = ['disk' => 'local', 'path' => (string) $application->id_photo_path];
        }

        if ($user) {
            $latestPhoto = $user->enrollmentApplication()
                ->whereNotNull('id_photo_path')
                ->where('id_photo_path', '!=', '')
                ->latest('id')
                ->value('id_photo_path');

            if (filled($latestPhoto)) {
                $candidates[] = ['disk' => 'local', 'path' => (string) $latestPhoto];
            }
        }

        $unique = [];
        foreach ($candidates as $candidate) {
            $key = $candidate['disk'].'|'.$candidate['path'];
            $unique[$key] = $candidate;
        }

        return array_values($unique);
    }

    /** @return array{path: string, mime: string, filename: string}|null */
    private function resolve(string $disk, string $path): ?array
    {
        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk($disk);
        $normalized = $this->normalize($path);

        foreach (array_unique(array_filter([$normalized, $path])) as $candidate) {
            if ($candidate === '' || str_contains($candidate, '..') || ! $filesystem->exists($candidate)) {
                continue;
            }

            $absolute = $filesystem->path($candidate);
            if (! is_file($absolute)) {
                continue;
            }

            $mime = $filesystem->mimeType($candidate) ?: (mime_content_type($absolute) ?: '');
            $extension = strtolower((string) pathinfo($candidate, PATHINFO_EXTENSION));
            $isImage = str_starts_with((string) $mime, 'image/')
                || in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);

            if (! $isImage) {
                continue;
            }

            return [
                'path' => $absolute,
                'mime' => str_starts_with((string) $mime, 'image/')
                    ? $mime
                    : 'image/'.($extension === 'jpg' ? 'jpeg' : ($extension !== '' ? $extension : 'jpeg')),
                'filename' => basename($candidate),
            ];
        }

        return null;
    }

    private function normalize(string $path): string
    {
        $relative = ltrim(str_replace('\\', '/', trim($path)), '/');

        return preg_replace('#^(?:storage/app/private/|app/private/|private/|storage/app/public/|storage/)#', '', $relative) ?? $relative;
    }
}
