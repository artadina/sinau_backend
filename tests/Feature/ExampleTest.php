<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    // FR-A01: the panel login page is available.
    public function test_the_admin_login_page_is_available(): void
    {
        $this->get('/login')->assertOk();
    }
}
