<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Tests opt into model responses explicitly; never call the user's running model.
        config()->set('services.ollama.model', null);
        config()->set('services.ollama.chat_model', null);
        // Public-source tests opt in explicitly and fake all HTTP; ordinary tests never crawl job sites.
        config()->set('services.public_jobs.sources', []);
    }
}
