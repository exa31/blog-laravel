<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SplFileInfo;

class ImageOptimizerService
{
    /**
     * Convert an image from any format to compressed WebP and store it in the specified disk.
     *
     * @param  mixed  $file  UploadedFile, TemporaryUploadedFile, SplFileInfo, base64 data URI, or binary string
     * @param  string  $directory  Target storage directory (e.g. 'images', 'avatars', 'attachments')
     * @param  string|null  $disk  Storage disk name (defaults to filesystems.default)
     * @param  int  $quality  WebP compression quality (0-100, default: 80)
     * @param  int|null  $maxWidth  Maximum width for resizing (default: 1920)
     * @return string Stored relative file path (e.g. 'images/01HXYZ...webp')
     */
    public function optimizeAndStore(
        mixed $file,
        string $directory = 'images',
        ?string $disk = null,
        int $quality = 80,
        ?int $maxWidth = 1920
    ): string {
        $disk ??= config('filesystems.default', 'public');
        $webpBinary = $this->convertToWebpBinary($file, $quality, $maxWidth);

        $filename = (string) Str::ulid().'.webp';
        $relativePath = trim($directory.'/'.$filename, '/');

        Storage::disk($disk)->put($relativePath, $webpBinary);

        return $relativePath;
    }

    /**
     * Convert any supported image input into a compressed WebP binary string.
     *
     * @param  mixed  $file  UploadedFile, TemporaryUploadedFile, SplFileInfo, base64 data URI, or binary string
     * @param  int  $quality  WebP compression quality (0-100, default: 80)
     * @param  int|null  $maxWidth  Maximum width for resizing (default: 1920)
     * @return string Binary WebP data
     */
    public function convertToWebpBinary(
        mixed $file,
        int $quality = 80,
        ?int $maxWidth = 1920
    ): string {
        $rawBytes = $this->extractRawBytes($file);

        if (empty($rawBytes)) {
            throw new InvalidArgumentException('Empty image payload provided.');
        }

        $image = @imagecreatefromstring($rawBytes);
        if (! $image) {
            throw new InvalidArgumentException('Unable to decode image data into a valid graphic resource.');
        }

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        // Proportional resizing if image exceeds maxWidth
        if ($maxWidth !== null && $maxWidth > 0 && $origWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int) max(1, round(($origHeight / $origWidth) * $maxWidth));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);

            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            $image = $resized;
        } else {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        ob_start();
        imagewebp($image, null, $quality);
        $webpBinary = ob_get_clean();

        if ($webpBinary === false || empty($webpBinary)) {
            throw new InvalidArgumentException('Failed to encode image to WebP format.');
        }

        return $webpBinary;
    }

    /**
     * Extract raw byte string from various file or payload representations.
     */
    protected function extractRawBytes(mixed $file): string
    {
        if ($file instanceof UploadedFile || $file instanceof SplFileInfo || (is_object($file) && method_exists($file, 'getRealPath'))) {
            $path = $file->getRealPath();
            if ($path && file_exists($path)) {
                return file_get_contents($path);
            }
        }

        if (is_string($file)) {
            // Check if base64 data URI e.g. "data:image/png;base64,iVBORw..."
            if (preg_match('/^data:image\/[a-zA-Z0-9\+\-\.]+;base64,(.+)$/s', $file, $matches)) {
                $decoded = base64_decode($matches[1], true);
                if ($decoded !== false) {
                    return $decoded;
                }
            }

            // Check if direct local file path
            if (strlen($file) < 4096 && @file_exists($file) && @is_file($file)) {
                return file_get_contents($file);
            }

            // Otherwise treat as raw binary data
            return $file;
        }

        throw new InvalidArgumentException('Unsupported file input type for image optimization.');
    }
}
