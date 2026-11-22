<?php

namespace App\Services\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class AssetSecurityService
{
    public const DISK = 'local';

    public const STORAGE_PATH = 'document-studio/assets';

    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024; // 5 MB

    public const MAX_PDF_BYTES = 15 * 1024 * 1024; // 15 MB

    public const MAX_SVG_BYTES = 2 * 1024 * 1024; // 2 MB

    public const MAX_DOCX_BYTES = 10 * 1024 * 1024; // 10 MB

    public const MAX_IMAGE_WIDTH = 4096;

    public const MAX_IMAGE_HEIGHT = 4096;

    /**
     * Store and validate an uploaded document asset securely.
     */
    public function storeUploadedFile(
        UploadedFile $file,
        string $type,
        ?int $templateId = null,
        ?User $user = null,
    ): DocumentAsset {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is corrupt or failed upload.',
            ]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = $file->getMimeType() ?: '';
        $size = $file->getSize() ?: 0;
        $sha256 = hash_file('sha256', $file->getRealPath());

        $dimensions = null;

        match ($type) {
            DocumentAsset::TYPE_PDF_BASE => $this->validatePdfUpload($file, $extension, $mime, $size),
            DocumentAsset::TYPE_IMAGE, DocumentAsset::TYPE_LOGO, DocumentAsset::TYPE_SIGNATURE => $dimensions = $this->validateImageUpload($file, $extension, $mime, $size),
            default => throw ValidationException::withMessages(['type' => 'Unsupported asset type: '.$type]),
        };

        // If SVG image, sanitize XML content before persisting
        $contents = file_get_contents($file->getRealPath());
        if ($mime === 'image/webp') {
            if (! function_exists('imagecreatefromwebp')) {
                throw ValidationException::withMessages(['file' => 'WebP conversion is unavailable. Use PNG or JPG.']);
            }
            $image = imagecreatefromwebp($file->getRealPath());
            if (! $image) {
                throw ValidationException::withMessages(['file' => 'The WebP image could not be decoded.']);
            }
            ob_start();
            imagepng($image);
            $contents = ob_get_clean();
            imagedestroy($image);
            if (! is_string($contents) || strlen($contents) > self::MAX_IMAGE_BYTES) {
                throw ValidationException::withMessages(['file' => 'The converted image exceeds the 5 MB limit.']);
            }
            $extension = 'png';
            $mime = 'image/png';
            $sha256 = hash('sha256', $contents);
            $size = strlen($contents);
        }
        if ($extension === 'svg') {
            $contents = $this->sanitizeSvg($contents);
            $mime = 'image/svg+xml';
            $sha256 = hash('sha256', $contents);
            $size = strlen($contents);
        }

        $storedFilename = Str::uuid()->toString().'.'.$extension;
        $storedPath = self::STORAGE_PATH.'/'.$storedFilename;

        if (! Storage::disk(self::DISK)->put($storedPath, $contents)) {
            throw ValidationException::withMessages(['file' => 'The uploaded asset could not be stored.']);
        }

        try {
            return DocumentAsset::create([
                'document_template_id' => $templateId,
                'name' => Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 150, ''),
                'type' => $type,
                'disk' => self::DISK,
                'file_path' => $storedPath,
                'mime_type' => $mime,
                'file_size' => $size,
                'dimensions' => $dimensions,
                'sha256' => $sha256,
                'is_system' => false,
                'uploaded_by_id' => $user?->id,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($storedPath);
            throw $exception;
        }
    }

