<?php

namespace Justijndepover\Bullhorn\Test;

use Justijndepover\Bullhorn\Bullhorn;
use PHPUnit\Framework\TestCase;

class AuthenticateTest extends TestCase
{
    private $bullhorn;

    protected function setUp(): void
    {
        $this->bullhorn = new Bullhorn('client_id', 'client_secret', 'redirect_uri', 'state');
    }

    /** @test */
    public function it_can_instantiate_without_throwing_an_exception()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_get_and_set_client_id()
    {
        $this->assertEquals($this->bullhorn->getClientId(), 'client_id');

        $this->bullhorn->setClientId('test_client_id');
        $this->assertEquals($this->bullhorn->getClientId(), 'test_client_id');
    }

    /** @test */
    public function it_can_get_and_set_client_secret()
    {
        $this->assertEquals($this->bullhorn->getClientSecret(), 'client_secret');

        $this->bullhorn->setClientSecret('test_client_secret');
        $this->assertEquals($this->bullhorn->getClientSecret(), 'test_client_secret');
    }

    /** @test */
    public function it_can_get_and_set_redirect_uri()
    {
        $this->assertEquals($this->bullhorn->getRedirectUri(), 'redirect_uri');

        $this->bullhorn->setRedirectUri('test_redirect_uri');
        $this->assertEquals($this->bullhorn->getRedirectUri(), 'test_redirect_uri');
    }

    /** @test */
    public function it_can_get_and_set_state()
    {
        $this->assertEquals($this->bullhorn->getState(), 'state');

        $this->bullhorn->setState('test_state');
        $this->assertEquals($this->bullhorn->getState(), 'test_state');
    }

    /** @test */
    public function it_can_get_and_set_authorization_code()
    {
        $this->bullhorn->setAuthorizationCode('authorization_code');
        $this->assertEquals($this->bullhorn->getAuthorizationCode(), 'authorization_code');
    }

    /** @test */
    public function it_can_get_and_set_access_token()
    {
        $this->bullhorn->setAccessToken('access_token');
        $this->assertEquals($this->bullhorn->getAccessToken(), 'access_token');
    }

    /** @test */
    public function it_can_get_and_set_refresh_token()
    {
        $this->bullhorn->setRefreshToken('refresh_token');
        $this->assertEquals($this->bullhorn->getRefreshToken(), 'refresh_token');
    }

    /** @test */
    public function it_can_get_and_set_token_expires_at()
    {
        $timestamp = time();

        $this->bullhorn->setTokenExpiresAt($timestamp);
        $this->assertEquals($this->bullhorn->getTokenExpiresAt(), $timestamp);
    }

    /** @test */
    public function it_can_get_and_set_bh_rest_token()
    {
        $this->bullhorn->setBHRestToken('bh_rest_token');
        $this->assertEquals($this->bullhorn->getBHRestToken(), 'bh_rest_token');
    }

    /** @test */
    public function it_can_get_and_set_rest_url()
    {
        $this->bullhorn->setRestUrl('rest_url');
        $this->assertEquals($this->bullhorn->getRestUrl(), 'rest_url');
    }

    /** @test */
    public function it_knows_when_to_authorize()
    {
        $this->bullhorn->setAuthorizationCode(null);
        $this->bullhorn->setRefreshToken(null);
        $this->assertTrue($this->bullhorn->shouldAuthorize());

        $this->bullhorn->setAuthorizationCode('authorization_code');
        $this->bullhorn->setRefreshToken(null);
        $this->assertFalse($this->bullhorn->shouldAuthorize());

        $this->bullhorn->setAuthorizationCode(null);
        $this->bullhorn->setRefreshToken('refresh_token');
        $this->assertFalse($this->bullhorn->shouldAuthorize());
    }

    /** @test */
    public function it_knows_when_to_refresh_access_token()
    {
        $this->bullhorn->setAccessToken(null);
        $this->assertTrue($this->bullhorn->shouldRefreshToken());

        $this->bullhorn->setAccessToken('access_token');
        $this->bullhorn->setTokenExpiresAt(time() - 1);
        $this->assertTrue($this->bullhorn->shouldRefreshToken());

        $this->bullhorn->setAccessToken('access_token');
        $this->bullhorn->setTokenExpiresAt(time() + (60 * 60));
        $this->assertFalse($this->bullhorn->shouldRefreshToken());
    }

    /** @test */
    public function it_can_set_a_token_update_callback()
    {
        $this->bullhorn->setTokenUpdateCallback(function (Bullhorn $bullhorn) {
            //
        });

        $this->assertTrue(true);
    }
}