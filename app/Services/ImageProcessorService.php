<?php

namespace App\Services;

use Spatie\Image\Image;
use Spatie\Image\Enums\Fit;

class ImageProcessorService
{
    public function process(string $sourcePath, array $adjustments): string
    {
        $tempPath = storage_path('app/temp/' . uniqid('processed_') . '.jpg');

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $image = Image::load($sourcePath);

        // Apply rotation
        if (!empty($adjustments['rotation'])) {
            $image->orientation($adjustments['rotation']);
        }

        // Apply flip
        if (!empty($adjustments['flipH'])) {
            $image->flip('h');
        }
        if (!empty($adjustments['flipV'])) {
            $image->flip('v');
        }

        // Apply crop if specified
        if (!empty($adjustments['crop'])) {
            $crop = $adjustments['crop'];
            $image->manualCrop(
                (int) $crop['width'],
                (int) $crop['height'],
                (int) $crop['x'],
                (int) $crop['y']
            );
        }

        // Apply brightness (-100 to 100)
        if (isset($adjustments['brightness']) && $adjustments['brightness'] != 0) {
            $image->brightness($adjustments['brightness']);
        }

        // Apply contrast (-100 to 100)
        if (isset($adjustments['contrast']) && $adjustments['contrast'] != 0) {
            $image->contrast($adjustments['contrast']);
        }

        // Apply gamma for exposure simulation (0.1 to 9.99)
        if (isset($adjustments['exposure']) && $adjustments['exposure'] != 0) {
            // Convert -100 to 100 range to gamma range (0.5 to 2.0)
            $gamma = 1 + ($adjustments['exposure'] / 100);
            $gamma = max(0.5, min(2.0, $gamma));
            $image->gamma($gamma);
        }

        // Apply filter
        if (!empty($adjustments['filter'])) {
            $this->applyFilter($image, $adjustments['filter']);
        }

        $image->quality(90)->save($tempPath);

        return $tempPath;
    }

    public function export(string $sourcePath, string $format, int $quality): string
    {
        $tempPath = storage_path('app/temp/' . uniqid('export_') . '.' . $format);

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $image = Image::load($sourcePath);

        $image->quality($quality)->save($tempPath);

        return $tempPath;
    }

    private function applyFilter(Image $image, string $filter): void
    {
        match ($filter) {
            'grayscale', 'bw' => $image->greyscale(),
            'sepia' => $image->sepia(),
            default => null,
        };
    }
}
