<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_error_preview_pages_can_be_rendered(): void
    {
        foreach ([403, 404, 413, 419, 429, 500, 503] as $code) {
            $this->get(route('errors.preview', $code))
                ->assertStatus($code)
                ->assertSee((string) $code)
                ->assertSee('RTT');
        }
    }
}
