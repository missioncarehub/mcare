<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentStudio\UploadAssetRequest;
use App\Models\AdminActivityLog;
use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Services\DocumentStudio\AssetSecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentStudioAssetController extends Controller
{
    public function upload(UploadAssetRequest $request, AssetSecurityService $security): JsonResponse
    {
        $file = $request->file('file');
        $type = $request->input('type');
        $templateId = $request->input('document_template_id');

        $asset = $security->storeUploadedFile($file, $type, $templateId, $request->user());

        AdminActivityLog::record($request->user(), 'admin.document-studio.asset-uploaded', $asset, [
            'name' => $asset->name,
            'type' => $asset->type,
            'size' => $asset->file_size,
        ]);

        return response()->json([
            'success' => true,
            'asset' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'type' => $asset->type,
                'mime_type' => $asset->mime_type,
                'file_size' => $asset->file_size,
                'dimensions' => $asset->dimensions,
                'url' => route('admin.document-studio.assets.stream', $asset),
            ],
        ]);
    }

    public function stream(Request $request, DocumentAsset $asset): BinaryFileResponse
    {
        // Enforce admin permission to view document studio assets
        abort_unless(
            $request->user() && ($request->user()->is_super_admin || $request->user()->can('official-documents.manage')),
            403
        );

        abort_unless($asset->existsOnDisk(), 404, 'Asset file not found.');
        abort_unless(hash_equals($asset->sha256, hash('sha256', $asset->fileContents())), 409, 'Asset integrity check failed.');

        $fullPath = Storage::disk($asset->disk)->path($asset->file_path);

        return response()->file($fullPath, [
            'Content-Type' => $asset->mime_type,
            'Cache-Control' => 'private, no-store, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    public function destroy(Request $request, DocumentAsset $asset): JsonResponse
    {
        abort_unless(
            $request->user() && ($request->user()->is_super_admin || $request->user()->can('official-documents.manage')),
            403
        );

        return DB::transaction(function () use ($asset): JsonResponse {
            if ($asset->document_template_id) {
                DocumentTemplate::query()->lockForUpdate()->findOrFail($asset->document_template_id);
            }
            if ($asset->is_system) {
                return response()->json(['error' => 'System assets cannot be deleted.'], 422);
            }

            $security = app(AssetSecurityService::class);
            $isReferencedInVersions = DocumentTemplateVersion::query()->cursor()
                ->contains(fn ($version) => in_array($asset->id, $security->assetIds($version->editor_state ?? []), true));
            $isReferencedInDrafts = DocumentTemplate::query()->cursor()
                ->contains(fn ($template) => in_array($asset->id, $security->assetIds($template->draft_state ?? []), true));

            if ($isReferencedInVersions || $isReferencedInDrafts) {
                return response()->json([
                    'error' => 'Asset cannot be deleted because it is referenced by one or more template drafts or published versions.',
                ], 422);
            }

            if (Storage::disk($asset->disk)->exists($asset->file_path)) {
                Storage::disk($asset->disk)->delete($asset->file_path);
            }

            $asset->delete();

            return response()->json(['success' => true]);
        });
    }
}
