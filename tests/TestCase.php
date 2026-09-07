<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists(public_path('build/manifest.json'))) {
            @mkdir(public_path('build'), 0755, true);
            file_put_contents(public_path('build/manifest.json'), json_encode([
                'resources/js/app.js' => ['file' => 'assets/app.js']
            ]));
        }
    }
}
