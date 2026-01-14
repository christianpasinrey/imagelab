<?php

namespace App\Http\Controllers;

use App\Models\EditHistory;
use App\Models\Image;
use App\Services\ImageProcessorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ImageController extends Controller
{
    public function __construct(
        private ImageProcessorService $processor
    ) {}

    // Public gallery
    public function gallery(Request $request): View
    {
        // Validate and sanitize search inputs
        $request->validate([
            'q' => 'nullable|string|max:100',
            'tag' => 'nullable|string|max:50',
        ]);

        $query = Image::with('media')->latest();

        // Search by title or tags (Eloquent protects against SQL injection)
        if ($search = $request->get('q')) {
            // Sanitize: remove special chars that could cause issues
            $search = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search);
            $search = trim(substr($search, 0, 100));

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhereJsonContains('tags', $search);
                });
            }
        }

        // Filter by tag
        if ($tag = $request->get('tag')) {
            // Sanitize tag
            $tag = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $tag);
            $tag = trim(substr($tag, 0, 50));

            if (!empty($tag)) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        $images = $query->paginate(24);

        // Get popular tags (sanitized on input, safe to display)
        $popularTags = Image::whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(12)
            ->keys();

        return view('welcome', compact('images', 'popularTags'));
    }

    // Editor view
    public function index(Request $request, ?Image $image = null): View
    {
        $sessionId = $request->session()->getId();

        // Get user's own images (from this session)
        $myImages = Image::with('media')
            ->where('session_id', $sessionId)
            ->latest()
            ->get()
            ->map(fn($img) => $this->formatImageResponse($img, $sessionId));

        $currentImage = null;
        $canEdit = false;

        if ($image) {
            $image->incrementViews();
            $currentImage = $this->formatImageResponse($image, $sessionId);
            $canEdit = $image->canEdit($sessionId);
        }

        return view('editor', compact('myImages', 'currentImage', 'canEdit', 'sessionId'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => [
                'required',
                'image',
                'max:20480', // 20MB
                'mimes:jpeg,jpg,png,gif,webp',
                'dimensions:max_width=8000,max_height=8000',
            ],
            'title' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:500',
        ]);

        $file = $request->file('image');
        $filePath = $file->getPathname();

        // Security: verify it's actually a valid image
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return response()->json(['error' => 'El archivo no es una imagen válida'], 422);
        }

        // Verify MIME type matches actual content
        $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($imageInfo[2], $allowedTypes)) {
            return response()->json(['error' => 'Tipo de imagen no permitido'], 422);
        }

        // Attempt to load the image with GD to verify it's not corrupted
        $imageResource = null;
        try {
            switch ($imageInfo[2]) {
                case IMAGETYPE_JPEG:
                    $imageResource = @imagecreatefromjpeg($filePath);
                    break;
                case IMAGETYPE_PNG:
                    $imageResource = @imagecreatefrompng($filePath);
                    break;
                case IMAGETYPE_GIF:
                    $imageResource = @imagecreatefromgif($filePath);
                    break;
                case IMAGETYPE_WEBP:
                    $imageResource = @imagecreatefromwebp($filePath);
                    break;
            }

            if ($imageResource === false || $imageResource === null) {
                return response()->json(['error' => 'La imagen está corrupta o no se puede procesar'], 422);
            }

            // Image is valid, free memory
            imagedestroy($imageResource);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error al validar la imagen: ' . $e->getMessage()], 422);
        }

        // Sanitize filename - remove any path traversal attempts
        $originalName = $file->getClientOriginalName();
        $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $name = substr($name, 0, 100); // Limit length

        $sessionId = $request->session()->getId();

        // Parse and sanitize tags from comma-separated string
        $tags = null;
        if ($request->filled('tags')) {
            $tags = array_map(function($tag) {
                // Strip HTML, trim, and limit length
                return substr(strip_tags(trim($tag)), 0, 50);
            }, explode(',', $request->input('tags')));
            $tags = array_filter($tags, fn($t) => strlen($t) > 0);
            $tags = array_values(array_unique($tags));
            $tags = array_slice($tags, 0, 20); // Max 20 tags
        }

        // Sanitize title
        $title = $request->input('title')
            ? substr(strip_tags(trim($request->input('title'))), 0, 255)
            : $name;

        $image = Image::create([
            'name' => $name,
            'title' => $title,
            'tags' => $tags,
            'session_id' => $sessionId,
            'original_filename' => $originalName,
        ]);

        $image->addMedia($file)
            ->toMediaCollection('original');

        $image->editHistories()->create([
            'adjustments' => EditHistory::getDefaultAdjustments(),
            'version_number' => 1,
        ]);

        return response()->json([
            'success' => true,
            'image' => $this->formatImageResponse($image, $sessionId),
        ]);
    }

    public function update(Request $request, Image $image): JsonResponse
    {
        $sessionId = $request->session()->getId();

        if (!$image->canEdit($sessionId)) {
            return response()->json(['error' => 'No tienes permiso para editar esta imagen'], 403);
        }

        $request->validate([
            'title' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:500',
        ]);

        $data = [];

        if ($request->has('title')) {
            // Sanitize title - strip HTML tags
            $data['title'] = substr(strip_tags(trim($request->input('title', ''))), 0, 255);
        }

        if ($request->has('tags')) {
            // Sanitize tags
            $tags = array_map(function($tag) {
                return substr(strip_tags(trim($tag)), 0, 50);
            }, explode(',', $request->input('tags', '')));
            $tags = array_filter($tags, fn($t) => strlen($t) > 0);
            $tags = array_values(array_unique($tags));
            $data['tags'] = array_slice($tags, 0, 20);
        }

        $image->update($data);

        return response()->json([
            'success' => true,
            'image' => $this->formatImageResponse($image->fresh(), $sessionId),
        ]);
    }

    public function show(Image $image): JsonResponse
    {
        $image->load(['media', 'editHistories']);

        return response()->json([
            'image' => $this->formatImageResponse($image),
            'history' => $image->editHistories->map(fn($h) => [
                'id' => $h->id,
                'version' => $h->version_number,
                'adjustments' => $h->adjustments,
                'thumbnail' => $h->thumbnail,
                'created_at' => $h->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function process(Request $request, Image $image): JsonResponse
    {
        $request->validate([
            'adjustments' => 'required|array',
            'save_version' => 'boolean',
            'thumbnail' => 'nullable|string',
            'canvas_image' => 'nullable|string',
        ]);

        $adjustments = $request->input('adjustments');
        $saveVersion = $request->boolean('save_version', false);
        $thumbnail = $request->input('thumbnail');
        $canvasImage = $request->input('canvas_image');

        // If canvas_image is provided, use it directly (exact visual from frontend)
        if ($canvasImage && $saveVersion) {
            // Decode base64 canvas image
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $canvasImage);
            $imageData = base64_decode($imageData);

            if (!$imageData) {
                return response()->json(['error' => 'Invalid image data'], 400);
            }

            // Save to temp file
            $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $versionNumber = $image->editHistories()->max('version_number') + 1;
            $tempPath = $tempDir . DIRECTORY_SEPARATOR . "v{$versionNumber}_" . Str::random(8) . '.jpg';
            file_put_contents($tempPath, $imageData);

            $image->addMedia($tempPath)
                ->toMediaCollection('versions');

            $image->editHistories()->create([
                'adjustments' => $adjustments,
                'thumbnail' => $thumbnail,
                'version_number' => $versionNumber,
            ]);

            return response()->json([
                'success' => true,
                'preview' => $canvasImage,
                'version' => $versionNumber,
            ]);
        }

        // Fallback: process with GD (for preview without saving)
        $originalMedia = $image->getFirstMedia('original');

        if (!$originalMedia) {
            return response()->json(['error' => 'Original image not found'], 404);
        }

        $processedPath = $this->processor->process(
            $originalMedia->getPath(),
            $adjustments
        );

        $base64 = base64_encode(file_get_contents($processedPath));
        @unlink($processedPath);

        return response()->json([
            'success' => true,
            'preview' => 'data:image/jpeg;base64,' . $base64,
            'version' => null,
        ]);
    }

    public function versions(Image $image): JsonResponse
    {
        $versions = $image->getMedia('versions')->map(fn($media) => [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'thumb' => $media->getUrl('thumb'),
            'created_at' => $media->created_at->format('d/m/Y H:i'),
        ]);

        return response()->json(['versions' => $versions]);
    }

    public function download(Request $request, Image $image): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $request->validate([
            'format' => 'in:jpg,png,webp',
            'quality' => 'integer|min:1|max:100',
            'adjustments' => 'array',
        ]);

        $format = $request->input('format', 'jpg');
        $quality = $request->input('quality', 90);
        $adjustments = $request->input('adjustments', []);

        $originalMedia = $image->getFirstMedia('original');

        if (!$originalMedia) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        $sourcePath = $originalMedia->getPath();

        if (!empty($adjustments)) {
            $sourcePath = $this->processor->process($sourcePath, $adjustments);
        }

        $outputPath = $this->processor->export($sourcePath, $format, $quality);

        $filename = $image->name . '_edited.' . $format;

        return response()->download($outputPath, $filename)->deleteFileAfterSend(true);
    }

    public function destroy(Request $request, Image $image): JsonResponse
    {
        $sessionId = $request->session()->getId();

        // Only the owner can delete their images
        if (!$image->canEdit($sessionId)) {
            return response()->json(['error' => 'No tienes permiso para eliminar esta imagen'], 403);
        }

        $image->delete();

        return response()->json(['success' => true]);
    }

    private function formatImageResponse(Image $image, ?string $sessionId = null): array
    {
        $originalMedia = $image->getFirstMedia('original');

        return [
            'id' => $image->id,
            'slug' => $image->slug,
            'name' => $image->name,
            'title' => $image->title,
            'tags' => $image->tags ?? [],
            'original_filename' => $image->original_filename,
            'url' => $originalMedia?->getUrl(),
            'thumb' => $originalMedia?->getUrl('thumb'),
            'preview' => $originalMedia?->getUrl('preview'),
            'views' => $image->views_count,
            'can_edit' => $sessionId ? $image->canEdit($sessionId) : false,
            'created_at' => $image->created_at->format('d/m/Y H:i'),
        ];
    }
}
