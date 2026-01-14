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

    public function index(): View
    {
        $images = Image::with('media')->latest()->get()->map(function ($img) {
            return [
                'id' => $img->id,
                'name' => $img->name,
                'url' => $img->getFirstMediaUrl('original'),
                'thumb' => $img->getFirstMediaUrl('original', 'thumb'),
                'preview' => $img->getFirstMediaUrl('original', 'preview'),
            ];
        });

        return view('editor', compact('images'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:20480',
        ]);

        $file = $request->file('image');
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $image = Image::create([
            'name' => $name,
            'original_filename' => $file->getClientOriginalName(),
        ]);

        $image->addMedia($file)
            ->toMediaCollection('original');

        $image->editHistories()->create([
            'adjustments' => EditHistory::getDefaultAdjustments(),
            'version_number' => 1,
        ]);

        return response()->json([
            'success' => true,
            'image' => $this->formatImageResponse($image),
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
                'created_at' => $h->created_at->format('d/m/Y H:i'),
            ]),
        ]);
    }

    public function process(Request $request, Image $image): JsonResponse
    {
        $request->validate([
            'adjustments' => 'required|array',
            'save_version' => 'boolean',
        ]);

        $adjustments = $request->input('adjustments');
        $saveVersion = $request->boolean('save_version', false);

        $originalMedia = $image->getFirstMedia('original');

        if (!$originalMedia) {
            return response()->json(['error' => 'Original image not found'], 404);
        }

        $processedPath = $this->processor->process(
            $originalMedia->getPath(),
            $adjustments
        );

        if ($saveVersion) {
            $versionNumber = $image->editHistories()->max('version_number') + 1;

            $image->addMedia($processedPath)
                ->usingFileName("v{$versionNumber}_" . Str::random(8) . '.jpg')
                ->toMediaCollection('versions');

            $image->editHistories()->create([
                'adjustments' => $adjustments,
                'version_number' => $versionNumber,
            ]);
        }

        $base64 = base64_encode(file_get_contents($processedPath));

        if (!$saveVersion) {
            @unlink($processedPath);
        }

        return response()->json([
            'success' => true,
            'preview' => 'data:image/jpeg;base64,' . $base64,
            'version' => $saveVersion ? $image->editHistories()->max('version_number') : null,
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

    public function destroy(Image $image): JsonResponse
    {
        $image->delete();

        return response()->json(['success' => true]);
    }

    private function formatImageResponse(Image $image): array
    {
        $originalMedia = $image->getFirstMedia('original');

        return [
            'id' => $image->id,
            'name' => $image->name,
            'original_filename' => $image->original_filename,
            'url' => $originalMedia?->getUrl(),
            'thumb' => $originalMedia?->getUrl('thumb'),
            'preview' => $originalMedia?->getUrl('preview'),
            'created_at' => $image->created_at->format('d/m/Y H:i'),
        ];
    }
}
