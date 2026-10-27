<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    private function image(string $name = 'banner.png'): UploadedFile
    {
        // 1x1 PNG, so the tests do not depend on the GD extension.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $png);
    }

    private function banner(array $attributes = []): Banner
    {
        return Banner::create([
            'title' => 'Banner mẫu',
            'image' => $this->image()->store('banners', 'media'),
            ...$attributes,
        ]);
    }

    public function test_admin_adds_a_banner_that_shows_on_the_home_page(): void
    {
        $this->actingAs($this->admin)->get(route('admin.banners.create'))->assertOk();

        $this->post(route('admin.banners.store'), [
            'title' => 'Khai giảng khoá Laravel',
            'subtitle' => 'Giảm 50% học phí đến hết tháng 11',
            'image' => $this->image(),
            'link_url' => '/khoa-hoc?q=laravel',
            'button_label' => 'Đăng ký ngay',
            'position' => 1,
            'is_active' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.banners.index'));

        $banner = Banner::firstOrFail();
        $this->assertStringStartsWith('banners/', $banner->image);
        Storage::disk('media')->assertExists($banner->image);

        $this->get(route('admin.banners.index'))->assertOk()->assertSee('Khai giảng khoá Laravel')->assertSee('Đang hiển thị');

        $this->post('/logout');
        $this->get(route('home'))->assertOk()
            ->assertSee('Khai giảng khoá Laravel')
            ->assertSee('Giảm 50% học phí đến hết tháng 11')
            ->assertSee('href="/khoa-hoc?q=laravel"', false)
            ->assertSee('Đăng ký ngay')
            ->assertSee(Storage::disk('media')->url($banner->image));
    }

    public function test_home_page_hides_banners_that_are_off_scheduled_or_expired(): void
    {
        $this->banner(['title' => 'Đang chạy', 'position' => 2]);
        $this->banner(['title' => 'Chạy trước', 'position' => 1, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        $this->banner(['title' => 'Đã tắt', 'is_active' => false]);
        $this->banner(['title' => 'Chưa tới ngày', 'starts_at' => now()->addDay()]);
        $this->banner(['title' => 'Hết hạn', 'ends_at' => now()->subMinute()]);

        $this->get(route('home'))->assertOk()
            ->assertSeeInOrder(['Chạy trước', 'Đang chạy'])
            ->assertDontSee('Đã tắt')
            ->assertDontSee('Chưa tới ngày')
            ->assertDontSee('Hết hạn');

        $this->actingAs($this->admin)->get(route('admin.banners.index'))
            ->assertSee('Đang tắt')
            ->assertSee('Chờ đến ngày')
            ->assertSee('Đã hết hạn');
    }

    public function test_home_page_without_banners_keeps_the_default_hero(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('Nâng tầm kiến thức')
            ->assertDontSee('aria-roledescription="carousel"', false);
    }

    public function test_image_is_required_and_links_must_be_safe(): void
    {
        $this->actingAs($this->admin)->post(route('admin.banners.store'), ['title' => 'Không ảnh'])
            ->assertSessionHasErrors('image');

        foreach (['javascript:alert(1)', '//evil.example', 'data:text/html,hi', 'khoa-hoc'] as $link) {
            $this->post(route('admin.banners.store'), ['title' => 'Link xấu', 'image' => $this->image(), 'link_url' => $link])
                ->assertSessionHasErrors('link_url');
        }

        $this->post(route('admin.banners.store'), ['title' => 'Lịch sai', 'image' => $this->image(), 'starts_at' => '2026-11-10 08:00', 'ends_at' => '2026-11-01 08:00'])
            ->assertSessionHasErrors('ends_at');

        $this->assertSame(0, Banner::count());
        $this->assertEmpty(Storage::disk('media')->allFiles());

        $this->post(route('admin.banners.store'), ['title' => 'Link ngoài', 'image' => $this->image(), 'link_url' => 'https://uth.edu.vn', 'is_active' => '1', 'ends_at' => now()->addMonth()->format('Y-m-d H:i')])
            ->assertSessionHasNoErrors();
        $html = $this->get(route('home'))->getContent();
        $this->assertMatchesRegularExpression('~href="https://uth\.edu\.vn"\s+target="_blank"~', $html);
    }

    public function test_updating_without_a_new_image_keeps_it_and_a_new_image_replaces_it(): void
    {
        $banner = $this->banner();
        $oldImage = $banner->image;

        $this->actingAs($this->admin)->get(route('admin.banners.edit', $banner))->assertOk();

        $this->put(route('admin.banners.update', $banner), ['title' => 'Tên mới', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('Tên mới', $banner->refresh()->title);
        $this->assertSame($oldImage, $banner->image);

        $this->put(route('admin.banners.update', $banner), ['title' => 'Tên mới', 'image' => $this->image('moi.png')])->assertSessionHasNoErrors();
        $banner->refresh();
        $this->assertNotSame($oldImage, $banner->image);
        $this->assertFalse($banner->is_active);
        Storage::disk('media')->assertMissing($oldImage);
        Storage::disk('media')->assertExists($banner->image);
    }

    public function test_deleting_a_banner_deletes_its_image(): void
    {
        $banner = $this->banner();

        $this->actingAs($this->admin)->delete(route('admin.banners.destroy', $banner))->assertRedirect();

        $this->assertModelMissing($banner);
        Storage::disk('media')->assertMissing($banner->image);
    }

    public function test_only_admins_manage_banners(): void
    {
        $banner = $this->banner();

        foreach ([UserRole::Teacher, UserRole::Student] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.banners.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.banners.store'), ['title' => 'X', 'image' => $this->image()])->assertForbidden();
            $this->actingAs($user)->delete(route('admin.banners.destroy', $banner))->assertForbidden();
        }

        $this->assertModelExists($banner);
    }

    public function test_media_library_shows_where_banner_images_are_used(): void
    {
        $this->banner(['title' => 'Tuyển sinh']);

        $this->actingAs($this->admin)->get(route('admin.media.index'))->assertOk()
            ->assertSee('Banner &quot;Tuyển sinh&quot;', false);
    }
}