    private function validatePdfUpload(UploadedFile $file, string $extension, string $mime, int $size): void
    {
        if ($size > self::MAX_PDF_BYTES) {
            throw ValidationException::withMessages([
                'file' => 'The PDF base file exceeds the 15 MB limit.',
            ]);
        }

        if ($extension !== 'pdf' || ! in_array($mime, ['application/pdf', 'application/x-pdf'], true)) {
            throw ValidationException::withMessages([
                'file' => 'Only valid PDF files are accepted for base templates.',
            ]);
        }

        $handle = fopen($file->getRealPath(), 'rb');
        $header = fread($handle, 1024);
        fclose($handle);

        if (! str_starts_with($header, '%PDF-')) {
            throw ValidationException::withMessages([
                'file' => 'The file is not a valid PDF header (%PDF-).',
            ]);
        }

        // Basic scan for suspicious launch/javascript action tags
        $fullContent = file_get_contents($file->getRealPath());
        if (preg_match('/\/JavaScript\b|\/JS\b|\/Launch\b|\/EmbeddedFiles\b/i', $fullContent)) {
            throw ValidationException::withMessages([
                'file' => 'PDF files with embedded scripts, actions, or executable payloads are rejected.',
            ]);
        }
        try {
            $process = NodeProcess::make([base_path('scripts/document-studio-validate-pdf.mjs'), $file->getRealPath()], 20, 256);
            $process->mustRun();
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'The PDF is malformed, encrypted, oversized, or contains unsupported active content.']);
        }
    }

    private function validateImageUpload(UploadedFile $file, string $extension, string $mime, int $size): array
    {
        if ($extension === 'svg') {
            if (! in_array($mime, ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'], true)) {
                throw ValidationException::withMessages(['file' => 'The file contents do not match an SVG image.']);
            }
            if ($size > self::MAX_SVG_BYTES) {
                throw ValidationException::withMessages(['file' => 'SVG file exceeds the 2 MB limit.']);
            }

            return ['width' => 800, 'height' => 600, 'is_vector' => true];
        }

        if ($size > self::MAX_IMAGE_BYTES) {
            throw ValidationException::withMessages(['file' => 'Image exceeds the 5 MB limit.']);
        }

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp'];
        $allowedMimes = ['image/png', 'image/jpeg', 'image/webp'];

        if (! in_array($extension, $allowedExtensions, true) || ! in_array($mime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'file' => 'Accepted image formats are PNG, JPG, JPEG, WEBP, and SVG.',
            ]);
        }

        $imageInfo = @getimagesize($file->getRealPath());
        if (! $imageInfo) {
            throw ValidationException::withMessages(['file' => 'Invalid or unreadable image file.']);
        }

        [$width, $height] = $imageInfo;

        if ($width > self::MAX_IMAGE_WIDTH || $height > self::MAX_IMAGE_HEIGHT) {
            throw ValidationException::withMessages([
                'file' => sprintf('Image dimensions (%dx%d) exceed maximum %dx%d px.', $width, $height, self::MAX_IMAGE_WIDTH, self::MAX_IMAGE_HEIGHT),
            ]);
        }

        return ['width' => $width, 'height' => $height];
    }

    /**
     * Strictly sanitize SVG XML to neutralize XSS, external entity expansions, scripts, and event handlers.
     */
    public function sanitizeSvg(string $svg): string
    {
        // 1. Strictly reject external DTD and entity declarations (no entity expansions)
        if (preg_match('/<!ENTITY|<!DOCTYPE/i', $svg)) {
            throw ValidationException::withMessages(['file' => 'SVG must not contain DTD or entity declarations.']);
        }

        // 2. Parse using DOMDocument with entity substitution disabled
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw ValidationException::withMessages(['file' => 'Malformed or invalid SVG XML document.']);
        }

        $root = $dom->documentElement;
        if (! $root) {
            throw ValidationException::withMessages(['file' => 'Empty SVG XML document.']);
        }

        $rootLocalName = strtolower($root->localName ?: $root->tagName);
        if ($rootLocalName !== 'svg' || $root->namespaceURI !== 'http://www.w3.org/2000/svg') {
            throw ValidationException::withMessages(['file' => 'SVG root element must be <svg>.']);
        }

        // 3. Allowed SVG element names (localName lowercased)
        $allowedElements = [
            'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
            'text', 'tspan', 'image', 'defs', 'lineargradient', 'radialgradient', 'stop',
            'clippath', 'pattern', 'mask', 'use', 'symbol', 'marker', 'title', 'desc',
        ];

        // Sanitize attributes on the root element itself (e.g. onload, onclick, style)
        $this->sanitizeElementAttributes($root);

        // Sanitize descendants recursively
        $this->sanitizeSvgNode($root, $allowedElements);

        $saved = $dom->saveXML($root);
        if (! $saved) {
            throw ValidationException::withMessages(['file' => 'Failed to serialize sanitized SVG.']);
        }

        return $saved;
    }

    private function sanitizeElementAttributes(DOMElement $element): void
    {
        $attrsToRemove = [];
        foreach ($element->attributes as $attr) {
            $attrName = strtolower($attr->name);
            $attrVal = $attr->value;
            if (strtolower($attr->localName) === 'base') {
                $attrsToRemove[] = $attr->name;
            }
            if (str_contains($attrVal, '\\') || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $attrVal)) {
                $attrsToRemove[] = $attr->name;
            }

            // Strip any on* event handler (onload, onerror, onclick, etc.)
            if (str_starts_with($attrName, 'on')) {
                $attrsToRemove[] = $attr->name;

                continue;
            }

            // Check URL/link attributes (href, xlink:href, src, etc.)
            if (str_contains($attrName, 'href') || $attrName === 'src') {
                $decoded = html_entity_decode($attrVal, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $decoded = rawurldecode($decoded);
                $normalized = preg_replace('/[\x00-\x20\s]+/', '', strtolower($decoded));

                if (str_contains($normalized, 'javascript:') || str_contains($normalized, 'vbscript:') || str_contains($normalized, 'data:text/html')) {
                    $attrsToRemove[] = $attr->name;

                    continue;
                }

                // Only allow local fragment links (#id) or data image URIs
                if (! preg_match('/^#[A-Za-z_][\w.-]*$/D', $decoded)) {
                    $attrsToRemove[] = $attr->name;

                    continue;
                }
            }

            // Sanitize style attributes (strip expression, javascript, url)
            if ($attrName === 'style') {
                $lowerStyle = strtolower($attrVal);
                if (str_contains($lowerStyle, 'expression') || str_contains($lowerStyle, 'javascript:') || str_contains($lowerStyle, 'url(') || str_contains($lowerStyle, '@import')) {
                    $attrsToRemove[] = $attr->name;

                    continue;
                }
            }
            // Resource-bearing SVG attributes accept local fragment references only.
            if (preg_match('/url\s*\(/i', $attrVal)
                && ! preg_match('/^url\(\s*#[A-Za-z_][\w.-]*\s*\)$/D', $attrVal)) {
                $attrsToRemove[] = $attr->name;
            }
            if ($attr->namespaceURI && ! in_array($attr->namespaceURI, [
                'http://www.w3.org/2000/xmlns/', 'http://www.w3.org/1999/xlink', 'http://www.w3.org/XML/1998/namespace',
            ], true)) {
                $attrsToRemove[] = $attr->name;
            }
        }

        foreach ($attrsToRemove as $name) {
            $element->removeAttribute($name);
        }
    }

    private function sanitizeSvgNode(DOMElement $element, array $allowedElements): void
    {
        $toRemove = [];

        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMProcessingInstruction) {
                $toRemove[] = $child;

                continue;
            }
            if ($child instanceof DOMElement) {
                $localName = strtolower($child->localName ?: $child->tagName);

                // If element is not in allowlist (e.g. script, foreignObject, object, etc.), mark for removal
                if ($child->namespaceURI !== 'http://www.w3.org/2000/svg'
                    || ! in_array($localName, $allowedElements, true)) {
                    $toRemove[] = $child;

                    continue;
                }

                $this->sanitizeElementAttributes($child);

                // Recursively sanitize children
                $this->sanitizeSvgNode($child, $allowedElements);
            }
        }

        foreach ($toRemove as $node) {
            $element->removeChild($node);
        }
    }

    /**
     * Validate DOCX archive container security (no path traversal, reasonable uncompressed ratio, no macros).
     */
    public function validateDocxUpload(UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension !== 'docx') {
            throw ValidationException::withMessages(['file' => 'Only .docx files are supported. Macro-enabled .docm files are rejected.']);
        }

        if ($file->getSize() > self::MAX_DOCX_BYTES) {
            throw ValidationException::withMessages(['file' => 'DOCX file exceeds the 10 MB limit.']);
        }

        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['file' => 'The uploaded file is not a valid DOCX zip archive.']);
        }

        $hasWordDoc = false;
        if ($zip->numFiles > 2000) {
            $zip->close();
            throw ValidationException::withMessages(['file' => 'DOCX contains too many archive entries.']);
        }
        $seen = [];
        $totalUncompressed = 0;
        $totalCompressed = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'] ?? '';

            // Guard against Zip Slip path traversal
            if (isset($seen[$name]) || preg_match('~(^/|\\\\|:|(?:^|/)\.\.(?:/|$))~', $name)) {
                $zip->close();
                throw ValidationException::withMessages(['file' => 'Malicious zip entry detected in DOCX archive.']);
            }
            $seen[$name] = true;
            $uncompressed = (int) ($stat['size'] ?? 0);
            $compressed = (int) ($stat['comp_size'] ?? 0);
            $totalUncompressed += $uncompressed;
            $totalCompressed += max(1, $compressed);
            if ($totalUncompressed > 50 * 1024 * 1024
                || ($totalUncompressed > 512 * 1024 && $totalUncompressed / $totalCompressed > 100)) {
                $zip->close();
                throw ValidationException::withMessages(['file' => 'DOCX archive exceeds expansion safety limits.']);
            }
            if ((int) ($stat['size'] ?? 0) > 10 * 1024 * 1024) {
                $zip->close();
                throw ValidationException::withMessages(['file' => 'DOCX entry exceeds the safety limit.']);
            }
            if (preg_match('/\.(xml|rels)$/i', $name)) {
                $xml = $zip->getFromIndex($i);
                if ($xml === false || preg_match('/<!ENTITY|<!DOCTYPE/i', $xml)
                    || (str_ends_with($name, '.rels') && preg_match('/TargetMode\s*=\s*["\x27]External["\x27]/i', $xml))) {
                    $zip->close();
                    throw ValidationException::withMessages(['file' => 'DOCX contains unsafe XML or external relationships.']);
                }
            }

            // Reject macro files
            if (str_ends_with(strtolower($name), '.bin') || str_contains(strtolower($name), 'vba')) {
                $zip->close();
                throw ValidationException::withMessages(['file' => 'Macro-enabled DOCX files are rejected for security.']);
            }

            if ($name === 'word/document.xml') {
                $hasWordDoc = true;
                $docXml = $zip->getFromIndex($i);
                if ($docXml && preg_match('/<!ENTITY|<!DOCTYPE/i', $docXml)) {
                    $zip->close();
                    throw ValidationException::withMessages(['file' => 'DOCX document.xml contains unsafe DTD/entity declarations.']);
                }
            }

        }

        $zip->close();

        if (! $hasWordDoc) {
            throw ValidationException::withMessages(['file' => 'Invalid DOCX structure: missing word/document.xml.']);
        }

        // Guard against zip bomb (e.g. max 50MB uncompressed)
        if ($totalUncompressed > 50 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'DOCX uncompressed size exceeds maximum safety limits.']);
        }

        // Guard against zip bomb compression ratio (e.g. max 100:1 ratio)
        $ratio = $totalUncompressed / max(1, $totalCompressed);
        if ($ratio > 100.0 && $totalUncompressed > 1024 * 512) {
            throw ValidationException::withMessages([
                'file' => sprintf('DOCX compression ratio (%.1f:1) exceeds maximum 100:1 safety limit.', $ratio),
            ]);
        }
    }

    /**
     * Recursively validate editor state against prototype pollution keys, raw server paths, and unbounded nesting.
     */
    public function sanitizeEditorState(mixed $data, int $depth = 0): mixed
    {
        if ($depth > 30) {
            throw ValidationException::withMessages(['draft_state' => 'Editor state nesting depth exceeded maximum limit.']);
        }

        if (is_array($data)) {
            if (count($data) > 2000 || ($depth === 0 && strlen(json_encode($data)) > 5 * 1024 * 1024)) {
                throw ValidationException::withMessages(['draft_state' => 'Editor state exceeds the safety limit.']);
            }
            $clean = [];
            foreach ($data as $key => $value) {
                $strKey = (string) $key;

                // Prototype pollution keys
                if ($strKey === '__proto__' || $strKey === 'constructor' || $strKey === 'prototype') {
                    continue;
                }

                $lowerKey = strtolower($strKey);

                // Disallow client-provided server file paths and internal locators
                if (in_array($lowerKey, ['basepdfpath', 'base_pdf_path', 'verifiedpath', 'verified_path', 'filepath', 'file_path', 'sourcepath', 'source_path', 'realpath', 'real_path', 'localpath', 'local_path', 'absolutepath', 'absolute_path'], true)
                    || str_contains($lowerKey, 'verifiedpath')
                    || str_contains($lowerKey, 'verified_path')
                    || (str_ends_with($lowerKey, 'path') && ! in_array($lowerKey, ['path', 'clip_path', 'svg_path'], true))) {
                    throw ValidationException::withMessages([
                        'draft_state' => 'Draft state must not contain server file paths or verified paths. Use scoped asset IDs instead.',
                    ]);
                }

                // If string value looks like a server filesystem path, reject it
                if (is_string($value) && (in_array($lowerKey, ['basepdfpath', 'source', 'src', 'file', 'verifiedpath', 'url'], true) || str_contains($lowerKey, 'path'))) {
                    if ($this->assetIdFromUrl($value) !== null) {
                        $clean[$strKey] = $value;

                        continue;
                    }
                    if (preg_match('/^(?:\/|[A-Za-z]:\\\\|[A-Za-z]:\/|\\\\|\.\.\/|\.\.\\\\)/', $value) || str_contains($value, ':\\') || str_contains($value, ':/')) {
                        throw ValidationException::withMessages([
                            'draft_state' => 'Client state must not reference local server file paths.',
                        ]);
                    }
                }

                if ($lowerKey === 'content' && $value === null && in_array($data['type'] ?? null, ['text', 'line', 'rectangle', 'ellipse'], true)) {
                    $clean[$strKey] = '';

                    continue;
                }
                if ($lowerKey === 'content' && ($data['type'] ?? null) === 'svg' && is_string($value)) {
                    $clean[$strKey] = $this->sanitizeSvg($value);

                    continue;
                }
                if ($lowerKey === 'content' && ($data['type'] ?? null) === 'text' && is_string($value)) {
                    $clean[$strKey] = strip_tags($value);

                    continue;
                }
                // Sanitize HTML in rk_word state (html, content, body)
                if (in_array($lowerKey, ['html', 'content', 'body'], true) && is_string($value)) {
                    $clean[$strKey] = $this->sanitizeHtml($value);

                    continue;
                }

                $clean[$strKey] = $this->sanitizeEditorState($value, $depth + 1);
            }

            return $clean;
        }

        return $data;
    }

    /**
     * Sanitize HTML content to strip active scripts, on* event handlers, and unsafe URLs.
     */
    public function sanitizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="utf-8"?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return strip_tags($html);
        }

        $container = $dom->getElementsByTagName('div')->item(0);
        if (! $container) {
            return strip_tags($html);
        }

        $this->sanitizeHtmlNode($container);

        $cleanHtml = '';
        foreach ($container->childNodes as $child) {
            $cleanHtml .= $dom->saveHTML($child);
        }

        return $cleanHtml;
    }

    private function sanitizeHtmlNode(DOMNode $node): void
    {
        $disallowedTags = ['script', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'link', 'style', 'meta', 'applet', 'svg', 'math'];

        $toRemove = [];
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (in_array($tag, $disallowedTags, true)) {
                    $toRemove[] = $child;

                    continue;
                }

                // Sanitize attributes
                $attrsToRemove = [];
                foreach ($child->attributes as $attr) {
                    $attrName = strtolower($attr->name);
                    $attrVal = strtolower(trim($attr->value));
                    if (! in_array($attrName, ['style', 'colspan', 'rowspan', 'border', 'width', 'height', 'src', 'alt', 'href', 'data-type', 'data-text-align'], true)) {
                        $attrsToRemove[] = $attr->name;

                        continue;
                    }
                    if ($attrName === 'style') {
                        $safe = [];
                        foreach (explode(';', $attr->value) as $declaration) {
                            if (preg_match('/^\s*(font-family|font-size|font-weight|font-style|text-decoration|text-align|color|background-color|border|border-collapse|padding|margin|width|line-height)\s*:\s*([a-zA-Z0-9#(),.%\s\x27"-]+)$/D', $declaration, $match)) {
                                $safe[] = $match[1].':'.$match[2];
                            }
                        }
                        $child->setAttribute('style', implode(';', $safe));
                    }

                    // Strip any on* event handler (e.g. onerror, onload, onclick)
                    if (str_starts_with($attrName, 'on')) {
                        $attrsToRemove[] = $attr->name;

                        continue;
                    }

                    // Strip javascript: or data: in href/src
                    if (in_array($attrName, ['href', 'src', 'action', 'formaction'], true)) {
                        if ($attrName === 'src' && $this->assetIdFromUrl($attr->value) === null) {
                            $attrsToRemove[] = $attr->name;
                        }
                        $decoded = html_entity_decode($attrVal, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $normalized = preg_replace('/[\x00-\x20\s]+/', '', $decoded);
                        if (str_starts_with($normalized, 'javascript:') || str_starts_with($normalized, 'vbscript:') || str_starts_with($normalized, 'data:text/html')) {
                            $attrsToRemove[] = $attr->name;
                        }
                    }
                }

                foreach ($attrsToRemove as $attrName) {
                    $child->removeAttribute($attrName);
                }

                // Recurse child nodes
                $this->sanitizeHtmlNode($child);
            }
        }

        foreach ($toRemove as $el) {
            $node->removeChild($el);
        }
    }

    public function assetIdFromUrl(string $url): ?int
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('~^/admin/document-studio/assets/(\d+)$~D', $path, $match)) {
            return null;
        }
        $host = parse_url($url, PHP_URL_HOST);

        return $host && $host !== parse_url(config('app.url'), PHP_URL_HOST) ? null : (int) $match[1];
    }

    /** Exact references, including Word image URLs; never search JSON by substring. */
    public function assetIds(array $state): array
    {
        $ids = [];
        $walk = function ($data) use (&$walk, &$ids): void {
            foreach ((array) $data as $key => $value) {
                if (in_array($key, ['asset_id', 'assetId', 'base_pdf_asset_id', 'background_asset_id'], true) && is_numeric($value)) {
                    $ids[] = (int) $value;
                } elseif (is_array($value)) {
                    if ($key !== '_asset_integrity') {
                        $walk($value);
                    }
                } elseif (is_string($value)) {
                    if ($id = $this->assetIdFromUrl($value)) {
                        $ids[] = $id;
                    }
                    if (in_array($key, ['html', 'content', 'body'], true)
                        && preg_match_all('~(?:src=["\x27])([^"\x27]+)~i', $value, $matches)) {
                        foreach ($matches[1] as $url) {
                            if ($id = $this->assetIdFromUrl(html_entity_decode($url))) {
                                $ids[] = $id;
                            }
                        }
                    }
                }
            }
        };
        $walk($state);
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    public function assetIntegrity(array $state, int $templateId): array
    {
        $hashes = [];
        foreach ($this->assetIds($state) as $id) {
            $asset = DocumentAsset::query()->where('document_template_id', $templateId)->find($id);
            if (! $asset || $asset->type === DocumentAsset::TYPE_PDF_SOURCE || ! $asset->existsOnDisk()
                || ! hash_equals($asset->sha256, hash('sha256', $asset->fileContents()))) {
                throw ValidationException::withMessages(['draft_state' => 'An asset is missing, changed, or belongs to another template.']);
            }
            $hashes[(string) $id] = $asset->sha256;
        }

        return $hashes;
    }
}
