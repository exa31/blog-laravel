<?php

namespace Tests\Unit;

use App\Services\ImageOptimizerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class ImageOptimizerServiceTest extends TestCase
{
    public function test_converts_uploaded_image_to_compressed_webp(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $service = app(ImageOptimizerService::class);
        $file = UploadedFile::fake()->image('banner.jpg', 800, 600);

        $path = $service->optimizeAndStore($file, 'images', 'public', quality: 80);

        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringStartsWith('images/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_converts_base64_data_uri_to_compressed_webp(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $service = app(ImageOptimizerService::class);

        // Create 100x100 PNG
        $img = imagecreatetruecolor(100, 100);
        $color = imagecolorallocate($img, 255, 100, 50);
        imagefill($img, 0, 0, $color);
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        $base64 = 'data:image/png;base64,'.base64_encode($png);

        $path = $service->optimizeAndStore($base64, 'avatars', 'public', quality: 80);

        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringStartsWith('avatars/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_proportional_resizing_when_exceeds_max_width(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $service = app(ImageOptimizerService::class);
        $file = UploadedFile::fake()->image('large.png', 3000, 1500);

        $path = $service->optimizeAndStore($file, 'images', 'public', quality: 80, maxWidth: 1000);

        Storage::disk('public')->assertExists($path);
        $content = Storage::disk('public')->get($path);

        $img = imagecreatefromstring($content);
        $this->assertNotFalse($img);
        $this->assertEquals(1000, imagesx($img));
        $this->assertEquals(500, imagesy($img));
        imagedestroy($img);
    }

    public function test_invalid_image_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $service = app(ImageOptimizerService::class);
        $service->convertToWebpBinary('corrupt-not-an-image');
    }
}
