<?php

namespace Tests\Unit\Rules;

use App\Rules\SafeLinkUrl;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SafeLinkUrlTest extends TestCase
{
    protected function fails(string $value): bool
    {
        return Validator::make(['link_url' => $value], ['link_url' => [new SafeLinkUrl]])->fails();
    }

    public function test_relative_paths_are_allowed(): void
    {
        $this->assertFalse($this->fails('/shop'));
        $this->assertFalse($this->fails('/products/some-slug'));
        $this->assertFalse($this->fails('#anchor'));
    }

    public function test_absolute_http_and_https_urls_are_allowed(): void
    {
        $this->assertFalse($this->fails('https://example.com/promo'));
        $this->assertFalse($this->fails('http://example.com'));
    }

    public function test_javascript_uris_are_rejected(): void
    {
        $this->assertTrue($this->fails('javascript:alert(1)'));
        $this->assertTrue($this->fails('JavaScript:alert(document.cookie)'));
    }

    public function test_other_dangerous_schemes_are_rejected(): void
    {
        $this->assertTrue($this->fails('data:text/html,<script>alert(1)</script>'));
        $this->assertTrue($this->fails('vbscript:msgbox(1)'));
        $this->assertTrue($this->fails('file:///etc/passwd'));
    }

    /**
     * Browsers strip leading/trailing whitespace and embedded tab/CR/LF
     * from a URL before interpreting its scheme (WHATWG URL spec) —
     * these obfuscated forms still execute as javascript: in a real
     * browser even though a naive parse_url() call would report no
     * scheme at all for them.
     */
    public function test_whitespace_obfuscated_javascript_uris_are_rejected(): void
    {
        $this->assertTrue($this->fails('  javascript:alert(1)'));
        $this->assertTrue($this->fails("java\tscript:alert(1)"));
        $this->assertTrue($this->fails("\n\njavascript:alert(1)"));
    }

    public function test_null_and_empty_are_allowed(): void
    {
        $this->assertFalse($this->fails(''));
    }
}
