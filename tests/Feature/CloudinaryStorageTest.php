<?php

namespace Tests\Feature;

use App\Filesystem\CloudinaryAdapter;
use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Mockery;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

class CloudinaryStorageTest extends TestCase
{
    private function disk(UploadApi $uploadApi, ?AdminApi $adminApi = null): FilesystemAdapter
    {
        $cloudinary = Mockery::mock(Cloudinary::class, ['cloudinary://key:secret@demo-cloud'])->makePartial();
        $cloudinary->shouldReceive('uploadApi')->andReturn($uploadApi);
        $cloudinary->shouldReceive('adminApi')->andReturn($adminApi ?? Mockery::mock(AdminApi::class));

        $adapter = new CloudinaryAdapter($cloudinary, 'online-elearning');

        return new FilesystemAdapter(new Filesystem($adapter), $adapter, ['throw' => true]);
    }

    public function test_media_disk_falls_back_to_local_storage_without_cloudinary_url(): void
    {
        $this->assertSame('local', config('filesystems.disks.media.driver'));
        $this->assertSame(storage_path('app/public'), config('filesystems.disks.media.root'));
    }

    public function test_media_disk_uses_cloudinary_when_url_is_set(): void
    {
        config(['filesystems.disks.media' => [
            'driver' => 'cloudinary',
            'url' => 'cloudinary://key:secret@demo-cloud',
            'folder' => 'online-elearning',
        ]]);
        Storage::forgetDisk('media');

        $this->assertSame(
            'https://res.cloudinary.com/demo-cloud/image/upload/f_auto,q_auto/online-elearning/avatars/abc.jpg',
            Storage::disk('media')->url('avatars/abc.jpg'),
        );
    }

    public function test_uploaded_image_is_sent_to_cloudinary_without_extension_in_public_id(): void
    {
        $uploadApi = Mockery::mock(UploadApi::class);
        $uploadApi->shouldReceive('upload')->once()->withArgs(function ($file, array $options) {
            return $file instanceof StreamInterface
                && $options['public_id'] === 'online-elearning/avatars/'.pathinfo($options['filename'], PATHINFO_FILENAME)
                && $options['resource_type'] === 'image'
                && $options['asset_folder'] === 'online-elearning/avatars';
        });

        $disk = $this->disk($uploadApi);
        $path = $disk->putFile('avatars', UploadedFile::fake()->createWithContent('me.png', 'png-bytes'));

        $this->assertStringStartsWith('avatars/', $path);
        $this->assertStringEndsWith('.png', $path);
    }

    public function test_urls_depend_on_the_file_type(): void
    {
        $disk = $this->disk(Mockery::mock(UploadApi::class));

        $this->assertSame('https://res.cloudinary.com/demo-cloud/video/upload/online-elearning/lessons/intro.mp4', $disk->url('lessons/intro.mp4'));
        $this->assertSame('https://res.cloudinary.com/demo-cloud/raw/upload/online-elearning/files/slides.pdf', $disk->url('files/slides.pdf'));
        $this->assertSame('https://res.cloudinary.com/demo-cloud/image/upload/online-elearning/logo.svg', $disk->url('logo.svg'));
    }

    public function test_delete_destroys_the_asset(): void
    {
        $uploadApi = Mockery::mock(UploadApi::class);
        $uploadApi->shouldReceive('destroy')->once()->with('online-elearning/courses/1/gallery/a', [
            'resource_type' => 'image',
            'invalidate' => true,
        ]);

        $this->assertTrue($this->disk($uploadApi)->delete('courses/1/gallery/a.jpg'));
    }

    public function test_listing_returns_paths_relative_to_the_folder(): void
    {
        $adminApi = Mockery::mock(AdminApi::class);
        $adminApi->shouldReceive('assets')->andReturnUsing(fn (array $options) => new ApiResponse(
            ['resources' => $options['resource_type'] === 'image' ? [
                ['public_id' => 'online-elearning/avatars/abc', 'format' => 'jpg', 'bytes' => 2048, 'created_at' => '2026-10-26T07:00:00Z'],
                ['public_id' => 'online-elearning/courses/1/gallery/x', 'format' => 'png', 'bytes' => 10, 'created_at' => '2026-10-26T07:00:00Z'],
            ] : []],
            [],
        ));

        $files = $this->disk(Mockery::mock(UploadApi::class), $adminApi)->allFiles();

        $this->assertSame(['avatars/abc.jpg', 'courses/1/gallery/x.png'], $files);
    }
}
