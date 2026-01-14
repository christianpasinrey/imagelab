<?php

namespace App\Services;

class ImageProcessorService
{
    private bool $hasImagick;

    public function __construct()
    {
        $this->hasImagick = extension_loaded('imagick');
    }

    /**
     * Check if ImageMagick is available
     */
    public static function hasImageMagick(): bool
    {
        return extension_loaded('imagick');
    }

    /**
     * Get supported formats
     */
    public static function getSupportedFormats(): array
    {
        $formats = ['jpeg', 'jpg', 'png', 'gif', 'webp'];

        if (self::hasImageMagick()) {
            try {
                $imagick = new \Imagick();
                $supported = $imagick->queryFormats();

                // Add HEIC/HEIF if supported
                if (in_array('HEIC', $supported) || in_array('HEIF', $supported)) {
                    $formats[] = 'heic';
                    $formats[] = 'heif';
                }

                // Add common RAW formats if supported
                $rawFormats = ['DNG', 'CR2', 'NEF', 'ARW', 'RAF', 'ORF', 'RW2'];
                foreach ($rawFormats as $raw) {
                    if (in_array($raw, $supported)) {
                        $formats[] = strtolower($raw);
                    }
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        return array_unique($formats);
    }

    /**
     * Get supported MIME types for validation
     */
    public static function getSupportedMimeTypes(): array
    {
        $mimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        if (self::hasImageMagick()) {
            $mimes = array_merge($mimes, [
                'image/heic',
                'image/heif',
                'image/x-canon-cr2',
                'image/x-nikon-nef',
                'image/x-sony-arw',
                'image/x-adobe-dng',
            ]);
        }

        return $mimes;
    }

    /**
     * Convert HEIC/RAW to JPEG for compatibility
     */
    public function convertToJpeg(string $sourcePath): string
    {
        if (!$this->hasImagick) {
            throw new \RuntimeException('ImageMagick is required for format conversion');
        }

        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('converted_') . '.jpg';

        try {
            $imagick = new \Imagick($sourcePath);

            // Handle multi-page images (some RAW files)
            if ($imagick->getNumberImages() > 1) {
                $imagick->setFirstIterator();
            }

            // Auto-rotate based on EXIF orientation
            $imagick->autoOrient();

            // Set output format
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(92);

            // Preserve color profile if possible
            try {
                $profiles = $imagick->getImageProfiles('icc', true);
            } catch (\Exception $e) {
                // No profile, continue
            }

            $imagick->writeImage($outputPath);
            $imagick->destroy();

            return $outputPath;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to convert image: ' . $e->getMessage());
        }
    }

    /**
     * Process image with adjustments
     */
    public function process(string $sourcePath, array $adjustments): string
    {
        if ($this->hasImagick) {
            return $this->processWithImagick($sourcePath, $adjustments);
        }

        return $this->processWithGd($sourcePath, $adjustments);
    }

    /**
     * Process using ImageMagick (supports more formats)
     */
    private function processWithImagick(string $sourcePath, array $adjustments): string
    {
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('processed_') . '.jpg';

        try {
            $imagick = new \Imagick($sourcePath);

            // Handle multi-page images
            if ($imagick->getNumberImages() > 1) {
                $imagick->setFirstIterator();
            }

            // Auto-orient based on EXIF
            $imagick->autoOrient();

            // Apply rotation
            if (!empty($adjustments['rotation'])) {
                $imagick->rotateImage(new \ImagickPixel('transparent'), $adjustments['rotation']);
            }

            // Apply flip
            if (!empty($adjustments['flipH'])) {
                $imagick->flopImage();
            }
            if (!empty($adjustments['flipV'])) {
                $imagick->flipImage();
            }

            // Apply crop
            if (!empty($adjustments['crop'])) {
                $crop = $adjustments['crop'];
                $imagick->cropImage(
                    (int)$crop['width'],
                    (int)$crop['height'],
                    (int)$crop['x'],
                    (int)$crop['y']
                );
                $imagick->setImagePage(0, 0, 0, 0);
            }

            // Apply brightness
            $brightness = $adjustments['brightness'] ?? 0;
            if ($brightness != 0) {
                $brightnessValue = 100 + $brightness;
                $imagick->modulateImage($brightnessValue, 100, 100);
            }

            // Apply contrast
            $contrast = $adjustments['contrast'] ?? 0;
            if ($contrast != 0) {
                // Sigmoid contrast for better results
                $sharpen = $contrast > 0;
                $midpoint = 0.5;
                $factor = abs($contrast) / 10;
                $imagick->sigmoidalContrastImage($sharpen, $factor, $midpoint * \Imagick::getQuantum());
            }

            // Apply saturation
            $saturation = $adjustments['saturation'] ?? 0;
            if ($saturation != 0) {
                $satValue = 100 + $saturation;
                $imagick->modulateImage(100, $satValue, 100);
            }

            // Apply filter
            $filter = $adjustments['filter'] ?? null;
            if ($filter) {
                $this->applyFilterImagick($imagick, $filter);
            }

            // Apply vignette
            $vignette = $adjustments['vignette'] ?? 0;
            if ($vignette > 0) {
                $imagick->vignetteImage(0, $vignette * 2, 0, 0);
            }

            // Apply sharpness
            $sharpness = $adjustments['sharpness'] ?? 0;
            if ($sharpness > 0) {
                $imagick->sharpenImage(0, $sharpness / 50);
            }

            // Output
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(90);
            $imagick->writeImage($tempPath);
            $imagick->destroy();

            return $tempPath;
        } catch (\Exception $e) {
            throw new \RuntimeException('ImageMagick processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Process using GD (basic formats only)
     */
    private function processWithGd(string $sourcePath, array $adjustments): string
    {
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('processed_') . '.jpg';

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \RuntimeException("Cannot read image: {$sourcePath}");
        }

        $srcImage = $this->loadImageGd($sourcePath, $imageInfo['mime']);
        if (!$srcImage) {
            throw new \RuntimeException("Failed to load image: {$sourcePath}");
        }

        // Apply rotation
        if (!empty($adjustments['rotation'])) {
            $rotation = -$adjustments['rotation'];
            $srcImage = imagerotate($srcImage, $rotation, 0);
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
            }
        }

        // Apply brightness
        $brightness = $adjustments['brightness'] ?? 0;
        if ($brightness != 0) {
            $gdBrightness = (int)($brightness * 2.55);
            imagefilter($srcImage, IMG_FILTER_BRIGHTNESS, $gdBrightness);
        }

        // Apply contrast
        $contrast = $adjustments['contrast'] ?? 0;
        if ($contrast != 0) {
            $gdContrast = (int)(-$contrast);
            imagefilter($srcImage, IMG_FILTER_CONTRAST, $gdContrast);
        }

        // Apply filter
        $filter = $adjustments['filter'] ?? null;
        if ($filter) {
            $this->applyFilterGd($srcImage, $filter);
        }

        // Save
        $result = imagejpeg($srcImage, $tempPath, 90);
        imagedestroy($srcImage);

        if (!$result || !file_exists($tempPath)) {
            throw new \RuntimeException("Failed to save processed image");
        }

        return $tempPath;
    }

    /**
     * Export image to specified format
     */
    public function export(string $sourcePath, string $format, int $quality): string
    {
        if ($this->hasImagick) {
            return $this->exportWithImagick($sourcePath, $format, $quality);
        }

        return $this->exportWithGd($sourcePath, $format, $quality);
    }

    private function exportWithImagick(string $sourcePath, string $format, int $quality): string
    {
        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('export_') . '.' . $format;

        try {
            $imagick = new \Imagick($sourcePath);
            $imagick->setImageFormat($format);
            $imagick->setImageCompressionQuality($quality);
            $imagick->writeImage($tempPath);
            $imagick->destroy();

            return $tempPath;
        } catch (\Exception $e) {
            throw new \RuntimeException('Export failed: ' . $e->getMessage());
        }
    }

    private function exportWithGd(string $sourcePath, string $format, int $quality): string
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

        $srcImage = $this->loadImageGd($sourcePath, $imageInfo['mime']);
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
            throw new \RuntimeException("Failed to export image");
        }

        return $tempPath;
    }

    private function loadImageGd(string $path, string $mime): ?\GdImage
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => null,
        };
    }

    private function applyFilterImagick(\Imagick $imagick, string $filter): void
    {
        switch ($filter) {
            case 'bw':
            case 'grayscale':
                $imagick->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
                break;

            case 'sepia':
                $imagick->sepiaToneImage(80);
                break;

            case 'noir':
                $imagick->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
                $imagick->contrastImage(true);
                $imagick->contrastImage(true);
                break;

            case 'vintage':
                $imagick->sepiaToneImage(40);
                $imagick->modulateImage(95, 80, 100);
                break;

            case 'cool':
                $imagick->modulateImage(100, 100, 90);
                break;

            case 'warm':
                $imagick->modulateImage(100, 100, 110);
                break;
        }
    }

    private function applyFilterGd(\GdImage $image, string $filter): void
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
