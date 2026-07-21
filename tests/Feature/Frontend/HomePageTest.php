<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_route_renders_the_public_home_shell(): void
    {
        $this->withoutVite();

        $this->assertSame('/', route('home', absolute: false));

        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertViewIs('home')
            ->assertSee('<h1>KUPATBekasi</h1>', false)
            ->assertSee('<header>', false)
            ->assertSee('<main>', false)
            ->assertSee('<footer>', false)
            ->assertSee('<title>Beranda', false);
    }
}
