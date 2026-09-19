<?php

namespace Tests\Feature;

use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_xml_with_offer_urls(): void
    {
        $this->seed();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('<?xml version="1.0"', false);
        $response->assertSee('<urlset', false);
        $response->assertSee('/offers/llm7', false);
        $response->assertSee('/offers/apimaster', false);
    }

    public function test_sitemap_covers_every_offer_once(): void
    {
        $this->seed();

        $content = $this->get('/sitemap.xml')->getContent();
        $offerCount = Offer::query()->count();

        $this->assertGreaterThan(0, $offerCount);
        $this->assertSame($offerCount, substr_count((string) $content, '/offers/'));
    }
}
