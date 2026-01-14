<?php

namespace App\Services;

class ImageProcessorService
{
    public function process(string $sourcePath, array $adjustments): string
    {
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('processed_') . '.jpg';

        // Load source image
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \RuntimeException("Cannot read image: {$sourcePath}");
        }

        $srcImage = $this->loadImage($sourcePath, $imageInfo['mime']);
        if (!$srcImage) {
            throw new \RuntimeException("Failed to load image: {$sourcePath}");
        }

        $width = imagesx($srcImage);
        $height = imagesy($srcImage);

        // Apply rotation
        if (!empty($adjustments['rotation'])) {
            $rotation = -$adjustments['rotation']; // GD rotates counter-clockwise
            $srcImage = imagerotate($srcImage, $rotation, 0);
            $width = imagesx($srcImage);
            $height = imagesy($srcImage);
        }

        // Apply flip
        if (!empty($adjustments['flipH'])) {
            imageflip($srcImage, IMG_FLIP_HORIZONTAL);
        }
        if (!empty($adjustments['flipV'])) {
            imageflip($srcImage, IMG_FLIP_VERTICAL);
        }

        // Apply crop
        if (!empty($adjustments['crop'])) {
            $crop = $adjustments['crop'];
            $cropped = imagecrop($srcImage, [
                'x' => (int)$crop['x'],
                'y' => (int)$crop['y'],
                'width' => (int)$crop['width'],
                'height' => (int)$crop['height'],
            ]);
            if ($cropped) {
                imagedestroy($srcImage);
                $srcImage = $cropped;
                $width = imagesx($srcImage);
                $height = imagesy($srcImage);
            }
        }

        // Apply color adjustments using imagefilter
        $brightness = $adjustments['brightness'] ?? 0;
        $contrast = $adjustments['contrast'] ?? 0;

        if ($brightness != 0) {
            // GD brightness is -255 to 255, our input is -100 to 100
            $gdBrightness = (int)($brightness * 2.55);
            imagefilter($srcImage, IMG_FILTER_BRIGHTNESS, $gdBrightness);
        }

        if ($contrast != 0) {
            // GD contrast is -100 to 100 (inverted: -100 = more contrast)
            $gdContrast = (int)(-$contrast);
            imagefilter($srcImage, IMG_FILTER_CONTRAST, $gdContrast);
        }

        // Apply filter
        $filter = $adjustments['filter'] ?? null;
        if ($filter) {
            $this->applyFilter($srcImage, $filter);
        }

        // Save the processed image
        $result = imagejpeg($srcImage, $tempPath, 90);
        imagedestroy($srcImage);

        if (!$result || !file_exists($tempPath)) {
            throw new \RuntimeException("Failed to save processed image to: {$tempPath}");
        }

        return $tempPath;
    }

    public function export(string $sourcePath, string $format, int $quality): string
    {
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('export_') . '.' . $format;

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \RuntimeException("Cannot read image: {$sourcePath}");
        }

        $srcImage = $this->loadImage($sourcePath, $imageInfo['mime']);
        if (!$srcImage) {
            throw new \RuntimeException("Failed to load image: {$sourcePath}");
        }

        $result = match ($format) {
            'png' => imagepng($srcImage, $tempPath, (int)(9 - ($quality / 11))),
            'webp' => imagewebp($srcImage, $tempPath, $quality),
            default => imagejpeg($srcImage, $tempPath, $quality),
        };

        imagedestroy($srcImage);

        if (!$result || !file_exists($tempPath)) {
            throw new \RuntimeException("Failed to export image to: {$tempPath}");
        }

        return $tempPath;
    }

    private function loadImage(string $path, string $mime): ?\GdImage
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => null,
        };
    }

    private function applyFilter(\GdImage $image, string $filter): void
    {
        match ($filter) {
            'bw', 'grayscale' => imagefilter($image, IMG_FILTER_GRAYSCALE),
            'sepia' => $this->applySepia($image),
            'noir' => $this->applyNoir($image),
            default => null,
        };
    }

    private function applySepia(\GdImage $image): void
    {
        imagefilter($image, IMG_FILTER_GRAYSCALE);
        imagefilter($image, IMG_FILTER_COLORIZE, 90, 60, 30);
    }

    private function applyNoir(\GdImage $image): void
    {
        imagefilter($image, IMG_FILTER_GRAYSCALE);
        imagefilter($image, IMG_FILTER_CONTRAST, -20);
    }
}
