<?php

namespace App\Services;

use App\Contracts\OfficialDocumentRenderer;
use App\Models\OfficialDocument;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BrowsershotOfficialDocumentRenderer implements OfficialDocumentRenderer
{
    public function render(OfficialDocument $document): string
    {
        $document->loadMissing([
            'application.batch',
            'application.competencyRecords.unit',
        ]);

        $view = OfficialDocument::templateViewForType($document->type);
        $html = view($view, [
            'document' => $document,
            'application' => $document->application,
            'organization' => config('official_documents.organization'),
            'logoDataUri' => $this->logoDataUri(),
            'cotcTemplateDataUri' => $document->type === OfficialDocument::TYPE_COTC
                ? $this->publicPngDataUri('assets/cotc-official-template.png')
                : null,
        ])->render();

        $browsershot = Browsershot::html($html)
            ->showBackground()
            ->allowFileAccess();

        match ($document->type) {
            OfficialDocument::TYPE_TOR => $browsershot->format('A4')->margins(0, 0, 0, 0),
            OfficialDocument::TYPE_COTC => $browsershot->paperSize(279.4, 215.9, 'mm')->margins(0, 0, 0, 0),
        };

        $this->applyConfiguredBinaries($browsershot);

        try {
            return $browsershot->pdf();
        } catch (ProcessFailedException $exception) {
            throw new RuntimeException(
                'Browser PDF rendering needs Node.js, npm, and Chrome or Edge. On shared hosting set OFFICIAL_DOCUMENT_PDF_ENGINE=fpdf (or auto).',
                0,
                $exception,
            );
        }
    }

    public static function environmentIsReady(): bool
    {
        return self::resolveExistingBinary(config('official_documents.browsershot.node_binary'), self::nodeCandidates()) !== null
            && self::resolveExistingBinary(config('official_documents.browsershot.npm_binary'), self::npmCandidates()) !== null
            && self::resolveChromePath() !== null;
    }

    private function applyConfiguredBinaries(Browsershot $browsershot): void
    {
        $node = self::resolveExistingBinary(config('official_documents.browsershot.node_binary'), self::nodeCandidates());
        if ($node !== null) {
            $browsershot->setNodeBinary($node);
        }

        $npm = self::resolveExistingBinary(config('official_documents.browsershot.npm_binary'), self::npmCandidates());
        if ($npm !== null) {
            $browsershot->setNpmBinary($npm);
        }

        $chromePath = self::resolveChromePath();
        if ($chromePath !== null) {
            $browsershot->setChromePath($chromePath);
        }
    }

    public static function resolveChromePath(): ?string
    {
        $configured = config('official_documents.browsershot.chrome_path');
        if (filled($configured)) {
            return self::usableBinary((string) $configured);
        }

        foreach (self::chromeCandidates() as $candidate) {
            $resolved = self::usableBinary($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $candidates
     */
    private static function resolveExistingBinary(mixed $configured, array $candidates): ?string
    {
        if (filled($configured)) {
            return self::usableBinary((string) $configured);
        }

        foreach ($candidates as $candidate) {
            $resolved = self::usableBinary($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    private static function usableBinary(string $path): ?string
    {
        $path = trim($path);

        return $path !== '' && is_file($path) ? $path : null;
    }

    /** @return list<string> */
    private static function nodeCandidates(): array
    {
        $home = rtrim((string) (getenv('HOME') ?: ($_SERVER['HOME'] ?? '')), '/\\');

        return array_values(array_filter([
            'C:\\Program Files\\nodejs\\node.exe',
            '/usr/bin/node',
            '/usr/local/bin/node',
            '/opt/homebrew/bin/node',
            ...($home !== '' ? glob($home.'/nodevenv/*/bin/node') ?: [] : []),
            ...($home !== '' ? glob($home.'/.nvm/versions/node/*/bin/node') ?: [] : []),
        ]));
    }

    /** @return list<string> */
    private static function npmCandidates(): array
    {
        $home = rtrim((string) (getenv('HOME') ?: ($_SERVER['HOME'] ?? '')), '/\\');

        return array_values(array_filter([
            'C:\\Program Files\\nodejs\\npm.cmd',
            '/usr/bin/npm',
            '/usr/local/bin/npm',
            '/opt/homebrew/bin/npm',
            ...($home !== '' ? glob($home.'/nodevenv/*/bin/npm') ?: [] : []),
            ...($home !== '' ? glob($home.'/.nvm/versions/node/*/bin/npm') ?: [] : []),
        ]));
    }

    /** @return list<string> */
    private static function chromeCandidates(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
                'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
                'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            ];
        }

        return [
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/usr/bin/microsoft-edge',
            '/opt/google/chrome/chrome',
        ];
    }

    private function logoDataUri(): string
    {
        return $this->publicPngDataUri('assets/official-logo.png');
    }

    private function publicPngDataUri(string $relativePath): string
    {
        $path = public_path($relativePath);

        if (! is_file($path)) {
            throw new RuntimeException("Official document asset is missing: {$relativePath}");
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }
}
