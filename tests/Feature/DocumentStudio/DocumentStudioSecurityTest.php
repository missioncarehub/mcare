<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\User;
use App\Services\DocumentStudio\AssetSecurityService;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentStudioSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RolePermissionMatrix::ensureConfigured();
        Storage::fake('local');
    }

    public function test_asset_security_strips_prototype_pollution_keys(): void
    {
        $service = app(AssetSecurityService::class);

        $hostilePayload = [
            'valid_key' => 'hello',
            '__proto__' => ['polluted' => 'yes'],
            'constructor' => ['prototype' => ['admin' => true]],
            'nested' => [
                'clean' => 123,
                'prototype' => 'bad',
            ],
        ];

        $cleaned = $service->sanitizeEditorState($hostilePayload);

        $this->assertArrayHasKey('valid_key', $cleaned);
        $this->assertArrayNotHasKey('__proto__', $cleaned);
        $this->assertArrayNotHasKey('constructor', $cleaned);
        $this->assertArrayHasKey('nested', $cleaned);
        $this->assertArrayNotHasKey('prototype', $cleaned['nested']);
    }

    public function test_svg_sanitization_removes_scripts_and_event_handlers(): void
    {
        $service = app(AssetSecurityService::class);

        $hostileSvg = <<<'SVG'
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
    <script>alert('XSS');</script>
    <circle cx="50" cy="50" r="40" onload="alert('XSS-ONLOAD')" onclick="alert(1)"/>
    <foreignObject width="100" height="100">
        <iframe src="javascript:alert(1)"></iframe>
    </foreignObject>
    <a href="javascript:alert('CLICK')">link</a>
</svg>
SVG;

        $sanitized = $service->sanitizeSvg($hostileSvg);

        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringNotContainsString('onload', $sanitized);
        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringNotContainsString('<foreignObject', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringContainsString('<circle', $sanitized);
    }

    public function test_docx_validation_rejects_missing_document_xml(): void
    {
        $service = app(AssetSecurityService::class);

        // Create a dummy zip that does not contain word/document.xml
        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_docx_');
        $zip = new \ZipArchive;
        $zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('fake.txt', 'some content');
        $zip->close();

        $uploaded = new UploadedFile($tempZipPath, 'corrupt.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $this->expectException(ValidationException::class);
        $service->validateDocxUpload($uploaded);

        @unlink($tempZipPath);
    }

    public function test_uploading_asset_with_invalid_mime_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $fakeFile = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($admin)->postJson(route('admin.document-studio.assets.upload'), [
            'file' => $fakeFile,
            'type' => DocumentAsset::TYPE_IMAGE,
        ]);

        $response->assertStatus(422);
    }

    public function test_private_asset_stream_is_forbidden_for_trainees_and_guests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $trainee = User::factory()->create(['role' => 'trainee']);
        RolePermissionMatrix::syncUser($trainee);

        // Store a fake asset in private storage
        Storage::disk('local')->put('document-studio/assets/test.png', 'fake png content');

        $asset = DocumentAsset::create([
            'name' => 'Logo',
            'type' => DocumentAsset::TYPE_LOGO,
            'disk' => 'local',
            'file_path' => 'document-studio/assets/test.png',
            'mime_type' => 'image/png',
            'file_size' => 16,
            'sha256' => hash('sha256', 'fake png content'),
        ]);

        // Guest cannot access (redirected to login)
        $this->get(route('admin.document-studio.assets.stream', $asset))->assertRedirect(route('login'));

        // Trainee cannot access
        $this->actingAs($trainee)->get(route('admin.document-studio.assets.stream', $asset))->assertForbidden();

        // Admin can access with private headers
        $adminResponse = $this->actingAs($admin)->get(route('admin.document-studio.assets.stream', $asset));
        $adminResponse->assertOk();
        $this->assertStringContainsString('no-store', (string) $adminResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $adminResponse->headers->get('Cache-Control'));
    }
}
