<?php

namespace Justijndepover\Bullhorn\Test;

use Justijndepover\Bullhorn\Bullhorn;
use PHPUnit\Framework\TestCase;

class AuthenticateTest extends TestCase
{
    /**
     * @test
    **/
    public function it_can_instantiate_without_throwing_an_exception()
    {
        $bullhorn = new Bullhorn('client_id', 'client_secret', 'redirect_uri', 'state');
        $this->assertTrue(true);
    }
}