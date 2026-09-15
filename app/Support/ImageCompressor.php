<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageCompressor
{
    /**
     * Target default maximum size in bytes: 200 KB = 204,800 bytes.
     */
    public const DEFAULT_MAX_BYTES = 204800;

    /**
     * Compress an uploaded image file and store it under target max bytes (default 200KB).
     *
     * @param  string  $directory  Storage folder (e.g. 'candidates')
     * @param  string  $disk  Filesystem disk (default 'public')
     * @param  int  $maxBytes  Max target size in bytes (default 200KB)
     * @param  int  $maxDimension  Max width or height in pixels (default 1200px)
     * @return string Relative file path on storage disk
     */
    public static function compressAndStore(
        UploadedFile $file,
        string $directory = 'candidates',
        string $disk = 'public',
        int $maxBytes = self::DEFAULT_MAX_BYTES,
        int $maxDimension = 1200
    ): string {
        $realPath = $file->getRealPath();

        if (! $realPath || ! file_exists($realPath)) {
            return $file->store($directory, $disk);
        }

        // If GD is not available, fallback to standard store
        if (! extension_loaded('gd')) {
            return $file->store($directory, $disk);
        }

        try {
            $source = self::createImageFromFile($realPath, $file->getClientOriginalExtension());
            if (! $source) {
                return $file->store($directory, $disk);
            }

            // Correct orientation from EXIF data if present (common for camera / smartphone photos)
            $source = self::fixOrientation($source, $realPath);

            $origW = imagesx($source);
            $origH = imagesy($source);

            // Proportional scaling if dimensions exceed maxDimension
            $scale = min(1.0, $maxDimension / max($origW, $origH));
            $targetW = max(1, (int) round($origW * $scale));
            $targetH = max(1, (int) round($origH * $scale));

            $canvas = imagecreatetruecolor($targetW, $targetH);

            // Fill with white background to handle transparency
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);

            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

            // Progressive quality compression loop to ensure output <= $maxBytes
            $quality = 85;
            ob_start();
            imagejpeg($canvas, null, $quality);
            $buffer = ob_get_clean();

            while (strlen($buffer) > $maxBytes && $quality > 30) {
                $quality -= 8;
                ob_start();
                imagejpeg($canvas, null, $quality);
                $buffer = ob_get_clean();
            }

            // If still exceeding 200KB at quality 30, scale down resolution slightly
            if (strlen($buffer) > $maxBytes) {
                $reducedW = max(1, (int) round($targetW * 0.75));
                $reducedH = max(1, (int) round($targetH * 0.75));
                $reducedCanvas = imagecreatetruecolor($reducedW, $reducedH);
                $white = imagecolorallocate($reducedCanvas, 255, 255, 255);
                imagefill($reducedCanvas, 0, 0, $white);
                imagecopyresampled($reducedCanvas, $canvas, 0, 0, 0, 0, $reducedW, $reducedH, $targetW, $targetH);
                imagedestroy($canvas);
                $canvas = $reducedCanvas;

                $quality = 75;
                ob_start();
                imagejpeg($canvas, null, $quality);
                $buffer = ob_get_clean();

                while (strlen($buffer) > $maxBytes && $quality > 25) {
                    $quality -= 10;
                    ob_start();
                    imagejpeg($canvas, null, $quality);
                    $buffer = ob_get_clean();
                }
            }

            // Free resources
            imagedestroy($source);
            imagedestroy($canvas);

            // Save compressed JPEG to destination
            $filename = Str::random(40).'.jpg';
            $targetPath = trim($directory, '/').'/'.$filename;

            Storage::disk($disk)->put($targetPath, $buffer);

            return $targetPath;
        } catch (\Throwable $e) {
            // Safe fallback if any unexpected GD error occurs
            return $file->store($directory, $disk);
        }
    }

    /**
     * Create GD image resource from file path.
     *
     * @return \GdImage|resource|false
     */
    protected static function createImageFromFile(string $path, string $extension)
    {
        $ext = strtolower($extension);

        if (in_array($ext, ['jpg', 'jpeg'])) {
            $img = @imagecreatefromjpeg($path);
            if ($img) {
                return $img;
            }
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($path);
            if ($img) {
                return $img;
            }
        } elseif ($ext === 'webp') {
            $img = @imagecreatefromwebp($path);
            if ($img) {
                return $img;
            }
        } elseif ($ext === 'gif') {
            $img = @imagecreatefromgif($path);
            if ($img) {
                return $img;
            }
        }

        // Generic fallback using binary string
        $content = @file_get_contents($path);
        if ($content) {
            return @imagecreatefromstring($content);
        }

        return false;
    }

    /**
     * Fix rotation according to EXIF orientation tag.
     *
     * @param  \GdImage|resource  $image
     * @return \GdImage|resource
     */
    protected static function fixOrientation($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        if (empty($exif['Orientation'])) {
            return $image;
        }

        switch ($exif['Orientation']) {
            case 3:
                $rotated = imagerotate($image, 180, 0);
                imagedestroy($image);

                return $rotated;
            case 6:
                $rotated = imagerotate($image, -90, 0);
                imagedestroy($image);

                return $rotated;
            case 8:
                $rotated = imagerotate($image, 90, 0);
                imagedestroy($image);

                return $rotated;
        }

        return $image;
    }
}
