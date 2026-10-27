<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function dangerousHtml(): array
    {
        return [
            'script tag' => ['<p>a</p><script>alert(1)</script>'],
            'event handler' => ['<img src="x.png" onerror="alert(1)">'],
            'javascript link' => ['<a href="javascript:alert(1)">x</a>'],
            'javascript link with tab' => ["<a href=\"java\tscript:alert(1)\">x</a>"],
            'javascript link with entity' => ['<a href="java&#x09;script:alert(1)">x</a>'],
            'data html link' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>'],
            'iframe srcdoc' => ['<iframe src="https://www.youtube.com/embed/x" srcdoc="<script>alert(1)</script>"></iframe>'],
            'foreign iframe' => ['<iframe src="https://evil.example/"></iframe>'],
            'svg animate' => ['<svg><a><animate attributeName="href" values="javascript:alert(1)"/><text>x</text></a></svg>'],
        ];
    }

    #[DataProvider('dangerousHtml')]
    public function test_dangerous_markup_is_removed(string $html): void
    {
        $clean = strtolower(HtmlSanitizer::clean($html));

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('javascript', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('srcdoc', $clean);
        $this->assertStringNotContainsString('data:text', $clean);
        $this->assertStringNotContainsString('evil.example', $clean);
        $this->assertStringNotContainsString('<svg', $clean);
    }

    public function test_safe_editor_markup_is_kept(): void
    {
        $html = '<h2>Bài 1</h2><p><strong>Xin chào</strong> <a href="https://laravel.com">Laravel</a></p>'
            .'<figure class="image"><img src="/storage/editor/a.png" alt="a"></figure>'
            .'<img src="data:image/png;base64,iVBORw0KGgo=">'
            .'<iframe src="https://www.youtube.com/embed/abc"></iframe>';

        $clean = HtmlSanitizer::clean($html);

        $this->assertStringContainsString('<strong>Xin chào</strong>', $clean);
        $this->assertStringContainsString('href="https://laravel.com"', $clean);
        $this->assertStringContainsString('src="/storage/editor/a.png"', $clean);
        $this->assertStringContainsString('src="data:image/png;base64,iVBORw0KGgo="', $clean);
        $this->assertStringContainsString('src="https://www.youtube.com/embed/abc"', $clean);
    }
}
